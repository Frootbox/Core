<?php 
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 * 
 */
class Files extends \Frootbox\Db\Model
{
    use \Frootbox\Persistence\Repositories\Traits\Uid;

    protected $table = 'files';
    protected $class = \Frootbox\Persistence\File::class;
    protected $config;

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        if (!empty($row->getSourceFile())) {

            $sourceFile = $row->getSourceFile();

            $row->unset([
                'sourceFile',
                'targetPath'
            ]);
        }

        if (empty($row->getFolderId())) {

            $foldersRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Folders::class);

            $folder = $foldersRepository->fetchOne([
                'where' => [
                    'title' => 'Uploads vom Bearbeitungsmodus'
                ]
            ]);

            $row->setFolderId($folder->getId());
        }

        // Create database record
        $row = parent::insert($row);

        if (!empty($sourceFile)) {

            // Generate filename
            $name = self::generateFilename($row);

            // Generate filepath
            $xpath = 'uploads/' . date('Y-m') . '/';
            $path = FILES_DIR . $xpath;

            if (!file_exists($path)) {
                $dir = new \Frootbox\Filesystem\Directory($path);
                $dir->make();
            }

            if (!move_uploaded_file($sourceFile, $path . $name)) {
                copy($sourceFile, $path . $name);
            }

            $copyright = null;
            $x = explode('.', $name);
            $extension = array_pop($x);

            if (($extension == 'jpg' or $extension == 'jpeg') and (($exif = exif_read_data($path . $name, null, true)) !== false)) {

                if (!empty($exif['IFD0']['Copyright'])) {
                    $copyright = substr($exif['IFD0']['Copyright'], 0, 255);
                }

                if (preg_match('#adobestock-([0-9]+)#', $name, $match)) {
                    $copyright = '© Adobe Stock / ' . $match[1] . ' / ' . $copyright;
                }
            }

            $row->setSize(filesize($path . $name));

            $row->setData([
                'path' => $xpath . $name,
                'copyright' => $copyright,
                'hash' => md5_file($path . $name),
            ]);

            $row->save();
        }

        return $row;
    }

    /**
     *
     */
    public function setConfig(\Frootbox\Config\Config $config): void
    {
        $this->config = $config;
    }

    /**
     *
     */
    public static function generateFilename(\Frootbox\Persistence\File $file): string
    {
        $name = strtolower($file->getName());
        $x = explode('.', $name);
        $extension = array_pop($x);
        $name = implode('-', $x);
        $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
        $name = str_pad($file->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;

        return $name;
    }

    /**
     *
     */
    public static function getMimeTypeFromExtension(string $extension): string
    {
        if (empty($extension)) {
            throw new \Exception('Extension missing.');
        }

        $extension = strtolower($extension);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';

            case 'png':
                return 'image/png';

            case 'svg':
                return 'image/svg+xml';

            default:
                d($extension);
        }
    }

    public static function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.

        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }

    /**
     *
     */
    public static function getUploadMaxSize(): int
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = self::parseSize(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }


            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = self::parseSize(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }
}

<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2019-06-15
 */

namespace Frootbox\Persistence;

use Frootbox\Db\Row;

class File extends AbstractRow
{
    use \Frootbox\Http\Traits\UrlSanitize;
    use Traits\Config;
    use Traits\Uid;

    protected $table = 'files';
    protected $model = Repositories\Files::class;

    protected $forceThumbmailFromPath = false;

    /**
     * 
     */
    public function delete()
    {
        // Delete physical file
        if (!empty($this->getPath())) {

            if (file_exists(FILES_DIR . $this->getPath())) {
                // Move file to trash
                $file = new \Frootbox\Filesystem\File(FILES_DIR . $this->getPath());

                $trashFolder = CORE_DIR;
                $file->move(FILES_DIR . 'trash');
            }

            // Create trash entry
            $filesTrashRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\FilesTrash::class);
            $trashFile = new \Frootbox\Persistence\FileTrash([
                'userId' => USER_ID,
                'file' => basename($this->getPath()),
            ]);

            $filesTrashRepository->insert($trashFile);
        }

        return parent::delete();
    }

    /**
     *
     */
    public function duplicate(): \Frootbox\Db\Row
    {
        // Get original files path
        $originalFilePath = FILES_DIR . $this->getPath();

        $row = parent::duplicate();

        // Generate filename
        $name = strtolower($row->getName());
        $x = explode('.', $name);
        $extension = array_pop($x);
        $name = implode('-', $x);
        $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
        $name = str_pad($row->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;

        // Generate filepath
        $xpath = 'uploads/' . date('Y-m') . '/';
        $path = FILES_DIR . $xpath;

        $row->setData([
            'path' => $xpath . $name
        ]);

        $row->save();


        $directory = new \Frootbox\Filesystem\Directory($path);

        if (!$directory->exists()) {
            $directory->make();
        }

        @copy($originalFilePath, $path . $name);

        return $row;
    }

    /**
     *
     */
    public function isExistant(): bool
    {
        return file_exists(FILES_DIR . $this->getPath());
    }

    /**
     *
     */
    public function isTrashed(): bool
    {
        $path = FILES_DIR . 'trash/' . basename($this->getPath());

        return file_exists($path);
    }

    /**
     *
     */
    public function getAlt(): ?string
    {
        if (!empty($this->getConfig('alt'))) {
            return $this->getConfig('alt');
        }

        if (!empty($this->getTitle())) {
            return $this->getTitle();
        }

        if (!empty($this->getConfig('caption'))) {
            return strip_tags($this->getConfig('caption'));
        }

        return $this->getName();
    }

    /**
     *
     */
    public function getExtension()
    {
        $info = pathinfo($this->getPath());

        return $info['extension'] ?? null;
    }

    /**
     *
     */
    public function getIcon(): string
    {
        switch ($this->data['type']) {

            case 'image/jpeg':
                $icon = 'fa-image';
            break;

            case 'application/pdf':
                $icon = 'fa-file-pdf';
                break;

            default:
                $icon = 'fa-file';
        }

        return $icon . ' ' . str_replace('/', '-', $this->data['type']);
    }

    /**
     * @return string
     */
    public function getNameClean(): string
    {
        $name = explode('.', $this->getPath());
        $ext = strtolower(array_pop($name));

        $name = $this->getName();

        if (substr($name, strlen($ext) * -1) == $ext) {
            $name = substr($name, 0, (strlen($ext) + 1) * -1);
        }

        $name = str_replace('_', '-', $name);
        $name = $this->getStringUrlSanitized($name) . '.' . $ext;

        return $name;
    }

    public function getOrientation(): string
    {
        list($width, $height) = getimagesize(FILES_DIR . $this->getPath());

        if ($width > $height) {
            return 'Landscape';
        }
        elseif ($width < $height) {
            return 'Portrait';
        }
        else {
            return 'Square';
        }
    }

    /**
     *
     */
    public function getRotation(): ?int
    {
        if (!empty($this->getConfig('rotation'))) {
            return (int) $this->getConfig('rotation');
        }

        if (!empty($this->getConfig('suggestRotation'))) {
            return (int) $this->getConfig('suggestRotation');
        }

        return null;
    }

    /**
     *
     */
    public function getSizeDisplay(): string
    {
        $size = $this->getSize();

        $steps = [ 'b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];

        foreach($steps as $step) {

            if ($size < 1024) {
                return round($size, 2) . ' ' . $step;
            }

            $size = $size / 1000;
        }
    }

    /**
     *
     */
    public function getTitle ( ): string
    {
        if (empty($title = parent::getTitle())) {
            return $this->getName();
        }

        return $title;
    }

    /**
     *
     */
    public function getTitleReal ( ): ?string
    {
        return parent::getTitle();
    }

    /**
     * @deprecated
     */
    public function getUri(array $options = null): string
    {
        return $this->getUriThumbnail($options);
    }
    
    /**
     * 
     */
    public function getUriDownload(array $params = null): string
    {
        $url = 'static/Ext/Core/FileManager/Download/serve/qs/f/' . $this->getId() . '/n/' . $this->getNameClean();

        $url = (!empty($params['absolute']) ? SERVER_PATH_PROTOCOL : SERVER_PATH) . $url;

        return $url;
    }

    /**
     *
     */
    public function getUriInline(array $params = null): string
    {
        $url = 'static/Ext/Core/FileManager/Download/inline/qs/f/' . $this->getId() . '/n/' . $this->getNameClean();

        $url = (!empty($params['absolute']) ? SERVER_PATH_PROTOCOL : SERVER_PATH) . $url;

        return $url;
    }

    /**
     *
     */
    public function getUriStream(array $params = null): string
    {
        $url = 'static/Ext/Core/FileManager/Download/stream/qs/f/' . $this->getId() . '/n/' . $this->getNameClean();

        $url = (!empty($params['absolute']) ? SERVER_PATH_PROTOCOL : SERVER_PATH) . $url;

        return $url;
    }
    
    /**
     * 
     */
    public function getUriThumbnail(array $options = null): string
    {
        if (!$this->forceThumbmailFromPath and !empty($this->getId())) {
            $options['fileId'] = $this->getId();
        }
        else {
            $options['path'] = $this->getPath();
        }

        // Build hash
        $hash = md5($this->getDate() . json_encode($options));
        
        $options['hash'] = $hash;

        $thumbnail = new \Frootbox\Thumbnail([
            'width' => $options['width'] ?? null,
            'height' => $options['height'] ?? null,
            'crop' => $options['crop'] ?? null,
            'rotation' => $this->getRotation(),
            'path' => $this->getPath(),
        ]);

        $path = $thumbnail->getCacheFilePath();

        if (!file_exists($path)) {

            $cachePrimer = $path . '.xdata.json';

            $directory = dirname($cachePrimer);

            if (!file_exists($directory)) {
                $old = umask(0);
                mkdir($directory, 0755, true);
                umask($old);
            }

            file_put_contents($cachePrimer, json_encode($options));
        }

        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $publicpath = $protocol . '://' . $_SERVER['HTTP_HOST'] . PUBLIC_DIR . (str_replace(FILES_DIR, '', $path));

        return $publicpath;

        d($thumbnail->getCacheFilePath());

        if (file_exists($path = $thumbnail->getCacheFilePath())) {
            $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $publicpath = $protocol . '://' . $_SERVER['HTTP_HOST'] . PUBLIC_DIR . (str_replace(FILES_DIR, '', $path));

            return $publicpath;
        }
                
        return SERVER_PATH_PROTOCOL . 'static/Ext/Core/Images/Thumbnail/render/?' . $query;
    }
    
    /**
     *
     */
    public function isImage(): bool
    {
        $path = pathinfo(FILES_DIR . $this->getPath());

        return in_array($path['extension'], [ 'svg', 'png', 'jpeg', 'jpg', 'gif' ]);
    }
}

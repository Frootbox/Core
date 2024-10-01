<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\Image\Admin;

use Frootbox\Admin\Controller\Response;
use Frootbox\Config\Config;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * Delete image
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        if ($file) {
            $file->delete();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxRefresh(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository,
    ): Response
    {
        // Fetch file
        $file = $filesRepository->fetchByUid($get->get('uid'));

        $src = $file->getUriThumbnail([
            'width' => $get->get('width'),
            'height' => $get->get('height'),
        ]);

        return self::getResponse('json', 200, [
            'uid' => $get->get('uid'),
            'src' => $src,
        ]);
    }



    /**
     *
     */
    public function ajaxUpdate (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Config\Config $config
    ): Response
    {
        if (!empty($post->get('url'))) {

            if (empty($config->get('statics.files.uploadDefaultFolder'))) {

                // Write static config value
                $filePath = $config->get('filesRootFolder') . 'config/general.php';
                $statics = (file_exists($filePath) ? require $filePath : []);

                if (empty($config->get('statics.files.rootFolder'))) {

                    // Insert root folder
                    $parent = $folders->insertRoot(new \Frootbox\Persistence\Folder([
                        'title' => 'Files Index'
                    ]));

                    $statics['statics']['files']['rootFolder'] = (int) $parent->getId();
                }
                else {

                    $parent = $folders->fetchById($config->get('statics.files.rootFolder'));
                }


                // Insert root folder
                $folder = $parent->appendChild(new \Frootbox\Persistence\Folder([
                    'title' => 'Uploads vom Bearbeitungsmodus'
                ]));


                $statics['statics']['files']['uploadDefaultFolder'] = (int) $folder->getId();
                $source = '<?php return ' . var_export($statics, true) . ';';

                $file = new \Frootbox\Filesystem\File($filePath);
                $file->setSource($source);
                $file->write();
            }
            else {

                $folder = $folders->fetchById($config->get('statics.files.uploadDefaultFolder'));
            }

            // Clean existing files
            $result = $files->fetch([
                'where' => [
                    'uid' => $get->get('uid')
                ]
            ]);

            $result->map('delete');

            // Fetch source
            $context = stream_context_create(array(
                'http'=>array(
                    'method'=>"GET",
                    'header'=>"Accept-language: en\r\n" .
                        "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
                        "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
                )
            ));
            $source = file_get_contents($post->get('url'), false, $context);
            $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
            $info = pathinfo($post->get('url'));

            $handle = fopen($tmpfname, "w");
            fwrite($handle, $source);

            if (!empty($info['extension'])) {
                $type = $files::getMimeTypeFromExtension($info['extension']);
                $name = basename($post->get('url'));
            }
            else {
                $da = getimagesize($tmpfname);
                $type = $da['mime'];
                $name = 'unknown';
            }

            // Insert file
            $file = $files->insert(new \Frootbox\Persistence\File([
                'folderId' => $folder->getId(),
                'name' => $name,
                'uid' => $get->get('uid'),
                'type' => $type,
                'size' => filesize($tmpfname),
                'sourceFile' => $tmpfname,
                'targetPath' => $config->get('filesRootFolder'),
            ]));

            fclose($handle);

            return self::getResponse('json', 200, [
                'uid' => $get->get('uid'),
                'src' => $file->getUriThumbnail([
                    'width' => $get->get('width'),
                    'height' => $get->get('height'),
                ]),
            ]);
        }

        if (!empty($get->get('fileId'))) {

            try {

                // Fetch file
                $file = $files->fetchById($get->get('fileId'));
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {

                // Try to fetch file via uid (after fresh upload)
                $file = $files->fetchByUid($get->get('uid'));
            }

            $file->setCopyright($post->get('copyright'));

            $file->addConfig([
                'width' => $post->get('width'),
                'height' => $post->get('height'),
                'caption' => $post->get('caption'),
                'alt' => $post->get('alt'),
                'link' => $post->get('link'),
            ]);

            $file->save();
        }


        return self::getResponse('json', 200, [
            'uid' => $get->get('uid')
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Admin\View $view
     * @param \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $files
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        $view->set('file', $file);

        $view->set('get', $get);

        return self::getResponse();
    }
}

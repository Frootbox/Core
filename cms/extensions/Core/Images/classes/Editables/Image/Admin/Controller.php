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
     * Edit the focus point of the same image that is rendered in the frontend.
     */
    public function ajaxModalFocusPoint(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $files,
        Config $config
    ): Response
    {
        $options = [ 'fallbackLanguageDefault' => true, 'order' => 'orderId DESC' ];
        $file = !empty($get->get('fileId'))
            ? $files->fetchById($get->get('fileId'))
            : $files->fetchByUid($get->get('uid'), $options);

        if (!$file and !empty($get->get('fallbackUid'))) {
            $file = $files->fetchByUid($get->get('fallbackUid'), $options);
        }

        $dimensions = [];
        $preview = null;

        if ($file) {
            $thumbnail = $this->getFocusPointThumbnail($file, $config);
            $dimensions = $thumbnail->getDimensions();

            if ($dimensions) {
                $path = $thumbnail->getCacheFilePath();
                if (!is_file($path) or filesize($path) === 0) {
                    $thumbnail->render();
                }
                if (is_file($path) and filesize($path) > 0) {
                    $preview = PUBLIC_DIR . substr($path, strlen(FILES_DIR));
                }
            }
        }

        return self::getResponse('html', 200, [
            'file' => $file,
            'dimensions' => $dimensions,
            'preview' => $preview,
        ]);
    }

    private function getFocusPointThumbnail(\Frootbox\Persistence\File $file, Config $config): \Frootbox\Thumbnail
    {
        return new \Frootbox\Thumbnail([
            'path' => $file->getPath(),
            'rotation' => $file->getRotation(),
            'width' => 1280,
            'height' => 1280,
            'crop' => 'none',
            'type' => 'jpg',
        ], $config->get('thumbnails'));
    }

    /**
     * Store original-image pixel coordinates without changing image metadata.
     */
    public function ajaxUpdateFocusPoint(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $files,
        Config $config
    ): Response
    {
        $file = $files->fetchById($get->get('fileId'));
        $dimensions = $this->getFocusPointThumbnail($file, $config)->getDimensions();

        if (empty($dimensions['width']) or empty($dimensions['height'])) {
            throw new \Frootbox\Exceptions\RuntimeError('Die Bildmaße konnten nicht ermittelt werden.');
        }

        $focusPoint = null;

        if ($post->get('reset') !== '1') {
            $position = $post->get('position');

            foreach ([ 'x' => 'width', 'y' => 'height' ] as $axis => $dimension) {
                $value = is_array($position) ? ($position[$axis] ?? null) : null;
                $coordinate = filter_var($value, FILTER_VALIDATE_INT, [
                    'options' => [ 'min_range' => 0, 'max_range' => $dimensions[$dimension] - 1 ],
                ]);

                if ($coordinate === false) {
                    throw new \Frootbox\Exceptions\RuntimeError('Bitte einen gültigen Fokuspunkt innerhalb des Bildes wählen.');
                }

                $focusPoint[$axis] = $coordinate;
            }
        }

        $file->addConfig([ 'focusPoint' => $focusPoint ]);
        $file->save();

        return self::getResponse('json', 200, [ 'success' => true ]);
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
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Persistence\Repositories\Files $files
     * @param \Frootbox\Persistence\Repositories\Folders $folders
     * @param Config $config
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
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

            $tmpName = tempnam(sys_get_temp_dir(), md5(microtime(true)));
            $baseName = explode('?', $post->get('url'))[0];
            $info = pathinfo($baseName);

            $handle = fopen($tmpName, "w");
            fwrite($handle, $source);

            if (!empty($info['extension'])) {
                $type = $files::getMimeTypeFromExtension($info['extension']);
                $name = basename($baseName);
            }
            else {
                $da = getimagesize($tmpName);
                $type = $da['mime'];
                $name = 'unknown';
            }

            // Insert file
            $file = $files->insert(new \Frootbox\Persistence\File([
                'folderId' => $folder->getId(),
                'name' => $name,
                'uid' => $get->get('uid'),
                'type' => $type,
                'size' => filesize($tmpName),
                'sourceFile' => $tmpName,
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
                'isPresentationOnly' => $post->getBoolean('isPresentationOnly'),
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
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $files,
        Config $config
    ): Response
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        $view->set('file', $file);
        $view->set('dimensions', $file ? $this->getFocusPointThumbnail($file, $config)->getDimensions() : []);

        $view->set('get', $get);

        return self::getResponse();
    }
}

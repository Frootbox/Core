<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\StaticPages\Editor;

use Frootbox\View\Response;

class Page
{
    /**
     *
     */
    public function ajaxFileDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));
        $file->delete();

        die("OK");
    }

    /**
     *
     */
    public function ajaxUpdate (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        $file->setTitle($post->get('title'));
        $file->save();

        die(json_encode([ ]));
    }

    /**
     *
     */
    public function jumpToFile(
        \Frootbox\Http\Get $get,
        \DI\Container $container
    )
    {
        // Admin autoloader
        spl_autoload_register(function ( $class ) {

            $adminClass = substr($class, 15);

            $file = CORE_DIR . 'cms/admin/classes/' . str_replace('\\', '/', $adminClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });

        $appsRepository = $container->get(\Frootbox\Admin\Persistence\Repositories\Apps::class);

        $app = $appsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\FileManager\Apps\FileManager\App::class,
            ],
        ]);

        $url = $app->getUri('details', [ 'fileId' => $get->get('fileId') ]);

        header('Location: ' . $url);
        exit;
    }

    /**
     * Sort file from filemanager modal
     */
    public function sort (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        $orderId = count($get->get('files')) + 1;

        foreach ($get->get('files') as $fileId) {

            $file = $files->fetchById($fileId);
            $file->setOrderId($orderId--);
            $file->save();
        }

        die("OK");
    }

    /**
     *
     */
    public function upload (
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        if (empty($_FILES['file'])) {
            throw new \Frootbox\Exceptions\InputMissing();
        }

        if (!empty($_FILES['file']['error'])) {

            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $message = "File upload stopped by extension";
                    break;

                default:
                    $message = "Unknown upload error";
                    break;
            }

            throw new \Frootbox\Exceptions\InputInvalid($message);
        }

        $folder = $folders->fetchOne([
            'where' => [
                'title' => 'Uploads vom Bearbeitungsmodus',
            ],
        ]);

        if (empty($folder)) {

            $parent = $folders->fetchOne([
                'where' => [
                    'title' => 'Files Index',
                ],
            ]);

            if (empty($parent)) {

                // Insert root folder
                $parent = $folders->insertRoot(new \Frootbox\Persistence\Folder([
                    'title' => 'Files Index'
                ]));
            }

            // Insert upload folder
            $folder = $parent->appendChild(new \Frootbox\Persistence\Folder([
                'title' => 'Uploads vom Bearbeitungsmodus'
            ]));
        }

        if (empty($get->get('multiple'))) {

            $where = [
                'uid' => $get->get('uid'),
            ];

            if (!empty($config->get('i18n.multiAliasMode'))) {
                $where['language'] = $_SESSION['frontend']['language'];
            }

            // Clean existing files
            $result = $files->fetch([
                'where' => $where,
            ]);

            $result->map('delete');
        }

        // Insert file
        $file = $files->insert(new \Frootbox\Persistence\File([
            'folderId' => $folder->getId(),
            'name' => $_FILES['file']['name'],
            'uid' => $get->get('uid'),
            'type' => $_FILES['file']['type'],
            'size' => $_FILES['file']['size'],
            'sourceFile' => $_FILES['file']['tmp_name'],
            'targetPath' => $config->get('filesRootFolder'),
            'language' => $_SESSION['frontend']['language'],
        ]));

        die(json_encode([ ]));
    }
}
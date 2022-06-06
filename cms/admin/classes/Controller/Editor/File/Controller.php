<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-28
 */

namespace Frootbox\Admin\Controller\Editor\File;


/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController {


    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view
    )
    {

        // Fetch file
        $file = $files->fetchById($get->get('fileId'));
        $view->set('file', $file);

        return self::response();
    }


    /**
     *
     */
    public function ajaxDelete (
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Http\Get $get
    )
    {

        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        $file->delete();

        return self::response('json', 200, [
            'fadeOut' => '[data-file="' . $file->getId() . '"]',
            'success' => 'Die Datei wurde erfolgreich gelöscht.'
        ]);
    }


    /**
     *
     */
    public function ajaxUpdate (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {


        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        $file->setTitle($post->get('title'));
        $file->save();


        return self::response('json', 200, [
            'modalDismiss' => true
        ]);
    }


    /**
     *
     */
    public function ajaxUpload (
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        $key = $get->get('inputName') ? $get->get('inputName') : 'file';


        if (!empty($_FILES[$key]['error'])) {

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

            die($message);
        }

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
            'name' => $_FILES[$key]['name'],
            'uid' => $get->get('uid'),
            'type' => $_FILES[$key]['type'],
            'size' => $_FILES[$key]['size'],
            'sourceFile' => $_FILES[$key]['tmp_name'],
            'targetPath' => $config->get('filesRootFolder'),
            'language' => $_SESSION['frontend']['language'],
        ]));

        return self::getResponse('json', 200, [
            'html' => $gp->injectPartial(\Frootbox\Ext\Core\FileManager\Admin\Partials\FileManager\ListFiles\Partial::class, [
                'uid' => $get->get('uid')
            ]),
            'files' => [
                [
                    'uid' => $get->get('uid'),
                    'name' => $_FILES[$key]['name'],
                    'size' => $_FILES[$key]['size'],
                    'src' => $file->getUri([ 'width' => $get->get('width'), 'height' => $get->get('height') ])
                ]
            ]
        ]);

    }
}
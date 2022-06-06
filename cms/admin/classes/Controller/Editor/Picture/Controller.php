<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-28
 */

namespace Frootbox\Admin\Controller\Editor\Picture;


use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /*
    public function ajaxModalEdit(
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get
    ): Response
    {

        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        $view->set('file', $file);

        $view->set('get', $get);


        return self::getResponse('json', 200, [
            'modal' => [
                'html' => self::render()
            ]
        ]);
    }
    */

    /**
     * Delete image
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {

        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));
        $file->delete();

        return self::response('json');
    }

    /**
     *
     */
    public function ajaxUpload (
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
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


        // Insert file
        $file = $files->insert(new \Frootbox\Persistence\File([
            'folderId' => $folder->getId(),
            'name' => $_FILES['file']['name'],
            'uid' => $get->get('uid'),
            'type' => $_FILES['file']['type'],
            'size' => $_FILES['file']['size'],
            'sourceFile' => $_FILES['file']['tmp_name'],
            'targetPath' => $config->get('filesRootFolder')
        ]));


        return self::getResponse('json', 200, [
            'files' => [
                [
                    'uid' => $get->get('uid'),
                    'name' => $_FILES['file']['name'],
                    'size' => $_FILES['file']['size'],
                    'src' => $file->getUri([ 'width' => $get->get('width'), 'height' => $get->get('height') ])
                ]
            ]
        ]);

    }


    /**
     *
     */
    public function ajaxUpdateConfig (
        \Frootbox\Persistence\Content\Repositories\Widgets $boxes,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post
    )
    {
        // Fetch box
        $box = $boxes->fetchById($get->get('boxId'));


        // Build classname
        $segments = explode('/', $post->get('box'));
        $className = 'Frootbox\\Ext\\' . $segments[0] . '\\' . $segments[1] . '\\Boxes\\' . $segments[2] . '\\Box';


        $box->setClassName($className);
        $box->save();



        d($box);

    }
}

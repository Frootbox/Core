<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\ImageRollover\Admin;

use Frootbox\Config\Config;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
{
    /**
     *
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    /**
     *
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        if (!empty($file)) {

            $file->delete();
        }

        return self::getResponse('json');
    }


    /**
     *
     */
    public function ajaxRefresh(
        \Frootbox\Http\Post $post,
        \DI\Container $container
    )
    {
        $class = str_replace('/', '\\', $post->get('editable') . '/Editable');
        $editable = new $class;

        $source = $container->call([ $editable, 'parse' ], [ 'html' => $post->get('snippet') ]);

        http_response_code(200);

        die(json_encode([
            'snippet' => [
                'html' => $source
            ]
        ]));
    }

    /**
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        if (empty($file)) {

            $files->setConfig($config);
            $file = $files->insert(new \Frootbox\Persistence\File([
                'uid' => $get->get('uid'),
                'name' => 'Hintergrundkonfiguration',
                'userId' => $session->getUser()->getId(),
                'type' => 'autoconfig/json'
            ]));
        }

        $file->addConfig([
            'backgroundColor' => $post->get('color')
        ]);

        $file->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));
        $view->set('file', $file);

        return self::getResponse();
    }
}

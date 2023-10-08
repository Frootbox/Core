<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\PictureFull\Admin;

use Frootbox\Admin\Controller\Response;

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
     * Delete image
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'));

        // Delete file
        $file?->delete();

        // Generate default
        $default = $get->get('default');
        $default = str_replace('FILE:', '', $default);
        $default = str_replace(CORE_DIR, '', $default);

        return self::getResponse('json', 200, [
            'default' => $default,
        ]);
    }

    /**
     *
     */
    public function ajaxRefresh (
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
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'), [
            'fallbackLanguageDefault' => true,
        ]);

        if (MULTI_LANGUAGE and $_SESSION['frontend']['language'] != $file->getLanguage()) {

            $file = $file->duplicate();
            $file->setLanguage($_SESSION['frontend']['language']);
            $file->save();
        }

        $file->addConfig([
            'caption' => $post->get('caption'),
            'magnifier' => $post->get('magnifier'),
        ]);

        $file->setCopyright($post->get('copyright'));
        $file->save();

        return self::getResponse('json');
    }


    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($get->get('uid'), [
            'fallbackLanguageDefault' => true,
        ]);

        $view->set('file', $file);

        return self::getResponse();
    }
}
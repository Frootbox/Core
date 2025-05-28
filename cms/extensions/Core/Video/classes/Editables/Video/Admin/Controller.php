<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Editables\Video\Admin;

use Frootbox\Admin\Controller\Response;

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
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true,
        ]);

        $text->addConfig([
            'Muted' => $post->getBoolean('Muted'),
            'Loop' => $post->getBoolean('Loop'),
            'Autoplay' => $post->getBoolean('Autoplay'),
            'Controls' => $post->getBoolean('Controls'),
        ]);

        $text->save();


        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true
        ]);

        return self::getResponse('plain', 200, [
            'text' => $text,
        ]);
    }
}

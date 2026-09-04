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
        $sourceType = $post->get('SourceType') === 'youtube' ? 'youtube' : 'file';
        $videoUrl = trim((string) $post->get('VideoUrl'));

        if ($sourceType === 'youtube' && \Frootbox\Ext\Core\Video\Editables\Video\Editable::getYoutubeVideoId($videoUrl) === null) {
            return self::getResponse('json', 422, [
                'error' => 'Bitte gib eine gültige YouTube-URL ein.',
            ]);
        }

        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true,
        ]);

        $text->addConfig([
            'SourceType' => $sourceType,
            'VideoUrl' => $videoUrl,
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

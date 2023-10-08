<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Viewhelper;

class Embed extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getEmbedUrl' => [
            'videoLink'
        ]
    ];

    /**
     *
     */
    public function getEmbedUrlAction(
        string $videoLink,
    ): ?string
    {
        if (preg_match('#^https://www.youtube.com/watch\?v=(.*?)$#i', $videoLink, $match)) {
            return 'https://www.youtube.com/embed/' . $match[1];
        }

        if (preg_match('#^https://youtu.be/(.*?)$#i', $videoLink, $match)) {
            return 'https://www.youtube.com/embed/' . $match[1];
        }

        return null;
    }
}

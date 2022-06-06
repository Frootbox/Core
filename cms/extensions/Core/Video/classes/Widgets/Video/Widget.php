<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Widgets\Video;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
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
    public function getMaxWidth ( ) {

        return $this->config['maxWidth'] ?? 600;
    }


    /**
     *
     */
    public function getMaxWidthStyle(): string
    {
        // echo '">';d($this->getAlign());

        if ($this->getAlign() == 'justify') {
            return '100%';
        }

        return $this->getMaxWidth() . 'px';
    }


    /**
     *
     */
    public function indexAction (
        \Frootbox\View\Viewhelper\Partials $partials,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        if (empty($this->getConfig('url'))) {
            return false;
        }

        $video = [
            'type' => null
        ];

        // Match youtube urls
        if (preg_match('#^https://www\.youtube\.com\/watch\?.*?v\=(.*?)$#i', $this->config['url'], $match)) {
            $video['type'] = 'Youtube';
            $video['id'] = explode('&', $match[1])[0];
        }
        elseif (preg_match('#^https://youtu\.be\/(.*?)$#i', $this->config['url'], $match)) {

            $video['type'] = 'Youtube';
            $video['id'] = $match[1];
        }
        else {
            return 'Video-Typ wird leider nicht unterstützt.';
        }

        $playerHtml = $partials->renderPartial('Youtube', [ 'video' => $video, 'widget' => $this ]);

        $view->set('playerHtml', $playerHtml);
    }
}

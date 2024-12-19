<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Widgets\Video;

use Frootbox\Persistence\Repositories\Files;

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
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    )
    {
        if (!empty($this->getConfig('url'))) {

            $video = [
                'type' => null
            ];

            // Match youtube urls
            if (preg_match('#^https://www\.youtube\.com\/watch\?.*?v\=(.*?)$#i', $this->config['url'], $match)) {
                $video['type'] = 'Youtube';
                $video['id'] = explode('&', $match[1])[0];
            }
            elseif (preg_match('#^https://www\.youtube\.com\/embed\/(.*?)\?(.*?)$#i', $this->config['url'], $match)) {
                $video['type'] = 'Youtube';
                $video['id'] = explode('&', $match[1])[0];
            }
            elseif (preg_match('#^https://youtu\.be\/(.*?)$#i', $this->config['url'], $match)) {
                $video['type'] = 'Youtube';
                $video['id'] = $match[1];
            }
            elseif (preg_match('#^https://www\.youtube\.com\/embed\/(.*?)$#i', $this->config['url'], $match)) {
                $video['type'] = 'Youtube';
                $video['id'] = explode('&', $match[1])[0];
            }
            else {
                return 'Video-Typ wird leider nicht unterstützt.';
            }

            $playerHtml = $partials->renderPartial('Youtube', ['video' => $video, 'widget' => $this]);

            $view->set('playerHtml', $playerHtml);
        }
        else {

            // Fetch video file
            $files = $fileRepository->fetch([
                'where' => [
                    'uid' => $this->getUid('video'),
                ],
            ]);

            if ($files->getCount() > 0) {

                $playerHtml = $partials->renderPartial('GenericVideo', [
                    'videos' => $files,
                    'loop' => !empty($this->getConfig('loop')),
                    'muted' => !empty($this->getConfig('muted')),
                    'autoplay' => !empty($this->getConfig('autoplay')),
                    'height' => !empty($this->getConfig('maxHeight')),
                    'width' => !empty($this->getConfig('maxWidth')),
                ]);

                $view->set('playerHtml', $playerHtml);
            }
        }

        return null;
    }
}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\RSSFeedReader;

class Plugin extends \Frootbox\Persistence\AbstractPlugin {

    /**
     * Get plugins root path
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function indexAction (
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {

        // $rss = simplexml_load_file($this->getConfig('feedUri'));

        // d((string) $rss->channel->item->description);
    }
}

<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Article\Partials\ListArticles;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {
    
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles
    ): Response
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        $sorting = !empty($plugin->getConfig('sorting')) ? $plugin->getConfig('sorting') : 'DateStartDesc';
        $direction = preg_match('#Desc$#', $sorting) ? 'DESC' : 'ASC';
        $sortcol = substr($sorting, 0, strlen($direction) * -1);


        $result = $articles->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ $sortcol . ' ' . $direction ],
        ]);

        return new Response(body: [
            'articles' => $result,
        ]);
    }
}
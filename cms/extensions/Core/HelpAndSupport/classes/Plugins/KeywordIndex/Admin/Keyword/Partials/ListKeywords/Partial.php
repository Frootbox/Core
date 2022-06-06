<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Keyword\Partials\ListKeywords;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordsRepository
    ) {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $keywordsRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);

        $view->set('keywords', $result);
    }
}
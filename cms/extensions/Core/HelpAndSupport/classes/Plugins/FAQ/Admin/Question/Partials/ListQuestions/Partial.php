<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question\Partials\ListQuestions;

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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions
    ) {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $questions->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ]
        ]);

        $view->set('questions', $result);
    }
}
<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question\Partials\ListSeeAlso;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions
    )
    {
        // Fetch question
        $question = $this->getData('question');
        $view->set('question', $question);

        $list = [];

        if (!empty($question->getConfig('seeAlso'))) {

            foreach ($question->getConfig('seeAlso') as $questionId) {

                $list[] = $questions->fetchById($questionId);
            }
        }

        $view->set('questions', $list);
    }
}

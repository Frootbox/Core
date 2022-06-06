<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Viewhelper;

class FAQ extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getQuestions' => [

        ],
    ];

    /**
     *
     */
    public function getQuestionsAction(
        array $params = null,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch contacts
        $questions = $questionsRepository->fetch();

        return $questions;
    }
}

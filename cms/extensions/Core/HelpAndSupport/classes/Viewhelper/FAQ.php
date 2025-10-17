<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Viewhelper;

class FAQ extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getQuestions' => [
            'params',
        ],
    ];

    /**
     *
     */
    public function getQuestionsAction(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
        array $params = null,
    ): \Frootbox\Db\Result
    {
        $where = [];

        if (!empty($params['pluginId'])) {
            $where['pluginId'] = $params['pluginId'];
        }

        // Fetch contacts
        $questions = $questionsRepository->fetch([
            'where' => $where,
        ]);

        return $questions;
    }
}

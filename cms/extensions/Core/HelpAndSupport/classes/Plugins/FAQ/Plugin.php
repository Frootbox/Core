<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use Traits\CloneFactory;

    protected $publicActions = [
        'index',
        'showQuestion',
    ];

    protected $isContainerPlugin = true;
    protected $icon = 'fas fa-images';

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param Persistence\Question $question
     * @return Persistence\Question
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getPredecessorOf(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question $question): ?\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions::class);

        $successor = $repository->fetchOne([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('orderId', $question->getOrderId()),
                new \Frootbox\Db\Conditions\NotEqual('id', $question->getId()),
            ],
            'order' => [ 'orderId ASC', 'id DESC' ],
        ]);

        return $successor;
    }

    /**
     * @param Persistence\Question $question
     * @return Persistence\Question
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getSuccessorOf(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question $question): ?\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions::class);

        $successor = $repository->fetchOne([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\LessOrEqual('orderId', $question->getOrderId()),
                new \Frootbox\Db\Conditions\NotEqual('id', $question->getId()),
            ],
            'order' => [ 'orderId DESC', 'id ASC' ],
        ]);

        return $successor;
    }

    /**
     *
     */
    public function getQuestions(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions
    ): ?\Frootbox\Db\Result
    {
        // Fetch questions
        $result = $questions->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): void
    {
        // Fetch questions
        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ]
        ]);

        $result->map('delete');
    }

    /**
     *
     */
    public function showQuestionAction (
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): Response
    {
        // Fetch question
        $question = $questionsRepository->fetchById($this->getAttribute('questionId'));

        return new Response([
            'question' => $question
        ]);
    }

    /**
     *
     */
    public function indexAction (

    ): Response
    {
        return new Response();
    }
}

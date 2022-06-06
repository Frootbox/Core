<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Insert new question
        $question = $questions->insert(new \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title')
        ]));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#questionsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question\Partials\ListQuestions\Partial::class, [
                    'highlight' => $question->getId(),
                    'plugin' => $this->plugin,
                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxConnectionCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $get->require([ 'questionId', 'targetId' ]);

        // Fetch question
        $question = $questionsRepository->fetchById($get->get('questionId'));

        // Fetch question
        $target = $questionsRepository->fetchById($get->get('targetId'));

        $seeAlso = $question->getConfig('seeAlso') ?? [];

        $seeAlso[$target->getId()] = $target->getId();

        $question->addConfig([
            'seeAlso' => $seeAlso
        ]);

        $question->save();


        return self::getResponse('json', 200, [
            'addClass' => [
                'selector' => '[data-question="' . $target->getId() . '"]',
                'className' => 'selected',
            ],
            'replace' => [
                'selector' => '#seeAlsoReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question\Partials\ListSeeAlso\Partial::class, [
                    'highlight' => $target->getId(),
                    'question' => $question,
                    'plugin' => $this->plugin,
                ]),
            ],
            'success' => 'Die Fragen wurden erfolgreich verknüpft.',
        ]);
    }

    /**
     *
     */
    public function ajaxConnectionDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch question
        $question = $questions->fetchById($get->get('questionId'));

        $seeAlso = $question->getConfig('seeAlso') ?? [];

        unset($seeAlso[$get->get('targetId')]);

        $question->unsetConfig('seeAlso');
        $question->addConfig([
            'seeAlso' => $seeAlso
        ]);

        $question->save();

        return self::getResponse('json', 200, [
            'fadeOut' => '#row-' . $get->get('targetId')
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch question
        $question = $questions->fetchById($get->get('questionId'));


        // Delete question
        $question->delete();


        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#questionsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Question\Partials\ListQuestions\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ],
            'success' => 'Der Artikel wurde gelöscht.'
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalConnectionComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxSearchAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions
    ): Response
    {
        // Fetch questions
        $result = $questions->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('title', '%' . $get->get('query') . '%'),
                new \Frootbox\Db\Conditions\NotEqual('id', $get->get('questionId')),
            ],
        ]);

        return self::getResponse('plain', 200, [
            'questions' => $result
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questions
    ): Response
    {
        // Get orderId
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $questionId) {

            // Fetch question
            $question = $questions->fetchById($questionId);
            $question->setOrderId($orderId--);
            $question->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): Response
    {
        // Fetch question
        $question = $questionsRepository->fetchById($get->get('questionId'));

        $question->setTitle($post->get('title'));
        $question->addConfig([
            'link' => $post->get('link'),
        ]);
        $question->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): Response
    {
        // Fetch question
        $question = $questionsRepository->fetchById($get->get('questionId'));

        return self::getResponse('html', 200, [
            'question' => $question
        ]);
    }

    /**
     *
     */
    public function indexAction (

    ): Response
    {

        return self::getResponse();
    }
}

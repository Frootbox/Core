<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Apps\FormsManager;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Get;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        $post->require([ 'title' ]);

        // Insert new form
        $form = $formsRepository->insert(new \Frootbox\Ext\Core\Forms\Persistence\Form([
            'title' => $post->get('title'),
        ]));

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#formsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Apps\FormsManager\Partials\ListForms::class, [
                    'highlight' => $form->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxGroupCreateAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        $post->require([ 'title' ]);

        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        // Insert new group
        $group = $groupsRepository->insert(new \Frootbox\Ext\Core\Forms\Persistence\Group([
            'parentId' => $form->getId(),
            'title' => $post->get('title')
        ]));

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Apps\FormsManager\Partials\ListGroups::class, [
                    'highlight' => $group->getId(),
                    'form' => $form,
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): Response
    {
        return new Response;
    }

    /**
     *
     */
    public function ajaxModalGroupComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        return new Response('html', 200, [
            'form' => $form,
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        return new Response('html', 200, [
            'form' => $form,
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch forms
        $result = $formsRepository->fetch([
            'where' => [
            //    'className' => \Frootbox\Ext\Core\Forms\Plugins\Form\Plugin::class,
            ],
        ]);

        return self::getResponse('html', 200, [
            'forms' => $result,
        ]);
    }
}

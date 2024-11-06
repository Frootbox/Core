<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Blocks\Form1\Admin;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Persistence\Content\Blocks\AbstractAdminController
{
    protected $block;

    /**
     *
     */
    public function __construct(\Frootbox\Persistence\Content\Blocks\Block $block)
    {
        $this->block = $block;
    }

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
    public function ajaxUpdateAction(
        \DI\Container $container,
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->block->addConfig([
            'formId' => $post->get('formId'),
        ]);

        $this->block->save();

        $html = $container->call([ $this->block, 'renderHtml' ]);

        define('EDITING', true);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $this->block->getUidRaw(),
            'blockId' => $this->block->getId(),
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function indexAction (
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): Response
    {
        // Fetch forms
        $result = $formsRepository->fetch([
            'order' => [ 'title ASC' ],
        ]);

        return self::getResponse('plain', 200, [
            'forms' => $result
        ]);
    }
}

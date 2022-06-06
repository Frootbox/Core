<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Plugins\FormTeaser\Admin\Index;

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
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->plugin->addConfig([
            'formId' => $post->get('formId'),
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch forms
        $forms = $formsRepository->fetch();

        return self::getResponse('html', 200, [
            'forms' => $forms,
        ]);
    }
}

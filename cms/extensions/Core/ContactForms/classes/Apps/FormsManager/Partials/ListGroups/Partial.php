<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups;

use Frootbox\Admin\Controller\Response;

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
    public function _onBeforerendering(): Response
    {
        $form = $this->getData('form');

        return new Response('html', 200, [
            'xx' => 'yy'
        ]);
    }
}
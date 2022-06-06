<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListForms;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforerendering(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch forms
        $result = $formsRepository->fetch();

        return new Response('html', 200, [
            'forms' => $result
        ]);
    }
}
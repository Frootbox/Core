<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Apps\FormsManager\Partials\ListForms;

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
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        $result = $formsRepository->fetch();

        return new Response('html', 200, [
            'forms' => $result
        ]);
    }
}
<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Plugins\FormTeaser;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function completeAction(): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        if (!empty($this->getConfig('formId'))) {

            // Fetch form
            $form = $formsRepository->fetchById($this->getConfig('formId'));
        }

        return new \Frootbox\View\Response([
            'form' => $form ?? null,
        ]);
    }
}

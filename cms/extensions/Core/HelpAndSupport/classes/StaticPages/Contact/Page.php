<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\StaticPages\Contact;

use Frootbox\View\Response;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxPopover(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository,
    ): \Frootbox\View\Response
    {
        // Fetch contact
        $contact = $contactRepository->fetchById($get->get('contactId'));

        return new \Frootbox\View\Response([
            'contact' => $contact,
        ]);
    }
}

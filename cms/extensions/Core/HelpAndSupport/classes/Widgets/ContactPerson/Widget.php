<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Widgets\ContactPerson;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget {

    /**
     *
     */
    public function getContact (
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts
    ): ?\Frootbox\Ext\Core\HelpAndSupport\Persistence\Contact
    {

        if (empty($this->config['contactId'])) {
            return null;
        }

        // Fetch contact
        $contact = $contacts->fetchById($this->config['contactId']);

        return $contact;
    }

    /**
     *
     */
    public function getPath ( ): string
    {

        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}
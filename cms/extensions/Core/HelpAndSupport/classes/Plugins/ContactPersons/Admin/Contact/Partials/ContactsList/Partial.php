<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Contact\Partials\ContactsList;

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
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts
    ): void
    {
        $plugin = $this->getData('plugin');

        // Fetch contacts
        $result = $contacts->fetch([
            'where' => [
                'pluginId' => $plugin->getId(),
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        $view->set('contacts', $result);
    }
}

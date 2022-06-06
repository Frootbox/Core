<?php
/**
 *
 */

namespace Frootbox\Ext\{{ extension.getVendorId() }}\{{ extension.getExtensionId() }}\Widgets\{{ widgetId }};

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * Cleanup widgets resources before it gets deleted
     *
     * Dependencies get auto injected.
     */
    public function unload(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {

    }
}
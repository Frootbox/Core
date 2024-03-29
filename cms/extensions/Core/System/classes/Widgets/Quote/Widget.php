<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Widgets\Quote;

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
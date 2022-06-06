<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Plugins\Displacement;

use Frootbox\View\Response;
use Frootbox\View\ResponseRedirect;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
    ];

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
    public function indexAction(): Response
    {
        return new \Frootbox\View\Response;
    }
}

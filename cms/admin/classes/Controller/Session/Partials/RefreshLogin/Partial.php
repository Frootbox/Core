<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Session\Partials\RefreshLogin;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config
    )
    {

    }
}

<?php 
/**
 * 
 */

namespace Frootbox\Admin\View\Partials;

/**
 * 
 */
interface PartialInterface
{
    /**
     * 
     */
    public function getPath();

    /**
     * 
     */
    public function render(\Frootbox\Admin\View $view, \DI\Container $container);
}

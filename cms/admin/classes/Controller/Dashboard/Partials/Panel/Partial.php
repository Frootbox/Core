<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Dashboard\Partials\Panel;

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
        \DI\Container $container,
    ): string
    {
        // Obtain panel
        $panel = $this->getData('panel');

        // Obtain action
        $action = ($this->getData('action', true) ?? 'index');
        $method = $action . 'Action';

        $response = $container->call([ $panel, $method ]);

        $viewFile = $panel->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';
        d($viewFile);
    }
}
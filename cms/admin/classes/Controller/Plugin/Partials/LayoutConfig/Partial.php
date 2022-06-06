<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Plugin\Partials\LayoutConfig;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * Get partials root path
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
        \Frootbox\Config\Config $config
    )
    {

        // Obtain plugin
        $plugin = $this->getData('plugin');

        try {

            // Build view template
            $template = new \Frootbox\View\HtmlTemplate($plugin->getLayoutForAction($config, $this->getData('action')), [
                'variables' => $plugin->getConfig('variables.' . $this->getData('action'))
            ]);

            $view->set('template', $template);
        }
        catch ( \Exception $e ) {

            d($e->getFile());
            // Ignore possible missing view file
        }
    }
}

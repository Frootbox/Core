<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations;

class Renderer
{
    protected $navigation;
    protected $parameters;
    protected $container;

    protected $view = null;
    protected $icons = null;

    protected $loop;

    /**
     *
     */
    public function __construct(
        \Frootbox\Persistence\Navigation $navigation,
        \DI\Container $container,
        array $parameters = [],
    )
    {
        $this->navigation = $navigation;
        $this->parameters = $parameters;
        $this->container = $container;
    }

    /**
     *
     */
    protected function generateDefaultHtml(): string
    {
        $this->loop = 0;

        $html = '<nav class="' . ($this->parameters['class'] ?? null) . '">';

        foreach ($this->navigation->getItems() as $item) {

            if ($item->hasAutoItems()) {
                $items = $this->container->call([ $item, 'getAutoItems' ]);

                foreach ($items as $item) {
                    $html .= $this->getItemHtml($item);
                }
            }
            else {
                $html .= $this->getItemHtml($item);
            }
        }

        $html .= '</nav>';

        return $html;
    }

    /**
     *
     */
    protected function getItemHtml(\Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem $item): string
    {
        $html = '<a data-loop="' . ++$this->loop . '" class="' . implode(' ', $item->getClasses()) . ' ' . ($item->isActive($this->parameters) ? 'active' : '') . '" href="' . $item->getHref() . '"><span>';

        if (!empty($item->getConfig('icon'))) {

            if ($this->view === null) {
                $this->view = $this->container->get(\Frootbox\View\Engines\Interfaces\Engine::class);
            }

            if ($this->icons === null) {
                $this->icons = $this->view->getViewhelper('Ext/HerrUndFrauPixelExt/Icons/Icons', [
                    'createDummyOnFail' => true,
                ]);
            }

            $icon = $this->icons->render($item->getConfig('icon'), [ 'navigation-item-icon' ]);
        }
        else {
            $icon = (string) null;
        }

        if (!empty($item->getConfig('iconOnly'))) {
            $html .= $icon;
        }
        else {
            $html .= $icon . $item->getTitle();
        }

        $html .= '</span></a> ';

        $listChildren = !empty($this->parameters['listChildren']);

        if ($listChildren) {

            $children = $item->getItems();

            if ($children->getCount()) {

                foreach ($children as $item) {
                    $html .= $this->getItemHtml($item);
                }
            }
        }


        if ($item->hasAutoItems()) {
            $items = $this->container->call([ $item, 'getAutoItems' ]);

            foreach ($items as $item) {
                $html .= $this->getItemHtml($item);
            }
        }


        if ($item->hasAdditionalHtml()) {

            $viewFile = $item->getPath() . 'resources/private/views/Item.html.twig';

            if ($this->view === null) {
                $this->view = $this->container->get(\Frootbox\View\Engines\Interfaces\Engine::class);
            }

            $html .= $this->view->render($viewFile, [
                'item' => $item,
            ]);
        }

        return $html;
    }

    /**
     *
     */
    protected function getViewFile(): ?string
    {
        $config = $this->container->get(\Frootbox\Config\Config::class);

        if (empty($folders = $config->get('Ext.Core.Navigation.NavigationsViewFolders'))) {
            return null;
        }

        foreach ($folders->getData() as $path) {

            $viewFile = $path . $this->navigation->getNavId() . DIRECTORY_SEPARATOR . 'View.html.twig';

            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }

        return null;
    }

    /**
     *
     */
    public function render(): string
    {
        // Obtain viewfile
        if (($viewFile = $this->getViewFile()) === null) {
            return $this->generateDefaultHtml();
        }

        $view = $this->container->get(\Frootbox\View\Engines\Interfaces\Engine::class);

        $view->set('parameters', $this->parameters);
        $view->set('navigation', $this->navigation);

        $html = $view->render($viewFile);


        return $html;
    }
}

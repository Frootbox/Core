<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations;

class Renderer
{
    protected $view = null;
    protected $icons = null;

    protected $loop;

    /**
     * @param \Frootbox\Persistence\Navigation $navigation
     * @param \DI\Container $container
     * @param array $parameters
     */
    public function __construct(
        protected \Frootbox\Persistence\Navigation $navigation,
        protected \DI\Container $container,
        protected array $parameters = [],
    )
    { }

    /**
     * @param Items\AbstractItem $item
     * @return \Frootbox\Db\Result
     */
    public function getChildrenForItem(\Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem $item): ?\Frootbox\Db\Result
    {
        if ($item->hasAutoItems()) {

            $items = $this->container->call([$item, 'getAutoItems']);

            if (is_array($items)) {
                d($item);
            }
            return $items;
        }
        else {
            return $item->getItems();
        }
    }


    /**
     *
     */
    protected function generateDefaultHtml(): string
    {
        $this->loop = 0;

        $html = '<nav class="' . ($this->parameters['class'] ?? null) . '">';

        $configuration = $this->container->get(\Frootbox\Config\Config::class);

        foreach ($this->navigation->getItems() as $item) {

            $item->setConfiguration($configuration);

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
        $itemIsActive = $item->isActive($this->parameters);

        $html = (string) null;

        if (!empty($this->parameters['wrapItems'])) {
            $html .= '<div class="item">';
        }

        $html .= '<a data-item="' . $item->getId() . '" data-parent="' . $item->getParentId() . '" data-loop="' . ++$this->loop . '" class="' . implode(' ', $item->getClasses()) . ' ' . ($itemIsActive ? 'active' : '') . '" href="' . $item->getHref() . '"><span>';

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
            $html .= $icon;

            if (empty($this->parameters['wrapLinkText'])) {
                $html .= $item->getTitle();
            }
            else {
                $html .= '<span class="link-title">' . $item->getTitle() . '</span>';
            }
        }

        $html .= '</span></a> ';

        $listChildren = !empty($this->parameters['listChildren']);

        if ($listChildren) {

            $children = $item->getItems();

            if ($children and $children->getCount()) {

                $html = str_replace('class="', 'class="has-children ', $html);

                $html .= '<div data-item="' . $item->getId() . '" class="children-wrapper ' . ($itemIsActive ? 'active' : '') . '">';

                foreach ($children as $item) {
                    $item->addClass('child');
                    $html .= $this->getItemHtml($item);
                }

                $html .= '</div>';
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

        if (!empty($this->parameters['wrapItems'])) {
            $html .= '</div>';
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

        $navViewId = isset($this->parameters['view']) ? $this->parameters['view'] : $this->navigation->getNavId();

        if ($navViewId === false) {
            return null;
        }

        foreach ($folders->getData() as $path) {

            $viewFile = $path . $navViewId . DIRECTORY_SEPARATOR . 'View.html.twig';

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
        // Obtain view file
        if (($viewFile = $this->getViewFile()) === null) {
            return $this->generateDefaultHtml();
        }

        $view = $this->container->get(\Frootbox\View\Engines\Interfaces\Engine::class);

        $view->set('parameters', $this->parameters);
        $view->set('navigation', $this->navigation);
        $view->set('renderer', $this);

        $html = $view->render($viewFile);

        // Auto inject css file
        $stylesheetFile = dirname($viewFile) . '/public/standards.less';

        if (file_exists($stylesheetFile)) {
            $html = '<link type="text/css" rel="stylesheet/less" href="FILE:' . $stylesheetFile . '" />' . PHP_EOL . $html;
        }

        // Auto inject javascript file
        $scriptFile = dirname($viewFile) . '/public/init.js';

        if (file_exists($scriptFile)) {
            $html = '<script src="FILE:' . $scriptFile . '"></script>' . PHP_EOL . $html;
        }

        return $html;
    }
}

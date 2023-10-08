<?php
/**
 *
 */

namespace Frootbox\View\Blocks;

class PreviewRenderer
{
    /**
     * @param \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     */
    public function __construct(
        protected \Frootbox\Persistence\Repositories\Extensions $extensionRepository,
        protected \Frootbox\Persistence\Repositories\Pages $pageRepository,
        protected \Frootbox\View\Engines\Interfaces\Engine $view,
        protected \DI\Container $container,
    )
    {}

    /**
     *
     */
    public function render(\Frootbox\Persistence\Content\Blocks\Block $block): string
    {
        // Generate block html
        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        // Inject scss variables
        $result = $this->extensionRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $scss = (string) null;

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath();

            $scssFile = $path . 'resources/public/css/styles-variables.less';

            if (file_exists($scssFile)) {
                $scss .= PHP_EOL . file_get_contents($scssFile);
            }
        }

        $blockHtml = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $blockHtml;


        if (!empty($block->getPageId())) {

            $page = $this->pageRepository->fetchById($block->getPageId());
            $this->view->set('page', $page);
        }

        $this->view->set('view', $this->view);

        if (!defined('EDITING')) {
            define('EDITING', true);
        }

        $parser = new \Frootbox\View\HtmlParser($blockHtml, $this->container);
        $html = $this->container->call([ $parser, 'parse']);

        return $html;
    }
}

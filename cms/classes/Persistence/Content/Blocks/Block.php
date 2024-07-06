<?php
/**
 *
 */

namespace Frootbox\Persistence\Content\Blocks;

use DI\Container;

class Block extends \Frootbox\Persistence\AbstractConfigurableRow
{
    protected $table = 'blocks';
    protected $model = Repositories\Blocks::class;
    protected $viewFile = null;

    protected bool $wasCalledFirst = false;
    protected bool $isCaged = false;

    use \Frootbox\Persistence\Traits\DummyImage;
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Visibility;

    /**
     * @return string
     */
    protected function getCssClass(): string
    {
        $cssClass = 'EditableBlock ' . $this->getExtensionId() . ' ' . $this->getBlockId() . ' ';

        if (!empty($this->getConfig('noPrint'))) {
            $cssClass .= 'fbx-ctr-noPrint';
        }

        if (!empty($this->getConfig('css.className'))) {
            $cssClass .= ' ' . $this->getConfig('css.className');
        }

        return $cssClass;
    }

    /**
     * @return AbstractAdminController
     */
    public function getAdminController(): \Frootbox\Persistence\Content\Blocks\AbstractAdminController
    {
        $className = substr(get_class($this), 0,-5) . 'Admin\\Controller';

        return new $className($this);
    }

    /**
     *
     */
    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        $className = '\\Frootbox\\Ext\\' . $this->getVendorId() . '\\' . $this->getExtensionId() . '\\ExtensionController';

        return new $className;
    }

    /**
     * @return array
     */
    public function getInitConfig(): array
    {
        $template = new \Frootbox\View\HtmlTemplate($this->getViewFile(), [

        ]);

        $config = $template->getConfig();

        return $config['initConfig'] ?? [];
    }

    /**
     *
     */
    public function getNameFromView(): ?string
    {
        $source = file_get_contents($this->getViewFile());

        if (!preg_match('#title: (.*?)\n#', $source, $match)) {
            return null;
        }

        $title = trim($match[1]);
        $title = str_replace('---', '', $title);

        return $title;
    }

    /**
     *
     */
    public function getPathFromConfig(
        \Frootbox\Config\Config $config = null
    ): ?string
    {
        $className = '\\Frootbox\\Ext\\' . $this->getVendorId() . '\\' . $this->getExtensionId() . '\\ExtensionController';
        $controller = new $className;

        $viewFile = $controller->getPath() . 'classes/Blocks/' . $this->getBlockId() . '/';

        return $viewFile;
    }

    public function getSettings(\Frootbox\Config\Config $config): array
    {
        $template = new \Frootbox\View\HtmlTemplate($this->viewFile, [
            'variables' => $this->getConfig('template.variables'),
        ]);
    }

    public function getThumbnailSrc(): string
    {
        $controller = $this->getExtensionController();
        $path = $controller->getPath() . 'classes/Blocks/' . $this->getBlockId() . '/';

        $files = [
            $path . 'thumbnail.jpg',
            $path . 'thumbnail.png',
        ];

        $thumbnail = null;

        foreach ($files as $file) {
            if (file_exists($file)) {
                $thumbnail = $file;
                break;
            }
        }

        if ($thumbnail === null) {
            $thumbnail = CORE_DIR . 'cms/admin/resources/public/images/no-thumbnail.png';
        }

        $key = md5($thumbnail);

        $_SESSION['staticfilemap'][$key] = $thumbnail;

        return SERVER_PATH . 'static/Ext/Core/System/ServeStatic/serve?t=' . $key;
    }

    /**
     *
     */
    public function getTitleClean(): ?string
    {
        $title = $this->getTitle();

        if (empty($title)) {
            return null;
        }

        $title = str_replace('---', '', $title);

        return $title;
    }

    /**
     *
     */
    public function getVariables(): array
    {
        $template = new \Frootbox\View\HtmlTemplate($this->viewFile, [
            'variables' => $this->getConfig('template.variables'),
        ]);

        $list = [];

        foreach ($template->getVariables() as $variable) {
            $list[$variable['name']] = $variable['value'];
        }

        return $list;
    }

    /**
     *
     */
    public function getViewFile(): string
    {
        // Fetch extensions
        $extensionsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $baseViewFile = null;
        $overrideViewFile = null;

        foreach ($extensions as $extension) {

            $viewFile = $extension->getExtensionController()->getPath() . 'classes/Blocks/' . $this->getBlockId() . '/Block.html.twig';

            if (!file_exists($viewFile)) {
                continue;
            }

            if ($extension->getExtensionId() == $this->getExtensionId() and $extension->getVendorId() == $this->getVendorId()) {
                $baseViewFile = $viewFile;
            }

            $xstring = 'override: ' . $this->getVendorId() . '/' . $this->getExtensionId() . '/' . $this->getBlockId();

            if (strpos(file_get_contents($viewFile), $xstring) !== false) {
                $overrideViewFile = $viewFile;
            }

            if (!empty($baseViewFile) and !empty($overrideViewFile)) {
                break;
            }
        }

        return $overrideViewFile ?? $baseViewFile;
    }

    /**
     *
     */
    public function getWasCalledFirst(): bool
    {
        return $this->wasCalledFirst;
    }

    /**
     *
     */
    public function isCaged(): bool
    {
        return $this->isCaged;
    }

    /**
     *
     */
    public function isFirst(): bool
    {
        if (!$this->wasCalledFirst) {
            $this->wasCalledFirst = true;

            return true;
        }
        else {
            return false;
        }
    }

    /**
     *
     */
    public function renderHtml(
        \Frootbox\Config\Config $config,
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        array $injectedVariables = []
    ): string
    {
        if (method_exists($this, 'onBeforeRendering')) {

            $response = $container->call([ $this, 'onBeforeRendering' ]);

            if ($response instanceof \Frootbox\View\Response) {
                foreach ($response->getData() as $key => $value) {
                    $view->set($key, $value);
                }
            }
            elseif (is_array($response)) {
                foreach ($response as $key => $value) {
                    $view->set($key, $value);
                }
            }
        }

        $view->set('variables', $injectedVariables);
        $view->set('block', $this);
        $view->set('view', $view);

        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $baseViewFile = null;
        $overrideViewFile = null;

        foreach ($extensions as $extension) {

            $viewFile = $extension->getExtensionController()->getPath() . 'classes/Blocks/' . $this->getBlockId() . '/Block.html.twig';

            if (!file_exists($viewFile)) {
                continue;
            }

            if ($extension->getExtensionId() == $this->getExtensionId() and $extension->getVendorId() == $this->getVendorId()) {
                $baseViewFile = $viewFile;
            }

            $xstring = 'override: ' . $this->getVendorId() . '/' . $this->getExtensionId() . '/' . $this->getBlockId();

            if (strpos(file_get_contents($viewFile), $xstring) !== false) {
                $overrideViewFile = $viewFile;
            }

            if (!empty($baseViewFile) and !empty($overrideViewFile)) {
                break;
            }
        }

        $this->viewFile = $overrideViewFile ?? $baseViewFile;

        if (empty($this->viewFile) or !file_exists($this->viewFile)) {
            return '<div style="">Template missing for block ' . $this->getBlockId() . '.</div>';
        }

        $path = dirname($this->viewFile) . '/';

        $template = new \Frootbox\View\HtmlTemplate($path . 'Block.html.twig', [
            'variables' => $this->getConfig('template.variables'),
        ]);

        // Render block html
        $html = $view->render($this->viewFile, [
            'path' => $path,
            'template' => $template,
            'data' => $injectedVariables,
        ]);

        // Auto-inject css files
        $stylesheet = $path . 'standards.less';

        if (file_exists($stylesheet)) {
            $html .= PHP_EOL . PHP_EOL . '<link type="text/css" rel="stylesheet/less" href="FILE:' . $stylesheet . '" />';
        }

        // Auto-inject javascript files
        $scriptFile = $path . 'init.js';

        if (file_exists($scriptFile)) {
            $html .= PHP_EOL . PHP_EOL . '<script src="FILE:' . $scriptFile . '"></script>';
        }

        $customStyles = (string) null;

        if (!empty($margin = $this->getConfig('margin'))) {

            if (isset($margin['top']) and strlen($margin['top']) > 0) {
                $customStyles .= 'margin-top: ' . $margin['top'] . 'px; ';
            }

            if (isset($margin['bottom']) and strlen($margin['bottom']) > 0) {
                $customStyles .= 'margin-bottom: ' . $margin['bottom'] . 'px; ';
            }
        }

        if ($this->getVisibility() == 0) {
            $customStyles .= ' display: none; ';
        }

        if (!empty($customStyles)) {
            $customStyles = 'style="' . $customStyles . '"';
        }

        $html = '<div ' . $customStyles . ' class="' . $this->getCssClass() . '" data-class="' . !empty($this->getClassName()) . '" data-editable-block data-block="' . $this->getId() . '" data-loop="' . ($injectedVariables['loopId'] ?? 1) . '">' . $html . '</div>';

        return trim($html);
    }

    /**
     *
     */
    public function setIsCaged(bool $isCaged): void
    {
        $this->isCaged = $isCaged;
    }

    /**
     *
     */
    public function setWasCalledFirst(bool $wasCalledFirst): void
    {
        $this->wasCalledFirst = $wasCalledFirst;
    }
}

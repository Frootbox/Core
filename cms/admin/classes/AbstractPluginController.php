<?php
/**
 * 
 */

namespace Frootbox\Admin;

abstract class AbstractPluginController
{
    protected $plugin;

    /**
     *
     */
    public function __construct(\Frootbox\Persistence\AbstractPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     *
     */
    public function getAssetUri(string $assetPath): ?string
    {
        $path = $this->getPath() . 'resources/public/' . $assetPath;
        $token = md5($path);

        $_SESSION['staticfilemap'][$token] = $path;

        return SERVER_PATH . 'static/Ext/Core/System/ServeStatic/serve?t=' . $token;
    }

    /**
     *
     */
    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        preg_match('#^Frootbox\\\\Ext\\\\(\w+)\\\\(\w+)\\\\(.*?)$#', get_class($this), $match);

        $class = 'Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

        return new $class;
    }

    /**
     * 
     */
    abstract public function getPath(): string;

    /**
     *
     */
    public function getResponse($type = 'html', int $status = 200, $body = [ ]): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response($type, $status, $body);
    }

    /**
     *
     */
    public function log(string $code, array $data = null): void
    {
        $this->plugin->log($code, $data);
    }

    /**
     *
     */
    public function render(
        \Frootbox\Admin\View $view,
        $action
    ): string
    {
        $viewFile = $this->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $this->getPath() . 'resources/private/views/' . ucfirst($action) . '.html';
        }

        $pluginHtml = $view->render($viewFile);

        return $pluginHtml;
    }


    /**
     * @deprecated
     * @see \Frootbox\Admin\AbstractPluginController::getResponse()
     */
    public function response($type = 'html', int $status = 200, $body = [ ]): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse($type, $status, $body);
    }
}

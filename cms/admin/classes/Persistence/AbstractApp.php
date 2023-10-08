<?php
/**
 *
 */

namespace Frootbox\Admin\Persistence;

abstract class AbstractApp extends \Frootbox\Persistence\AbstractRow
{
    use \Frootbox\Persistence\Traits\Config;
    use \Frootbox\Persistence\Traits\Uid;

    protected $table = 'admin_apps';
    protected $model = \Frootbox\Admin\Persistence\Repositories::class;

    protected $action;

    /**
     *
     */
    abstract public function getPath(): string;

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
    public function getResponse(
        $type = 'html',
        int $status = 200,
        $body = null,
        array $headers = null
    ): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response($type, $status, $body, $headers);
    }

    /**
     *
     */
    public function getUri($action, array $payload = null)
    {
        $payload['appId'] = $this->getId();
        $payload['a'] = $action;

        return \Frootbox\Admin\Front::getUri('App', 'index', $payload);
    }

    /**
     *
     */
    public function render(
        \Frootbox\Admin\View $view,
        array $payload = null
    ): string
    {
        $file = $this->getPath() . 'resources/private/views/' . ucfirst($this->action) . '.html.twig';

        $html = $view->render($file, null, $payload);

        return $html;
    }

    /**
     * @deprecated
     */
    public function response(
        $type = 'html',
        int $status = 200,
        $body = null,
        array $headers = null
    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse($type, $status, $body, $headers);
    }

    /**
     *
     */
    public function setAction(string $action): void
    {
        $this->action = substr($action, 0, -6);
    }
}

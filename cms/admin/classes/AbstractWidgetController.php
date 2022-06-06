<?php
/**
 *
 */

namespace Frootbox\Admin;

abstract class AbstractWidgetController
{
    protected $widget;

    /**
     *
     */
    public function __construct(\Frootbox\Persistence\Content\AbstractWidget $widget)
    {
        $this->widget = $widget;
    }

    /**
     *
     */
    public function getAdminUrl ( $action, array $payload = null )
    {

        $payload['widgetId'] = $this->widget->getid();
        $payload['action'] = $action;

        return SERVER_PATH . 'cms/admin/Editor/Widget/ajaxModalEdit?' . http_build_query($payload);
    }

    /**
     *
     */
    abstract public function getPath(): string;

    /**
     * Generate controller response
     */
    public function getResponse($type = 'html', int $status = 200, $body = null): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response($type, $status, $body);
    }

    /**
     *
     */
    public function getWidget ( )
    {
        return $this->widget;
    }

    /**
     * @deprecated
     */
    public function response($type = 'html', int $status = 200, $body = null): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse($type, $status, $body);
    }
}
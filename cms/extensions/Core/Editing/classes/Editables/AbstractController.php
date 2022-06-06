<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Editing\Editables;

abstract class AbstractController
{
    /**
     *
     */
    public function getActionUri ( $method, array $payload = null ): string
    {
        $class = str_replace('\\', '/', substr(get_class($this), 0,-17));

        $payload['editable'] = $class;
        $payload['method'] = $method;

        $url = SERVER_PATH . 'cms/admin/Editor/callEditable?' . http_build_query($payload);

        return $url;
    }

    /**
     *
     */
    abstract public function getPath ( ): string;

    /**
     *
     */
    public function getResponse(
        $type = 'html',
        int $status = 200,
        $body = [ ]
    ): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response($type, $status, $body);
    }
}

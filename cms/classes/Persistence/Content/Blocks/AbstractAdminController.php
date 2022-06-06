<?php
/**
 *
 */

namespace Frootbox\Persistence\Content\Blocks;

abstract class AbstractAdminController
{
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
}

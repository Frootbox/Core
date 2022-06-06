<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Dummy extends AbstractViewhelper
{
    /**
     *
     */
    public function __call($method, ?array $arguments = null)
    {
        return null;
    }

}

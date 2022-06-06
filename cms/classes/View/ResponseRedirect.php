<?php
/**
 *
 */

namespace Frootbox\View;

class ResponseRedirect extends Response
{
    protected $target;

    /**
     *
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     *
     */
    public function getTarget(): string
    {
        return $this->target;
    }
}

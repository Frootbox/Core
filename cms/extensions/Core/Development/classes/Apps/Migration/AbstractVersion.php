<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration;

abstract class AbstractVersion
{
    protected $steps = [ ];

    /**
     *
     */
    public function getSteps ( )
    {
        return $this->steps;
    }
}

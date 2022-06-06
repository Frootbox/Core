<?php
/**
 *
 */

namespace Frootbox\Admin;

abstract class AbstractImportAdapter
{

    protected $items = [ ];


    /**
     *
     */
    public function __construct ( )
    {

        if (!method_exists($this, 'execute')) {
            throw new \Frootbox\Exceptions\RuntimeError('Method ' . get_class($this) . '::export() does not exists.');
        }
    }



    /**
     *
     */
    public function setItems ( array $items ): AbstractImportAdapter
    {
        $this->items = $items;

        return $this;
    }


}
<?php
/**
 *
 */

namespace Frootbox;

abstract class AbstractEditable
{
    protected $type = 'General';

    /**
     *
     */
    public function getType(): string
    {
        return $this->type;
    }
}

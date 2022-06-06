<?php
/**
 *
 */

namespace Frootbox;

class GenericObject
{
    /**
     *
     */
    public function __toString ( ) {

        return 'Object ' . get_class($this);
    }
}

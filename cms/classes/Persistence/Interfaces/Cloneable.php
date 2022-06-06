<?php
/**
 *
 */

namespace Frootbox\Persistence\Interfaces;

interface Cloneable
{
    /**
     *
     */
    public function cloneContentFromAncestor(\DI\Container $container, \Frootbox\Persistence\AbstractRow $ancestor);
}
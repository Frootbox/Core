<?php 
/**
 * 
 */

namespace Frootbox;

abstract class AbstractStaticPage
{
    use \Frootbox\Persistence\Traits\DummyImage;

    protected $path;
    
    /**
     * 
     */
    abstract public function getPath(): string;
}

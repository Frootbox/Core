<?php 
/**
 * 
 */

namespace Frootbox\View\Engines\Interfaces;

interface Engine
{
    /**
     *
     */
    public function addPath(string $pathName): Engine;

    /**
     *
     */
    public function render(string $filename): string;

    /**
     * @param $variable
     * @param null $value
     */
    public function set($variable, $value = null): void;

    /**
     *
     */
    public function setContainer(\DI\Container $container): Engine;
}

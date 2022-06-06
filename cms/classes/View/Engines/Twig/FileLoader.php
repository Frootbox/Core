<?php
/**
 *
 */

namespace Frootbox\View\Engines\Twig;

class FileLoader extends \Twig\Loader\FilesystemLoader
{
    protected function findTemplate(string $name, bool $throw = true)
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (is_file($name)) {
            $this->cache[$name] = $name;
            return $name;
        }

        return parent::findTemplate($name);
    }
}

<?php
/**
 *
 */

namespace Frootbox\Traits;

trait ViewConfigParser
{
    /**
     *
     */
    protected function parseViewConfigString($string)
    {
        $config = [ ];

        if (preg_match('#\{\# config\s*(.*?)\s*\/config \#\}#s', $string, $match)) {

            $config = \Symfony\Component\Yaml\Yaml::parse($match[1]);
        }

        return $config;
    }
}

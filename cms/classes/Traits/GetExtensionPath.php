<?php
/**
 *
 */

namespace Frootbox\Traits;

trait GetExtensionPath
{
    /**
     *
     */
    protected function getExtensionPath(\Frootbox\Config\Config $config, $vendorId, $extensionId): string
    {
        // Scan for extensions path
        $paths = [
            CORE_DIR . 'cms/extensions/'
        ];

        if (!empty($config->get('extensions.paths'))) {
            $paths = array_merge($paths, $config->get('extensions.paths')->getData());
        }

        foreach ($paths as $path) {

            $extPath = $path . $vendorId . '/' . $extensionId . '/';

            if (file_exists($extPath . 'configuration.php')) {
                return $extPath;
            }
        }

        throw new \Frootbox\Exceptions\NotFound('Extension path for ' . $vendorId . '/' . $extensionId . ' not found.');
    }
}

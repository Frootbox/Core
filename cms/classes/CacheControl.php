<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2021-08-22
 */

namespace Frootbox;

class CacheControl
{
    /**
     *
     */
    public function __construct(
        private \Frootbox\Config\Config $config,
        private \Frootbox\ConfigStatics $configStatics,
    ) {}

    /**
     *
     */
    public function clear(): void
    {
        $token = $this->config->get('statics.signing.token') ?? null;

        $this->removeDirectory($this->config->get('filesRootFolder') . 'cache/');

        $cacheRevision = $this->config->get('statics.cache.revision') ?? 1;

        $this->configStatics->addConfig([
            'statics' => [
                'cache' => [
                    'revision' => ++$cacheRevision
                ]
            ]
        ]);

        if ($token !== null) {
            $this->configStatics->addConfig([
                'statics' => [
                    'signing' => [
                        'token' => $token
                    ]
                ]
            ]);
        }

        $this->configStatics->write();
    }

    /**
     * Removes a directory tree without loading all entries into memory.
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) {
            return;
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (file_exists($directory . '.cachekeep')) {
            return;
        }

        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeDirectory($item->getPathname());
            }
            else {
                unlink($item->getPathname());
            }
        }

        @rmdir($directory);
    }
}

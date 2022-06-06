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

        function rrmdir($dir) {

            if (is_dir($dir)) {

                if (file_exists($dir . '.cachekeep')) {
                    return;
                }

                $objects = scandir($dir);

                foreach ($objects as $object) {

                    if ($object == "." or $object == "..") {
                        continue;
                    }

                    if (is_dir($dir . '/' . $object)) {
                        rrmdir($dir . "/" . $object . '/');
                    }
                    else {
                        unlink($dir . "/" . $object);
                    }
                }

                @rmdir($dir);
            }
        }

        rrmdir($this->config->get('filesRootFolder') . 'cache/');

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
}

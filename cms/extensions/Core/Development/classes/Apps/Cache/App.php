<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Cache;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxClearCacheAction(
        \Frootbox\Config\Config $config,
        \Frootbox\ConfigStatics $configStatics
    ): \Frootbox\Admin\Controller\Response
    {
        $token = $config->get('statics.signing.token') ?? null;

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

        rrmdir($config->get('filesRootFolder') . 'cache/');

        $cacheRevision = $config->get('statics.cache.revision') ?? 1;

        $configStatics->addConfig([
            'statics' => [
                'cache' => [
                    'revision' => ++$cacheRevision
                ]
            ]
        ]);

        if ($token !== null) {
            $configStatics->addConfig([
                'statics' => [
                    'signing' => [
                        'token' => $token
                    ]
                ]
            ]);
        }


        $configStatics->write();


        return self::response('json', 200, [
            'success' => 'Der Cache wurde geleert.'
        ]);
    }


    /** 
     * 
     */
    public function indexAction (

    )
    {



        return self::response();
    }    
}
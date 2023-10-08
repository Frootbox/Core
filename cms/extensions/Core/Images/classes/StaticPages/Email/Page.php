<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\StaticPages\Email;

class Page
{
    /**
     *
     */
    public function serve(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $configuration
    )
    {
        $cacheRevision = $configuration->get('statics.cache.revision') ?? 1;

        $da = explode(':', $get->get('EXT'));

        $class = '\\Frootbox\\Ext\\' . $da[0] . '\\' . $da[1] . '\\ExtensionController';

        $extController = new $class;

        $localPath = $extController->getAssetPath($da[2] . '/' . $get->get('f'));

        $cachefilePath = 'cache/public/' . $cacheRevision . '/ext/' . $da[0] . '/' . $da[1] . '/' . $da[2] . '/' . $get->get('f');
        $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

        if (false and !file_exists($cachefilePathFull)) {

            $file = new \Frootbox\Filesystem\File($cachefilePathFull);
            $file->setSource(file_get_contents($localPath));
            $file->write();
        }

        header('Content-Type: image/png');
        readfile($localPath);
        exit;
    }
}

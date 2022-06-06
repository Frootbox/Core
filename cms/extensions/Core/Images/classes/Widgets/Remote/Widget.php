<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Remote;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget {

    /**
     * Get widgets root path
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function serveAction (
        // \Frootbox\Http\Get $get,
        // \Frootbox\Config\Config $config
    )
    {

        $ch = curl_init($this->getConfig('url'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $return = curl_exec($ch);

        # get the content type
        header('Content-type: ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        die($return);

        /*
        $cacheRevision = $config->get('statics.cache.revision') ?? 1;

        $xpath = 'cache/public/' . $cacheRevision . '/tmp/;
        $newPath = $configuration->get('filesRootFolder') . $xpath;
        */

        d(file_get_contents($url));

    }
}
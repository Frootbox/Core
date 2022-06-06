<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class Assets extends AbstractViewhelper
{
    protected $arguments = [
        'import' => [
            'input'
        ]
    ];

    /**
     *
     */
    public function importAction(
        string $input,
        \Frootbox\Config\Config $configuration
    ): string
    {
        $cacheRevision = $configuration->get('statics.cache.revision') ?? 1;

        if (substr($input, -4) == '.css') {
            $xpath = 'cache/public/' . $cacheRevision . '/css/' . md5($input) . '.css';
            $html = '<link data-nocache="1" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $xpath . '" />';
        }
        else if (substr($input, -3) == '.js') {
            $xpath = 'cache/public/' . $cacheRevision . '/js/' . md5($input) . '.js';
            $html = '<script data-nocache="1" src="' . $configuration->get('publicCacheDir') . $xpath . '"></script>';
        }

        $newPath = $configuration->get('filesRootFolder') . $xpath;

        if (!file_exists($newPath)) {

            $contextOptions=  array('ssl' => array (
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true)
            ) ;

            $context = stream_context_create($contextOptions);

            $source = file_get_contents($input, false, $context);

            $cachefile = new \Frootbox\Filesystem\File($newPath);
            $cachefile->setSource($source);
            $cachefile->write();
        }

        return $html;
    }
}

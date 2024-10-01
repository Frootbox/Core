<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class View extends AbstractViewhelper
{
    protected $arguments = [
        'asset' => [
            'source',
            [ 'name' => 'type', 'default' => 'auto' ],
            [ 'name' => 'untouched', 'default' => false ]
        ],
        'imgUrl' => [
            'source'
        ],
        'link' => [
            [ 'name' => 'url', 'default' => '' ]
        ]
    ];

    /**
     *
     */
    protected function getAssetType($source): string
    {
        $segments = explode('.', $source);
        $extension = array_pop($segments);

        switch ( $extension ) {

            case 'less':
                return 'scss';

            case 'css':
                return 'style';

            case 'js':
                return 'script';

            default:
                throw new \Frootbox\Exceptions\RuntimeError('Unknown asset extension ".' . $extension . '"');
        }
    }

    /**
     *
     */
    public function assetAction($source, $type, $untouched, \Frootbox\Config\Config $config): string
    {
        if ($type == 'auto') {
            $type = $this->getAssetType($source);
        }

        if (substr($source, 0, 4) != 'EXT:') {
            $source = $config->get('publicDir') . $source;
        }

        $attributes = (string) null;

        if ($untouched) {
            $attributes .= ' data-nocache="1" data-untouched="1" ';
        }

        switch ( $type ) {

            case 'style':
                return '<link ' . $attributes .' rel="stylesheet" type="text/css" href="' . $source . '" />';

            case 'script':
                return '<script ' . $attributes .' src="' . $source . '"></script>';

            case 'scss':
                return '<link ' . $attributes .' rel="stylesheet/less" type="text/css" href="' . $source . '" />';

            default:
                throw new \Frootbox\Exceptions\RuntimeError('Unknown asset type "' . $type . '"');
        }
    }

    /**
     *
     */
    public function imgUrlAction($source, \Frootbox\Config\Config $config):string
    {
        return $config->get('publicDir') . $source;
    }

    /**
     *
     */
    public function linkAction($url, \Frootbox\Config\Config $config)
    {
        $base = SERVER_PATH;

        // Append edit mode if currently editing
        if (defined('EDITING')) {
            $base .= 'edit/';
        }

        return $base . $url;
    }

    /**
     *
     */
    public function embedSvg($path): string
    {
        preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $path, $match);

        $controllerClass = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';
        $controller = new $controllerClass;

        $path = $controller->getPath() . 'resources/public/' . $match[3];
        $source = file_get_contents($path);

        $source = preg_replace('#\<\?xml(.*?)\?\>#', '', $source);

        return $source;
    }
}

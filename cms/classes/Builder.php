<?php
/**
 *
 */

namespace Frootbox;

class Builder
{
    protected $pluginsFolders = [ ];
    protected $plugin;
    protected $template;

    /**
     *
     */
    public function __construct (
        \Frootbox\Config\Config $config
    ) {
        $this->pluginsFolders[] = $config->get('pluginsRootFolder');
    }


    /**
     *
     */
    protected function getPaths ( )
    {
        $paths = [
            $this->plugin->getPath() . 'Builder/'
        ];

        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this->plugin), $match);

        foreach ($this->pluginsFolders as $path) {
            $paths[] = $path . $match[1] . '/' . $match[2] . '/' . $match[3] . '/Builder/';
        }

        return $paths;
    }



    /**
     *
     */
    public function getFile ( $fileOption = null ): string
    {
        $paths = $this->getPaths();

        foreach ($paths as $path) {

            if (file_exists($path . $fileOption)) {
                return $path . $fileOption;
            }
        }

        return $paths[0] . $this->template . '.html';
    }


    /**
     *
     */
    public function getTemplates ( $template = null ): array
    {
        // Set template
        if ($template !== null) {
            $this->setTemplate(($template));
        }


        $paths = [
            $this->plugin->getPath() . 'Builder/'
        ];

        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this->plugin), $match);

        foreach ($this->pluginsFolders as $path) {
            $paths[] = $path . $match[1] . '/' . $match[2] . '/' . $match[3] . '/Builder/';
        }

        $layouts = [ ];

        foreach ($paths as $path) {

            $directory = new \Frootbox\Filesystem\Directory($path);

            foreach ($directory as $file) {

                if ($this->template . '.html.twig' == $file OR preg_match('#^' . $this->template . '\-(.*?).html.twig$#i', $file)) {
                    $layouts[] = [
                        'file' => $file
                    ];
                }
            }
        }

        return $layouts;
    }


    /**
     *
     */
    public function setPlugin (
        \Frootbox\Persistence\AbstractPlugin $plugin
    ): Builder
    {
        $this->plugin = $plugin;

        return $this;
    }


    /**
     *
     */
    public function setTemplate ( $template )
    {
        $this->template = $template;
    }
}
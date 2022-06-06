<?php 
/**
 * 
 */

namespace Frootbox;

abstract class AbstractExtensionController
{
    use \Frootbox\Persistence\Traits\Config;

    protected $config = [ ];
    
    /**
     * 
     */
    public function __construct()
    {
        $this->config = require $this->getPath() . 'configuration.php';
    }

    /**
     *
     */
    public function getAssetPath ($assetSegment)
    {
        return $this->getPath() . 'resources/public/' . $assetSegment;
    }

    /**
     *
     */
    public function getBaseNamespace(): string
    {
        return substr(get_class($this), 0, -19);
    }

    /**
     *
     */
    public function getWidgets()
    {
        $dir = new \Frootbox\Filesystem\Directory(($this->getPath() . 'classes/Widgets/'));

        $list = [ ];

        if (!$dir->exists()) {
            return $list;
        }


        foreach ($dir as $widgetId) {

            $className = 'Frootbox\\Ext\\' . $this->config['vendor']['id'] . '\\' . $this->config['id'] . '\\Widgets\\' . $widgetId . '\\Widget';
            $widget = new $className();

            $title = $this->config['vendor']['id'] . '/' . $this->config['id'] . '/' . $widgetId;

            if (file_exists($languageFile = $widget->getPath() . 'resources/private/language/de-DE.php')) {

                $languageData = require $languageFile;

                if (!empty($languageData['Widget.Title'])) {
                    $title = $languageData['Widget.Title'];
                }
            }

            $list[] = [
                'widgetId' => $widgetId,
                'title' => $title,
                'extensionId' => $this->config['id'],
                'vendorId' => $this->config['vendor']['id'],
                'className' => $className
            ];
        }

        return $list;
    }
    
    /**
     * 
     */
    abstract public function getPath(): string;

    /**
     * Get available plugins
     */
    public function getPlugins(array $parameters = null): array
    {
        // Load plugins directory
        $directory = new \Frootbox\Filesystem\Directory($this->getPath() . 'classes/Plugins/');
        
        if (!$directory->exists()) {
            return [ ];
        }
        
        $list = [ ];

        foreach ($directory as $file) {
            
            // Build class name
            $class = '\\Frootbox\\Ext\\' . $this->config['vendor']['id'] . '\\' . $this->config['id'] . '\\Plugins\\' . $file . '\\Plugin';

            if (!class_exists($class)) {
                continue;
            }

            // Create plugins object
            $plugin = new $class;

            if (!empty($parameters['onlyContainer']) and !$plugin->isContainer()) {
                continue;
            }

            $title = $plugin->getTitleDefault() ?? $file;

            $list[] = [
                'name' => $file,
                'title' => $title,
                'id' => $class
            ];
        }
        
        return $list;
    }
}

<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Plugin\Partials\ListLayouts;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config
    )
    {
        // Obtain element
        $element = $this->data['plugin'];

        if ($element->getType() == 'Text') {
            return [ ];
        }
        
        // Get layouts
        $list = [ ];
        
        foreach ($element->getPublicActions() as $action) {
            
            $paths = [ $element->getPath() . 'Layouts/' ];

            if (file_exists($path = $config->get('pluginsRootFolder'))) {

                preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($element), $match);

                $path .= $match[1] . '/' . $match[2] . '/' . $match[3] . '/';
                
                if (file_exists($path)) {
                    $paths[] = $path;                    
                }                
            }
            
            foreach ($paths as $path) {
                
                $dir = new \Frootbox\Filesystem\Directory($path);

                if (!$dir->exists()) {
                    continue;
                }
                
                foreach ($dir as $file) {
                    
                    if (!preg_match('#' . $action . '([0-9]{1,})#i', $file, $match)) {
                        continue;
                    }

                    $viewFile = $dir->getPath() . $file . '/View.html.twig';

                    if (!file_exists($viewFile)) {
                        $viewFile = $dir->getPath() . $file . '/View.html';
                    }

                    if (!file_exists($viewFile)) {
                        continue;
                    }

                    $list[$action][(int) $match[1]] = new \Frootbox\View\HtmlTemplate($viewFile, [
                        'templateId' => $file,
                        'number' => (int) $match[1],
                    ]);
                }
            }

            if (isset($list[$action])) {
                ksort($list[$action]);
            }
        }

        $view->set('layouts', $list);      
    }
}

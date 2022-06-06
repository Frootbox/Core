<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait Layouts
{

    /**
     *
     */
    abstract public function getLayoutForAction ( $action ) : string;


    /**
     *
     */
    abstract public function getPath ( ) : string;


    /**
     *
     */
    abstract public function getPublicActions ( ) : array;


    /**
     *
     */
    public function getLayouts(\Frootbox\Config\Config $config = null): array
    {
        // Get layouts
        $list = [ ];

        foreach ($this->getPublicActions() as $action) {

            $recentLayout = $this->getLayoutForAction($action);

            $paths = [ $this->getPath() . 'Layouts/' ];

            if ($config) {

                // Override / extend widgets layouts
                if (preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Widgets\\\\(.*?)\\\\Widget$#', get_class($this), $match)) {

                    if ($config->get('widgetsRootFolder')) {
                        $paths[] = $config->get('widgetsRootFolder') . $match[1] . DIRECTORY_SEPARATOR . $match[2] . DIRECTORY_SEPARATOR . $match[3] . DIRECTORY_SEPARATOR;
                    }
                }
            }


            /*
             *  TODO: Allow overriding / extending layouts
            if (file_exists($path = $config->get('pluginsRootFolder'))) {

                preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($plugin), $match);

                $path .= $match[1] . '/' . $match[2] . '/' . $match[3] . '/';

                if (file_exists($path)) {
                    $paths[] = $path;
                }
            }
            */


            foreach ($paths as $path) {

                $dir = new \Frootbox\Filesystem\Directory($path);

                if (!$dir->exists()) {
                    continue;
                }

                foreach ($dir as $file) {

                    if (!preg_match('#' . $action . '([0-9]{1,})#i', $file, $match)) {
                        continue;
                    }

                    $xfile = $dir->getPath() . $file . '/View.html';

                    if (!file_exists($xfile)) {

                        $xfile = $dir->getPath() . $file . '/View.html.twig';

                        if (!file_exists($xfile)) {
                            continue;
                        }
                    }

                    $list[$action][(int) $match[1]] = new \Frootbox\View\HtmlTemplate($xfile, [
                        'templateId' => $file,
                        'number' => (int) $match[1],
                        'active' => ($recentLayout == $file)
                    ]);
                }
            }

            if (isset($list[$action])) {
                ksort($list[$action]);
            }
        }

        return $list;
    }

}

<?php
/**
 *
 */

namespace Frootbox\Admin\Controller\Page\Viewhelper;

/**
 *
 */
class Page extends \Frootbox\Admin\Viewhelper\AbstractViewhelper
{
    use \Frootbox\Traits\ViewConfigParser;

    /**
     *
     */
    public function getLayoutsAction(
        \Frootbox\Config\Config $config
    ): array
    {
        if (!file_exists($config->get('layoutRootFolder'))) {
            return [ ];    
        }
        
        $dir = dir($config->get('layoutRootFolder'));

        $list = [ ];

        while (false !== ($entry = $dir->read())) {

            if ($entry[0] == '.') {
                continue;
            }

            $list[] = new \Frootbox\View\HtmlTemplate($dir->path . $entry);
        }

        return $list;
    }
}

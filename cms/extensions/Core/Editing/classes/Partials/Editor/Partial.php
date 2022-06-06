<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Editing\Partials\Editor;

use DI\Container;

class Partial extends \Frootbox\View\Partials\AbstractPartial
{
    /**
     *
     */
    protected function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        if (empty($config->get('editables'))) {
            return;
        }

        $html = (string) null;

        $view->set('SID', SID);

        foreach ($config->get('editables') as $editable) {

            $class = $editable['editable'] . '\\Editable';

            $editable = new $class;

            if (!method_exists($editable, 'initEditing')) {

                $viewfile = $editable->getPath() . 'View.html.twig';

                if (!file_exists($viewfile)) {
                    $viewfile = $editable->getPath() . 'View.html';

                    if (!file_exists($viewfile)) {
                        continue;
                    }
                }

                $html .= "\n\n\n" . $view->render($viewfile);
            }
        }

        $view->set('editablesHtml', $html);
    }
}

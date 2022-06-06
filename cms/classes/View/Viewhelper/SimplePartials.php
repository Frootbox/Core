<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class SimplePartials extends AbstractViewhelper
{
    protected $arguments = [
        'renderPartial' => [
            'segment',
            [ 'name' => 'parameters', 'default' => [ ] ]
        ]
    ];

    /**
     *
     */
    public function renderPartialAction(
        $segment,
        $parameters,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config
    ): string
    {
        $files = [];
        $files[] = $config->get('partialsRootFolder') . $segment . '.html';
        $files[] = $config->get('partialsRootFolder') . $segment . '.html.twig';

        if (!empty($parameters['fromfile'])) {
            $files[] = dirname($parameters['fromfile']) . '/' . $segment . '.html.twig';
        }

        $files[] = 'Partials/' . ucfirst($segment) . '.html';

        foreach ($files as $file) {

            if (file_exists($file)) {
                break;
            }
        }

        $view->set('data', $parameters);

        return $view->render($file);
    }
}

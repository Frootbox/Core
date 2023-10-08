<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class Partials extends AbstractViewhelper
{
    protected $arguments = [
        'renderPartial' => [
            'segment',
            [ 'name' => 'parameters', 'default' => [ ] ]
        ],
        'render' => [
            'partialClass',
            [ 'name' => 'attributes', 'default' => [ ] ]
        ]
    ];

    /**
     * @deprecated
     */
    public function renderPartialAction(
        $segment,
        $parameters,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config
    ): string
    {
        $files = [];

        if ($segment[0] == '\\') {

            $partialClass = $segment . '\\Partial';

            $partial = $this->container->get($partialClass);
            $partial->setAttributes($parameters);

            if (method_exists($partial, 'onBeforeRendering')) {
                $this->container->call([ $partial, 'onBeforeRendering']);
            }

            $html = $this->container->call([ $partial, 'render']);

            return $html;
        }
        elseif (preg_match('#^\/Frootbox\/Ext\/(.*?)\/(.*?)\/Plugins\/(.*?)\/(.*?)$#', $segment, $match)) {

            $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

            $extController = new $class;

            $files[] = $config->get('partialsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $match[4] . '/View.html.twig';
            $files[] = $extController->getPath() . 'classes/Plugins/' . $match[3] . '/resources/private/partials/' . $match[4] . '/View.html.twig';
            $files[] = $extController->getPath() . 'classes/Plugins/' . $match[3] . '/' . $match[4] . '/resources/private/views/View.html.twig';
        }
        elseif (preg_match('#^\/Frootbox\/Ext\/(.*?)\/(.*?)\/(.*?)$#', $segment, $match)) {

            $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';
            $extController = new $class;

            if (!empty($config->get('partialsRootFolder'))) {
                $files[] = $config->get('partialsRootFolder') . $match[3] . '/View.html.twig';
            }

            $files[] = $extController->getPath() . 'resources/private/partials/' . $match[3] . '/View.html.twig';
        }
        else {

            if (!empty($this->parameters['basePath'])) {
                $files[] = $this->parameters['basePath'] . 'Partials/' . $segment . '/View.html.twig';
            }

            $files[] = $config->get('partialsRootFolder') . $segment . '.html.twig';
            $files[] = $config->get('partialsRootFolder') . $segment . '.html';
            $files[] = $config->get('partialsRootFolder') . $segment . '/View.html.twig';

            if (!empty($this->parameters['plugin'])) {

                $plugin = $this->parameters['plugin'];

                $files[] = $plugin->getPath() . 'Layouts/' . $segment . '.html.twig';
                $files[] = $plugin->getPath() . 'Layouts/' . $segment . '.html';
                $files[] = $plugin->getPath() . 'resources/private/partials/' . $segment . '/View.html.twig';
            }
        }

        if (!empty($this->parameters['file'])) {
            $files[] = dirname($this->parameters['file']) . DIRECTORY_SEPARATOR . 'Partials/' . ucfirst($segment) . '.html.twig';
        }

        $file = null;

        foreach ($files as $xfile) {

            if (file_exists($xfile)) {
                $file = $xfile;
                break;
            }
        }

        if ($file === null) {
            $file = 'Partials/' . ucfirst($segment) . '.html';
        }

        $view->set('data', $parameters);

        foreach ($parameters as $key => $value) {
            $view->set($key, $value);
        }

        $html = $view->render($file, [
            'filePath' => dirname($file) . '/',
        ]);

        if (file_exists($file)) {

            $stylesheet = dirname($file) . '/public/standards.less';

            if (file_exists($stylesheet)) {
                $html = '<link rel="stylesheet/less" type="text/css" href="FILE:' . $stylesheet . '">' . PHP_EOL . PHP_EOL . $html;
            }

            $scriptFile = dirname($file) . '/public/init.js';

            if (file_exists($scriptFile)) {
                $html = '<script src="FILE:' . $scriptFile . '"></script>' . PHP_EOL . PHP_EOL . $html;
            }
        }

        return $html;
    }

    /**
     *
     */
    public function renderAction(
        $partialClass,
        $attributes,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \DI\Container $container
    ): string
    {
        // Fix classname if short name i sused
        if (substr($partialClass, -8) !== '\\Partial') {
            $partialClass .= '\\Partial';
        }

        // Get partial
        $partial = $container->get($partialClass);
        $partial->setAttributes($attributes);

        if (method_exists($partial, 'onBeforeRendering')) {
            $this->container->call([ $partial, 'onBeforeRendering']);
        }

        $html = $this->container->call([ $partial, 'render']);

        return $html;
    }
}

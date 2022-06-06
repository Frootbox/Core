<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Admin\Gizmos\Quota;

class Gizmo extends \Frootbox\Admin\AbstractGizmo
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config
    ): ?\Frootbox\Admin\Controller\Response
    {
        if (empty($config->get('system.quota'))) {
            throw new \Frootbox\Exceptions\ConfigurationMissing(null, [ 'system.quota' ]);
        }

        exec('du -sk ' . ($config->get('system.root') ?? CORE_DIR), $output);

        if (empty($output)) {
            return null;
        }

        [ $size, $path ] = explode("\t", $output[0]);

        $value = str_replace(',', '.', round($size / 1024  / 1024, 2));

        $view->set('max', $config->get('system.quota'));
        $view->set('value', $value);

        return self::getResponse();
    }
}
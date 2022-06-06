<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Admin\Gizmos\ExtensionUpdates;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): ?Response
    {
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1
            ]
        ]);

        foreach ($result as $index => $extension) {

            if (version_compare($extension->getVersion(), $extension->getExtensionController()->getConfig('version')) >= 0) {
                $result->removeByIndex($index);
            }
        }

        $view->set('extensions', $result);

        return self::getResponse();
    }
}

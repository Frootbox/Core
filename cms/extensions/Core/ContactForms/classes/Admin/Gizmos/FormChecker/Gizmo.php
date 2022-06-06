<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Forms\Admin\Gizmos\FormChecker;

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
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): ?Response
    {
        // Fetch form plugins
        $result = $contentElementsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Forms\Plugins\Form\Plugin::class
            ]
        ]);

        $list = [];

        foreach ($result as $plugin) {

            if (!empty($plugin->getConfig('recipients'))) {
                continue;
            }

            $list[] = [
                'plugin' => $plugin
            ];
        }

        if (empty($list)) {
            return null;
        }

        return self::getResponse([
            'list' => $list
        ]);
    }
}

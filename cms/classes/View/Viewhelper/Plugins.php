<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class Plugins extends AbstractViewhelper
{
    protected $arguments = [
        'getPlugin' => [
            'pluginId'
        ],
        'getByClass' => [
            'class'
        ]
    ];

    /**
     *
     */
    public function getByClassAction(
        $class,
        \Frootbox\Persistence\Content\Repositories\ContentElements $pluginsRepository
    )
    {
        return $pluginsRepository->fetchOne([
            'where' => [
                'className' => $class
            ]
        ]);
    }

    /**
     *
     */
    public function getPluginAction(
        $pluginId,
        \Frootbox\Persistence\Content\Repositories\ContentElements $pluginsRepository
    )
    {
        return $pluginsRepository->fetchById($pluginId);
    }
}

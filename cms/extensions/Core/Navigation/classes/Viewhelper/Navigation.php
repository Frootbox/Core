<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Viewhelper;

class Navigation extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getNavigation' => [
            'navUid',
            'parameters',
        ],
    ];

    protected $customFieldViewsFolder = null;

    /**
     *
     */
    public function getNavigationAction(
        string $navUid,
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository,
        array $parameters = [],
    ): ?\Frootbox\Ext\Core\Navigation\Navigations\Renderer
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchOne([
            'where' => [
                'navId' => $navUid,
            ],
        ]);

        if (empty($navigation)) {

            if (empty($parameters['default'])) {
                return null;
            }

            // Fetch default navigation
            $navigation = $navigationsRepository->fetchOne([
                'where' => [
                    'navId' => $parameters['default'],
                ],
            ]);

            if (empty($navigation)) {
                return null;
            }
        }

        $renderer = new \Frootbox\Ext\Core\Navigation\Navigations\Renderer($navigation, $container, $parameters);

        return $renderer;
    }
}

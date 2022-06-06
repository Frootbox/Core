<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Viewhelper;

class Teaser extends \Frootbox\View\Viewhelper\AbstractViewhelper
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
    public function getTeasersAction(
        array $parameters = [],
        \DI\Container $container,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasersRepository
    ): \Frootbox\Db\Result
    {
        // Fetch teasers
        $teasers = $teasersRepository->fetch([
            'where' => [

            ],
        ]);

        return $teasers;
    }
}

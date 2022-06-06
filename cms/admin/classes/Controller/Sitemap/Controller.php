<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-04-16
 */

namespace Frootbox\Admin\Controller\Sitemap;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     * Display main sitemap
     */
    public function index(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function multiDelete(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch root page
        $rootPage = $pages->fetchOne([
            'where' => [
                'language' => 'de-DE',
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        // Fetch pages tree
        $tree = $pages->getTree($rootPage->getId());
        $view->set('tree', $tree);

        return self::getResponse();
    }

    /**
     *
     */
    public function print(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch root page
        $rootPage = $pages->fetchOne([
            'where' => [
                'language' => 'de-DE',
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        // Fetch pages tree
        $tree = $pages->getTree($rootPage->getId());
        $view->set('tree', $tree);

        return self::getResponse();
    }
}
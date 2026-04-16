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
     * @param \Frootbox\Persistence\Repositories\Pages $pageRepository
     * @return \Frootbox\Admin\Controller\Response
     */
    public function export(
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch root page
        $rootPage = $pageRepository->fetchOne([
            'where' => [
                'language' => 'de-DE',
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        $data = [];

        foreach ($rootPage->getTree() as $page) {

            $data[] = [
                'title' => $page->getTitle(),
                'url' => SERVER_PATH_PROTOCOL . trim($page->getUri(), '/'),
                'itemDepth' => $page->getLevel(),
            ];
        }

        d(json_encode($data, JSON_PRETTY_PRINT));

        return self::getResponse();
    }

    /**
     * Display main sitemap
     */
    public function index(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }

    public function meta(
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch root page
        $rootPage = $pageRepository->fetchOne([
            'where' => [
                'language' => 'de-DE',
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        // Fetch pages tree
        $tree = $pageRepository->getTree($rootPage->getId());

        return self::getResponse('html', 200, [
            'Tree' => $tree,
        ]);
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
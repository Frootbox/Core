<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Sitemap\Partials\Sitemap;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
    ): void
    {
        // Fetch root page
        $result = $pagesRepository->fetch([
            'where' => [
                'language' => DEFAULT_LANGUAGE,
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ],
            'limit' => 1
        ]);

        if ($result->getCount() == 0) {

            $rootPage = $pagesRepository->insertRoot(new \Frootbox\Persistence\Page([
                'title' => 'Startseite',
                'language' => DEFAULT_LANGUAGE,
            ]));
        }
        else {
            
            $rootPage = $result->current();
        }

        // Generate sitemap tree
        $tree = $pagesRepository->getTree($rootPage->getId());

        $view->set('rootPage', $rootPage);
        $view->set('tree', $tree);
    }
}

<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\PageManager;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];

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
    public function indexAction(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): Response
    {
        // Fetch root page
        $rooPage = $pagesRepository->fetchOne([
            'where' => [
                'parentId' => 0,
            ],
        ]);

        $stack = [];

        function loop ($page, &$stack, $pagesRepository, $depth = 1) {

            $stack[] = [
                'page' => $page,
                'depth' => $depth,
            ];

            $children = $pagesRepository->fetch([
                'where' => [
                    'parentId' => $page->getId(),
                ],
                'order' => [ 'lft ASC' ],
            ]);

            foreach ($children as $child) {
                loop($child, $stack, $pagesRepository, $depth + 1);
            }

            return $stack;
        }

        loop($rooPage, $stack, $pagesRepository);

        return self::getResponse('html', 200, [
            'stack' => $stack,
        ]);
    }
}

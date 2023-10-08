<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\AppManager;

use DI\Container;
use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Get;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Admin\Persistence\Repositories\Apps $appRepository
     * @return Response
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Persistence\Repositories\Apps $appRepository,
    ): Response
    {
        foreach ($post->get('access') as $appId => $access) {

            $app = $appRepository->fetchById($appId);

            $app->setAccess($access);
            $app->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     * @param \Frootbox\Admin\Persistence\Repositories\Apps $appRepository
     * @return Response
     */
    public function indexAction(
        \Frootbox\Admin\Persistence\Repositories\Apps $appRepository,
    ): Response
    {
        // Fetch apps
        $apps = $appRepository->fetch([
            'where' => [
                'menuId' => 'Global',
            ],
        ]);

        return self::getResponse('html', 200, [
            'apps' => $apps,
        ]);
    }
}

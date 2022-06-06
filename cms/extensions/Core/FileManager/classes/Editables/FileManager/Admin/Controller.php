<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Editables\FileManager\Admin;

use DI\Container;
use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
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
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('html', 200, [
            'uid' => $get->get('uid'),
        ]);
    }
}

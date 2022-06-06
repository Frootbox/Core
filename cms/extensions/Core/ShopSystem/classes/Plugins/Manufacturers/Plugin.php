<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Manufacturers;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Fetch manufacturers
        $result = $manufacturersRepository->fetch();

        return new \Frootbox\View\Response([
            'manufacturers' => $result
        ]);
    }
}

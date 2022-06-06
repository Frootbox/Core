<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Apps\FormsManager;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Get;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function indexAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    ): Response
    {
        // fetch forms
        $result = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Forms\Plugins\Form\Plugin::class,
            ],
        ]);

        $list = [];

        foreach ($result as $plugin) {

            $list[] = [
                'plugin' => $plugin,
            ];
        }

        return self::getResponse('html', 200, [
            'plugins' => $list,
        ]);
    }
}

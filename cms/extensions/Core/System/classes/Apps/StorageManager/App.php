<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\StorageManager;

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
        Get $get,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        $directory = new \Frootbox\Filesystem\Directory($get->get('path') ?? CORE_DIR);

        $list = [];
        $total = 0;

        foreach ($directory as $file) {

            $path = $directory->getPath() . $file;

            if (!is_dir($path)) {
                continue;
            }

            $output = [];

            exec('du -sk ' . $path, $output);

            [ $size, $path ] = explode("\t", $output[0]);

            $total += $size;

            $value = str_replace(',', '.', round($size / 1024  / 1024, 2));

            $list[] = [
                'path' => $path,
                'size' => $value
            ];
        }

        return self::getResponse('html', 200, [
            'folders' => $list,
            'total' => str_replace(',', '.', round($total / 1024  / 1024, 2))
        ]);
    }
}

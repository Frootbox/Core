<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\CronManager;

use DI\Container;
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
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Extensions $extensionRepository,
    ): Response
    {
        // Fetch extensions
        $result = $extensionRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $list = [];

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath() . 'classes/Cronjobs';

            if (!file_exists($path)) {
                continue;
            }

            $directory = new \Frootbox\Filesystem\Directory($path);

            foreach ($directory as $file) {

                $path = $directory->getPath() . $file;

                require $path;

                $class = substr(get_class($extension->getExtensionController()), 0, -19) . 'Cronjobs\\' . substr($file, 0, -4);

                $list[] = $container->get($class);
            }
        }

        return self::getResponse('html', 200, [
            'cronjobs' => $list,
            'cronCommand' => CORE_DIR,
        ]);
    }

    /**
     *
     */
    public function runCronAction(
        \Frootbox\Http\Get $get,
    )
    {
        d($get);
    }
}

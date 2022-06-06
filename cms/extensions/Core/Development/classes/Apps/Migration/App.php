<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration;

class App extends \Frootbox\Admin\Persistence\AbstractApp {

    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];


    /**
     * 
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /** 
     * 
     */
    public function indexAction (
        \Frootbox\Config\Config $config,
        \Frootbox\ConfigStatics $statics,
        \Frootbox\Admin\View $view
    ) {

        if (empty($currentVersion = $config->get('system.version'))) {

            // Initialize system version with 0.0.0
            $statics->addConfig([
                'system' => [
                    'version' => '0.0.0'
                ]
            ]);

            $statics->write();

            $currentVersion = '0.0.0';
        }

        $view->set('version', $currentVersion);


        // Gather migration steps
        $directory = new \Frootbox\Filesystem\Directory($this->getPath() . 'Versions/');

        $steps = [ ];

        foreach ($directory as $file) {

            $version = substr($file, 1, -4);

            $class = '\\Frootbox\\Ext\\Core\\Development\\Apps\\Migration\\Versions\\' . substr($file, 0, -4);

            $xversion = new $class;


            $steps[(int) $version] = [
                'version' => $version,
                'steps' => $xversion->getSteps()
            ];
        }

        ksort($steps);


        $next = null;

        foreach ($steps as &$step) {

            $step['completed'] = (int) $step['version'] <= (int) $currentVersion;

            if (!$step['completed'] and $next === null) {
                $step['next'] = true;
                $next = $step['version'];
            }
        }


        krsort($steps);

        $view->set('steps', $steps);

        return self::response();
    }


    /**
     *
     */
    public function migrateAction (
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\ConfigStatics $statics
    ) {
        // Get migration class
        $migrationClass = '\\Frootbox\\Ext\\Core\\Development\\Apps\\Migration\\Versions\\V' . $get->get('version');
        $migration = $container->get($migrationClass);

        foreach ($migration->getSteps() as $step) {

            $container->call([ $migration, $step ]);
        }

        // Set new version
        $statics->addConfig([
            'system' => [
                'version' => $get->get('version')
            ]
        ]);

        $statics->write();

        return self::response('json', 200, [
            'redirect' => $this->getUri('index')
        ]);
    }
}
<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Extension extends AbstractRow
{
    protected $table = 'extensions';
    protected $model = Repositories\Extensions::class;

    /**
     *
     */
    public function getAvailableMigrations(): array
    {
        $migrationsPath = $this->getExtensionController()->getPath() . 'classes/Migrations/';

        $list = [];

        if (!file_exists($migrationsPath)) {
            return $list;
        }

        $directory = new \Frootbox\Filesystem\Directory($migrationsPath);

        foreach ($directory as $file) {

            preg_match('#^Version([0-9]{2})([0-9]{2})([0-9]{2})\.php$#', $file, $match);

            $version = (int) $match[1] . '.' . (int) $match[2] . '.' . (int) $match[3];

            $className = '\\Frootbox\\Ext\\' . $this->getVendorId() . '\\' . $this->getExtensionId() . '\\Migrations\\' . substr($file, 0, -4);
            $migration = new $className;

            if (version_compare($this->getVersion(), $version) < 0) {

                $list[$version] = [
                    'version' => $version,
                    'description' => $migration->getDescription(),
                    'migration' => substr($file, 0, -4),
                    'className' => $className,
                ];

            }
        }

        ksort($list);

        return $list;
    }

    /**
     *
     */
    public function getAvailableVersion(): ?string
    {
        if (empty($this->getIsActive())) {
            return null;
        }

        $configPath = $this->getExtensionController()->getPath() . 'configuration.php';
        $data = require $configPath;

        return $data['version'] ?? null;
    }

    /**
     * 
     */
    public function getBaseConfig(
        \Frootbox\Config\Config $config
    )
    {
        // Scan for extensions path
        $paths = [
            CORE_DIR . 'cms/extensions/'
        ];


        if (!empty($config->get('extensions.paths'))) {
            $paths = array_merge($paths, $config->get('extensions.paths')->getData());
        }

        foreach ($paths as $path) {

            $extPath = $path . $this->getVendorId() . '/' . $this->getExtensionId() . '/';

            if (file_exists($extPath . 'configuration.php')) {

                return require $extPath . 'configuration.php';
            }
        }

        throw new \Frootbox\Exceptions\NotFound('Extension path for ' . $this->getVendorId() . '/' . $this->getExtensionId() . 'not found.');
    }

    /**
     *
     */
    public function getWidgets()
    {
        return $this->getExtensionController()->getWidgets();
    }

    /**
     * 
     */
    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        // Build class name
        $className = '\\Frootbox\\Ext\\' . $this->getVendorId() . '\\' . $this->getExtensionId() . '\\ExtensionController';

        if (!class_exists($className)) {
            unlink(FILES_DIR . 'cache/system/autoload.php');
        }

        return new $className;
    }
    
    /**
     * 
     */
    public function install ( )
    {
        // Activate extension
        $this->setIsactive(1);
        $this->save();
    }

    /**
     * 
     */
    public function init(
        \Frootbox\Config\Config $config,
        \Frootbox\Admin\Persistence\Repositories\Apps $appsRepository
    ): void
    {
        // Get base config
        $baseConfig = $this->getBaseConfig($config);

        // Get statics configuration utility
        $statics = new \Frootbox\ConfigStatics($config);

        // Add extensions config data to global configuration
        if (!empty($baseConfig['autoinstall']['config'])) {

            if (!empty($baseConfig['autoinstall']['config']['injectPublics'])) {

                $injectPublics = $baseConfig['autoinstall']['config']['injectPublics'];

                $publics = !empty($config->get('injectPublics')) ? $config->get('injectPublics')->getData() : [];

                $injectPublics = array_merge($injectPublics, $publics);

                $baseConfig['autoinstall']['config']['injectPublics'] = $injectPublics;
            }

            $statics->addConfig($baseConfig['autoinstall']['config']);
        }


        // Install extensions editables
        if (!empty($baseConfig['autoinstall']['editables'])) {

            $editables = $config->get('editables') ? $config->get('editables')->getData() : [ ];

            foreach ($baseConfig['autoinstall']['editables'] as $editable) {

                foreach ($editables as $check) {
                    if ($check['editable'] == $editable) {
                        continue 2;
                    }
                }

                $editables[] = [ 'editable' => $editable ];
            }

            $statics->addConfig([
                'editables' => $editables
            ]);
        }

        // Install extensions routers
        if (!empty($baseConfig['autoinstall']['routes'])) {

            $routes = !empty($config->get('routes')) ? $config->get('routes')->getData() : [];

            foreach ($baseConfig['autoinstall']['routes'] as $route) {

                $routes[] = [
                    'route' => $route,
                ];
            }

            $statics->addConfig([
                'routes' => $routes,
            ]);
        }

        // Install extensions gizmos
        if (!empty($baseConfig['autoinstall']['gizmos'])) {

            $gizmos = !empty($config->get('gizmos')) ? $config->get('gizmos')->getData() : [];

            foreach ($baseConfig['autoinstall']['gizmos'] as $route) {

                $gizmos[] = [
                    'gizmo' => $route,
                ];
            }

            $statics->addConfig([
                'gizmos' => $gizmos,
            ]);
        }

        // Install blocks folders
        if (!empty($baseConfig['autoinstall']['blocksRootFolders'])) {

            $blocksRootFolders = $config->get('blocksRootFolders') ? $config->get('blocksRootFolders')->getData() : [ ];

            foreach ($baseConfig['autoinstall']['blocksRootFolders'] as $folder) {

                $blocksRootFolders[] = $folder;
            }

            $statics->addConfig([
                'blocksRootFolders' => $blocksRootFolders
            ]);
        }

        // Install extensions routers
        if (!empty($baseConfig['autoinstall']['postroutes'])) {

            $postroutes = !empty($config->get('postroutes')) ? $config->get('postroutes')->getData() : [];

            foreach ($baseConfig['autoinstall']['postroutes'] as $route) {

                $postroutes[] = [
                    'route' => $route,
                ];
            }

            $statics->addConfig([
                'postroutes' => $postroutes,
            ]);
        }

        // Install extensions fail routers
        if (!empty($baseConfig['autoinstall']['failroutes'])) {

            $failroutes = !empty($config->get('failroutes')) ? $config->get('failroutes')->getData() : [];

            foreach ($baseConfig['autoinstall']['failroutes'] as $route) {

                $failroutes[] = [
                    'route' => $route,
                ];
            }

            $statics->addConfig([
                'failroutes' => $failroutes,
            ]);
        }

        $statics->write();


        // Install extensions admin apps
        if (!empty($baseConfig['autoinstall']['apps'])) {
            
            foreach ($baseConfig['autoinstall']['apps'] as $appClass) {

                if (is_array($appClass)) {
                    list($appClass, $menuId) = $appClass;
                }
                                
                // Check if app is installed
                $result = $appsRepository->fetch([
                    'where' => [
                        'className' => $appClass
                    ]
                ]);
                                                                
                if ($result->getCount() == 0) {

                    $app = new $appClass([
                        'className' => $appClass,
                        'menuId' => ($menuId ?? 'Global')
                    ]);

                    $translator = new \Frootbox\Translation\Translator;

                    if (file_exists($langPath = $app->getPath() . 'resources/private/language/de-DE.php')) {

                        $translator->setScope(str_replace('\\', '.', substr(substr(get_class($app), 0, -4), 13)));
                        $translator->addResource($langPath);

                        $app->setTitle($translator->translate('App.Title') ?? 'kein Titel');
                        $app->setIcon($translator->translate('App.Icon'));
                    }
                                      
                    $appsRepository->insert($app);
                }    
            }                        
        }
    }
    
    /**
     *
     */
    public function uninstall(
        \Frootbox\Admin\Persistence\Repositories\Apps $appsRepository,
        \Frootbox\Config\Config $config
    ): Extension
    {
        // Get statics configuration utility
        $statics = new \Frootbox\ConfigStatics($config);

        $baseConfig = $this->getBaseConfig($config);

        // De-activate extension
        $this->setIsactive(0);
        $this->save();

        // Remove editables
        if (!empty($baseConfig['autoinstall']['editables'])) {

            $editables = $config->get('editables')->getData();

            foreach ($editables as $index => $editable) {

                if (in_array($editable['editable'], $baseConfig['autoinstall']['editables'])) {
                    unset($editables[$index]);
                }
            }

            $statics->unsetConfig('editables');
            $statics->addConfig([
                'editables' => $editables,
            ]);

            $statics->write();
        }

        // Remove extensions apps
        if (!empty($baseConfig['autoinstall']['apps'])) {

            foreach ($baseConfig['autoinstall']['apps'] as $appClass) {

                if (is_array($appClass)) {
                    list($appClass, $menuId) = $appClass;
                }

                $result = $appsRepository->fetch([
                    'where' => [
                        'className' => $appClass,
                    ],
                ]);
                
                $result->map('delete');
            }
        }

        // Remove custom post-routes
        if (!empty($baseConfig['autoinstall']['postroutes'])) {

            $routes = $config->get('postroutes')->getData();

            foreach ($routes as $index => $xroute) {

                if (in_array($xroute['route'], $baseConfig['autoinstall']['postroutes'])) {
                    unset($routes[$index]);
                }
            }

            $statics->unsetConfig('postroutes');
            $statics->addConfig([
                'postroutes' => $routes,
            ]);

            $statics->write();
        }

        // Remove custom gizmos
        if (!empty($baseConfig['autoinstall']['gizmos'])) {

            $gizmos = !empty($config->get('gizmos')) ? $config->get('gizmos')->getData() : [];

            foreach ($gizmos as $index => $xgizmo) {

                if (in_array($xgizmo['gizmo'], $baseConfig['autoinstall']['gizmos'])) {
                    unset($gizmos[$index]);
                }
            }

            $statics->unsetConfig('gizmos');
            $statics->addConfig([
                'gizmos' => $gizmos,
            ]);

            $statics->write();
        }

        // Remove custom routes
        if (!empty($baseConfig['autoinstall']['routes'])) {

            $routes = $config->get('routes')->getData();

            foreach ($routes as $index => $xroute) {

                if (in_array($xroute['route'], $baseConfig['autoinstall']['routes'])) {
                    unset($routes[$index]);
                }
            }

            $statics->unsetConfig('routes');
            $statics->addConfig([
                'routes' => $routes,
            ]);

            $statics->write();
        }

        // Remove injected publics
        if (!empty($baseConfig['autoinstall']['config']['injectPublics']) and !empty($config->get('injectPublics'))) {

            $publics = $config->get('injectPublics')->getData();
            $publics = array_unique($publics);

            foreach ($publics as $index => $value) {

                if (in_array($value, $baseConfig['autoinstall']['config']['injectPublics'])) {
                    unset($publics[$index]);
                }
            }

            $publics = array_values($publics);

            $statics->unsetConfig('injectPublics');
            $statics->addConfig([
                'injectPublics' => $publics,
            ]);

            $statics->write();
        }

        return $this;           
    }
}

<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2018-06-18
 */

namespace Frootbox\Admin\Controller\Extensions;

use DI\Container;
use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    use \Frootbox\Traits\GetExtensionPath;

    /**
     *
     */
    public function ajaxAppSwitch(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Admin\Persistence\Repositories\Apps $appsRepository
    ): Response
    {
        // Obtain apps class
        $appClass = 'Frootbox\\Ext\\' . $get->get('vendorId') . '\\' . $get->get('extensionId') . '\\Apps\\' . $get->get('app') . '\\App';

        // Check if app is installed
        $app = $appsRepository->fetchOne([
            'where' => [ 'className' => $appClass ]
        ]);

        if ($app) {
            $app->delete();
            $message = 'Die App wurde entfernt.';
        }
        else {
            $app = $appsRepository->insert(new $appClass);
            $message = 'Die App wurde installiert.';
        }

        return self::getResponse('json', 200, [
            'success' => $message,
            'replacements' => [
                [
                    'selector' => '#mainMenuReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\View\Partials\Menu\Partial::class, []),
                ],
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxDelete(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        // Fetch matching extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'extensionId' => $get->get('extensionId'),
                'vendorId' => $get->get('vendorId'),
            ],
        ]);

        foreach ($result as $extension) {
            $container->call([ $extension, 'uninstall' ]);

            try {
                $extension->delete();
            }
            catch ( \Exception $e ) {
                d($e);
            }
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Erweiterung wurde entfernt.',
            'replacements' => [
                [
                    'selector' => '#mainMenuReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\View\Partials\Menu\Partial::class, []),
                ],
                [
                    'selector' => '#extensionsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Extensions\Partials\ListExtensions\Partial::class, [
                        'highlight' => $extension->getId(),
                    ]),
                ],
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxInstall(
        \Frootbox\Db\Db $dbms,
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        // Obtain extension configuration
        $extConf = require_once $post->get('extension') . DIRECTORY_SEPARATOR . 'configuration.php';


        // Gather extensions
        $dependencyTree = [];

        function loopConfig($vendorId, $extensionId, $dependencyTree, $config) {

            $extKey = $vendorId . '/' . $extensionId;

            if (!in_array($extKey, $dependencyTree)) {
                array_unshift($dependencyTree, $extKey);
            }

            $paths = $config->get('extensions.paths');
            $paths = $paths->getData();
            $paths[] = CORE_DIR . 'cms/extensions/';

            foreach ($paths as $path) {

                $xpath = $path . $extKey . DIRECTORY_SEPARATOR . 'configuration.php';

                if (!file_exists($xpath)) {
                    continue;
                }

                $extConfig = require $xpath;

                break;
            }

            if (empty($extConfig['requires'])) {
                return $dependencyTree;
            }

            foreach ($extConfig['requires'] as $reqExtKey => $minVersion) {
                list($vendorId, $extensionId) = explode('/', $reqExtKey);
                $dependencyTree = loopConfig($vendorId, $extensionId, $dependencyTree, $config);
            }

            return $dependencyTree;

        }

        $dependencyTree = loopConfig($extConf['vendor']['id'], $extConf['id'], $dependencyTree, $config);

        // Loop dependencies
        $paths = $config->get('extensions.paths');
        $paths = $paths->getData();
        $paths[] = CORE_DIR . 'cms/extensions/';

        foreach ($dependencyTree as $extKey) {

            list($vendorId, $extensionId) = explode('/', $extKey);

            $extConfigPath = null;

            foreach ($paths as $path) {
                $cExtPath = $path . $vendorId . '/' . $extensionId . DIRECTORY_SEPARATOR . 'configuration.php';

                if (file_exists($cExtPath)) {
                    $extConfigPath = $cExtPath;
                    break;
                }
            }

            if ($extConfigPath === null) {
                d("Config missing");
            }

            $extConfig = require($extConfigPath);

            $extension = $extensionsRepository->fetchOne([
                'where' => [
                    'extensionId' => $extConfig['id'],
                    'vendorId' => $extConfig['vendor']['id'],
                ],
            ]);

            if (empty($extension)) {

                // Insert extension
                $extension = $extensionsRepository->insert(new \Frootbox\Persistence\Extension([
                    'extensionId' => $extConfig['id'],
                    'vendorId' => $extConfig['vendor']['id'],
                    'version' => '0.0.0',
                    'isactive' => 1
                ]));
            }
            else {

                $extension->setVersion('0.0.0');
                $extension->save();
            }

            // Write autoloader
            $extensionsRepository->writeAutoloader($config);

            require $config->get('filesRootFolder') . 'cache/system/autoload.php';

            $container->call([ $extension, 'init' ]);

            $migrations = $extension->getAvailableMigrations();

            foreach ($migrations as $migrationData) {

                $migration = new $migrationData['className'];

                $container->call([ $migration, 'pushUp' ]);
            }

            $extension->setVersion($migrationData['version'] ?? $extConf['version']);
            $extension->save();
        }


        return self::getResponse('json', 200, [
            'success' => 'Die Erweiterung wurde installiert.',
            'replacements' => [
                [
                    'selector' => '#mainMenuReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\View\Partials\Menu\Partial::class, []),
                ],
                [
                    'selector' => '#extensionsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Extensions\Partials\ListExtensions\Partial::class, [
                        'highlight' => $extension->getId(),
                    ]),
                ],
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxMigrateUp(
        \DI\Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch matching extensions
        $extension = $extensions->fetchOne([
            'where' => [
                'extensionId' => $get->get('extensionId'),
                'vendorId' => $get->get('vendorId'),
            ]
        ]);

        $class = '\\Frootbox\\Ext\\' . $get->get('vendorId') . '\\' . $get->get('extensionId') . '\\Migrations\\' . $get->get('migration');
        $migration = new $class;

        $container->call([ $migration, 'pushUp' ]);


        $extension->setVersion($migration->getVersion());
        $extension->save();

        return self::getResponse('json', 200, [
            'success' => 'Migration wurde erfolgreich ausgeführt.',
            'fadeOut' => '[data-migration="' . $get->get('migration') . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxModalCompose(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Gather extension paths
        $extensionPaths = [
            'custom' => [ ],
            'native' => [
                CORE_DIR . 'cms/extensions/',
            ],
        ];

        // Inject custom paths
        if (!empty($customPaths = $config->get('extensions.paths'))) {
            $extensionPaths['custom'] = array_merge($extensionPaths['custom'], $customPaths->getData());
        }

        // Gather available extensions
        $list = [ ];

        foreach ($extensionPaths as $section => $paths) {

            foreach ($paths as $path) {

                if (!file_exists($path)) {
                    continue;
                }

                $dir = new \Frootbox\Filesystem\Directory($path);

                foreach ($dir as $vendorId) {

                    $vendorDir = new \Frootbox\Filesystem\Directory($dir->getPath() . $vendorId . '/');

                    foreach ($vendorDir as $extensionId) {

                        if (!file_exists($vendorDir->getPath() . '/' . $extensionId . '/configuration.php')) {
                            continue;
                        }

                        // Check if extension is already installed
                        $extCheck = $extensionsRepository->fetchOne([
                            'where' => [
                                'extensionId' => $extensionId,
                                'vendorId' => $vendorId,
                            ],
                        ]);

                        if (!empty($extCheck)) {
                            continue;
                        }

                        $config = require($vendorDir->getPath() . '/' . $extensionId . '/configuration.php');

                        $ext = [
                            'vendorId' => $vendorId,
                            'extensionId' => $extensionId,
                            'config' => $config,
                            'path' => $vendorDir->getPath() . $extensionId,
                        ];

                        $list[$section][$vendorId . '_' . $extensionId] = $ext;
                    }
                }
            }
        }

        if (!empty($list['custom'])) {
            ksort($list['custom']);
        }

        if (!empty($list['native'])) {
            ksort($list['native']);
        }

        return self::getResponse('html', 200, [
            'extensions' => $list,
        ]);
    }

    /**
     *
     */
    public function ajaxReload(
        \DI\Container $container,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Get statics configuration utility
        $statics = new \Frootbox\ConfigStatics($config);
        $statics->unsetConfig('editables');

        // Fetch extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $loop = 0;
        $editables = [];

        foreach ($result as $extension) {

           $baseConfig = $extension->getBaseConfig($config);

           if (empty($baseConfig['autoinstall']['editables'])) {
               continue;
           }

           foreach ($baseConfig['autoinstall']['editables'] as $index => $editable) {

               if ($index < 1000) {
                   $index += 1000;
               }

               $index *= 100;

               $editables[($index + ++$loop)] = [ 'editable' => $editable ];
           }

           ksort($editables);
        }

        $statics->addConfig([
            'editables' => $editables,
        ]);

        $statics->write();

        return self::getResponse('json', 200, [
            'success' => 'Konfiguration wurde neu geladen.',
        ]);
    }

    /**
     *
     */
    public function ajaxSwitchState (
        \DI\Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensions,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch matching extensions
        $result = $extensions->fetch([
            'where' => [
                'extensionId' => $get->get('extensionId'),
                'vendorId' => $get->get('vendorId'),
            ],
        ]);


        // None found: Insert extension!
        if ($result->getCount() == 0) {

            $extPath = $this->getExtensionPath($config, $get->get('vendorId'), $get->get('extensionId'));
            $extConf = require_once $extPath . 'configuration.php';

            $extension = $extensions->insert(new \Frootbox\Persistence\Extension([
                'extensionId' => $extConf['id'],
                'vendorId' => $extConf['vendor']['id'],
                'version' => $extConf['version'],
                'isactive' => 0
            ]));
        }
        else {
            $extension = $result->current();
        }


        // Switch state!
        if (!$extension->getIsactive()) {

            // Check requirements
            $path = $this->getExtensionPath($config, $extension->getVendorId(), $extension->getExtensionId()) . 'configuration.php';
            $extConfig = require $path;

            if (!empty($extConfig['requires'])) {

                foreach ($extConfig['requires'] as $extensionCode => $version) {

                    list($vendorId, $extensionId) = explode('/', $extensionCode);


                    // Check if needed extension is installed
                    $result = $extensions->fetch([
                        'where' => [
                            'extensionId' => $extensionId,
                            'vendorId' => $vendorId,
                            'isactive' => 1
                        ],
                        'limit' => 1
                    ]);

                    if ($result->getCount() == 0) {
                        throw new \Frootbox\Exceptions\NotFound('Extension ' . $vendorId . '/' . $extensionId . ' is required for installation.');
                    }


                    // Check if needed extension meets version requirements
                    $installedExtension = $result->current();

                    if (version_compare($installedExtension->getVersion(), $version, 'lt')) {
                        throw new \Frootbox\Exceptions\NotFound('Extension requires ' . $vendorId . '/' . $extensionId . ' with minimum version ' . $version);
                    }
                }
            }

            $extension->install();
        }
        else {

            // Check others requirements
            // d("check others requirements");

            $container->call([ $extension, 'uninstall' ]);
        }
        
        // $config->clearCaches();

        
        // Write autoloader
        $extensions->writeAutoloader($config);

        require $config->get('filesRootFolder') . 'cache/system/autoload.php';

        if ($extension->getIsactive()) {
            $container->call([ $extension, 'init' ]);
        }

        return self::getResponse('json', 200, [
            'replacements' => [
                [
                    'selector' => '#mainMenuReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\View\Partials\Menu\Partial::class, [])
                ],
                [
                    'selector' => '#extensionsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Extensions\Partials\ListExtensions\Partial::class, [])
                ],
            ],
            'modalDismiss' => true
        ]);      
    }

    /**
     *
     */
    public function details(
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        // Obtain extension controller
        $class = '\\Frootbox\\Ext\\' . $get->get('vendorId') . '\\' . $get->get('extensionId') . '\\ExtensionController';
        $extensionController = new $class;

        // Fetch active extension
        $extension = $extensionsRepository->fetchOne([
            'where' => [
                'vendorId' => $get->get('vendorId'),
                'extensionId' => $get->get('extensionId'),
            ]
        ]);

        if ($extension) {

            $migrationsPath = $extensionController->getPath() . 'classes/Migrations/';


            $versions = [];

            if (file_exists($migrationsPath)) {

                $directory = new \Frootbox\Filesystem\Directory($migrationsPath);

                foreach ($directory as $file) {

                    if (!preg_match('#^Version([0-9]{2})([0-9]{2})([0-9]{2})\.php$#', $file, $match)) {
                        continue;
                    }

                    $version = (int) $match[1] . '.' . (int) $match[2] . '.' . (int) $match[3];


                    $className = '\\Frootbox\\Ext\\' . $extension->getVendorId() . '\\' . $extension->getExtensionId() . '\\Migrations\\' . substr($file, 0, -4);
                    $migration = new $className;

                    if (version_compare($extension->getVersion(), $version) < 0) {

                        $versions[$match[1] . $match[2] . $match[3]] = [
                            'version' => $version,
                            'description' => $migration->getDescription(),
                            'migration' => substr($file, 0, -4)
                        ];

                    }
                }

                ksort($versions);
            }
        }

        // Fetch extensions apps
        $dir = new \Frootbox\Filesystem\Directory($extensionController->getPath() . 'classes/Apps/');
        $apps = [];

        foreach ($dir as $file) {

            $title = $file;
            $icon = 'fa-puzzle-peace';

            // Load language files
            $languageFile = $dir->getPath() . $file . '/resources/private/language/de-DE.php';

            if (file_exists($languageFile)) {

                $data = require $languageFile;

                $title = $data['App.Title'] ?? $title;
                $icon = $data['App.Icon'] ?? $icon;
            }

            $apps[] = [
                'title' => $title,
                'icon' => $icon,
                'id' => $file,
            ];
        }

        return self::getResponse('html', 200, [
            'extension' => $extension,
            'extensionController' => $extensionController,
            'versions' => $versions,
            'apps' => $apps,
        ]);
    }

    /**
     *
     */
    public function index(): Response
    {
        return self::getResponse();
    }
}

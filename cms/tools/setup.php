<?php
/**
 * 
 */

declare(strict_types = 1);

namespace Frootbox;

echo '<html>
    <head>
        <title>CMS Initialisierung</title>
        
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    
    </head>
    <body>
        <div class="container">';

try {

    $steps = [];

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
    date_default_timezone_set('Europe/Berlin');

    ob_start();

    $steps['initAutoloading'] = 'started';

    // Global autoloader
    require '../../lib/autoload.php';

    $steps['initAutoloading'] = 'completed';


    /**
     * Local configuration
     */
    $steps['initConfig'] = 'started';

    $path = realpath(dirname(__FILE__) . '/../../') . '/localconfig.php';
    $config = require $path;

    define('GLOBAL_LANGUAGE', $config['i18n']['defaults'][0] ?? $config['i18n']['languages'][0] ?? 'de-DE');
    define('DEFAULT_LANGUAGE', $config['i18n']['defaults'][0] ?? $config['i18n']['languages'][0] ?? 'de-DE');
    define('MULTI_LANGUAGE', !empty($config['i18n']['multiAliasMode']));

    $configAccess = new \Frootbox\Config\Config(null);

    $configAccess->append([
        'database' => $config['database']
    ]);

    $steps['initConfig'] = 'completed';


    /**
     * Database connection
     */
    $steps['initDatabaseConnection'] = 'started';


    try {

        $dbms = new \Frootbox\Db\Dbms\Mysql($configAccess);
        $db = new \Frootbox\Db\Db($dbms);
    }
    catch (\PDOException $exception) {

        if (preg_match('#Unknown database \'(.*?)\'#', $exception->getMessage())) {
            die('Create database "' . $configAccess->get('database')['schema'] . '" first.');
        }
    }


    $steps['initDatabaseConnection'] = 'completed';

    /**
     * Database tables
     */
    $steps['initDatabaseTables'] = 'started';

    $queries = [ ];
    $queries[] = <<<EOT
    CREATE TABLE `admin_apps` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `className` varchar(255) NOT NULL,
      `title` varchar(255) NOT NULL,
      `icon` varchar(24) DEFAULT NULL,
      `menuId` varchar(255) DEFAULT 'Global',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `admin_changelog` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `date` datetime NOT NULL,
        `updated` datetime NOT NULL,
        `userId` int(11) NOT NULL,
        `pageId` int(11) DEFAULT NULL,
        `pluginId` int(11) DEFAULT NULL,
        `itemId` int(11) DEFAULT NULL,
        `itemClass` varchar(255) DEFAULT NULL,
        `action` varchar(255) DEFAULT NULL,
        `logData` text,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    EOT;

    $queries[] = <<<EOT
     CREATE TABLE `aliases` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `pageId` int(11) DEFAULT NULL,
      `itemId` int(11) DEFAULT NULL,
      `itemModel` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
      `alias` varchar(255) CHARACTER SET latin1 NOT NULL,
      `status` int(11) NOT NULL DEFAULT '200',
      `payload` text CHARACTER SET latin1,
      `type` varchar(45) CHARACTER SET latin1 DEFAULT 'Generic',
      `config` text CHARACTER SET latin1,
      `visibility` int(11) DEFAULT NULL,
      `language` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT 'de-DE',
      `section` varchar(255) NOT NULL DEFAULT 'index',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `assets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `pageId` int(11) DEFAULT NULL,
      `pluginId` int(11) DEFAULT '0',
      `parentId` int(11) DEFAULT '0',
      `orderId` int(11) DEFAULT '0',
      `className` varchar(255) NOT NULL,
      `customClass` varchar(255) DEFAULT NULL,
      `uid` varchar(255) DEFAULT NULL,
      `userId` int(11) DEFAULT NULL,
      `title` varchar(255) DEFAULT NULL,
      `alias` varchar(255) DEFAULT NULL,
      `aliases` text,
      `locationId` int(11) DEFAULT NULL,
      `dateStart` datetime DEFAULT NULL,
      `dateEnd` datetime DEFAULT NULL,
      `config` text,
      `state` varchar(45) DEFAULT NULL,
      `visibility` int(11) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `blocks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `uid` varchar(255) CHARACTER SET utf8 NOT NULL,
      `blockId` varchar(45) CHARACTER SET utf8 NOT NULL,
      `orderId` int(11) DEFAULT NULL,
      `config` text CHARACTER SET utf8,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `pageId` int(11) DEFAULT NULL,
      `pluginId` int(11) DEFAULT NULL,
      `rootId` int(11) DEFAULT '0',
      `parentId` int(11) DEFAULT '0',
      `lft` int(11) NOT NULL,
      `rgt` int(11) NOT NULL,
      `model` varchar(255) DEFAULT NULL,
      `className` varchar(255) NOT NULL,
      `title` varchar(255) NOT NULL,
      `config` varchar(45) DEFAULT NULL,
      `uid` varchar(255) DEFAULT NULL,
      `alias` varchar(255) DEFAULT NULL,
      `visibility` int(11) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `categories_2_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `categoryId` int(11) NOT NULL,
      `categoryClass` varchar(255) CHARACTER SET utf8 NOT NULL,
      `itemId` int(11) NOT NULL,
      `itemClass` varchar(255) CHARACTER SET utf8 NOT NULL,
      `config` text CHARACTER SET utf8,
      `pageId` int(11) DEFAULT NULL,
      `pluginId` int(11) DEFAULT NULL,
      `alias` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `content_elements` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `parentId` int(11) DEFAULT '0',
      `pageId` int(11) NOT NULL,
      `socket` varchar(255) NOT NULL,
      `orderId` int(11) NOT NULL DEFAULT '0',
      `className` varchar(255) NOT NULL,
      `title` varchar(255) DEFAULT NULL,
      `config` text,
      `type` varchar(255) NOT NULL,
      `inheritance` varchar(45) DEFAULT 'None',
      `visibility` varchar(16) NOT NULL DEFAULT 'Public',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `content_texts` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `userId` int(11) NOT NULL,
      `uid` varchar(255) CHARACTER SET utf8 NOT NULL,
      `text` text,
      `config` text CHARACTER SET latin1,
      `language` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT 'de-DE',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `content_widgets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `userId` int(11) NOT NULL,
      `className` varchar(255) CHARACTER SET latin1 NOT NULL,
      `config` text CHARACTER SET latin1,
      `textUid` varchar(255) CHARACTER SET latin1 NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `extensions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `vendorId` varchar(255) CHARACTER SET latin1 NOT NULL,
      `extensionId` varchar(255) CHARACTER SET latin1 NOT NULL,
      `isactive` int(11) NOT NULL DEFAULT '0',
      `version` varchar(45) CHARACTER SET latin1 NOT NULL,
      `config` text CHARACTER SET latin1,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `files` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `folderId` int(11) NOT NULL,
      `masterId` int(11) DEFAULT NULL,
      `orderId` int(11) DEFAULT '0',
      `title` varchar(255) DEFAULT NULL,
      `name` varchar(255) NOT NULL,
      `description` text,
      `type` varchar(45) NOT NULL,
      `size` int(11) NOT NULL DEFAULT '0',
      `path` varchar(255) DEFAULT NULL,
      `uid` varchar(255) DEFAULT NULL,
      `copyright` varchar(255) DEFAULT NULL,
      `config` text,
      `language` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT 'de-DE',
      `hash` varchar(32) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `logs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `pluginId` int(11) DEFAULT NULL,
      `className` varchar(255) CHARACTER SET utf8 NOT NULL,
      `logdata` text CHARACTER SET utf8,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `pages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `rootId` int(11) DEFAULT NULL,
      `parentId` int(11) DEFAULT '0',
      `lft` int(11) DEFAULT NULL,
      `rgt` int(11) DEFAULT NULL,
      `language` varchar(8) CHARACTER SET latin1 DEFAULT 'de-DE',
      `title` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
      `alias` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
      `aliases` text,
      `config` text CHARACTER SET latin1,
      `visibility` varchar(16) NOT NULL DEFAULT 'Public',
      `type` varchar(45) NOT NULL DEFAULT 'Default',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `tags` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `itemId` int(11) NOT NULL,
      `itemClass` varchar(255) CHARACTER SET utf8 NOT NULL,
      `config` text CHARACTER SET utf8,
      `pageId` int(11) DEFAULT NULL,
      `pluginId` int(11) DEFAULT NULL,
      `tag` varchar(255) DEFAULT NULL,
      `category` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `email` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
      `password` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
      `lastClick` datetime DEFAULT NULL,
      `type` varchar(45) DEFAULT 'Admin',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `navigations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `navId` varchar(255) NOT NULL,
      `title` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    EOT;

    $queries[] = <<<EOT
    CREATE TABLE `navigations_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `navId` int(11) NOT NULL,
      `orderId` int(11) DEFAULT NULL,
      `title` varchar(255) DEFAULT NULL,
      `className` varchar(255) NOT NULL,
      `config` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    EOT;



    foreach ($queries as $query) {

        try {
            $db->query($query);
        }
        catch ( \PDOException $e ) {
            continue;
        }
    }

    $steps['initDatabaseTables'] = 'completed';


    /**
     *
     */
    $steps['initFilesDirectory'] = 'started';

    if (!file_exists($config['filesRootFolder'])) {

        $directory = new \Frootbox\Filesystem\Directory($config['filesRootFolder']);
        $directory->make();
    }

    $steps['initFilesDirectory'] = 'completed';



    /**
     * Install base extensions
     */
    require '../classes/Front.php';

    $container = Front::init();

    $config = $container->get(\Frootbox\Config\Config::class);

    // Admin autoloader
    spl_autoload_register(function ( $class ) {

        $adminClass = substr($class, 15);

        $file = CORE_DIR . 'cms/admin/classes/' . str_replace('\\', '/', $adminClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });

    $extensionsRepository = $container->get(\Frootbox\Persistence\Repositories\Extensions::class);


    $baseExtensions = [
        [ 'Core', 'System' ],
        [ 'Core', 'Editing' ],
        [ 'Core', 'FileManager' ],
        [ 'Core', 'Images' ],
    ];

    $db->transactionStart();


    foreach ($baseExtensions as $extension) {

        $check = $extensionsRepository->fetchOne([
            'where' => [
                'vendorId' => $extension[0],
                'extensionId' => $extension[1],
            ],
        ]);

        if (!empty($check)) {
            continue;
        }

        // Insert extension
        $extension = $extensionsRepository->insert(new \Frootbox\Persistence\Extension([
            'extensionId' => $extension[1],
            'vendorId' => $extension[0],
            'version' => '0.0.0',
            'isactive' => 1,
        ]));

        // Write autoloader
        $extensionsRepository->writeAutoloader($config);

        require $config->get('filesRootFolder') . 'cache/system/autoload.php';

        $container->call([ $extension, 'init' ]);

        $migrations = $extension->getAvailableMigrations();

        if (count($migrations) > 0) {

            foreach ($migrations as $migrationData) {

                $migration = new $migrationData['className'];

                $container->call([ $migration, 'pushUp' ]);
            }

            $extension->setVersion($migrationData['version']);
            $extension->save();
        }
    }


}
catch ( \Exception $e ) {

    d($e);
}



echo '
            <div class="row justify-content-center">
                <div class="col-8">    
                    <br /><br /><h1>Setup</h1><br />
            
                    <table class="table">
                        <tbody>';

                            foreach ($steps as $step => $state) {
                                echo '<tr>
                                    <td>' . $step . '</td>
                                    <td>' . $state . '</td>
                                </tr>';
                            }

                        echo '</tbody>
                    </table>';

                    if ($state == 'started') {
                        echo '<br /><br /><p>letzter Fehler:</p><p>' . $e->getMessage() . '</p>';
                    }

                    echo '<br /><p><a class="btn btn-primary" href="../">zum CMS</a></p>';
                    
                echo '</div>
            </div>
    
        </div>
    </body>    
</html>';
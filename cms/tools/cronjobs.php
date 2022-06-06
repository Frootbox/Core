<?php
/**
 *
 */

declare(strict_types = 1);

namespace Frootbox;

try {

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE);

    setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
    date_default_timezone_set('Europe/Berlin');

    // mb_internal_encoding('UTF-8');
    // mb_http_output('UTF-8');

    ob_start();

    define('REQUEST', null);

    // Global autoloader
    require __DIR__ . '/../../lib/autoload.php';

    // Admin autoloader
    spl_autoload_register(function ($class) {

        $adminClass = substr($class, 15);

        $file = CORE_DIR . 'cms/admin/classes/' . str_replace('\\', '/', $adminClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });

    // Init front
    require __DIR__ . '/../classes/Front.php';
    $container = Front::init();

    $config = $container->get(\Frootbox\Config\Config::class);
    $db = $container->get(\Frootbox\Db\Db::class);

    $autoloadConfig = $config->get('filesRootFolder') . 'cache/system/autoload.php';
    require $autoloadConfig;

    define('GLOBAL_LANGUAGE', 'de-DE');
    define('DEFAULT_LANGUAGE', 'de-DE');
    define('MULTI_LANGUAGE', !empty($config->get('i18n.multiAliasMode')));

    // Preapre cron executeion
    $crons = [];
    if (!empty($_GET['cron'])) {

        $session = $container->get(\Frootbox\Session::class);

        $class = $_GET['cron'];
        $crons[] = $container->get($class);
        $logCodeSuccess = 'ExecutedCronManually';
    }
    else {

        // Prime log
        $usersRepository = $container->get(\Frootbox\Persistence\Repositories\Users::class);

        $cronRunner = $usersRepository->fetchOne([
            'where' => [
                'type' => 'System',
                'firstName' => 'Cron-Runner',
            ],
        ]);

        if (empty($cronRunner)) {

            // Create cron runner
            $cronRunner = $usersRepository->insert(new \Frootbox\Persistence\User([
                'type' => 'System',
                'firstName' => 'Cron-Runner',
            ]));
        }

        define('USER_ID', $cronRunner->getId());

        d("GET CRONS FROM CRONTAB");
        $logCodeSuccess = 'ExecutedCron';
        $cronExecutionUserId = $cronRunner->getId();
    }

    $systemLogsRepository = $container->get(\Frootbox\Persistence\Repositories\SystemLogs::class);

    try {

        foreach ($crons as $cron) {

            $container->call([ $cron, 'execute' ]);

            $systemLogsRepository->insert(new \Frootbox\Persistence\SystemLog([
                'userId' => USER_ID,
                'log_code' => $logCodeSuccess,
                'config' => [
                    get_class($cron),
                ],
            ]));
        }
    }
    catch ( \Exception $exception ) {

        $systemLogsRepository->insert(new \Frootbox\Persistence\SystemLog([
            'userId' => USER_ID,
            'log_code' => 'ExecutedCronWithError',
            'config' => [
                get_class($cron),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ],
        ]));
    }
}
catch ( \Exception $e ) {

    $systemLogsRepository->insert(new \Frootbox\Persistence\SystemLog([
        'userId' => $cronRunner->getId(),
        'log_code' => 'CronGeneralException',
        'config' => [
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        ],
    ]));
}

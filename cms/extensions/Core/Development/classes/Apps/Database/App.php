<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Database;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];

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
    public function ajaxImportAction(
        \Frootbox\Config\Config $config
    ): Response
    {
        $path = $config->get('database.bin.pathToMysql') ?? '/usr/bin/';

        $options = [
            '-u ' . $config->get('database.user'),
            '-p' . $config->get('database.password')
        ];

        $command = $path . 'mysql ' . implode(' ', $options) . ' ' . $config->get('database.schema') . ' < ' . $_FILES['file']['tmp_name'];

        $out = (array) null;
        $return = null;

        exec($command, $out, $return);

        if ($return != 0) {

            d($return);
            throw new \Exception('Import failed.');
        }

        die("OK");
    }

    /**
     *
     */
    public function backupsAction(
        \Frootbox\Config\Config $config
    ): Response
    {
        $path = $config->get('filesRootFolder') . 'backup/database/';

        $dir = new \Frootbox\Filesystem\Directory($path);
        $list = [];
        $loop = 0;

        foreach ($dir->loadFiles() as $file) {

            $timestamp = filemtime($dir->getPath() . $file);

            $list[($timestamp * 1000) + ++$loop] = [
                'date' => $timestamp,
                'size' => filesize($dir->getPath() . $file->getName()),
                'filename' => $file->getName(),
            ];
        }

        krsort($list);


        return self::getResponse('html', 200, [
            'backups' => $list,
        ]);
    }

    /**
     *
     */
    public function exportAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get
    ): Response
    {
        $path = new \Frootbox\Filesystem\Directory($config->get('filesRootFolder') . 'tmp/');
        $path->make();

        $sqlFile = tempnam($path->getPath(), 'sql-export-');

        if ($sqlFile === false) {
            throw new \RuntimeException('Could not create temporary export file.');
        }

        // Remove the potentially large temporary dump even if generation or
        // transmission is interrupted.
        register_shutdown_function(static function () use ($sqlFile): void {
            if (is_file($sqlFile)) {
                unlink($sqlFile);
            }
        });

        $settings = [
            'add-drop-table' => true
        ];

        if (!empty($get->get('structureOnly'))) {
            $settings['no-data'] = true;
        }

        $dump = new \Ifsnop\Mysqldump\Mysqldump(
            'mysql:host=' . $config->get('database.host') . ';dbname=' . $config->get('database.schema'),
            $config->get('database.user'),
            $config->get('database.password'),
            $settings
        );

        $dump->start($sqlFile);

        // The application/framework may have active output buffers. Leaving
        // them enabled would make readfile() retain the complete dump in PHP
        // memory before it is sent to the client.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(200);

        header('Content-type: application/sql');
        header('Content-Disposition: attachment; filename="export-' . $config->get('database.schema') . '-' . date('Y-m-d-H-i-s') . '.sql"');
        header('Content-Length: ' . filesize($sqlFile));
        header('X-Content-Type-Options: nosniff');

        if (readfile($sqlFile) === false) {
            throw new \RuntimeException('Could not stream database export.');
        }

        exit;
    }
    
    /**
     *
     */
    public function importAction(): Response
    {
        return self::getResponse();
    }

    /** 
     * 
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function integrityAction(
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Fetch root page
        $root = $pages->fetchOne([
            'where' => [
                'parentId' => 0
            ]
        ]);

        $pages->rewriteIds($root->getRootId());

        return self::getResponse();
    }

    /**
     *
     */
    public function serveBackupAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get
    ): Response
    {
        $path = $config->get('filesRootFolder') . 'backup/database/' . $get->get('file');

        if (!file_exists($path)) {
            d("FILE NOT FOUND");
        }

        // End output buffering
        while (ob_get_level()) {
           ob_end_clean();
        }

        http_response_code(200);
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $get->get('file'));

        readfile($path);

        exit;
    }

    /**
     *
     */
    public function showTableInfoAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Db\Export\Factory $factory
    ): Response
    {
        $php = $factory->export('php');

        return self::getResponse('attachment', 200, $php, [
            'Content-disposition: attachment; filename=database.php',
            'Content-type: application/php'
        ]);
    }
}

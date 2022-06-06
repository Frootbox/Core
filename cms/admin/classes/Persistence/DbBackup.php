<?php
/**
 *
 */

namespace Frootbox\Admin\Persistence;

class DbBackup
{
    protected $dbconfig;

    /**
     *
     */
    public function __construct(
        \Frootbox\Config\Config $config
    )
    {
        $this->dbconfig = $config->get('database')->getData();
    }

    /**
     *
     */
    public function cleanSnapshots(array $options = null): void
    {
        $path = FILES_DIR . 'backup/database/';

        if (!file_exists($path)) {
            return;
        }

        $maxage = $options['maxage'] ?? (3600 * 24 * 30);

        $path = new \Frootbox\Filesystem\Directory($path);

        foreach ($path as $file) {

            $t = explode('-', $file);
            $time = mktime($t[3], $t[4], 0, $t[1], $t[2], $t[0]);
            $age = $_SERVER['REQUEST_TIME'] - $time;

            if ($age > $maxage) {
                unlink($path->getPath() . $file);
            }
        }
    }

    /**
     *
     */
    public function snapshot(

    )
    {
        $path = new \Frootbox\Filesystem\Directory(FILES_DIR . 'backup/database/');
        $path->make();

        $sqlFile = $path->getPath() . date('Y-m-d-H-i') . '-sql-export.sql';

        $settings = [
            'add-drop-table' => true
        ];

        $dump = new \Ifsnop\Mysqldump\Mysqldump(
            'mysql:host=' . $this->dbconfig['host'] . ';dbname=' . $this->dbconfig['schema'],
            $this->dbconfig['user'],
            $this->dbconfig['password'],
            $settings
        );

        $dump->start($sqlFile);
    }
}
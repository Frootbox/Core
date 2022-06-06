<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010011 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit von Kategorien.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms,
        \Frootbox\Persistence\Repositories\Categories $categoriesRepository
    ): void
    {
        $queries = [];

        $queries[] = "ALTER TABLE `aliases` ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `visibility`;";

        foreach ($queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }


        // Fetch categories
        $result = $categoriesRepository->fetch();

        try {
            foreach ($result as $category) {

                try {
                    $category->setVisibility($category->getVisibility() >= 1 ? 2 : 0);
                    $category->save();
                }
                catch ( \Frootbox\Exceptions\NotFound $e ) {
                    continue;
                }
            }
        }
        catch ( \Exception $e ) {
            d($e);
        }

    }
}

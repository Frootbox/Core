<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert die Sichbarkeitseinstellungen der Content-Elemente (1/2).';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): void
    {
        $result = $contentElementsRepository->fetch();

        foreach ($result as $element) {

            $visbility = $element->getVisibility();

            if ($visbility == 'Public') {
                $element->setVisibility(2);
                $element->save();
            }
            else if ($visbility == 'Hidden') {
                $element->setVisibility(0);
                $element->save();
            }
            else if ($visbility == 2 or $visbility == 1 or $visbility == 0) {
                continue;
            }
            else {
                d($element);
            }
        }
    }
}

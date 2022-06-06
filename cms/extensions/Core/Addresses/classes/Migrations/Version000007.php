<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert die URL aller Adressen.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): void
    {
        // Fetch addresses
        $addresses = $addressesRepository->fetch();

        foreach ($addresses as $address) {
            $address->save();
        }
    }
}

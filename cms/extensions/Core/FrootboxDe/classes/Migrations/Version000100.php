<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Migrations;

class Version000100 extends \Frootbox\AbstractMigration
{
    protected $description = 'Zeigt die App „frootbox.de“ nur SuperAdmins.';

    public function up(): void
    {
        $this->addSql("UPDATE `admin_apps` SET `access` = 'SuperAdmin' WHERE `className` = 'Frootbox\\\\Ext\\\\Core\\\\FrootboxDe\\\\Apps\\\\Connection\\\\App';");
    }
}

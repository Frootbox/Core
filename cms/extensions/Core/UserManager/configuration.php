<?php
/**
 *
 */

return [
    'id' => 'UserManager',
    'name' => 'Frootbox Benutzerverwaltung',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.2',
    'requires' => [

    ],
    'autoinstall' => [
        'apps' => [
            \Frootbox\Ext\Core\UserManager\Apps\UserManager\App::class
        ]
    ]
];
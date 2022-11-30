<?php
/**
 *
 */

return [
    'id' => 'Development',
    'name' => 'Frootbox Development Kit',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.1',
    'requires' => [

    ],
    'autoinstall' => [
        'apps' => [
            \Frootbox\Ext\Core\Development\Apps\DeveloperTools\App::class,
            [ \Frootbox\Ext\Core\Development\Apps\Database\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\Cache\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\ClassCreator\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\Import\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\Migration\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\PhpInfo\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\AliasManager\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\PageManager\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\Api\App::class, 'Frootbox/Ext/Core/Development' ],
            [ \Frootbox\Ext\Core\Development\Apps\Plugins\App::class, 'Frootbox/Ext/Core/Development' ],
        ],
    ],
];

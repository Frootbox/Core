<?php
/**
 *
 */

return [
    'id' => 'Events',
    'name' => 'Frootbox Events',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.1',
    'requires' => [
        'Core/Addresses' => '0.0.1'
    ],
    'autoinstall' => [
        'apps' => [
            // \Frootbox\Ext\Core\FileManager\Apps\FileManager\App::class
        ],
    ],
];
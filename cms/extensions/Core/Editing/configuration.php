<?php
/**
 *
 */


return [
    'id' => 'Editing',
    'name' => 'Addresses',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.0',
    'requires' => [

    ],
    'autoinstall' => [
        'routes' => [
            \Frootbox\Ext\Core\Editing\Routing\EditorRoute::class
        ],
    ],
];
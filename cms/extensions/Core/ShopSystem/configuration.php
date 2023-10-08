<?php
/**
 *
 */


return [
    'id' => 'ShopSystem',
    'name' => 'Addresses',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.1.0',
    'requires' => [

    ],
    'autoinstall' => [
        'apps' => [

        ],
        'config' => [
            'injectPublics' => [
                'EXT:Core/ShopSystem/javascript/init-shop.js',
                'EXT:Core/ShopSystem/css/standards.less',
            ],
        ],
    ],
];

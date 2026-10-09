<?php
/**
 *
 */

return [
    'Ext' => [
        'Core' => [
            'System' => [
                'Editables' => [
                    'Block' => [
                        // Restrict selection to block types already stored in the database.
                        // Active template blocks remain available; SuperAdmins are exempt.
                        'OnlyUsedBlocks' => false,
                        // Additional allowed extensions (Vendor/Extension).
                        'AllowedCategories' => [],
                        // Additional allowed block types (Vendor/Extension/Block).
                        'AllowedBlocks' => [],
                    ],
                ],
            ],
        ],
    ],
    'database' => [
        'dbms' => 'mysql',
        'host' => 'localhost',
        'user' => 'xxxxx',
        'password' => 'xxxxx',
        'schema' => 'xxxxx'
    ],
    'session' => [
        'name' => 'xxxxx'
    ],

    'filesRootFolder' => __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR,
    'publicCacheDir' => '/files/',

    'includes' => [
        // dirname(__FILE__) . '/vendor/localconfig.php'
    ],
    'extensions' => [
        'paths' => [
            __DIR__ . '/vendor/'
        ]
    ],
    'thumbnails' => [
        'imagemagick' => [
            'path' => '/usr/bin/convert'
        ]
    ],
    'system' => [
        'quota' => 1
    ],
    'mail' => [
        'smtp' => [
            'host' => 'newyork.hosting-server.cc',
            'username' => 'homepage-relay@frootbox.de',
            'password' => 'osekTebWeimEk5',
        ],
        'defaults' => [
            'from' => [
                'address' => 'info@frootbox.de',
                'name' => 'frootbox::cms',
            ],
        ],
    ],
    '_recaptcha' => [
        'v3' => [
            'key' => 'xxx',
            'secret' => 'xxx',
        ],
    ],
];

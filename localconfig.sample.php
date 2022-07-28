<?php
/**
 *
 */

return [
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
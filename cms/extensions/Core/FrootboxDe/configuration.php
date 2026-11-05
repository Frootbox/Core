<?php

return [
    'id' => 'FrootboxDe',
    'name' => 'frootbox.de',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox',
    ],
    'version' => '0.1.0',
    'requires' => [],
    'autoinstall' => [
        'apps' => [
            [\Frootbox\Ext\Core\FrootboxDe\Apps\Connection\App::class, 'Global'],
        ],
        'routes' => [
            \Frootbox\Ext\Core\FrootboxDe\Routing\CallbackRoute::class,
        ],
    ],
];

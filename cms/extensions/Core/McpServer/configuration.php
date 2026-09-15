<?php

return [
    'id' => 'McpServer',
    'name' => 'MCP Server',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox',
    ],
    'version' => '0.0.1',
    'requires' => [],
    'autoinstall' => [
        'apps' => [
            [\Frootbox\Ext\Core\McpServer\Apps\McpServer\App::class, 'Frootbox/Ext/Core/McpServer'],
        ],
        'routes' => [
            \Frootbox\Ext\Core\McpServer\Routing\McpRoute::class,
        ],
    ],
];

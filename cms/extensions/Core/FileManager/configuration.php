<?php
/**
 *
 */

return [
    'id' => 'FileManager',
    'name' => 'Frootbox FileManager',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.5',
    'requires' => [
    ],
    'autoinstall' => [
        'editables' => [
            6000 => \Frootbox\Ext\Core\FileManager\Editables\FileManager::class,
        ],
        'apps' => [
            \Frootbox\Ext\Core\FileManager\Apps\FileManager\App::class,
        ],
        'gizmos' => [
            \Frootbox\Ext\Core\FileManager\Admin\Gizmos\Quota\Gizmo::class,
        ],
    ],
];
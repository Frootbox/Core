<?php
/**
 *
 */

return [
    'id' => 'Forms',
    'name' => 'Frootbox Forms',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.1',
    'requires' => [

    ],
    'autoinstall' => [
        'gizmos' => [
            \Frootbox\Ext\Core\Forms\Admin\Gizmos\FormChecker\Gizmo::class,
        ],
        'apps' => [
            \Frootbox\Ext\Core\Forms\Apps\FormsManager\App::class,
        ],
    ],
];
<?php
/**
 *
 */

return [
    'id' => 'ContactForms',
    'name' => 'Frootbox Contact Forms',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.2',
    'requires' => [

    ],
    'autoinstall' => [
        'gizmos' => [
            \Frootbox\Ext\Core\ContactForms\Admin\Gizmos\FormChecker\Gizmo::class,
        ],
        'apps' => [
            \Frootbox\Ext\Core\ContactForms\Apps\FormsManager\App::class,
        ],
    ],
];

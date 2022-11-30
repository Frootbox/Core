<?php
/**
 *
 */


return [
    'id' => 'System',
    'name' => 'System',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox',
    ],
    'version' => '1.2.6',
    'requires' => [

    ],
    'autoinstall' => [
        'editables' => [
            5000 => \Frootbox\Ext\Core\System\Editables\Block::class,
            6000 => \Frootbox\Ext\Core\System\Editables\Headline::class,
            6001 => \Frootbox\Ext\Core\System\Editables\SimpleElement::class,
            6002 => \Frootbox\Ext\Core\System\Editables\LinkedElement::class,
            6003 => \Frootbox\Ext\Core\System\Editables\Entity::class,
            6004 => \Frootbox\Ext\Core\System\Editables\Plugin::class,

        ],
        'postroutes' => [
            \Frootbox\Ext\Core\System\Routing\EditingRoute::class,
        ],
        'gizmos' => [
            \Frootbox\Ext\Core\System\Admin\Gizmos\ExtensionUpdates\Gizmo::class,
        ],
    ],
];

<?php
/**
 *
 */

return [
    'id' => 'Images',
    'name' => 'Frootbox Image Utilities',
    'vendor' => [
        'id' => 'Core',
        'name' => 'Frootbox'
    ],
    'version' => '0.0.2',
    'requires' => [
        'Core/FileManager' => '0.0.1'
    ],
    'autoinstall' => [
        'editables' => [
            7001 => \Frootbox\Ext\Core\Images\Editables\Image::class,
            7002 => \Frootbox\Ext\Core\Images\Editables\ImageRollover::class,
            7003 => \Frootbox\Ext\Core\Images\Editables\PictureFull::class,
            7004 => \Frootbox\Ext\Core\Images\Editables\Background::class,
            7005 => \Frootbox\Ext\Core\Images\Editables\PhysicalImage::class,
        ],
    ],
];
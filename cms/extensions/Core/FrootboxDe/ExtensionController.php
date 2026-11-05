<?php

namespace Frootbox\Ext\Core\FrootboxDe;

class ExtensionController extends \Frootbox\AbstractExtensionController
{
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}

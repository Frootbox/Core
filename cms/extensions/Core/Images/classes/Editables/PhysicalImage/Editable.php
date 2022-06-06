<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\PhysicalImage;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function parse(
        $html
    ): string
    {
        return $html;
    }
}
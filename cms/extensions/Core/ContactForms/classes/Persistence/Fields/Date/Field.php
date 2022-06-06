<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Date;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField
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
    public function getValueDisplay(): string
    {
        if (empty($this->value)) {
            return "";
        }

        $date = new \DateTime($this->value);

        return $date->format('d.m.Y');
    }
}

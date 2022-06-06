<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Privacy;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\Checkbox\Field
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}

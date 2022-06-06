<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Textarea;

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
     * Update field form post data
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'rows' => $post->get('rows'),
        ]);
    }
}

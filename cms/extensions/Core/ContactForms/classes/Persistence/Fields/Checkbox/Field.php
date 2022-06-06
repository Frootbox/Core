<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Checkbox;

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
        return 'Gewählt: ' . (!empty($this->value) ? 'Ja' : 'Nein') . PHP_EOL . $this->getConfig('checkboxText');
    }

    /**
     * Update field form post data
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'checkboxText' => $post->get('checkboxText'),
        ]);
    }
}

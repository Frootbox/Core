<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Channel;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField
{
    /**
     *
     */
    public function getOptions(): array
    {
        $options = [];

        $lines = $this->getConfig('options');
        $lines = explode("\n", $lines);

        foreach ($lines as $line) {
            $da = explode(':', $line);
            $options[] = $da[0];
        }

        return $options;
    }

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
            'options' => $post->get('options'),
        ]);
    }
}

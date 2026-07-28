<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Button;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField
{
    protected $isSkippedInLog = true;

    /**
     * @return string
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
            'ButtonType' => $post->get('ButtonType'),
        ]);
    }
}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Files;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField
{
    protected $files = [];

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
        $value = (string) null;

        foreach ($this->files as $file) {
            $value .= '#' . $file['id'] . ' ' . $file['name'] . "\n";
        }

        return $value;
    }

    /**
     *
     */
    public function getMaxSize(): float
    {
        $max = round(\Frootbox\Persistence\Repositories\Files::getUploadMaxSize() / 1024 / 1024, 2);

        if (!empty($this->getConfig('maxSize')) and $this->getConfig('maxSize') < $max) {
            $max = $this->getConfig('maxSize');
        }

        return $max;
    }

    /**
     *
     */
    public function isEmpty(): bool
    {
        if (empty($_SESSION['contactforms'][$this->getId()]['uploads'])) {
            return true;
        }

        return count($_SESSION['contactforms'][$this->getId()]['uploads']) == 0;
    }

    /**
     *
     */
    public function setFiles(array $files)
    {
        $this->files = $files;
    }

    /**
     * Update field form post data
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'maxSize' => $post->get('maxSize'),
        ]);
    }
}

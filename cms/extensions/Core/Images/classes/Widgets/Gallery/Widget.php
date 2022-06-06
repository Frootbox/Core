<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Gallery;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     *
     */
    public function getColumns(): int
    {
        return (int) ($this->config['imageColumns'] ?? 4);
    }

    /**
     * Get images of gallery
     */
    public function getImages(
        \Frootbox\Persistence\Repositories\Files $files
    ): \Frootbox\Db\Result
    {
        // Fetch files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getUid('images')
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        return $result;
    }

    /**
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getImageHeight(): int
    {
        return (int) ($this->config['imageHeight'] ?? null);
    }

    /**
     *
     */
    public function getImageWidth(array $parameters = null): int
    {
        if (empty($this->config['imageWidth']) and !empty($parameters['default'])) {
            return $parameters['default'];
        }

        return (int) ($this->config['imageWidth'] ?? null);
    }

    /**
     * Cleanup widgets resources before it gets deleted
     *
     * Dependencies get auto injected.
     */
    public function unload (
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch images
        $result = $this->getImages($files);


        // Delete images
        $result->map('delete');
    }
}
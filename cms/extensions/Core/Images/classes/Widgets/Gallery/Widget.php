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
     *
     * @param \Frootbox\Persistence\Repositories\Files $files
     * @return \Frootbox\Db\Result
     */
    public function getImages(
        \Frootbox\Persistence\Repositories\Files $files
    ): \Frootbox\Db\Result
    {
        $order = $this->getSorting() == 'Default' ? 'orderId DESC' : 'RAND()';

        // Fetch files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getUid('images')
            ],
            'order' => [ $order ]
        ]);

        return $result;
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
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function getSorting(): string
    {
        return !empty($this->getConfig('Sorting')) ? $this->getConfig('Sorting') : 'Default';
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
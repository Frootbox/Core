<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Persistence;

class Image extends \Frootbox\Persistence\File
{
    /**
     *
     */
    public function getCaption(): ?string
    {
        return $this->getConfig('caption');
    }

    /**
     *
     */
    public function getDimensions(): array
    {
        if (!file_exists(FILES_DIR . $this->getPath())) {
            return [];
        }

        $data = getimagesize(FILES_DIR . $this->getPath());

        return [
            'width' => $data[0] ?? null,
            'height' => $data[1] ?? null,
            'mime' => $data['mime'] ?? null,
        ];
    }
}

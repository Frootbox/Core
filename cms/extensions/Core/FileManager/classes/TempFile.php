<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager;

class TempFile
{
    protected $id = null;
    protected $meta = [];

    /**
     *
     */
    public function __construct(string $id = null)
    {
        if ($id === null) {

            $this->id = self::getUniqueFileId();
        }
        else {

            $this->id = $id;

            $metaFile = $this->getPathMeta();

            if (file_exists($metaFile)) {
                $this->meta = json_decode(file_get_contents($metaFile), true);
            }
        }
    }

    /**
     *
     */
    private function persistMeta(): void
    {
        $file = new \Frootbox\Filesystem\File($this->getPathMeta());
        $file->setSource(json_encode($this->meta));
        $file->write();
    }

    /**
     *
     */
    public function delete(): void
    {
        if (file_exists($this->getPath())) {
            unlink($this->getPath());
        }

        if (file_exists($this->getPathMeta())) {
            unlink($this->getPathMeta());
        }
    }

    /**
     *
     */
    public function getId(): string
    {
        if ($this->id === null) {
            $this->id = self::getUniqueFileId();
        }

        return $this->id;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return FILES_DIR . 'temp/--' . $this->id . '.data';
    }

    /**
     *
     */
    public function getPathMeta(): string
    {
        return FILES_DIR . 'temp/--' . $this->id . '.meta';
    }

    /**
     *
     */
    public static function createFromFile(string $path): self
    {
        // Clear folder
        $directory = new \Frootbox\Filesystem\Directory(FILES_DIR . 'temp/');

        foreach ($directory as $file) {

            if (!preg_match('#^--([0-9]+)-#', $file, $match)) {
                continue;
            }

            if (($_SERVER['REQUEST_TIME'] - $match[1]) >= 86400) {
                unlink(FILES_DIR . 'temp/' . $file);
            }
        }

        $tempFile = new self;

        $file = new \Frootbox\Filesystem\File($tempFile->getPath());
        $file->setSource(file_get_contents($path));
        $file->write();

        return $tempFile;
    }

    /**
     *
     */
    public function getName(): ?string
    {
        return $this->meta['name'] ?? null;
    }

    /**
     *
     */
    public function getSize(): int
    {
        if (!file_exists($this->getPath())) {
            return false;
        }

        return filesize($this->getPath());
    }

    /**
     *
     */
    public function getType(): ?string
    {
        return $this->meta['type'] ?? null;
    }

    /**
     *
     */
    public static function getUniqueFileId(): string
    {
        return $_SERVER['REQUEST_TIME'] . '-' . md5(microtime(true));
    }

    /**
     *
     */
    public function setName(string $name): void
    {
        $this->meta['name'] = $name;
        $this->persistMeta();
    }

    /**
     *
     */
    public function setType(string $type): void
    {
        $this->meta['type'] = $type;
        $this->persistMeta();
    }
}

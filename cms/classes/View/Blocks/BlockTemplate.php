<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2020-03-22
 */

namespace Frootbox\View\Blocks;

/**
 *
 */
class BlockTemplate
{
    protected string $path;
    protected ?string $overrideThumbnail = null;

    use \Frootbox\Traits\ViewConfigParser;
    use \Frootbox\Persistence\Traits\Config;

    /**
     *
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        $this->config = $this->parseViewConfigString(file_get_contents($this->path . '/Block.html.twig'));
    }

    /**
     * @param string $path
     * @return void
     */
    public function captureThumbnail(string $path): void
    {
        $files = [
            $path . 'thumbnail.jpg',
            $path . 'thumbnail.png',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $this->overrideThumbnail = $file;
                break;
            }
        }
    }

    /**
     *
     */
    public function getId(): string
    {
        return basename($this->path);
    }

    /**
     * @return string|null
     */
    public function getOverrideThumbnail(): ?string
    {
        return $this->overrideThumbnail;
    }

    /**
     * @return string
     */
    public function getThumbnailSrc(): string
    {
        if (!empty($this->overrideThumbnail)) {
            $path = $this->overrideThumbnail;
        }
        else {
            $paths = [];
            $paths[] = $this->path . '/thumbnail.svg';
            $paths[] = $this->path . '/thumbnail.png';
            $paths[] = $this->path . '/thumbnail.jpg';
            $paths[] = CORE_DIR . 'cms/admin/resources/public/images/no-thumbnail.png';

            foreach ($paths as $path) {

                if (file_exists($path)) {
                    break;
                }
            }
        }

        $key = md5($path);

        $_SESSION['staticfilemap'][$key] = $path;

        return SERVER_PATH . 'static/Ext/Core/System/ServeStatic/serve?t=' . $key;
    }

    /**
     *
     */
    public function getSubtitle(): ?string
    {
        return $this->config['subtitle'] ?? null;
    }

    /**
     *
     */
    public function getTitle(): string
    {
        return $this->config['title'] ?? 'Standard';
    }
}

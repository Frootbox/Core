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
    protected $path;

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
     *
     */
    public function getId(): string
    {
        return basename($this->path);
    }

    /**
     *
     */
    public function getThumbnailSrc(): string
    {
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

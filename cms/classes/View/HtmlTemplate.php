<?php
/**
 *
 */

namespace Frootbox\View;


class HtmlTemplate
{
    use \Frootbox\Traits\ViewConfigParser;
    use \Frootbox\Persistence\Traits\Config;

    protected $title = null;
    protected $file = null;
    protected $config = [ ];
    protected $active = false;
    protected $source = null;
    protected $variables = [ ];

    /**
     *
     */
    public function __construct($filepath = null, array $options = null)
    {
        if (!empty($filepath)) {
            $this->load($filepath);
        }
        
        if (!empty($options['templateId'])) {
            $this->config['templateId'] = $options['templateId'];
        }

        if (!empty($options['number'])) {
            $this->config['number'] = $options['number'];
        }

        if (!empty($options['active'])) {
            $this->active = true;
        }

        if (!empty($options['variables'])) {
            $this->variables = $options['variables'];
        }
    }

    /**
     *
     */
    public function getFileName ( ) {

        return basename($this->file);
    }

    /**
     *
     */
    public function getName ( ) {

        return $this->config['title'] ?? basename($this->file);
    }

    /**
     * @return string
     */
    public function getTemplateId(): string
    {
        return $this->config['templateId'] ?? basename($this->file);
    }

    /**
     *
     */
    public function getTemplateNumber ( ) {

        return $this->config['number'];
    }

    /**
     *
     */
    public function getThumbnail(): string
    {
        $paths = [
            dirname($this->file) . '/public/thumbnail.svg',
            dirname($this->file) . '/public/thumbnail.png',
            dirname($this->file) . '/public/thumbnail.jpg',
            CORE_DIR . 'cms/admin/resources/public/images/no-thumbnail.png'
        ];

        foreach ($paths as $path) {

            if (file_exists($path)) {
                break;
            }
        }

        $key = md5($path);

        $_SESSION['staticfilemap'][$key] = $path;

        // return SERVER_PATH . 'static/Ext/Core/System/ServeStatic/serve?t=' . $key;
        return SERVER_PATH . 'cms/admin/Plugin/serveStaticThumbnail?t=' . $key;
    }

    /**
     *
     */
    public function getTitle(): string
    {
        return $this->title ?? $this->getTemplateId();
    }

    /**
     *
     */
    public function getVariables ( )
    {
        if (empty($this->config['variables'])) {
            return [ ];
        }

        $variables = [ ];

        foreach ($this->config['variables'] as $variable => $parameters) {

            $parameters['name'] = $variable;
            $parameters['label'] = $parameters['label'] ?? $variable;

            $parameters['value'] = $this->variables[$variable] ?? $parameters['default'] ?? null;

            $variables[] = $parameters;
        }

        return $variables;
    }

    /**
     *
     */
    public function isActive ( ): bool
    {
        return $this->active;
    }

    /**
     * @param string $filepath
     * @return $this
     */
    public function load(string $filepath): HtmlTemplate
    {
        $this->file = $filepath;
        $this->source = file_get_contents($this->file);

        $this->config = $this->parseViewConfigString($this->source);

        return $this;
    }

    /**
     *
     */
    public function parseConfig()
    {
        $source = file_get_contents($this->file);

        $config = $this->parseViewConfigString($source);

        if (!empty($config['title'])) {
            $this->title = $config['title'];
        }

        if (!empty($config['variables'])) {
            $this->variables = $config['variables'];
        }
    }
}

<?php
/**
 *
 */

namespace Frootbox\View\Partials;

abstract class AbstractPartial implements PartialInterface
{
    protected $attributes = [];

    /**
     *
     */
    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute];
    }

    /**
     *
     */
    abstract protected function getPath ( ): string;

    /**
     *
     */
    public function hasAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->attributes);
    }

    /**
     *
     */
    public function render (
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {
        // Get view file
        $files = [];
        $files[] = $this->getPath() . 'resources/private/views/View.html.twig';
        $files[] = $this->getPath() . 'resources/private/views/View.html';

        $viewFile = null;

        foreach ($files as $file) {

            if (file_exists($file)) {
                $viewFile = $file;
                break;
            }
        }

        foreach ($this->attributes as $key => $value) {
            $view->set($key, $value);
        }

        $html = $view->render($viewFile);

        // Auto inject scss
        $scssfile = $this->getPath() . '/resources/public/css/standards.less';

        if (file_exists($scssfile)) {
            $html = '<link type="text/css" rel="stylesheet/less" href="FILE:' . $scssfile . '" />' . PHP_EOL . $html;
        }

        // Auto inject javascript
        $jsfile = $this->getPath() . '/resources/public/javascript/init.js';

        if (file_exists($jsfile)) {
            $html = '<script src="FILE:' . $jsfile . '"></script>' . PHP_EOL . $html;
        }

        return $html;
    }

    /**
     *
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
}



<?php
/**
 *
 */

namespace Frootbox\Persistence\Content;

class Widget extends AbstractWidget
{
    protected $table = 'content_widgets';
    protected $model = Repositories\Widgets::class;

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * Get size of editing modal
     *
     * @return string
     */
    public function getSize(): string
    {
        return 'm';
    }

    /**
     * Render widget html
     */
    public function renderHtml(
        $action,
        \DI\Container $container,
        \Frootbox\Session $session,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        $editable = true
    ): string
    {
        return '<figure data-ce-moveable class="widget widget-' . $this->getAlign() . ' col-' . $this->getWidth() . '" data-id="' . $this->getId() . '">
            Widget wurde noch nicht konfguriert.
            <a class="widget-config" href="' . $this->getId() . '">Jetzt konfigurieren!</a>
        </figure>';
    }
}

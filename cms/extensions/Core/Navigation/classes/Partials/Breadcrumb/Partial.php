<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Partials\Breadcrumb;

use Frootbox\Persistence\Repositories\Pages;

class Partial extends \Frootbox\View\Partials\AbstractPartial
{
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
    public function onBeforeRendering (
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        // Obtain page
        $page = $this->getAttribute('page');

        $view->set('xpage', $page);
    }
}

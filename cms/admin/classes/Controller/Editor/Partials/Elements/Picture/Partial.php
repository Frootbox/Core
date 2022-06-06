<?php
/**
 *
 */

namespace Frootbox\Admin\Controller\Editor\Partials\Elements\Picture;

/**
 *
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {

    /**
     *
     */
    public function getPath ( ): string {

        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering ( \Frootbox\Admin\View $view, \Frootbox\Http\Get $get ) {

        $view->set('get', $get);
    }
}
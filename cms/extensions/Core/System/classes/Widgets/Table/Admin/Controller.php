<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Widgets\Table\Admin;

class Controller extends \Frootbox\Admin\AbstractWidgetController
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
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \DI\Container $container
    ): \Frootbox\Admin\Controller\Response
    {
        $post->require([ 'columns', 'rows' ]);

        $this->widget->addConfig([
            'columns' => $post->get('columns'),
            'rows' => $post->get('rows'),
            'withHeader' => $post->get('withHeader'),
            'tabledata' => $post->get('tabledata'),
            'headerdata' => $post->get('headerdata'),
            'annotation' => $post->get('annotation'),
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::response('json', 200, [
            'widget' => [
                'id'=> $this->widget->getId(),
                'html' => $widgetHtml
            ]
        ]);
    }


    /**
    *
    */
    public function indexAction (
       //  \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
       \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {

        $rows = [];
        $header = [];

        if ($this->widget->getConfig('withHeader')) {

            $headerdata = $this->widget->getConfig('headerdata');

            for ($i = 0; $i < $this->widget->getColCount(); ++$i) {
                $header[] = !empty($headerdata[$i]) ? $headerdata[$i] : null;
            }
        }

        $tabledata = $this->widget->getConfig('tabledata');

        for ($i = 0; $i < $this->widget->getRowCount(); ++$i) {

            $row = [];

            for ($x = 0; $x < $this->widget->getColCount(); ++$x) {
                $row[] = !empty($tabledata[$i][$x]) ? $tabledata[$i][$x] : null;
            }

            $rows[] = $row;
        }

        return self::getResponse('html', 200, [
            'rows' => $rows,
            'header' => $header,
        ]);
    }
}


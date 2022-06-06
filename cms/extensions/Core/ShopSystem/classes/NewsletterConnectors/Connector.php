<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\NewsletterConnectors;

abstract class Connector
{
    protected $view;

    /**
     *
     */
    public function __construct(
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        $this->view = $view;
    }

    /**
     *
     */
    public static function getConnectors(): array
    {
        $dir = dir(__DIR__);

        $list = [];

        while (false !== ($entry = $dir->read())) {

            if ($entry[0] == '.' or $entry == 'Connector.php' or $entry == 'Unknown') {
                continue;
            }

            // Load language file
            $path = $dir->path . '/' . $entry . '/resources/private/language/de-DE.php';
            $title = $entry;

            if (file_exists($path)) {

                $data = require $path;

                if (!empty($data['Connector.Title'])) {
                    $title = $data['Connector.Title'];
                }
            }

            $list[] = [
                'id' => $entry,
                'title' => $title,
                'class' => '\Frootbox\Ext\Core\ShopSystem\NewsletterConnectors\\' . $entry . '\\Connector'
            ];
        }

        $dir->close();

        return $list;
    }

    /**
     *
     */
    public function getInputHtml(

    ): string
    {
        // Get view file
        $file = $this->getPath() . 'resources/private/views/View.html.twig';

        return $this->view->render($file);
    }

    /**
     *
     */
    abstract public function execute(\Frootbox\Http\Post $post, \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart): void;
}

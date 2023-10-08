<?php
/**
 *
 */

namespace Frootbox\Persistence\Content\Elements;

class Grid extends \Frootbox\Persistence\AbstractConfigurableRow implements \Frootbox\Persistence\Interfaces\ContentElement
{
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Visibility;

    protected $table = 'content_elements';
    protected $model = Frootbox\Persistence\Content\Repositories\ContentElements::class;
    protected $isFirst = false;

    /**
     *
     */
    public function getColumns(): array
    {
        if (empty($this->getConfig('columns'))) {
            return [ ];
        }

        $sec = explode('-', $this->getConfig('columns'));

        $list = [ ];

        $model = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);

        foreach ($sec as $index => $gridCount) {

            $socket = $this->getSocket() . '_' . $this->getId() . '_' . $index;

            $list[] = [
                'grid' => $gridCount,
                'socket' => $socket,
                'pageId' => $this->getPageId(),
                'elements' => $model->fetch([
                    'where' => [
                        'pageId' => $this->getPageId(),
                        'socket' => $socket,
                        new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', (IS_LOGGED_IN ? 1 : 2)),
                    ],
                ]),
            ];
        }

        return $list;
    }

    /**
     *
     */
    public function renderHtml(
        $order,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \DI\Container $container,
        \Frootbox\Config\Config $config,
    ): string
    {
        $html = (string) null;

        if (!empty($config->get('general.view.grid.forceContainer'))) {
            $html .= '<div class="container">';
        }

        $html .= '<div class="grid-outer-wrapper">
            <div class="row">';

        foreach ($this->getColumns() as $column) {

            $html .= '<div class="col-12 col-md-' . $column['grid'] . '">';

            foreach ($column['elements'] as $element) {

                // Call plugin action
                if (method_exists($element, 'indexAction')) {

                    // Call action
                    $xresult = $container->call([ $element, 'indexAction' ]);

                    if (!empty($xresult)) {

                        if ($xresult instanceof \Frootbox\View\ResponseRedirect) {
                            http_response_code(200);
                            header('Location: ' . $xresult->getTarget());
                            exit;
                        } else {

                            foreach ($xresult->getData() as $key => $value) {
                                $view->set($key, $value);
                            }
                        }
                    }
                }

                $html .= $container->call([ $element, 'renderHtml' ], [
                    'action' => 'index',
                    'order' => $order
                ]);
            }

            $html .= '</div>';
        }

        $html .= '</div>
        </div>';

        if (!empty($config->get('general.view.grid.forceContainer'))) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Mark element as first
     */
    public function setFirst(): Grid
    {
        // Mark as first
        $this->isFirst = true;

        return $this;
    }
}

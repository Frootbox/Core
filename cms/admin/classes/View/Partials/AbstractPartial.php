<?php 
/**
 * 
 */

namespace Frootbox\Admin\View\Partials;


/**
 * 
 */
abstract class AbstractPartial extends \Frootbox\GenericObject implements PartialInterface
{
    protected $path;
    protected array $data = [];

    /**
     *
     */
    public function getActionUri($action, array $payload = null): string
    {
        $payload['action'] = $action;
        $payload['partial'] = get_class($this);

        $query = http_build_query($payload);

        return SERVER_PATH . 'cms/admin/Assets/Partials/genericAction/?' . $query;

    }

    /**
     *
     */
    public function getData($attribute = null, $optional = false)
    {
        if (!array_key_exists($attribute, $this->data)) {

            if ($optional) {
                return null;
            }

            throw new \Frootbox\Exceptions\NotFound('Missing attribute ' . $attribute);
        }

        return $this->data[$attribute];
    }

    /**
     * @param \Frootbox\Admin\View $view
     * @param \DI\Container $container
     * @return string
     * @throws \Frootbox\Exceptions\ResourceMissing
     */
    public function render(
        \Frootbox\Admin\View $view,
        \DI\Container $container
    ): string
    {
        if (empty($this->path)) {
            $this->path = $this->getPath();
        }
                
        $viewFile = $this->path . 'resources/private/views/View.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $this->path . 'resources/private/views/View.html';
        }

        if (!file_exists($viewFile)) {
            return 'Partial view file missing.';
        }

        if (method_exists($this, 'onBeforeRendering')) {
            $response = $container->call([ $this, 'onBeforeRendering' ]);
        }

        if (!empty($response)) {
            if (!empty($response->getBodyData())) {
                foreach ($response->getBodyData() as $key => $value) {
                    $view->set($key, $value);
                }
            }
        }

        $view->set('data', $this->data);
        $view->set('view', $view);
        $view->set('partial', $this);

        $xPath = dirname(dirname($this->getPath())) . DIRECTORY_SEPARATOR;

        return $view->render($viewFile, $xPath);
    }

    /**
     * @param array $data
     * @return PartialInterface
     */
    public function setData(array $data): PartialInterface
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasData(string $attribute): bool
    {
        return isset($this->data[$attribute]);
    }
}

<?php
/**
 *
 */

namespace Frootbox\Persistence\Content;

abstract class AbstractWidget extends \Frootbox\Persistence\AbstractConfigurableRow
{
    use \Frootbox\Persistence\Traits\Config;
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Layouts;
    use \Frootbox\Persistence\Traits\DummyImage;

    protected $table = 'content_widgets';
    protected $model = Repositories\Widgets::class;

    /**
     *
     */
    public function getAdminController()
    {
        $className = substr(get_class($this), 0,-6) . 'Admin\\Controller';

        return new $className($this);
    }

    /**
     *
     */
    public function getAlign(): string
    {
        /*
        if (!empty($this->config['width']) and $this->config['width'] == 12) {
            return 'justify';
        }
        */

        return $this->config['align'] ?? 'left';
    }

    /**
     *
     */
    public function getLayoutForAction($action): string
    {
        return $this->config['layoutId'] ?? 'Index01';
    }

    /**
     *
     */
    abstract public function getPath(): string;

    /**
     *
     */
    public function getPublicActions(): array
    {
        return [ 'index' ];
    }

    /**
     *
     */
    public function getViewTemplate(): \Frootbox\View\HtmlTemplate
    {
        $layout = $this->getLayoutForAction('index');

        $file = $this->getPath() . 'Layouts/' . $layout . '/View.html.twig';

        if (!file_exists($file)) {
            $file = $this->getPath() . 'Layouts/' . $layout . '/View.html';
        }

        $template = new \Frootbox\View\HtmlTemplate($file, [
            'variables' => $this->getConfig('variables')
        ]);

        return $template;
    }

    /**
     *
     */
    public function getWidth()
    {
        if (!empty($this->config['align']) and ($this->config['align'] == 'justify' OR $this->config['align'] == 'center')) {
            return 12;
        }

        return $this->config['width'] ?? 6;
    }

    /**
     *
     */
    public function injectScss(\Frootbox\Config\Config $config, $path)
    {
        return '<link type="text/css" rel="stylesheet/less" href="FILE:' . $this->getPath() . 'Layouts/' . $path . '" />';
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
        $widgetAction = lcfirst($action) . 'Action';
        $response = null;

        $layout = $this->config['layoutId'] ?? 'Index01';

        preg_match('#Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Widgets\\\\(.*?)\\\\Widget#', get_class($this), $match);

        // Obtain template file
        $templateFile = null;

        $files = [];
        $files[] = $config->get('widgetsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $layout . '/View.html.twig';;
        $files[] = $this->getPath() . 'Layouts/' . $layout . '/View.html.twig';
        $files[] = $this->getPath() . 'Layouts/' . $layout . '/View.html';

        foreach ($files as $file) {

            if (file_exists($file)) {
                $templateFile = $file;
                break;
            }
        }

        if (method_exists($this, $widgetAction)) {

            $view->addPath(dirname($templateFile) . '/');

            $response = $container->call([ $this, $widgetAction ]);
        }

        // Hide widget if it has no content and we are not editing
        // TODO target edit mode precisely
        if ($response === false and !$session->isLoggedIn()) {
            return (string) null;
        }
        elseif (defined('EDITING') and EDITING and $response === false and $session->isLoggedIn()) {
            $response = "Dieses Widget enthält noch keinen Inhalt.";
        }

        // Render layout
        if (is_string($response)) {
            $viewHtml = $response;
        }
        elseif (file_exists($templateFile)) {

            $view->set('widget', $this);
            $view->set('view', $view);
            $view->set('variables', $this->getConfig('variables') ?? []);

            $payload = (!empty($response) and !empty($response->getData())) ? $response->getData() : [];

            try {
                $viewHtml = $view->render($templateFile, $payload);
            }
            catch ( \Exception $e ) {
                $viewHtml = $e->getMessage();
            }
        }
        else {
            $viewHtml = 'Template für die Ansicht "Index" nicht vorhanden.';
        }

        $style = [ ];

        if (!empty($margin = $this->getConfig('margin'))) {


            if (!empty($margin['top'])) {
                $style[] = 'margin-top: ' . $margin['top'] . 'px';
            }

            if (!empty($margin['bottom'])) {
                $style[] = 'margin-bottom: ' . $margin['bottom'] . 'px';
            }

            if (!empty($margin['right'])) {
                $style[] = 'margin-right: ' . $margin['right'] . 'px';
            }

            if (!empty($margin['left'])) {
                $style[] = 'margin-left: ' . $margin['left'] . 'px';
            }
        }

        $html = '<figure style="' . implode(';', $style) . '" data-ce-moveable class="widget ' . $match[1] . ' ' . $match[2] . ' ' . $match[3] . ' ' . $layout . ' widget-' . $this->getAlign() . ' col-12 col-md-' . $this->getWidth() . '" data-id="' . $this->getId() . '">';

        if (!$editable) {
            $html = str_replace('<figure ', '<figure data-noedit ', $html);
        }

        if ($this->getAlign() == 'center') {
            $html .= '<div class="row justify-content-md-center"><div class="col-12 col-sm-' . $this->getConfig('width') . '">';
        }

        $html .= $viewHtml;

        if ($this->getAlign() == 'center') {
            $html .= '</div></div>';
        }

        // Auto inject styles
        $scssFile = dirname($templateFile) . '/public/standards.less';

        if (file_exists($scssFile)) {
            $html .= PHP_EOL . PHP_EOL . '<link type="text/css" rel="stylesheet/less" href="FILE:' . $scssFile . '" />';
        }

        preg_match('#\/([a-z0-9]+)\/View(\.html|\.html.twig)$#i', $templateFile, $xmatch);

        $customScssFile = $config->get('widgetsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $xmatch[1] . '/public/custom.less';

        if (file_exists($customScssFile)) {
            $html .= PHP_EOL . PHP_EOL . '<link type="text/css" rel="stylesheet/less" href="FILE:' . $customScssFile . '" />';
        }

        $html .= '</figure>';

        return $html;
    }
}

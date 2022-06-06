<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller;


/**
 * 
 */
abstract class AbstractController
{
    protected $lastAction = null;
    
    
    /**
     * 
     */
    protected $view;
    
    
    /**
     * 
     */
    public function __construct(\Frootbox\Admin\View $view)
    {
        $this->view = $view;
    }
    
    
    /**
     * 
     */
    public function getPath(): string
    {
        return CORE_DIR . 'cms/admin/classes/' . dirname(str_replace('\\', '/', str_replace('Frootbox\\Admin\\', '', get_class($this)))) . '/';
    }
    
    
    /**
     * 
     */
    public function getUri ( $action = 'index', array $payload = null ) {

        $controller = dirname(str_replace('\\', '/', str_replace('Frootbox\\Admin\\Controller\\', '', get_class($this))));

        return \Frootbox\Admin\Front::getUri($controller, $action, $payload);
    }
    
    
    /**
     * 
     */
    public function getView ( ): \Frootbox\Admin\View {
        
        return $this->view;        
    }
    
    
    /**
     * 
     */
    public function injectActionCss ( ): string {
        
        $file = $this->getPath() . 'resources/public/css/' . $this->lastAction . '.less';
        
        if (!file_exists($file)) {
            return (string) null;
        }
        
        $publicPath = \Frootbox\Admin\View::convertToPublicPath($file);

        return '<link rel="stylesheet/less" type="text/css" href="' . $publicPath . '" />';
    }


    /**
     *
     */
    public function redirect ( $controller, $action = 'index', array $payload = null ): \Frootbox\Admin\Controller\Response {

        return new \Frootbox\Admin\Controller\Response('redirect', 302, \Frootbox\Admin\Front::getUri($controller, $action, $payload));
    }
    
    
    /**
     * 
     */
    public function render(string $action = null, $variables = null): string
    {
        if ($action === null) {
            $action = $this->lastAction;
        }

        $viewFile = $this->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $this->getPath() . 'resources/private/views/' . ucfirst($action) . '.html';
        }
        
        return $this->view->render($viewFile, dirname($viewFile) . '/', $variables);
    }
    
    /**
     * 
     */
    public function getResponse ( $type = 'html', int $status = 200, $body = null ): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response($type, $status, $body);
    }


    /**
     * @deprecated
     * @see \Frootbox\Admin\Controller\AbstractController::getResponse()
     */
    public function response ( $type = 'html', int $status = 200, $body = null ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse($type, $status, $body);
    }


    /**
     *
     */
    public function setLastAction ( $action ) {

        $this->lastAction = $action;

        return $this;
    }
}
<?php 
/**
 * 
 */

namespace Frootbox\Admin\Viewhelper;

/**
 * 
 */
class GeneralPurpose extends AbstractViewhelper
{
    /**
     * 
     */
    public function getMenueAction(
        \Frootbox\Admin\Persistence\Repositories\Apps $apps,
        \Frootbox\Config\Config $config
    ): array
    {
        // Fetch menu apps
        $result = $apps->fetch([
            'where' => [
                'menuId' => 'Global'
            ]
        ]);

        $list = [ ];

        try {

            foreach ($result as $app) {
                $list[] = $app;
            }

        }
        catch ( \Exception $e ) {

        }

        return $list;
    }

    /**
     * @param string $uriSegments
     * @param string $action
     * @param string $payload
     */
    public function getStaticUriAction(): string
    {
        $arguments = $this->getArgumenets();

        $url = SERVER_PATH . 'cms/admin/StaticController/index?segment=' . $arguments['uriSegments'] . '&a=' . $arguments['action'];

        if (!empty($arguments['payload'])) {
            $url .= '&' . http_build_query($arguments['payload']);
        }

        return $url;
    }
    
    /**
     * Get controller URI
     *
     * @param string $controller
     * @param string $action
     * @param array $payload
     */
    public function getUriAction(): string
    {
        // } $controller, string $action = 'index' , array $payload = null ): string {

        $arguments = $this->getArgumenets();

        return \Frootbox\Admin\Front::getUri($arguments['controller'], $arguments['action'] ?? 'index', $arguments['payload']);
    }

    /**
     * Get absolute controller URI
     *
     * @param string $controller
     * @param string $action
     * @param array $payload
     */
    public function getUriAbsoluteAction(): string
    {
        $arguments = $this->getArgumenets();

        return \Frootbox\Admin\Front::getUri($arguments['controller'], $arguments['action'] ?? 'index', $arguments['payload']);
    }

    /**
     * 
     */
    public function inject ( $libPath ) {
        
        
        if (substr($libPath, -4) == '.css') {
                        
            return '<link rel="stylesheet" type="text/css" href="' . SERVER_PATH . 'cms/admin/resources/public/css/' . $libPath . '" />';
        }
        elseif (substr($libPath, -5) == '.less') {
        
            return '<link rel="stylesheet/less" type="text/css" href="' . SERVER_PATH . 'cms/admin/resources/public/css/' . $libPath . '" />';
        }
        elseif (substr($libPath, -3) == '.js') {
            
            return '<script type="text/javascript" src="' . SERVER_PATH . 'cms/admin/resources/public/javascript/' . $libPath . '"></script>';
        }
        
        return '<!-- unknown public library: ' . $libPath . ' -->'; 
    }
    
    
    /**
     * 
     */
    public function injectLibrary ( $libPath ) {
                
        if (substr($libPath, -4) == '.css') {
                        
            return '<link rel="stylesheet" type="text/css" href="' . SERVER_PATH . 'cms/admin/resources/public/libs/' . $libPath . '" />';
        }
        elseif (substr($libPath, -3) == '.js') {
            
            return '<script type="text/javascript" src="' . SERVER_PATH . 'cms/admin/resources/public/libs/' . $libPath . '"></script>';
        }
        
        return '<!-- unknown public library: ' . $libPath . ' -->'; 
    }    
    
    
    /**
     * @param string $partialClass
     * @param array $data
     */
    public function injectPartialAction(
        \Frootbox\Admin\View $view
    ): string
    {
        $arguments = $this->getArgumenets();

        $partialClass = str_replace('/', '\\', $arguments['partialClass']);

        if (substr($partialClass, -8) != '\\Partial') {
            $partialClass .= '\\Partial';
        }
        
        $partial = $this->container->get($partialClass);

        if (!empty($arguments['data'])) {
            $partial->setData($arguments['data']);
        }



        try {

            if (is_callable([ $partial, 'onBeforeRendering' ])) {

                $response = $this->container->call([ $partial, 'onBeforeRendering' ]);

                if (!empty($response) and !empty($data = $response->getBodyData())) {

                    foreach ($data as $key => $value) {
                        $view->set($key, $value);
                    }
                }
            }

            $html = $this->container->call([ $partial, 'render' ]);
        }
        catch (\Exception $exception) {
            $html = '<div style="margin: 20px 0; padding: 10px; text-align: center; border: 1px solid red; color: red;">' . $exception->getMessage() . '</div>';
        }


        return trim($html);
    }

    /**
     * @param string $vendorId
     * @param string $extensionId
     */
    public function isExtensionInstalledAction(
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    )
    {
        $arguments = $this->getArgumenets();
        $result = $extensionsRepository->fetchOne([
            'where' => [
                'vendorId' => $arguments['vendorId'],
                'extensionId' => $arguments['extensionId'],
                'isactive' => 1
            ],
        ]);

        return !empty($result);
    }
}
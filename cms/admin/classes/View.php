<?php 
/**
 * 
 */

namespace Frootbox\Admin;

class View
{
    protected $variables;
    protected $twig;
    protected $loader;
        
    /**
     * 
     */
    public function __construct()
    {
        $this->loader = new \Frootbox\View\Engines\Twig\FileLoader(CORE_DIR);
        $this->twig = new \Twig\Environment($this->loader, array(
            'debug' => true,
         //   'cache' => CORE_DIR . 'vendor/cache/admin/views/',
        ));
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        
        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('escapePageTitle', function ($string) {

            $string = str_replace('<br />', '', $string);
            $string = str_replace('&nbsp;', ' ', $string);

            return $string;
        });
        $this->twig->addFilter($filter);

        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('dump', function ($data) {

            p($data);

            return '';
        });
        $this->twig->addFilter($filter);

        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('ucfirst', function ($string) {
            return ucfirst($string);
        });
        $this->twig->addFilter($filter);


        $this->set('view', $this);
    }    

    /**
     *
     */
    public function addPath($path)
    {
        $this->loader->addPath($path);

        return $this;
    }
    
    /**
     * 
     */
    public function get($variable)
    {
        if (!array_key_exists($variable, $this->variables)) {
            return null;
        }
        
        return $this->variables[$variable];        
    }
    
    /**
     * 
     */
    public function getViewHelper($viewHelperClass, array $params = null)
    {
        if (!$container = $this->get('container')) {
            die("no container!!!! .-/");
        }
        
        $viewHelper = $container->make($viewHelperClass);

        if (!empty($params)) {
            $viewHelper->setParameters($params);
        }

        return $viewHelper;
    }
    
    /**
     * 
     */
    public function render($absoluteViewFilePath, $basePath = null, $variables = null): string
    {
        if ($basePath === null) {
            $basePath = CORE_DIR;
        }

        if (DIRECTORY_SEPARATOR == '\\') {
            $absoluteViewFilePath = str_replace('/', '\\', $absoluteViewFilePath);
            $basePath = str_replace('/', '\\', $basePath);
        }

        $this->addPath($basePath);

        $relativeViewFilePath = str_replace($basePath, '', $absoluteViewFilePath);

        if (!file_exists($absoluteViewFilePath)) {
            throw new \Frootbox\Exceptions\ResourceMissing($relativeViewFilePath);
        }

        if (!empty($variables)) {
            $variables = array_merge($variables, $this->variables);
        }
        else {
            $variables = $this->variables;
        }
        
        return $this->twig->render($relativeViewFilePath, $variables);
    }

    /**
     * 
     */
    public function set($variable, $value = null)
    {
        if (is_array($variable)) {
            foreach ($variable as $key => $value) {
                $this->variables[$key] = $value;    
            }
        }
        else {            
            $this->variables[$variable] = $value;            
        }
    }

    /**
     * 
     */
    public static function convertToPublicPath($localPath)
    {
        return SERVER_PATH . str_replace(CORE_DIR, '', $localPath);
    }
}

<?php
/**
 *
 */

namespace Frootbox\View\Engines;

use Frootbox\View\Viewhelper\AbstractViewhelper;

class Twig extends \Frootbox\GenericObject implements Interfaces\Engine {

    protected $twig;
    protected $container;
    protected $variables = [ ];
    protected $breadcrumb = [ ];

    /**
     *
     */
    public function __construct ( ) {

        $loader = new \Frootbox\View\Engines\Twig\FileLoader(CORE_DIR);
        $this->twig = new \Twig\Environment($loader, array(
            'debug' => true,
            'cache' => FILES_DIR . 'cache/views/',
        ));
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('cleanescape', function ($string) {

            $string = mb_strtolower($string);
            $trans = [
                'ö' => 'oe',
                'ü' => 'ue',
                'ä' => 'ae',
                'ß' => 'ss',
            ];
            $string = strtr($string, $trans);
            $string = preg_replace("/[^a-z0-9.]+/i", '-', $string);

            // Reduce multiple consecutive hyphens to a single one
            $string = preg_replace('/-+/', '-', $string);

            // Remove leading and trailing hyphens
            return trim($string, '-');
        });
        $this->twig->addFilter($filter);

        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('pascalcase', function ($string) {

            $string = mb_strtolower($string);
            $trans = [
                'ö' => 'oe',
                'ü' => 'ue',
                'ä' => 'ae',
                'ß' => 'ss',
            ];
            $string = strtr($string, $trans);
            $string = preg_replace("/[^a-z0-9.]+/i", ' ', $string);

            $string = ucwords($string);
            $string = str_replace(' ', '', $string);

            return $string;
        });
        $this->twig->addFilter($filter);




        $filter = new \Twig\TwigFilter('md5', function ($string) {

            return md5($string);
        });
        $this->twig->addFilter($filter);
    }

    /**
     *
     */
    public function addBreadcrumb($title, $uri = null): void
    {
        $this->breadcrumb[] = [
            'title' => $title,
            'uri' => $uri
        ];
    }

    /**
     *
     */
    public function addFilter($filter): void
    {
        $this->twig->addFilter($filter);
    }

    /**
     *
     */
    public function addGlobal ( $name, $value ): void {

        $this->twig->addGlobal($name, $value);
    }

    /**
     *
     */
    public function addPath(string $pathName): Interfaces\Engine
    {
        $this->twig->getLoader()->addPath($pathName);

        return $this;
    }

    /**
     *
     */
    public function append(string $variable, $value): void
    {
        d($this->variables);
    }

    /**
     *
     */
    public function getBreadcrumb(): array
    {
        return $this->breadcrumb ?? [];
    }

    /**
     *
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     *
     */
    public function getViewHelper(
        $viewhelper,
        array $params = null
    ): AbstractViewhelper
    {
        $viewhelper = str_replace('/', '\\', $viewhelper);


        if (substr($viewhelper, 0, 4) == 'Ext\\') {
            
            $x = explode('\\', $viewhelper);
            $helperClass = array_pop($x);
            
            $class = '\\Frootbox\\' . implode('\\', $x) . '\\Viewhelper\\' . $helperClass;
        }
        else {
        
            $class = '\\Frootbox\\View\\Viewhelper\\' . $viewhelper;
        }

        if (!class_exists($class) and !empty($params['createDummyOnFail'])) {

            $class = \Frootbox\View\Viewhelper\Dummy::class;
        }
                
        try {

            if (!empty($params['singleton'])) {
                $viewhelper = $this->container->get($class);
            }
            else {
                $viewhelper = $this->container->make($class);
            }

            if (!empty($params)) {
                $viewhelper->setParameters($params);
            }
        }
        catch ( \Exception $e ) {
            d($e->getMessage());
        }
        return $viewhelper;
    }

    /**
     *
     */
    public function pushToGlobalArray($arrayName, $elemet): void
    {
        $array = $this->get($arrayName) ?? [ ];

        $array[] = $elemet;

        $this->set($arrayName, $array);
    }


    /**
     *
     */
    public function render ( string $filename, array $variables = null ): string {

        $data = array_replace_recursive($this->variables, $variables ?? []);

        $data['__FILE__'] = $filename;

        return $this->twig->render($filename, $data);
    }

    /**
     * @param $attribute
     * @return mixed|null
     */
    public function get( $attribute )
    {
        return $this->variables[$attribute] ?? null;
    }

    /**
     *
     */
    public function set($variable, $value = null): void
    {
        if (!is_array($variable)) {

            $this->variables[$variable] = $value;
        }
        else {

            $this->variables = array_replace_recursive($this->variables, $variable);
        }
    }


    /**
     *
     */
    public function setContainer( \DI\Container $container): Interfaces\Engine {

        $this->container = $container;

        return $this;
    }

}
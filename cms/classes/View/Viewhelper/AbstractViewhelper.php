<?php


namespace Frootbox\View\Viewhelper;


abstract class AbstractViewhelper extends \Frootbox\GenericObject
{
    protected $container;
    protected $arguments = [ ];
    protected $parameters = [ ];

    /**
     * @param \DI\Container $container
     * @param array|null $params
     */
    public function __construct ( \DI\Container $container )
    {
        $this->container = $container;

        if (method_exists($this, 'onInit')) {
            $this->container->call([ $this, 'onInit' ]);
        }
    }

    /**
     *
     */
    public function __call ( $method, array $arguments = null )
    {
        if (method_exists($this, $method . 'Action')) {

            $injectArguments = [];

            if (!empty($this->arguments[$method])) {
                    
                foreach ($this->arguments[$method] as $index => $name) {
    
                    if (is_array($name)) {
                        $default = $name['default'] ?? null;
                        $name = $name['name'];
                    }
                    else {
                        $default = null;
                    }
    
                    if (!isset($arguments[$index]) and $default === null) {
                        continue;
                    }
    
                    $injectArguments[$name] = $arguments[$index] ?? $default;
    
                }
            }

            return $this->container->call([ $this, $method . 'Action'], $injectArguments);
        }

        throw new \Frootbox\Exceptions\RuntimeError('Method ' . $method . ' not callable.');
    }


    /**
     *
     */
    public function setParameters ( array $parameters ): AbstractViewhelper {

        $this->parameters = $parameters;

        return $this;
    }
}
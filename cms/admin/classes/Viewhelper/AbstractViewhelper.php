<?php
/**
 *
 */

namespace Frootbox\Admin\Viewhelper;


class AbstractViewhelper implements \Frootbox\View\Viewhelper\Interfaces\Viewhelper {

    protected $container;
    protected $arguments;
    protected $parameters = [ ];

    /**
     *
     */
    public function __construct ( \Psr\Container\ContainerInterface $container ) {

        $this->container = $container;
    }


    /**
     *
     */
    public function __call ( $method, array $arguments = null ) {

        $method = $method . 'Action';

        if (!method_exists($this, $method)) {
            throw new \Frootbox\Exceptions\RuntimeError('Method "' . $method . '" not callable."');
        }

        $this->setArguments($arguments);

        return $this->container->call([ $this, $method ]);
    }


    /**
     *
     */
    protected function getArgumenets ( ) {

        $action = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1];

        $class = new \ReflectionClass(get_called_class());
        $method = $class->getMethod($action['function']);

        $doc = $method->getDocComment();

        $factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docblock = $factory->create($doc);

        $arguments = [];

        foreach ($docblock->getTags() as $index => $tag) {

            $arguments[$tag->getVariableName()] = $this->arguments[$index] ?? null;
        }

        return $arguments;
    }


    /**
     *
     */
    protected function setArguments ( $argumenets ) {

        $this->arguments = $argumenets;
    }


    /**
     *
     */
    public function setParameters ( array $parameters ): AbstractViewhelper
    {

        $this->parameters = $parameters;

        return $this;
    }
}
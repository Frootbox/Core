<?php
/**
 *
 */

namespace Frootbox\Admin\Viewhelper;


class Delegator extends AbstractViewhelper {

    /**
     *
     */
    public function __call ( $method, array $arguments = null ) {

        $object = $this->parameters['object'];

        if (!isset($arguments[0])) {
            $arguments[0] = [ ];
        }

        // Perform interface action
        $result = $this->container->call([ $object, $method ], $arguments[0]);

        return $result;
    }
}


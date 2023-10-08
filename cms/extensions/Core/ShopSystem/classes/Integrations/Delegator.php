<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Integrations;

class Delegator
{
    protected array $integrations = [];

    /**
     * @param \Frootbox\Config\Config $configuration
     * @param \DI\Container $container
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(
        protected \Frootbox\Config\Config $configuration,
        protected \DI\Container $container,
    )
    {
        if (!empty($this->configuration->get('Ext.Core.ShopSystem.Integrations'))) {

            foreach ($this->configuration->get('Ext.Core.ShopSystem.Integrations') as $integrationClass) {
                $this->integrations[] = $this->container->get($integrationClass);
            }
        }
    }

    /**
     *
     */
    public function __call(string $method, array $parameters)
    {
        $integration = null;

        foreach ($this->integrations as $xintegration) {
            if ($xintegration->isActionRegistered($method)) {
                $integration = $xintegration;
                break;
            }
        }

        if ($integration === null) {

            if (substr($method, 0, 3) == 'can') {
                return false;
            }

            throw new \Exception('Action ' . $method . ' is not registered in any integration.');
        }

        switch (count($parameters)) {
            case 0:
                return $integration->$method();

            case 1:
                return $integration->$method($parameters[0]);
        }
    }

}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Integrations;

abstract class AbstractIntegration implements \Frootbox\Ext\Core\ShopSystem\Integrations\IntegrationInterface
{
    protected array $registeredActions = [];

    /**
     * @param string $action
     * @return bool
     */
    public function isActionRegistered(string $action): bool
    {
        return in_array($action, $this->registeredActions);
    }
}

<?php
/**
 *
 */

namespace Frootbox\Persistence;

abstract class AbstractConfigurableRow extends AbstractRow
{
    use Traits\Config;

    /**
     *
     */
    public function save(): \Frootbox\Db\Row
    {
        $this->data['config'] = json_encode($this->config);

        return parent::save();
    }
}

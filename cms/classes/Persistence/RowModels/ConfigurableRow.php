<?php


namespace Frootbox\Persistence\RowModels;


class ConfigurableRow extends \Frootbox\Db\Row
{
    use \Frootbox\Persistence\Traits\Config;

    /**
     *
     */
    public function __construct(array $record = null, \Frootbox\Db\Db $db = null)
    {
        parent::__construct($record, $db);

        if (!empty($this->data['config']) and !is_array($this->data['config'])) {

            $this->config = json_decode($this->data['config'], true);
        }
    }

    /**
     *
     */
    public function save(): \Frootbox\Db\Row
    {
        $this->data['config'] = json_encode($this->config);

        return parent::save();
    }
}

<?php
/**
 *
 */
namespace Frootbox\Persistence\RowModels;


class ConfigurableNestedSet extends \Frootbox\Db\Rows\NestedSet {

    use \Frootbox\Persistence\Traits\Config;

    /**
     *
     */
    public function __construct ( array $record = null, \Frootbox\Db\Db $db = null ) {

        parent::__construct($record, $db);

        if (!empty($this->data['config'])) {

            $this->config = (!is_array($this->data['config']) ? json_decode($this->data['config'], true) : $this->data['config']);
        }
    }


    /**
     *
     */
    public function save ( array $options = null ): \Frootbox\Db\Row
    {
        $this->data['config'] = json_encode($this->config);

        return parent::save();
    }
}
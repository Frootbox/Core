<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class Categories extends \Frootbox\Db\Models\NestedSet
{
    protected $table = 'categories';
    
    /**
     *
     */
    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        if (!empty($this->class)) {
            $params['where']['className'] = $this->class;
        }

        return parent::fetch($params);
    }

    /**
     * 
     */
    public function insertRoot(\Frootbox\Db\Rows\NestedSet $rootNode): \Frootbox\Db\Row
    {
        if (empty($rootNode->getDataRaw('className'))) {

            $rootNode->setData([
                'className' => get_class($rootNode)
            ]);
        }

        return parent::insertRoot($rootNode);
    }
}

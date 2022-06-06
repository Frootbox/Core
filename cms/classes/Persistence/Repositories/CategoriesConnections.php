<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class CategoriesConnections extends \Frootbox\Db\Model
{
    protected $table = 'categories_2_items';
    protected $class = \Frootbox\Persistence\CategoryConnection::class;
    protected $itemClass = null;
    protected $categoryClass = null;

    /**
     *
     */
    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        if (!empty($this->categoryClass)) {
            $params['where']['categoryClass'] = $this->categoryClass;
        }

        if (!empty($this->itemClass)) {
            $params['where']['itemClass'] = $this->itemClass;
        }

        return parent::fetch($params);
    }
}
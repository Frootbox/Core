<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Content\Repositories;

/**
 *
 */
class ContentElements extends \Frootbox\Db\Model
{
    protected $table = 'content_elements';
    protected $class = \Frootbox\Persistence\ContentElement::class;

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        $row->setClassName(get_class($row));

        return parent::insert($row);
    }
}

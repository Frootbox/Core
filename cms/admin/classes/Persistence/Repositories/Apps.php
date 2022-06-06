<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Admin\Persistence\Repositories;

/**
 *
 */
class Apps extends \Frootbox\Db\Model
{
    protected $table = 'admin_apps';

    /**
     *
     */
    public function insert ( \Frootbox\Db\Row $row ): \Frootbox\Db\Row
    {
        $row->setClassName(get_class($row));

        if (empty($row->getTitle())) {

            $data = require $row->getPath() . 'resources/private/language/de-DE.php';

            $row->setTitle($data['App.Title']);
        }

        if (empty($row->getIcon())) {

            $data = require $row->getPath() . 'resources/private/language/de-DE.php';

            $row->setIcon($data['App.Icon']);
        }

        return parent::insert($row);
    }
}

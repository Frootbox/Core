<?php
/**
 *
 */

namespace Frootbox\Ext\Core\UserManager\Utilities\Import;

class User extends \Frootbox\Admin\AbstractImportAdapter
{
    /**
     *
     */
    public function execute(
        \Frootbox\Persistence\Repositories\Users $users
    )
    {

        foreach ($this->items as $item) {

            unset($item['lastClick']);

            $users->insert(new \Frootbox\Persistence\User($item));
        }

        die("Import successfull.");
    }
}

<?php 
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 * 
 */
class Users extends \Frootbox\Db\Model
{
    protected $table = 'users';
    protected $class = \Frootbox\Persistence\User::class;

    /**
     *
     */
    public function insert(
        \Frootbox\Db\Row $row
    ): \Frootbox\Db\Row
    {
        // Check if username is unique
        $result = $this->fetch([
            'where' => [
                'email' => $row->getEmail()
            ],
            'limit' => 1
        ]);

        if ($result->getCount()) {
            throw new \Frootbox\Exceptions\InputInvalid('Ein Benutzer mit dem Login "' . $row->getEmail() . '" existiert bereits.');
        }

        return parent::insert($row);
    }
}

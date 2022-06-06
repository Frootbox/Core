<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class Tags extends \Frootbox\Db\Model
{
    protected $table = 'tags';
    protected $class = \Frootbox\Persistence\Tag::class;
}

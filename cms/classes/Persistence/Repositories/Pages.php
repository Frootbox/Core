<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;


/**
 *
 */
class Pages extends \Frootbox\Db\Models\NestedSet
{
    protected $table = 'pages';
    protected $class = \Frootbox\Persistence\Page::class;
}

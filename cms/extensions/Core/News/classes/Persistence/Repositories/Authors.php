<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\News\Persistence\Repositories;

/**
 *
 */
class Authors extends \Frootbox\Persistence\Repositories\Users
{
    protected $class = \Frootbox\Ext\Core\News\Persistence\Author::class;
}
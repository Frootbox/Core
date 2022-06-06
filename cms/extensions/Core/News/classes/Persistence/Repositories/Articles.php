<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\News\Persistence\Repositories;

/**
 *
 */
class Articles extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    protected $class = \Frootbox\Ext\Core\News\Persistence\Article::class;

    use \Frootbox\Persistence\Repositories\Traits\Tags;
}
<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Persistence\Repositories;

class CategoriesConnections extends \Frootbox\Persistence\Repositories\CategoriesConnections
{
    protected $class = \Frootbox\Ext\Core\News\Persistence\CategoryConnection::class;
    protected $itemClass = \Frootbox\Ext\Core\News\Persistence\Article::class;
    protected $categoryClass = \Frootbox\Ext\Core\News\Persistence\Category::class;
}

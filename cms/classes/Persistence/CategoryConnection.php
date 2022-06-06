<?php
/**
 *
 */

namespace Frootbox\Persistence;

class CategoryConnection extends AbstractConfigurableRow
{
    protected $table = 'categories_2_items';
    protected $model = Repositories\CategoriesConnections::class;
}
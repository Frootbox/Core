<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}

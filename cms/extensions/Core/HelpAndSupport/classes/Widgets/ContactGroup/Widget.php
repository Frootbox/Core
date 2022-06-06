<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Widgets\ContactGroup;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories
    ): ?\Frootbox\Ext\Core\HelpAndSupport\Persistence\Category
    {
        if (empty($this->getConfig('categoryId'))) {
            return null;
        }

        $category = $categories->fetchById($this->getConfig('categoryId'));

        return $category;
    }
}

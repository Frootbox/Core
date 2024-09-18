<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;
    protected $connectionModel = Repositories\CategoriesConnections::class;
    protected $connectionClass = CategoryConnection::class;
    protected $itemModel = Repositories\Recipes::class;

    /**
     *
     */
    public function delete()
    {
        $recipes = $this->getDirectItems();

        foreach ($recipes as $recipe) {
            $recipe->delete();
        }

        $aliases = $this->db->getModel(\Frootbox\Persistence\Repositories\Aliases::class);
        $result = $aliases->fetch([
            'where' => [
                'alias' => $this->getAlias()
            ]
        ]);

        $result->map('delete');

        parent::delete();
    }

    /**
     * Generate items alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (empty($this->getParentId())) {
            return null;
        }

        $trace = $this->getTrace();
        $trace->shift();

        $vd = [ ];

        foreach ($trace as $child) {
            $vd[] = $child->getTitle();
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => $vd,
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showCategory',
                'categoryId' => $this->getId()
            ])
        ]);
    }
}
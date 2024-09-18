<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2020-07-13
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence;

class Recipe extends \Frootbox\Persistence\AbstractRow
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Config;

    protected $table = 'recipes';
    protected $model = Repositories\Recipes::class;

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showRecipe',
                'recipeId' => $this->getId()
            ])
        ]);
    }
}

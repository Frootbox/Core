<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Persistence;

class Group extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Groups::class;

    /**
     *
     */
    public function delete()
    {
        // Drop fields
        $result = $this->getFields(new \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields($this->getDb()));
        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getColumns()
    {
        return $this->config['columns'] ?? 12;
    }

    /**
     *
     */
    public function getFields(): \Frootbox\Db\Result
    {
        $fields = $this->db->getRepository(\Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields::class);

        // Fetch fields
        $result = $fields->fetch([
            'where' => [
                'pluginId' => $this->getPluginId(),
                'parentId' => $this->getId(),
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}

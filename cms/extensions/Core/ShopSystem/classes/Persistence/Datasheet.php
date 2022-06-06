<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Datasheet extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Datasheets::class;

    /**
     * Delete datasheet
     */
    public function delete()
    {
        // Cleanup fields
        $fields = $this->db->getModel(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);
        $result = $fields->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getFields(): \Frootbox\Db\Result
    {
        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        // Fetch fields
        $result = $fieldsRepository->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getGroups(): \Frootbox\Db\Result
    {
        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup::class);

        // Fetch fields
        $result = $fieldsRepository->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}

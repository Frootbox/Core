<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence;

class Group extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Groups::class;

    /**
     *
     */
    public function delete()
    {
        // Drop fields
        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields::class);

        $result = $fieldsRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getColumns(): array
    {
        $columnsString = $this->getColumnsString();

        $columns = explode('-', $columnsString);

        $list = [];

        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields::class);

        $result = $fieldsRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        foreach ($columns as $index => $column) {

            $col = [
                'columns' => $column,
                'fields' => [

                ],
            ];

            foreach ($result as $field) {

                if (($index + 1) == $field->getConfig('column')) {
                    $col['fields'][] = $field;
                }
            }

            $list[] = $col;
        }

        return $list;
    }

    /**
     *
     */
    public function getColumnsString(): string
    {
        return $this->getConfig('columns') ?? '12';
    }

    /**
     *
     */
    public function getFields(): \Frootbox\Db\Result
    {
        $fields = $this->db->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields::class);

        // Fetch fields
        $result = $fields->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getForm(): \Frootbox\Ext\Core\ContactForms\Persistence\Form
    {
        $formsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms::class);
        $form = $formsRepository->fetchById($this->getParentId());

        return $form;
    }

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}

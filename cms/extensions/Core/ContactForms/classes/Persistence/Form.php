<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence;

class Form extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Forms::class;

    /**
     * @return void
     */
    public function delete()
    {
        // Clean up groups
        $this->getGroups()->map('delete');

        return parent::delete();
    }

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     * 
     */
    public function getAction(array $payload = null): string
    {
        $payload['formId'] = $this->getId();

        return SERVER_PATH . 'static/Ext/Core/ContactForms/Form/ajaxSubmit?' . http_build_query($payload);
    }

    /**
     *
     */
    public function getGroups(): \Frootbox\Db\Result
    {
        $respository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups::class);

        $result = $respository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ]
        ]);

        return $result;
    }
}

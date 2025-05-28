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
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function delete()
    {
        // Start database transaction
        $this->getDb()->transactionStart();

        // Clean up groups
        $this->getGroups()->map('delete');

        // Clear logs
        $logsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs::class);
        $logs = $logsRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        $logs->map('delete');

        // Delete record itself
        parent::delete();

        // Commit database transaction
        $this->getDb()->transactionCommit();
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
     * @return \Frootbox\Ext\Core\ContactForms\Persistence\Group[]
     * @throws \Frootbox\Exceptions\RuntimeError
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

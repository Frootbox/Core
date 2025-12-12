<?php
/**
 * @noinspection SqlNoDataSourceInspection
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Persistence;

class Contact extends \Frootbox\Persistence\AbstractPerson
{
    use \Frootbox\Persistence\Traits\Alias;

    protected $model = Repositories\Contacts::class;

    /**
     * @param int|null $parentId
     * @return \Frootbox\Db\Result
     */
    public function getCategories(
        int $parentId = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            c.*,
            i.config as connConfig,
            i.id as connId
        FROM
            categories c,
            categories_2_items i
        WHERE            
            i.categoryId = c.id AND
            i.itemId = ' . $this->getId() . ' AND
            c.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' ';

        if ($parentId) {
            $sql .= ' AND c.parentId = ' . $parentId;

        }

        $sql .= ' ORDER BY
            i.orderId DESC';

        // Fetch contacts
        $model = new \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts($this->db);
        $result = $model->fetchByQuery($sql);

        return $result;
    }

    /**
     * @return array|null
     */
    public function getConnConfig(): ?array
    {
        if (empty($this->data['connConfig'])) {
            return null;
        }

        return json_decode($this->data['connConfig'], true);
    }

    /**
     * Generate contacts alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noPersonsDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getFirstName() . ' ' . $this->getLastName(),
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showContact',
                'contactId' => $this->getId(),
            ]),
        ]);
    }

    /**
     * @return string|null
     */
    public function getPosition(): ?string
    {
        $config = $this->getConnConfig() ?? [];

        if (!empty($config['position'])) {
            return $config['position'];
        }

        return parent::getPosition();
    }
}

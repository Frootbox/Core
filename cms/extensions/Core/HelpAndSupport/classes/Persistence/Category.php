<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Persistence;

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
        if (!empty($this->getConfig('noCategoriesDetailPage'))) {
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

    /**
     *
     */
    public function getContacts(): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            p.*,
            i.config as connConfig,
            i.id as connId
        FROM
            persons p,
            categories_2_items i
        WHERE
            i.categoryId = ' . $this->getId() . ' AND
            i.itemId = p.id AND
            p.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . '
        ORDER BY
            i.orderId DESC';

        // Fetch contacts
        $model = new \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts($this->db);
        $result = $model->fetchByQuery($sql);

        return $result;
    }
}

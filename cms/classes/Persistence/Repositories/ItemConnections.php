<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class ItemConnections extends \Frootbox\Db\Model
{
    protected $class = \Frootbox\Persistence\ItemConnection::class;
    protected $table = 'item_connections';

    /**
     * @param \Frootbox\Persistence\AbstractRow $row
     * @param \Frootbox\Persistence\AbstractRow $base
     * @param string|null $uid
     * @return \Frootbox\Persistence\ItemConnection
     */
    public function connect(
        \Frootbox\Persistence\AbstractRow $row,
        \Frootbox\Persistence\AbstractRow $base,
        string $uid = null,
        array $parameters = null,
    ): \Frootbox\Persistence\ItemConnection
    {
        // Compose connection
        $connectionData = [
            'itemId' => $row->getId(),
            'itemClass' => get_class($row),
            'baseId' => $base->getId(),
            'baseClass' => get_class($base),
            'uid' => $uid,
        ];

        if (!empty($parameters['visibility'])) {
            $connectionData['visibility'] = $parameters['visibility'];
        }

        if (!empty($parameters['orderId'])) {
            $connectionData['orderId'] = $parameters['orderId'];
        }

        $itemConnection = new \Frootbox\Persistence\ItemConnection($connectionData);

        // Insert new connection
        $item = $this->insert($itemConnection);

        return $item;
    }


    public function dropEntityOnBase(
        \Frootbox\Persistence\AbstractRow $row,
        \Frootbox\Persistence\AbstractRow $base,
    ): void
    {
        // Build sql
        $sql = 'DELETE FROM item_connections WHERE 
            itemId = ' . $row->getId() . ' AND
            itemClass = :itemClass AND
            baseClass = :baseClass AND
            baseId = ' . $base->getId();


        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':itemClass', get_class($row));
        $stmt->bindValue(':baseClass', get_class($base));

        $stmt->execute();
    }

    public function getBasesByEntitiy(
        \Frootbox\Persistence\AbstractRow $row,
        \Frootbox\Db\Model $baseRepository,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            x.id as connectionId,
            x.visibility as connectionVisibility
        FROM
            ' . $baseRepository->getTable() . ' e,
            item_connections x
        WHERE
            x.itemId = ' . $row->getId() . ' AND
            x.itemClass = :itemClass AND
            x.baseClass = :baseClass AND
            x.baseId = e.id';

        if (!empty($parameters['order'])) {
            $sql .= ' ORDER BY e.' . $parameters['order'];

        }

        $result = $baseRepository->fetchByQuery($sql, [
            'itemClass' => get_class($row),
            'baseClass' => $baseRepository->getClass(),
        ]);

        return $result;
    }

    /**
     *
     */
    public function getEntitiesByBase(
        \Frootbox\Db\Model $repository,
        \Frootbox\Db\Row $base,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            x.id as connectionId,
            x.visibility as connectionVisibility
        FROM
            ' . $repository->getTable() . ' e,
            item_connections x
        WHERE
        
            x.itemId = e.id AND 
            x.itemClass = :itemClass AND
            x.baseCLass = :baseClass AND
            x.baseId = :baseId
        ORDER BY
            x.orderId DESC, x.id ASC';

        $result = $repository->fetchByQuery($sql, [
            'itemClass' => $repository->getClass(),
            'baseClass' => get_class($base),
            'baseId' => $base->getId(),
        ]);

        return $result;
    }

    /**
     *
     */
    public function getEntitiesByBaseVisible(
        \Frootbox\Db\Model $repository,
        \Frootbox\Db\Row $base,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            x.id as connectionId,
            x.visibility as connectionVisibility
        FROM
            ' . $repository->getTable() . ' e,
            item_connections x
        WHERE
            x.visibility >= ' . (IS_EDITOR ? 1 : 2) . ' AND
            e.visibility >= ' . (IS_EDITOR ? 1 : 2) . ' AND
            x.itemId = e.id AND 
            x.itemClass = :itemClass AND
            x.baseCLass = :baseClass AND
            x.baseId = :baseId
        ORDER BY
            x.orderId DESC';

        $result = $repository->fetchByQuery($sql, [
            'itemClass' => $repository->getClass(),
            'baseClass' => get_class($base),
            'baseId' => $base->getId(),
        ]);

        return $result;
    }

    /**
     *
     */
    public function getEntitiesByUid(\Frootbox\Db\Model $repository, string $uid): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            x.id as connectionId,
            x.visibility as connectionVisibility
        FROM
            ' . $repository->getTable() . ' e,
            item_connections x
        WHERE
            x.itemId = e.id AND 
            x.itemClass = :itemClass AND
            x.uid = :uid';

        $result = $repository->fetchByQuery($sql, [
            'itemClass' => $repository->getClass(),
            'uid' => $uid,
        ]);

        return $result;
    }

    /**
     *
     */
    public function isConnected(
        \Frootbox\Persistence\AbstractRow $row,
        \Frootbox\Persistence\AbstractRow $base,
        string $uid = null
    ): bool
    {
        $repository = $row->getRepository();

        // Build sql
        $sql = 'SELECT
            e.*,
            x.id as connectionId,
            x.visibility as connectionVisibility
        FROM
            ' . $repository->getTable() . ' e,
            item_connections x
        WHERE
            e.id = ' . $row->getId() . ' AND
            x.itemId = e.id AND 
            x.itemClass = :itemClass AND
            x.baseCLass = :baseClass AND
            x.baseId = ' . $base->getId() . '
        LIMIT 1';


        $result = $repository->fetchByQuery($sql, [
            'itemClass' => $repository->getClass(),
            'baseClass' => get_class($base),
        ]);

        return ($result->getCount() == 1);
    }
}

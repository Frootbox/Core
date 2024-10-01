<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\Teaser;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getAddresses(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        $order = null,
        $limit = null
    ): ?\Frootbox\Db\Result
    {
        if ($order === null) {
            $order = 'title ASC';
        }

        if ($limit === null) {
            $limit = $this->config['limit'] ?? 10;
        }

        // Fetch addresses
        $result = $addressesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
            'order' => [ $order ],
            'limit' => $limit
        ]);

        return $result;
    }

    /**
     *
     */
    public function getAddressesByUidText(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        $uidSegment,
        $order = null,
        $limit = 20
    ): ?\Frootbox\Db\Result
    {
        // Generate sql
        $sql = 'SELECT 
            l.*,
            MAX(t.text)
        FROM
            locations l,
            content_texts t
        WHERE
            t.uid = CONCAT("' . str_replace('\\', '-', get_class($addressesRepository)) . ':", l.id, ":' . $uidSegment . '")
        GROUP BY
            l.id';


        if ($order !== null) {
            $sql .= ' ORDER BY ' . $order;
        }

        $sql .= ' LIMIT ' . (int) $limit;

        // Fetch addresses
        $result = $addressesRepository->fetchByQuery($sql);

        return $result;
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}

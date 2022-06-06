<?php
/**
 *
 */

namespace Frootbox\Persistence;

class ItemConnection extends \Frootbox\Db\Row
{
    use Traits\Visibility;
    use Traits\Uid;

    protected $table = 'item_connections';
    protected $model = Repositories\ItemConnections::class;

    /**
     *
     */
    public function getEntity(): \xxx
    {
        d("OL");
    }
}

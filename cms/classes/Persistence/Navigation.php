<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Navigation extends AbstractRow
{
    protected $table = 'navigations';
    protected $model = Repositories\Navigations::class;

    use Traits\Visibility;

    /**
     * @param array|null $params
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getItems(array $params = null): \Frootbox\Db\Result
    {
        $language = $params['language'] ?? GLOBAL_LANGUAGE;

        // Fetch navigations items
        $navigationsItems = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\NavigationsItems::class);

        $where = [
            'navId' => $this->getId(),
            'parentId' => 0,
            'language' => $language,
        ];

        if (empty($params['ignoreVisibility'])) {
            $where[] = new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2));
        }

        $result = $navigationsItems->fetch([
            'where' => $where,
            'order' => [
                'orderID DESC',
                'id ASC',
            ],
        ]);

        return $result;
    }
}

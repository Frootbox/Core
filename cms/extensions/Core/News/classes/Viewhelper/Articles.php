<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Viewhelper;

class Articles extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getArticles' => [
            'params'
        ]
    ];

    /**
     *
     */
    public function getArticlesAction(
        array $params = null,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articleRepository,
    ): \Frootbox\Db\Result
    {
        if (!empty($params['tags'])) {
            $params['tags'] = array_filter($params['tags'], fn($value) => !is_null($value) && $value !== '');
        }

        if (!empty($params['tags'])) {

            $result = $articleRepository->fetchByTags($params['tags'], [
                'order' => [ 'date DESC' ],
            ]);
        }
        else {

            if (empty($params['limit'])) {
                $params['limit'] = 10;
            }

            $where = [
                new \Frootbox\Db\Conditions\LessOrEqual('dateStart', date('Y-m-d H:i:s')),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
            ];

            if (empty($params['pluginId'])) {
                $where['pluginId'] = $params['pluginId'];
            }

            $result = $articleRepository->fetch([
                'where' => $where,
                'limit' => $params['limit'],
                'order' => [ 'dateStart DESC' ],
            ]);
        }

        return $result;
    }
}

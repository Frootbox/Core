<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Viewhelper;

class References extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getReferences' => [

        ],
        'getByTags' => [
            'tags',
            'params',
        ],
    ];

    /**
     *
     */
    public function getReferencesAction(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): \Frootbox\Db\Result
    {
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }

        return $referencesRepository->fetch();
    }

    /**
     *
     */
    public function getByTagsAction(
        array $tags,
        array $params = null,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): \Frootbox\Db\Result
    {
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }

        return $referencesRepository->fetchByTags($tags, [
            'limit' => $params['limit'],
        ]);
    }



}

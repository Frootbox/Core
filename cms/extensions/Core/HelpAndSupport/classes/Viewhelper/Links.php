<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Viewhelper;

class Links extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getLinks' => [
            'parameters'
        ],
        'getLinksByTag' => [
            'tag'
        ],
    ];

    /**
     * @param array|null $parameters
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $linkRepository
     * @return \Frootbox\Db\Result
     */
    public function getLinksAction(
        array $parameters = null,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $linkRepository,
    ): \Frootbox\Db\Result
    {
        $where = [
            new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
        ];

        if (!empty($parameters['pluginId'])) {
            $where['pluginId'] = $parameters['pluginId'];
        }

        // Fetch links
        $result = $linkRepository->fetch([
            'where' => $where,
            'order' => [ 'orderId ASC' ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getLinksByTagAction(
        string $tag,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $linkRepository,
    ): \Frootbox\Db\Result
    {
        // Fetch links
        $links = $linkRepository->fetchByTag($tag, [
         //   'complyVisibility' => true,
        ]);

        return $links;
    }
}

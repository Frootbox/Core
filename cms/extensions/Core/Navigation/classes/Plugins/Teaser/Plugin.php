<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Teaser;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\Uid;

    protected $publicActions = [
        'index',
        'showTeaser'
    ];

    /**
     * @param \DI\Container $container
     * @param \Frootbox\Persistence\AbstractRow $ancestor
     */
    public function cloneContentFromAncestor(\DI\Container $container, \Frootbox\Persistence\AbstractRow $ancestor): void
    {
        $teasersRepository = $container->get(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers::class);
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        $result = $teasersRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ]
        ]);

        foreach ($result as $teaser) {

            // Duplicate teaser
            $newItem = $teaser->duplicate();

            $newItem->setPluginId($this->getId());
            $newItem->setPageId($this->getPage()->getId());
            $newItem->save();

            $cloningMachine->cloneContentsForElement($newItem, $teaser->getUidBase());
        }
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers
    ): void
    {
        // Fetch teasers
        $result = $teasers->fetch([
            'where' => [
                'pageId' => $this->getPageId(),
                'pluginId' => $this->getId()
            ]
        ]);

        // Drop teasers
        $result->map('delete');
    }
    
    /**
     * Get plugins base path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;        
    }

    /**
     *
     */
    public function getTeasers(array $parameters = null): \Frootbox\Db\Result
    {
        $teasersRepository = $this->getDb()->getRepository(Persistence\Repositories\Teasers::class);

        // Fetch teasers
        $result = $teasersRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', IS_EDITOR ? 1 : 2)
            ]
        ]);

        foreach ($result as $index => $teaser) {

            if (!empty($teaser->getConfig('skipLanguages.' . GLOBAL_LANGUAGE))) {
                $result->removeByIndex($index);
            }
        }

        $result->rewind();

        return $result;
    }

    /**
     * Show teaser
     */
    public function showTeaserAction(
        Persistence\Repositories\Teasers $teasers,
    ): Response
    {
        // Fetch teaser
        $teaser = $teasers->fetchById($this->getAttribute('teaserId'));

        if ($teaser->getVisibility() < (IS_EDITOR ? 1 : 2)) {
            throw new \Exception('File not found.');
        }

        return new Response([
            'teaser' => $teaser,
        ]);
    }
}

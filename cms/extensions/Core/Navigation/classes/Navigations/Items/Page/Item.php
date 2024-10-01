<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\Page;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getChildren(): \Frootbox\Db\Result
    {
        // Fetch pages
        $pagesRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
        $pages = $pagesRepository->fetch([
            'where' => [
                'parentId' => $this->getConfig('pageId'),
                new \Frootbox\Db\Conditions\NotEqual('visibility', 'Hidden')
            ],
            'order' => [
                'lft ASC',
            ],
        ]);

        return $pages;
    }

    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('pageId'))) {
            return '#unconfigured-navigation-item';
        }

        $url = 'fbx://page:' . $this->getConfig('pageId');

        if (!empty($this->getConfig('anchor'))) {
            $url .= '#' . $this->getConfig('anchor');
        }

        return $url;
    }

    /**
     *
     */
    public function getItems(): ?\Frootbox\Db\Result
    {
        if (empty($this->getConfig('showChildren'))) {
            return parent::getItems();
        }

        if (empty($this->getConfig('pageId'))) {
            return null;
        }

        // Fetch pages
        $pagesRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
        $result = $pagesRepository->fetch([
            'where' => [
                'parentId' => $this->getConfig('pageId'),
                'visibility' => 'Public',
            ],
            'order' => [
                'lft ASC',
            ],
        ]);

        $items = new \Frootbox\Db\Result([], $this->getDb());
        $items->setClassName(\Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy::class);

        foreach ($result as $page) {

            // Compose navigation dummy
            $dummy = new \Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy([
                'href' => $page->getUri(),
                'title' => $page->getTitle(GLOBAL_LANGUAGE),
            ]);

            if ($page->getConfig('pageType') == 'Frame' and $page->getConfig('frame.forceBlankWindow')) {
                $dummy->setTarget('_blank');
            }

            if (!empty($page->getConfig('variables'))) {
                $dummy->addConfig($page->getConfig('variables'));
            }

            $items->push($dummy);
        }

        return $items;
    }

    /**
     *
     */
    public function getPages(): \Frootbox\Db\Result
    {
        // Fetch pages
        $pagesRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
        $pages = $pagesRepository->fetch([
            'order' => [
                'lft ASC',
            ],
        ]);

        return $pages;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function initialize(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): void
    {
        // Fetch matching pages
        $result = $pagesRepository->fetch([
            'where' => [
                'title' => $this->getTitle(),
            ],
        ]);

        if ($result->getCount() == 1) {

            $page = $result->current();

            $this->addConfig([
                'pageId' => $page->getId(),
            ]);
            $this->save();
        }
    }

    /**
     * @param array|null $parameters
     * @return bool
     */
    public function isActive(array $parameters = null): bool
    {
        if (empty($parameters['page']) or empty($this->config['pageId'])) {
            return false;
        }

        // Obtain page object
        $page = $parameters['page'];

        if ($this->config['pageId'] == $page->getId()) {
            return true;
        }


        if ($this->config['pageId'] == $page->getParentId() and $page->getParent()->getParentId() != 0) {
            return true;
        }

        if (!empty($parameters['inheritActiveFromeParent']) and $this->config['pageId'] == $page->getParentId() and $page->getParent()->getParentId() != 0) {
            return true;
        }

        // Check left and right
        return false;

        d($parameters);
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'pageId' => $post->get('pageId'),
            'anchor' => $post->get('anchor'),
            'showChildren' => $post->get('showChildren'),
        ]);
    }
}

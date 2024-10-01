<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Navigations\Items\JobsPortal;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('PluginId'))) {
            return '#unconfigured-navigation-item';
        }

        $contentElements = $this->getDb()->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $plugin = $contentElements->fetchById($this->getConfig('PluginId'));

        return $plugin->getActionUri('index');
    }

    public function getItems(): ?\Frootbox\Db\Result
    {
        if (empty($this->getConfig('ShowChildren'))) {
            return parent::getItems();
        }

        if (empty($this->getConfig('PluginId'))) {
            return null;
        }

        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs::class);
        $result = $repository->fetch([
            'where' => [
                'pluginId' => $this->getConfig('PluginId'),
            ],
        ]);

        $items = new \Frootbox\Db\Result([], $this->getDb());
        $items->setClassName(\Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy::class);

        foreach ($result as $job) {

            $items->push(new \Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy([
                'href' => $job->getUri(),
                'title' => $job->getTitle(GLOBAL_LANGUAGE),
            ]));
        }

        return $items;
    }

    /**
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getJobPlugins(): \Frootbox\Db\Result
    {
        $contentElements = $this->getDb()->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $plugins = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Plugin::class,
            ],
        ]);

        return $plugins;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'PluginId' => $post->get('PluginId'),
            'ShowChildren' => $post->get('ShowChildren'),
        ]);
    }
}

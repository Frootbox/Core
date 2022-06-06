<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000002 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixCategoriesMissingPageId',
        'fixCategoriesMissingPluginId'
    ];

    /**
     *
     */
    public function fixCategoriesMissingPageId(
        \Frootbox\Persistence\Repositories\Categories $categories,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements
    ): void
    {
        // Fetch all categories
        $result = $categories->fetch();

        foreach ($result as $category) {

            $data = $category->getData();

            if (!empty($data['pageId']) or empty($data['uid'])) {
                continue;
            }

            if (!preg_match('#\-ContentElements:(\d{1,}):#', $data['uid'], $match)) {
                continue;
            }


            try {

                $plugin = $contentElements->fetchById($match[1]);
                $category->setPageId($plugin->getPageId());

                $category->save();
            }
            catch ( \Exception $e ) {
                // Ignore error here
            }
        }
    }

    /**
     *
     */
    public function fixCategoriesMissingPluginId(
        \Frootbox\Persistence\Repositories\Categories $categories,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements
    ): void
    {
        // Fetch all categories
        $result = $categories->fetch();

        foreach ($result as $category) {

            $data = $category->getData();

            if (!empty($data['pluginId']) or empty($data['uid'])) {
                continue;
            }

            if (!preg_match('#\-ContentElements:(\d{1,}):#', $data['uid'], $match)) {
                continue;
            }

            try {

                $plugin = $contentElements->fetchById($match[1]);

                $category->setPageId($plugin->getPageId());
                $category->setPluginId($plugin->getId());

                $category->save();
            }
            catch ( \Exception $e ) {
                // Ignore error here
            }
        }
    }
}

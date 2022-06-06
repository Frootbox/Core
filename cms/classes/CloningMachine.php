<?php
/**
 *
 */

namespace Frootbox;

class CloningMachine
{
    protected $container;

    /**
     *
     */
    public function __construct(\DI\Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param object $newElement
     * @param string $uidbase
     * @param array $parameters Array containing the optional parameters
     *      $parameters = [
     *          'skipBlocks' => (bool) skip cloning blocks from source
     *      ]
     */
    public function cloneContentsForElement($newElement, string $uidbase, array $parameters = [])
    {
        $textsRepository = $this->container->get(\Frootbox\Persistence\Content\Repositories\Texts::class);
        $widgetsRepository = $this->container->get(\Frootbox\Persistence\Content\Repositories\Widgets::class);
        $filesRepository = $this->container->get(\Frootbox\Persistence\Repositories\Files::class);
        $blocksRepository = $this->container->get(\Frootbox\Persistence\Content\Repositories\Blocks::class);

        // Import files
        $files = $filesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', $uidbase . '%'),
            ],
        ]);

        foreach ($files as $file) {

            $uidData = $newElement->extractUid($file->getDataRaw('uid'));
            $newUid = $newElement->getUid($uidData['segment']);

            // Insert file
            $newFile = $file->duplicate();
            $newFile->setUid($newUid);
            $newFile->save();
        }

        // Import blocks
        if (empty($parameters['skipBlocks'])) {

            // Fetch blocks
            $blocks = $blocksRepository->fetch([
                'where' => [
                    new \Frootbox\Db\Conditions\Like('uid', $uidbase . '%'),
                ],
            ]);

            foreach ($blocks as $block) {

                $uidData = $block->extractUid($block->getDataRaw('uid'));
                $newUid = $newElement->getUid($uidData['segment']);

                $newBlock = clone $block;
                $newBlock->setUid($newUid);
                $newBlock->addConfig(['test' => true]);

                $newBlock = $blocksRepository->insert($newBlock);

                $this->cloneContentsForElement($newBlock, $block->getUidBase());
            }
        }


        // Import texts
        $texts = $textsRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', $uidbase . '%'),
            ],
        ]);

        foreach ($texts as $text) {

            preg_match('#^.*?:\d+:(?P<uidSegment>.*?)$#', $text->getDataRaw('uid'), $match);

            $newUid = $newElement->getUid($match['uidSegment']);

            $newText = clone $text;
            $newText->setUid($newUid);

            if (preg_match_all('#\<figure data\-id\=\"(?P<widgetId>\d+)\"\>\<\/figure\>#', $newText->getText(), $matches)) {

                foreach ($matches['widgetId'] as $index => $widgetId) {

                    $widget = $widgetsRepository->fetchById($widgetId);

                    $nWidget = clone $widget;
                    $nWidget->setTextUid($newUid);

                    $nWidget = $widgetsRepository->insert($nWidget);

                    $textString = $newText->getText();

                    $textString = str_replace($matches[0][$index], '<figure data-id="' . $nWidget->getId() . '"></figure>', $textString);

                    $newText->setText($textString);

                    $this->cloneContentsForElement($nWidget, $widget->getUidBase());
                }
            }

            $textsRepository->insert($newText);
        }
    }

    /**
     *
     */
    public function cloneContent(
        \Frootbox\Persistence\Page $target,
        \Frootbox\Persistence\Page $source
    ): void
    {
        $this->container->call([ $this, 'doClone' ], [
            'target' => $target,
            'source' => $source
        ]);
    }

    /**
     * Clone pages contents from source page into target page
     *
     * @param \Frootbox\Persistence\Page $tPage content receiving page
     * @param \Frootbox\Persistence\Page $source content source page
     * @param array $parameters Array containing the optional parameters
     *      $parameters = [
     *          'skipBlocks' => (bool) skip cloning blocks from source page
     *          'skipPlugins' => (bool) skip cloning plugins from source page
     *      ]
     *
     *
     */
    public function clonePage(
        \Frootbox\Persistence\Page $tPage,
        \Frootbox\Persistence\Page $source,
        array $parameters = [],
    ): void
    {
        $contentElementsRepository = $this->container->get(\Frootbox\Persistence\Content\Repositories\ContentElements::class);

        // Import general contents
        $this->cloneContentsForElement($tPage, $source->getUidBase(), $parameters);

        if (empty($parameters['skipPlugins'])) {

            // Copy plugins
            $sql = 'SELECT
                    e.*
                FROM
                    pages p,
                    content_elements e
                WHERE
                    (
                        ( e.pageId = ' . $source->getId() . ' AND e.pageId = p.id )
                        /*  OR ( e.pageId = p.id AND e.inheritance = "Inherited" AND p.lft < ' . $source->getLft() . ' AND p.rgt > ' . $source->getRgt() . ') */
                    )
                ORDER BY orderId DESC';

            $elements = $contentElementsRepository->fetchByQuery($sql);

            $associations = [];

            foreach ($elements as $element) {

                $newElement = clone $element;

                $newElement->setPageId($tPage->getId());

                $newElement = $contentElementsRepository->insert($newElement);

                $associations[$element->getId()] = $newElement->getId();

                if (method_exists($newElement, 'getUidBase')) {

                    // Adjust socket if element belongs to grid view
                    if (preg_match('#^(.*?)_([0-9]+)_([0-9]+)$#', $newElement->getSocket(), $match)) {

                        $newSocket = $match[1] . '_' . $associations[$match[2]] . '_' . $match[3];

                        $newElement->setSocket($newSocket);
                        $newElement->save();
                    }

                    // Import general contents
                    $this->cloneContentsForElement($newElement, $element->getUidBase());
                }

                // Clone elements contents
                if ($newElement instanceof \Frootbox\Persistence\Interfaces\Cloneable) {

                    $this->container->call([$newElement, 'cloneContentFromAncestor'], [
                        'ancestor' => $element,
                    ]);
                }
            }
        }
    }

    /**
     *
     */
    public function doClone(
        \Frootbox\Persistence\Page $target,
        \Frootbox\Persistence\Page $source,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): void
    {
        $target = $pagesRepository->fetchById($target->getId());

        foreach ($target->getOffspring() as $tPage) {

            if (empty($tPage->getConfig('clonedFrom'))) {
                continue;
            }

            $source = $pagesRepository->fetchById($tPage->getConfig('clonedFrom'));

            $this->clonePage($tPage, $source);
        }
    }
}

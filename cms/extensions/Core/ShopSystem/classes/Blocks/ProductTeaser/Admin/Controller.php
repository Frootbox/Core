<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Blocks\ProductTeaser\Admin;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Persistence\Content\Blocks\AbstractAdminController
{
    protected $block;

    /**
     * @param \Frootbox\Persistence\Content\Blocks\Block $block
     */
    public function __construct(\Frootbox\Persistence\Content\Blocks\Block $block)
    {
        $this->block = $block;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\View\Blocks\PreviewRenderer $previewRenderer,
    ): Response
    {
        // Update block
        $this->block->unsetConfig('tags');
        $this->block->addConfig([
            'tags' => $post->get('tags'),
            'limit' => $post->get('limit'),
        ]);

        $this->block->save();

        // Render blocks html
        $html = $previewRenderer->render($this->block);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'blocks' => [
                'uid' => $this->block->getUidRaw(),
                'html' => $html,
            ],
        ]);
    }

    /**
     *
     */
    public function indexAction (
        \Frootbox\Persistence\Repositories\Tags $tagsRepository,
    ): Response
    {
        // Build sql
        $sql = 'SELECT MIN(id) as id, tag FROM tags GROUP BY tag ORDER BY tag ASC';

        // Fetch tags
        $result = $tagsRepository->fetchByQuery($sql);

        return self::getResponse('plain', 200, [
            'tags' => $result
        ]);
    }
}

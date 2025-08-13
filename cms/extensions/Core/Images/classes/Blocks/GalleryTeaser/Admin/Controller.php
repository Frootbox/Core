<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Blocks\GalleryTeaser\Admin;

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
        \DI\Container $container,
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->block->addConfig([
            'CategoryId' => $post->get('CategoryId'),
        ]);

        $this->block->save();

        $html = $container->call([ $this->block, 'renderHtml' ]);

        define('EDITING', true);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $this->block->getUidRaw(),
            'blockId' => $this->block->getId(),
            'html' => $html,
        ]);
    }


    /**
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return Response
     */
    public function indexAction (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch categories
        $categories = $categoriesRepository->fetch();

        return self::getResponse('plain', 200, [
            'Categories' => $categories
        ]);
    }
}

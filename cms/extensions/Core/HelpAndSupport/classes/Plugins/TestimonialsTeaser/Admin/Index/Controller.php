<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\TestimonialsTeaser\Admin\Index;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Update config
        $this->plugin->unsetConfig('tags');
        $this->plugin->unsetConfig('source');
        $this->plugin->addConfig([
            'source' => $post->get('source'),
            'limit' => $post->get('limit'),
            'tags' => (!empty($post->get('tags')) ? explode(',', $post->get('tags')) : null),
            'maxAgeDays' => $post->get('maxAgeDays'),
        ]);

        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $pluginsRepository,
    ): Response
    {
        // Fetch news plugins
        $result = $pluginsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Plugin::class,
            ],
        ]);

        return self::getResponse(body: [
            'plugins' => $result,
        ]);
    }
}

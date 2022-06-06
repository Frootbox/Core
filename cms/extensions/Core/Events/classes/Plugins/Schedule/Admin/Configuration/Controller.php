<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Configuration;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
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
    public function getForms(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $formsRepository->fetch([

        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noEventsDetailPage' => $post->get('noEventsDetailPage'),
            'bookingPluginId' => $post->get('bookingPluginId'),
            'formId' => $post->get('formId'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $eventsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noEventsDetailPage' => $post->get('noEventsDetailPage'),
            ]);

            $entity->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch booking plugins
        $result = $contentElements->fetch([
            'where' => [
                'className' => 'Frootbox\\Ext\\Core\\Events\\Plugins\\Booking\\Plugin',
            ],
        ]);

        return self::getResponse(body: [
            'bookingPlugins' => $result,
        ]);
    }
}

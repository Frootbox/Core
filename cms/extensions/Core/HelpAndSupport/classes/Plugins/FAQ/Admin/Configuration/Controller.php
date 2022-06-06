<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Configuration;

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
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noQuestionsDetailPage' => $post->get('noQuestionsDetailPage'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noQuestionsDetailPage' => $post->get('noQuestionsDetailPage'),
            ]);

            $entity->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}

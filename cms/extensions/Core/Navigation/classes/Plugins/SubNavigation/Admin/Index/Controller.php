<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\SubNavigation\Admin\Index;

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
        \Frootbox\Http\Post $post
    )
    {
        $pageId = null;

        if (!empty($post->get('targetInput'))) {

            if (!preg_match('#fbx://page:(.*?)$#', $post->get('targetInput'), $match)) {
                throw new \Frootbox\Exceptions\InputInvalid('Die Eingabe kann nicht verarbeitet werden.');
            }

            $pageId = $match[1];
        }

        $this->plugin->addConfig([
            'pageId' => $pageId,
            'showParentLink' => $post->get('showParentLink')
        ]);

        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction()
    {
        return self::getResponse();
    }
}

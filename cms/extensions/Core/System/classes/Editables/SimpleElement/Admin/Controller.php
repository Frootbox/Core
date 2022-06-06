<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\SimpleElement\Admin;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
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
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true
        ]);

        $text->setText($post->get('value'));
        $text->save();

        if (preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:title$#i', $get->get('uid'), $match)) {

            $className = str_replace('-', '\\', $match[1]);

            $model = new $className($db);
            $row = $model->fetchById($match[2]);

            if ($row->hasColumn('title')) {
                $row->setTitle($post->get('value'));
                $row->save();
            }
        }

        return self::getResponse('json');
    }


    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view
    )
    {
        $view->set('content', $get->get('content'));

        return self::getResponse();
    }
}
<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\LinkedElement\Admin;

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
            'createOnMiss' => true,
        ]);

        $url = null;
        if (!empty($post->get('url'))) {

            $url = $post->get('url');
            $url = str_replace(SERVER_PATH_PROTOCOL, '', $url);

            if (preg_match('#^edit\/#', $url)) {
                $url = substr($url, 5);
            }
        }

        //d("OKKKK");
        // Save link
        $text->addConfig([
            'link' => $url,
            'email' => $post->get('email'),
            'phone' => $post->get('phone'),
            'label' => $post->get('label'),
            'filelink' => $post->get('fileId'),
            'pageId' => $post->get('pageId'),
            'conversionId' => $post->get('conversionId'),
        ]);

        $text->save();

        return self::getResponse('json', 200, [
            'url' => $url,
            'email' => $post->get('email'),
            'uid' => $get->get('uid'),
            'label' => $post->get('label'),
        ]);
    }


    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
    ): Response
    {
        // Fetch text
        $text = $textsRepository->fetchByUid($get->get('uid'));

        // Fetch pages
        $result = $pagesRepository->fetch();

        return self::getResponse('html', 200, [
            'text' => $text,
            'pages' => $result,
        ]);



        // Parse uid
        preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:(.*?)$#i', $get->get('uid'), $match);
        $className = str_replace('-', '\\', $match[1]);

        // Fetch target object
        $model = new $className($db);
        $row = $model->fetchById($match[2]);

        $view->set('fileId', $row->getConfig('generatedFilelinks.' . $match[3]));
        $view->set('label', $row->getConfig('generatedlinkLabels.' . $match[3]));



        $attributes = !empty($get->get('attributes')) ? $get->get('attributes') : [];

        return self::getResponse('html', 200, [
            'link' => $row->getConfig('generatedlinks.' . $match[3]),
            'email' => $row->getConfig('generatedlinksEmail.' . $match[3]),
            'phone' => $row->getConfig('generatedlinksPhone.' . $match[3]),

            'noLabel' => array_key_exists('nolabel', $attributes),
        ]);
    }
}
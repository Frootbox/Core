<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-08-01
 */

namespace Frootbox\Admin\Controller\Editor\Background;


/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController {

    /**
     *
     */
    public function ajaxModalEdit (

        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get

    ) {

        // Fetch text
        $file = $files->fetchByUid($get->get('uid'));

        $view->set('file', $file);
        $view->set('get', $get);

        return self::response('json', 200, [
            'modal' => [
                'html' => self::render()
            ]
        ]);
    }


    /**
     *
     */
    public function ajaxUpdate (

        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get

    )
    {

        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true
        ]);

        $headline = $post->get('headline');

        if (!empty($post->get('subtitle'))) {

            $headline .= '<span class="subtitle">' . $post->get('subtitle') . '</span>';
        }

        $text->setText($headline);

        $text->addConfig([
            'headline' => $post->get('headline'),
            'subtitle' => $post->get('subtitle')
        ]);

        $text->save();


        return self::response('json', 200, [
            'headline' => [
                'text' => $post->get('headline'),
                'subtitle' => $post->get('subtitle')
            ]
        ]);
    }
}
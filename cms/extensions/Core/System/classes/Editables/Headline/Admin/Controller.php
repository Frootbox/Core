<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Headline\Admin;

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
    public function ajaxUpdate(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true,
        ]);

        $headline = '<span class="head">' . $post->get('headline') . '</span>';

        if (!empty($post->get('subtitle'))) {
            $headline .= '<span class="subtitle">' . $post->get('subtitle') . '</span>';
        }

        $text->setText($headline);

        $text->addConfig([
            'headline' => $post->get('headline'),
            'subtitle' => $post->get('subtitle'),
            'supertitle' => $post->get('supertitle'),
            'level' => $post->get('headlineLevel'),
            'elementId' => $post->get('elementId'),
            'style' => [
                'textAlign' => $post->get('textAlign'),
                'color' => $post->get('color'),
                'fontSize' => $post->get('fontSize'),
            ],
        ]);

        $text->save();

        if (preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:title$#i', $get->get('uid'), $match)) {

            if (!MULTI_LANGUAGE or $_SESSION['frontend']['language'] == DEFAULT_LANGUAGE) {

                $className = str_replace('-', '\\', $match[1]);

                $model = new $className($db);
                $row = $model->fetchById($match[2]);

                if ($row->hasColumn('title')) {
                    $row->setTitle($post->get('headline'));

                    if ($row->hasColumn('subtitle')) {
                        $row->setSubtitle($post->get('subtitle'));
                    }

                    $row->save();
                }
            }
            elseif (MULTI_LANGUAGE) {

                $className = str_replace('-', '\\', $match[1]);

                $model = new $className($db);
                $row = $model->fetchById($match[2]);

                $row->addConfig([
                    'titles' => [
                        $_SESSION['frontend']['language'] => $post->get('headline'),
                    ],
                ]);

                $row->save();
            }
        }

        return self::getResponse('json', 200, [
            'headline' => [
                'subTitleAbove' => (!empty($configuration->get('Ext.Core.System.Editables.Headline.subtitleAbove'))),
                'text' => nl2br($post->get('headline')),
                'subtitle' => $post->get('subtitle'),
                'supertitle' => $post->get('supertitle'),
                'level' => $post->get('headlineLevel'),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
          //  'createOnMiss' => true
        ]);

        return self::getResponse('plain', 200, [
            'text' => $text,
        ]);
    }
}

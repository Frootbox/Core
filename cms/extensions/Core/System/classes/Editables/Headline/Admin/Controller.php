<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Headline\Admin;

use Frootbox\Admin\Controller\Response;
use Frootbox\View\InlineText;

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

        $title = InlineText::sanitize($post->get('headline'));
        $subtitle = InlineText::sanitize($post->get('subtitle'));
        $supertitle = InlineText::sanitize($post->get('supertitle') ?? $text->getConfig('supertitle'));
        $level = strtoupper((string) $post->get('headlineLevel'));
        $level = in_array($level, ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'], true) ? $level : null;

        $headline = '<span class="head">' . $title . '</span>';

        if ($subtitle !== '') {
            $headline .= '<span class="subtitle">' . $subtitle . '</span>';
        }

        $text->setText($headline);

        $text->addConfig([
            'headline' => $title,
            'subtitle' => $subtitle,
            'supertitle' => $supertitle,
            'level' => $level,
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
                    // Entity titles are also used in navigation and metadata.
                    $row->setTitle(InlineText::plainText($title));

                    if ($row->hasColumn('subtitle')) {
                        $row->setSubtitle(InlineText::plainText($subtitle));
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
                        $_SESSION['frontend']['language'] => InlineText::plainText($title),
                    ],
                ]);

                $row->save();
            }
        }

        return self::getResponse('json', 200, [
            'headline' => [
                'subTitleAbove' => (!empty($configuration->get('Ext.Core.System.Editables.Headline.subtitleAbove'))),
                'text' => nl2br($title),
                'subtitle' => nl2br($subtitle),
                'supertitle' => nl2br($supertitle),
                'level' => $level,
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
            'fields' => [
                'headline' => InlineText::sanitize($text ? ($text->getConfig('headline') ?? $get->get('headline')) : $get->get('headline')),
                'supertitle' => InlineText::sanitize($text ? ($text->getConfig('supertitle') ?? $get->get('supertitle')) : $get->get('supertitle')),
                'subtitle' => InlineText::sanitize($text ? ($text->getConfig('subtitle') ?? $get->get('subtitle')) : $get->get('subtitle')),
            ],
        ]);
    }
}

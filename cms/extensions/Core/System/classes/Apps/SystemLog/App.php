<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\SystemLog;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Get;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Fetch text
        $text = $textsRepository->fetchById($get->get('textId'));
        $text->delete();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxSearchAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Search texts
        $result = $textsRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('text', '%' . $post->get('q') . '%'),
            ],
        ]);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#resultReceiver',
                'html' => self::render($view, [
                    'query' => $post->get('q'),
                    'texts' => $result,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Fetch text
        $text = $textsRepository->fetchById($get->get('textId'));
        $text->setText($post->get('text'));
        $text->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Fetch text
        $text = $textsRepository->fetchById($get->get('textId'));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#detailsReceiver',
                'html' => self::render($view, [
                    'text' => $text,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Repositories\SystemLogs $logsRepository
    ): Response
    {
        // Fetch logs
        $result = $logsRepository->fetch([
            'order' => [ 'date DESC' ],
        ]);

        return self::getResponse('html', 200, [
            'logs' => $result,
        ]);
    }
}

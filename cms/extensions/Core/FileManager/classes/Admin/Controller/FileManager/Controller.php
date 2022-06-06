<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-09-19
 */

namespace Frootbox\Ext\Core\FileManager\Admin\Controller\FileManager;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
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
    public function ajaxFileDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));
        $file->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'fadeOut' => 'tr[data-file="' . $file->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $files
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));
        $view->set('file', $file);

        return self::getResponse();
    }

    /**
     *
     */
    public function ajaxUpdateFile(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        $file->setTitle($post->get('title'));
        $file->setCopyright($post->get('copyright'));
        $file->addConfig([
            'caption' => $post->get('caption'),
        ]);
        $file->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function list(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fileManager',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\FileManager\Admin\Partials\FileManager\ListFiles\Partial::class, [
                    'uid' => $get->get('uid')
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function serveOriginal(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    )
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        http_response_code(200);

        header('Content-Type: ' . $file->getType());
        header('Content-Disposition: attachment; filename="' . $file->getName() . '"');

        readfile(FILES_DIR . $file->getPath());

        exit;
    }
}

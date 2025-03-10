<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-04-16
 */

namespace Frootbox\Admin\Controller\Dashboard;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxModalPanelCompose(
        \Frootbox\Persistence\Repositories\Extensions $extensionRepository,
    ): Response
    {
        // Fetch extensions
        $extensions = $extensionRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $panels = [];

        foreach ($extensions as $extension) {

            $controller = $extension->getExtensionController();

            if (file_exists($panelsDir = $controller->getPath() . 'classes/Admin/Panels/')) {

                $directory = new \Frootbox\Filesystem\Directory($panelsDir);

                foreach ($directory as $panelId) {

                    $panels[$extension->getVendorId() . '/' . $extension->getExtensionId()][] = [
                        'title' => $panelId,
                        'class' => '\\Frootbox\\Ext\\' . $extension->getVendorId() . '\\' . $extension->getExtensionId() . '\\Admin\\Panels\\' . $panelId . '\\Panel',
                    ];
                }
            }
        }

        return new Response('html', 200, [
            'panels' => $panels,
        ]);
    }

    /**
     *
     */
    public function ajaxPanelCreate(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Persistence\Repositories\Panels $panelRepository,
    ): Response
    {
        $panelClass = $post->get('panel');

        preg_match('#\\\\([a-z]*)\\\\Panel$#i', $panelClass, $match);

        // Compose new panel
        $panel = new $panelClass([
            'title' => $match[1],
            'className' => \Frootbox\Admin\Persistence\Panel::class,
            'customClass' => $panelClass,
        ]);

        $panelRepository->insert($panel);
        d($panelRepository);

    }

    /**
     * @param \DI\Container $container
     * @param \Frootbox\Admin\View $view
     * @param \Frootbox\Config\Config $config
     * @param \Frootbox\Admin\Persistence\Repositories\Panels $panelRepository
     * @param \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
     * @return Response
     */
    public function index(
        \DI\Container $container,
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Admin\Persistence\Repositories\Panels $panelRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): Response
    {
        // Fetch panels
        $panels = $panelRepository->fetch();

        // Fetch gizmos (deprecated)
        $gizmos = [];

        if (!empty($config->get('gizmos'))) {

            foreach($config->get('gizmos') as $gizmodata) {
                $gizmos[] = $gizmodata['gizmo'];
            }
        }

        $gizmos = array_unique($gizmos);

        $gizmoHtml = [];

        foreach ($gizmos as $index => $gizmoClass) {

            if (!class_exists($gizmoClass)) {
                unset($gizmos[$index]);
                $gizmoHtml[] = '<div class="Gizmo">Missing ' . $gizmoClass . '</div>';
                continue;
            }

            $gizmo = new $gizmoClass;

            $html = $container->call([ $gizmo, 'renderHtml' ]);

            if (empty($html)) {
                continue;
            }

            $gizmoHtml[] = $html;
        }

        $view->set('gizmos', $gizmoHtml);

        // Fetch container plugins
        $containerPlugins = $contentElementsRepository->fetch([
            'where' => [
                'socket' => 'ContainerPlugin',
            ],
        ]);

        return new Response('html', 200, [
            'devmode' => DEVMODE,
            'panels' => $panels,
            'containerPlugins' => $containerPlugins,
        ]);
    }
}
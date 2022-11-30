<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\ClassCreator;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development',
    ];

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
    public function ajaxCreateAppAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch extension
        $extension = $extensions->fetchById($post->get('extensionId'));

        $files = [
            'App.php.twig',
            'resources/private/views/Index.html.twig',
            'resources/private/language/de-DE.php.twig',
        ];

        $path = $this->getPath() . 'resources/private/templates/App/';
        $realpath = $extension->getExtensionController()->getPath() . 'classes/Apps/' . $post->get('appId') . '/';

        $view->set('extension', $extension);
        $view->set('appId', $post->get('appId'));
        $view->set('appTitle', $post->get('title'));

        foreach ($files as $file) {

            $source = $view->render($path . $file);

            $file = new \Frootbox\Filesystem\File($realpath . substr($file, 0, -5));
            $file->setSource($source);

            $file->write();
        }

        d($source);


        d($extension);
    }

    /**
     *
     */
    public function ajaxCreateBlockAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch extension
        $extension = $extensions->fetchById($post->get('extensionId'));

        $files = [
            'Block.html.twig.twig',
            'standards.less.twig',
        ];

        $path = $this->getPath() . 'resources/private/templates/Block/';
        $realpath = $extension->getExtensionController()->getPath() . 'classes/Blocks/' . $post->get('blockId') . '/';

        if (file_exists($realpath)) {
            throw new \Exception('Der Block existiert bereits.');
        }

        $view->set('extension', $extension);
        $view->set('blockId', $post->get('blockId'));
        $view->set('title', $post->get('title'));
        $view->set('subtitle', $post->get('subtitle'));

        foreach ($files as $file) {

            $source = $view->render($path . $file);

            $file = new \Frootbox\Filesystem\File($realpath . substr($file, 0, -5));
            $file->setSource($source);

            $file->write();
        }

        d("FERtIG");
    }

    /**
     *
     */
    public function ajaxCreateExtensionAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post
    ): Response
    {
        // Assign data to view
        $view->set('vendorId', $post->get('vendorId'));
        $view->set('extensionId', $post->get('extensionId'));
        $view->set('title', $post->get('title'));
        $view->set('type', $post->get('type'));

        $files = [
            'configuration.php.twig',
            'ExtensionController.php.twig'
        ];


        if ($post->get('type') == 'Template') {
            $files[] = 'layouts/Default.html.twig.twig';
            $files[] = 'pages/Page.html.twig.twig';
            $files[] = 'resources/public/css/1standards-xs.less.twig';
            $files[] = 'resources/public/css/2standards-sm.less.twig';
            $files[] = 'resources/public/css/3standards-md.less.twig';
            $files[] = 'resources/public/css/4standards-lg.less.twig';
            $files[] = 'resources/public/css/5standards-xl.less.twig';
            $files[] = 'resources/public/css/styles-elements.less.twig';
            $files[] = 'resources/public/css/styles-variables.less.twig';
            $files[] = 'resources/public/js/init.js.twig';
            $files[] = 'resources/public/favicon/android-chrome-192x192.png';
            $files[] = 'resources/public/favicon/android-chrome-512x512.png';
            $files[] = 'resources/public/favicon/apple-touch-icon.png';
            $files[] = 'resources/public/favicon/browserconfig.xml.twig';
            $files[] = 'resources/public/favicon/favicon.ico';
            $files[] = 'resources/public/favicon/favicon-16x16.png';
            $files[] = 'resources/public/favicon/favicon-32x32.png';
            $files[] = 'resources/public/favicon/mstile-150x150.png';
            $files[] = 'resources/public/favicon/safari-pinned-tab.svg';
            $files[] = 'resources/public/favicon/site.webmanifest.twig';
            $files[] = 'resources/public/images/';
        }

        $path = $this->getPath() . 'resources/private/templates/Extension/';

        if (!empty($xpath = ($post->get('pathFromConfig') ?? $post->get('path')))) {

            if (!file_exists($xpath)) {
                throw new \Frootbox\Exceptions\InputInvalid('Der Pfad "' . $xpath . '" existiert nicht.');
            }

            $realpath = realpath($xpath) . '/' . $post->get('vendorId') . '/' . $post->get('extensionId') . '/';
        } else {
            $realpath = CORE_DIR . 'cms/extensions/' . $post->get('vendorId') . '/' . $post->get('extensionId') . '/';
        }


        foreach ($files as $file) {

            if (substr($file, -1) == '/') {
                $directory = new \Frootbox\Filesystem\Directory($realpath . $file);
                $directory->make();
                continue;
            }

            if (file_exists($path . $file)) {

                if (substr($path . $file, -5) == '.twig') {
                    $nfile = new \Frootbox\Filesystem\File($realpath . substr($file, 0, -5));
                    $source = $view->render($path . $file);
                }
                else {
                    $nfile = new \Frootbox\Filesystem\File($realpath . $file);
                    $source = file_get_contents($path . $file);
                }

                $nfile->setSource($source);
                $nfile->write();
            }
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxCreatePluginAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch extension
        $extension = $extensions->fetchById($post->get('extensionId'));
        $view->set('extension', $extension);

        $view->set('pluginId', $post->get('pluginId'));
        $view->set('pluginTitle', $post->get('pluginTitle'));

        $files = [
            'Plugin.php.twig',
            'Layouts/Index01/View.html.twig.twig',
            'Layouts/Index01/public/standards.less.twig',
            'Admin/Index/Controller.php.twig',
            'Admin/Index/resources/private/views/Index.html.twig.twig',
            'Admin/Entity/Controller.php.twig',
            'Admin/Entity/resources/private/views/Details.html.twig.twig',
            'Admin/Entity/resources/private/views/AjaxModalCompose.html.twig.twig',
            'Admin/Entity/Partials/ListEntities/Partial.php.twig',
            'Admin/Entity/Partials/ListEntities/resources/private/views/View.html.twig.twig',
            'Persistence/Entity.php.twig',
            'Persistence/Repositories/Entities.php.twig',
            'resources/private/language/de-DE.php.twig',
        ];

        $path = $this->getPath() . 'resources/private/templates/Plugin/';
        $realpath = $extension->getExtensionController()->getPath() . 'classes/Plugins/' . $post->get('pluginId') . '/';

        foreach ($files as $file) {

            $source = $view->render($path . $file);

            $file = new \Frootbox\Filesystem\File($realpath . substr($file, 0, -5));
            $file->setSource($source);

            $file->write();
        }

        return self::getResponse('json');
    }


    /**
     *
     */
    public function ajaxCreateWidgetAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch extension
        $extension = $extensions->fetchById($post->get('extensionId'));
        $view->set('extension', $extension);

        $view->set('title', $post->get('title'));
        $view->set('widgetId', $post->get('widgetId'));

        $files = [
            'Widget.php.tpl',
            'Layouts/Index01/View.html.tpl',
            //   'Layouts/Index01/public/standards.less.tpl',
            'resources/private/language/de-DE.php.tpl',
            'Admin/Controller.php.tpl',
            'Admin/resources/private/views/Index.html.tpl'
        ];

        $path = $this->getPath() . 'resources/private/templates/Widget/';
        $realpath = $extension->getExtensionController()->getPath() . 'classes/Widgets/' . $post->get('widgetId') . '/';

        foreach ($files as $file) {

            $targetFile = new \Frootbox\Filesystem\File($realpath . substr($file, 0, -4));

            if (file_exists($path . $file)) {
                $source = $view->render($path . $file);
                $targetFile->setSource($source);
            }

            $targetFile->write();
        }

        return self::getResponse('json');
    }

    /** 
     * 
     */
    public function indexAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch available extensions
        $result = $extensions->fetch([
            'order' => [ 'vendorId', 'extensionId' ]
        ]);
        $view->set('extensions', $result);

        // Set configuration
        $view->set('configuration', $config);

        // Set extension directories path
        $view->set('extensionPath', CORE_DIR);

        return self::getResponse();
    }    
}

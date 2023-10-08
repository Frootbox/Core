<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\ExtensionCloner;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];

    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function ajaxCloneAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\Blocks $blockRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionRepository,
    ): Response
    {
        list($sourceVendorId, $sourceExtensionId) = explode('-', $post->get('source'));

        $targetVendorId = $post->get('targetVendorId');
        $targetExtensionId = $post->get('targetExtensionId');

        $sourcePath = null;

        foreach ($config->get('extensions.paths') as $path) {

            $extensionControllerPath = $path . $sourceVendorId . '/' . $sourceExtensionId . '/ExtensionController.php';

            if (file_exists($extensionControllerPath)) {
                $sourcePath = $extensionControllerPath;
                break;
            }
        }

        if ($sourcePath === null) {
            throw new \Exception('Source path not found.');
        }

        $sourcePath = dirname($sourcePath) . '/';
        $targetPath = dirname(dirname($sourcePath)) . '/' . $targetVendorId . '/' . $targetExtensionId;

        // Create base directory
        if (!file_exists($targetPath)) {
            $oldUmask = umask(0);
            mkdir($targetPath, 0755, true);
            umask($oldUmask);
        }

        // Copy all files
        foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {

            $targetFileName = $targetPath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if (file_exists($targetFileName)) {
                continue;
            }

            if ($item->isDir()) {
                mkdir($targetFileName);
            } else {
                copy($item, $targetFileName);
            }
        }


        foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {

            if ($item->isDir()) {
                continue;
            }

            $fileName = basename($item->getFileName());
            $da = explode('.', $fileName);
            $extension = array_pop($da);

            $skipFiles = [
                'Block.html.twig',
                'site.webmanifest',
                'browserconfig.xml',
            ];

            if (in_array($fileName, $skipFiles)) {
                continue;
            }

            $skipExtensions = [
                'jpg',
                'svg',
                'js',
                'png',
                'ico',
            ];

            if (in_array($extension, $skipExtensions)) {
                continue;
            }


            if (substr($item->getFileName(), -5) == '.less') {

                $source = file_get_contents($item->getPathName());
                $source = str_replace('.' . $sourceExtensionId . '.', '.' . $targetExtensionId . '.', $source);

                file_put_contents($item->getPathName(), $source);
            }
            elseif (substr($item->getFileName(), -4) == '.php') {

                $source = file_get_contents($item->getPathName());
                $source = str_replace('\\' . $sourceVendorId . '\\' . $sourceExtensionId . ';', '\\' . $targetVendorId . '\\' . $targetExtensionId . ';', $source);

                file_put_contents($item->getPathName(), $source);
            }
            elseif (substr($item->getFileName(), -5) == '.twig') {

                $source = file_get_contents($item->getPathName());
                $source = str_replace('EXT:' . $sourceVendorId . '/' . $sourceExtensionId . '/', 'EXT:' . $targetVendorId . '/' . $targetExtensionId . '/', $source);

                file_put_contents($item->getPathName(), $source);
            }
            //
            else {
                p("NO MATCH");
                p($item);
                exit;
            }
        }




            d("FERTIG");



        // Fetch blocks
        $blocks = $blockRepository->fetch([
            'where' => [
                'vendorId' => $sourceVendorId,
                'extensionId' => $sourceExtensionId,
            ],
        ]);


        foreach ($blocks as $block) {

            if (!empty($block->getClassName())) {
                if (str_starts_with($block->getClassName(),'Frootbox\\Ext\\' . $sourceVendorId . '\\' . $sourceExtensionId . '\\')) {
                    d($block);
                }
            }

            $block->setVendorId($post->get('targetVendor'));
            $block->setExtensionId($post->get('targetName'));
            $block->save();
        }

        d("FERTIG");

    }

    /** 
     * 
     */
    public function indexAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        // Gather extension paths
        $extensionPaths = [
            'custom' => [ ],
            'native' => [
                CORE_DIR . 'cms/extensions/',
            ],
        ];

        // Inject custom paths
        if (!empty($customPaths = $config->get('extensions.paths'))) {
            $extensionPaths['custom'] = array_merge($extensionPaths['custom'], $customPaths->getData());
        }

        // Gather available extensions
        $list = [];
        $vendors = [];

        foreach ($extensionPaths as $section => $paths) {

            foreach ($paths as $path) {

                if (!file_exists($path)) {
                    continue;
                }

                $dir = new \Frootbox\Filesystem\Directory($path);

                foreach ($dir as $vendorId) {

                    $vendorDir = new \Frootbox\Filesystem\Directory($dir->getPath() . $vendorId . '/');

                    if ($vendorId != 'Core') {
                        $vendors[] = $vendorId;
                    }

                    foreach ($vendorDir as $extensionId) {

                        if (!file_exists($vendorDir->getPath() . '/' . $extensionId . '/configuration.php')) {
                            continue;
                        }

                        $config = require($vendorDir->getPath() . '/' . $extensionId . '/configuration.php');

                        $ext = [
                            'vendorId' => $vendorId,
                            'extensionId' => $extensionId,
                            'config' => $config,
                            'path' => $vendorDir->getPath() . $extensionId,
                        ];

                        $list[$section][$vendorId . '_' . $extensionId] = $ext;
                    }
                }
            }
        }

        if (!empty($list['custom'])) {
            ksort($list['custom']);
        }

        if (!empty($list['native'])) {
            ksort($list['native']);
        }

        sort($vendors);

        return self::getResponse('html', 200, [
            'list' => $list,
            'vendors' => $vendors,
        ]);
    }
}

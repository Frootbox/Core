<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-04-16
 */

namespace Frootbox\Admin\Controller\Page;

use DI\Container;
use Frootbox\Admin\Controller\Response;
use Frootbox\CacheControl;
use Frootbox\Db\Db;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @param \Frootbox\Persistence\Repositories\Pages $pages
     * @param \Frootbox\CloningMachine $cloningMachine
     * @param \Frootbox\Admin\Logger $logger
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxContentClone(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Admin\Logger $logger
    ): Response
    {
        preg_match('#fbx://page:([0-9]+)$#', $post->get('targetInput'), $match);

        // Fetch source page
        $source = $pages->fetchById($match[1]);

        // Fetch target page
        $target = $pages->fetchById($get->get('pageId'));

        // Clone page
        $cloningMachine->clonePage($target, $source, [
            'skipBlocks' => $post->get('skipBlocks'),
            'skipPlugins' => $post->get('skipPlugins'),
        ]);

        $logger->log('ClonedContents', [ 'sourcePage' => $source->getTitle(), 'sourceId' => $source->getId() ], $source);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#contentSocketsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Page\Partials\ListSockets::class, [
                    'pageId' => $target->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     * 
     */
    public function ajaxContentCreate(
        \DI\Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\CacheControl $cacheControl,
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $title = null;

        $db->transactionStart();

        // Check if we insert first element
        $result = $contentElements->fetch([
            'where' => [
                'pageId' => $page->getId(),
                'socket' => $get->get('socket')
            ]
        ]);

        if ($result->getCount() == 0 AND $get->get('main')) {

            if (preg_match('#^(?P<socket>.*?)_(\d+)_(\d+)$#', $get->get('socket'), $match)) {

                $result = $contentElements->fetch([
                    'where' => [
                        'pageId' => $page->getId(),
                        'socket' => $match['socket']
                    ]
                ]);

                if ($result->getCount() == 0) {
                    $title = $page->getTitle();
                }
            }
            else {
                $title = $page->getTitle();
            }
        }

        // Build content element
        $contentClass = $post->get('model');

        $contentElement = new $contentClass([
            'pageId' => $page->getId(),
            'socket' => $get->get('socket'),
            'type' => $get->get('type'),
            'orderId' => 1,
            'visibility' => (DEVMODE ? 2 : 1),
        ]);

        // Look for default plugin title
        if ($title === null and file_exists($langfile = $contentElement->getPath() . 'resources/private/language/de-DE.php')) {

            $data = require $langfile;

            if (!empty($data['Plugin.TitleInitial'])) {
                $title = $data['Plugin.TitleInitial'];
            }
            elseif (!empty($data['Plugin.Title'])) {
                $title = $data['Plugin.Title'];
            }
        }

        if ($title !== null) {
            $contentElement->setTitle($title);
        }

        if ($get->get('type') == 'Grid') {

            $contentElement->addConfig([
                'columns' => $post->get('columns')
            ]);
        }

        // Insert content element
        $contentElement = $contentElements->insert($contentElement);

        if (method_exists($contentElement, 'onAfterCreate')) {
            $container->call([ $contentElement, 'onAfterCreate' ]);
        }

        $db->transactionCommit();

        $cacheControl->clear();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#contentSocketsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Page\Partials\ListSockets::class, [
                    'highlight' => $contentElement->getId(),
                    'pageId' => $page->getId(),
                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     * Create new page
     */
    public function ajaxCreate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Db\Db $db,
        \Frootbox\CloningMachine $cloningMachine
    ): Response
    {
        // Validate required input
        $post->requireOne([ 'title', 'titles' ]);

        // Start database transaction
        $db->transactionStart();

        // Fetch parent page
        $parent = $pages->fetchById($get->get('parentId'));

        // Create multiple
        if (!empty($post->get('titles'))) {

            $titles = explode("\n", $post->get('titles'));

            foreach ($titles as $title) {

                if (empty(trim($title))) {
                    continue;
                }

                // Insert child page
                $child = $parent->appendChild(new \Frootbox\Persistence\Page([
                    'title' => trim($title),
                    'language' => $parent->getLanguage(),
                    'config' => [
                        'view' => $parent->getConfig('view')
                    ]
                ]));

                $child->save();
            }
        }
        // Insert single page
        else {

            // Insert child page
            $child = $parent->appendChild(new \Frootbox\Persistence\Page([
                'title' => $post->get('title'),
                'language' => $parent->getLanguage(),
                'config' => [
                    'view' => $parent->getConfig('view')
                ]
            ]));

            $child->save();

            // Clone pages
            if (!empty($post->get('cloneFromPage'))) {

                // Fetch clone parent
                $cloneParent = $pages->fetchById($post->get('cloneFromPage'));

                $config = json_decode($cloneParent->getDataRaw('config'), true);
                $config['clonedFrom'] = $cloneParent->getId();

                $child->addConfig($config);
                $child->save();

                // Clone structure
                function copyChildren(\Frootbox\Persistence\Page $target, \Frootbox\Persistence\Page $source, \Frootbox\Persistence\Repositories\Pages $pages, \Frootbox\CloningMachine $cloningMachine)
                {
                    // $cloningMachine->cloneContentsForElement($target, $source->getUidBase());

                    foreach ($source->getChildren() as $child) {

                        $config = $child->getConfig();
                        $config['clonedFrom'] = $child->getId();

                        $nChild = clone $child;
                        $nChild->setAlias(null);
                        $nChild->addConfig($config);

                        // Insert new page
                        $nchild = $target->appendChild($nChild);
                        $nchild->save();

                        if ($child->getRgt() - $child->getLft() != 1) {
                            copyChildren($nchild, $child, $pages, $cloningMachine);
                        }
                        else {
                            // $cloningMachine->cloneContentsForElement($nChild, $child->getUidBase());
                        }
                    }
                }

                // $cloningMachine->cloneContentsForElement($child, $cloneParent->getUidBase());
                copyChildren($child, $cloneParent, $pages, $cloningMachine);

                // Rewrite nested set
                $pages->rewriteIds($child->getRootId());

                // Clone page content
                $cloningMachine->cloneContent($child, $cloneParent);
            }
        }

        $db->transactionCommit();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#sitemapReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Sitemap\Partials\Sitemap\Partial::class, [
                    'highlight' => $child->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Validate required data
        $post->require([
            'confirmDelete'
        ]);

        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        // Fetch plugins
        $plugins = $contentElements->fetch([
            'where' => [
                'pageId' => $page->getId(),
            ],
        ]);

        foreach ($plugins as $plugin) {

            // Call plugins cleanup method
            if (method_exists($plugin, 'onBeforeDelete')) {
                $container->call([ $plugin, 'onBeforeDelete' ]);
            }


            // Delete plugin
            $plugin->delete();
        }

        // Delete page
        $page->delete();

        return self::getResponse('json', 200, [
            'replacements' => [
                [
                    'selector' => '#sitemapReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Sitemap\Partials\Sitemap\Partial::class)
                ],
                [
                    'selector' => '.afterDeleteReceiver',
                    'html' => '<div class="alert alert-success" role="alert">
                        Die Seite wurde erolgreich gelöscht.
                    </div>'
                ]
            ],
            'success' => 'Die Seite wurde erfolgreich gelöscht.'
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteBranch(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        // Delete offspring of page
        $offspring = $page->getOffspring([
            'order' => [ 'lft DESC' ]
        ]);

        foreach ($offspring as $child) {

            // Fetch plugins
            $plugins = $contentElements->fetch([
                'where' => [
                    'pageId' => $child->getId(),
                ],
            ]);

            foreach ($plugins as $plugin) {

                // Call plugins cleanup method
                if (method_exists($plugin, 'onBeforeDelete')) {
                    $container->call([ $plugin, 'onBeforeDelete' ]);
                }

                // Delete plugin
                $plugin->delete();
            }

            $child->reload();
            $child->delete();
        }

        return self::getResponse('json', 200, [
            'reload' => true
        ]);
    }

    /**
     *
     */
    public function ajaxNestedSetRebuild(
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Fetch root page
        $root = $pages->fetchOne([
            'where' => [
                'parentId' => 0,
            ],
        ]);

        function loop (\Frootbox\Persistence\Page $page, $lft, $pages) {

            $page->setLft(++$lft);

            $children = $pages->fetch([
                'where' => [
                    'parentId' => $page->getId(),
                ],
                'order' => [
                    'lft ASC'
                ]
            ]);

            foreach ($children as $child) {
                $lft = loop($child, $lft, $pages);
            }

            $page->setRgt(++$lft);
            $page->save();

            return $lft;
        }

        loop($root, 0, $pages);

        return self::getResponse('json', 200, [
            'success' => 'Die IDs wurden neu gesetzt.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([
           // 'title',
            'type',
            'visibility'
        ]);

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        if (empty($title)) {
            throw new \Exception('Bitte alle benötigten Felder ausfüllen.');
        }

        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $page->unsetConfig('titles');
        $page->addConfig([
            'pageType' => $post->get('type'),
            'redirect' => [
                'target' => $post->get('target'),
            ],
            'titles' => $post->get('titles'),
            'frame' => [
                'source' => $post->get('source'),
                'forceBlankWindow' => $post->get('forceBlankWindow'),
            ],
            'seo' => [
                'preventIndexing' => $post->get('preventIndexing'),
            ],
            'search' => [
                'preventIndexing' => $post->get('searchPreventIndexing'),
            ],
            'aliasForced' => strtolower($post->get('aliasForced')),
        ]);

        $page->setTitle($title);
        $page->setVisibility($post->get('visibility'));
        $page->setType($post->get('type'));
        $page->save([
            'forceAlias' => strtolower($post->get('aliasForced')),
        ]);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#sitemapReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Sitemap\Partials\Sitemap::class, [
                    'active' => $page->getId(),
                    'skipAutoOpen' => true
                ])
            ]
        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\CacheControl $cacheControl
     * @param \Frootbox\Persistence\Repositories\Pages $pages
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAdditionalConfig(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\CacheControl $cacheControl,
        \Frootbox\Persistence\Repositories\Pages $pages,
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $page->unsetConfig('variables');
        $page->addConfig([
            'variables' => $post->get('variables'),
            'ExtraCss' => $post->get('Css'),
        ]);

        $page->save();

        $layout = $page->getConfig('view.layout');

        // Copy config to all children
        if ($post->get('inheritVariables')) {

            foreach ($page->getOffspring() as $child) {

                $childLayout = $child->getConfig('view.layout');

                if ($layout != $childLayout) {
                    continue;
                }

                $child->addConfig([
                    'variables' => $post->get('variables')
                ]);
                $child->save();
            }
        }

        // Dismiss cache
        $cacheControl->clear();

        return self::getResponse('json', 200, [ ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Persistence\Repositories\Pages $pages
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateLayout(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Validate required input
        $post->require([
            'layout',
            'language'
        ]);

        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $page->addConfig([
            'view' => [
                'layout' => $post->get('layout')
            ]
        ]);

        $page->setLanguage($post->get('language'));
        $page->save();


        // Copy config to all children
        if ($post->get('inheritConfig')) {

            foreach ($page->getOffspring() as $child) {

                $child->addConfig([
                    'view' => [
                        'layout' => $post->get('layout')
                    ]
                ]);

                $child->setLanguage($post->get('language'));

                $child->save();
            }
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxUpdateSecurity(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $oldToken = $page->getConfig('security.token');

        // Store new security data
        $security = $post->get('security');
        $security['token'] = md5(microtime());

        $page->unsetConfig('security');
        $page->addConfig([
            'security' => $security,
        ]);

        $page->save();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxUpdateSeo(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        $page->addConfig([
            'seo' => $post->get('seo')
        ]);
        $page->save();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxModalCompose(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Fetch parent page
        $parent = $pages->fetchById($get->get('pageId'));
        $view->set('parent', $parent);

        // Fetch page tree
        $tree = $pages->getTree($parent->getRootId());
        $view->set('tree', $tree);

        return self::getResponse();
    }
    
    /**
     * Display content composer modal
     */
    public function ajaxModalContentCompose(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Repositories\Extensions $extensions
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        
        // Fetch extensions
        $result = $extensions->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        return self::getResponse('html', 200, [
            'page' => $page,
            'extensions' => $result,
        ]);
    }

    /**
     * Display pages configuration panel
     */
    public function ajaxPanelConfig(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        $view->set('page', $page);

        // Obtain available languages
        $view->set('availableLanguages', $config->get('i18n.languages') ?? [ $page->getLanguage() ]);

        // Obtain additional page config from template
        $layoutFile = $config->get('layoutRootFolder') . $page->getLayout();

        $source = file_exists($layoutFile) ? file_get_contents($layoutFile) : (string) null;

        if (preg_match('#\{% extends "(.*?)\.html\.twig" %\}#i', $source, $match)) {

            $pageFile = $config->get('pageRootFolder') . $match[1] . '.html.twig';
            $pageSource = file_get_contents($pageFile);

            $source = str_replace($match[0], $pageSource, $source);
        }

        $pageConfig = (array) null;

        if (preg_match_all('#\{\# config\s*(.*?)\s*\/config \#\}#s', $source, $matches)) {

            foreach ($matches[1] as $match) {

                $xconfig = \Symfony\Component\Yaml\Yaml::parse($match);

                if (!empty($xconfig)) {
                    $pageConfig = array_replace_recursive($pageConfig, $xconfig);
                }
            }
        }

        if (!empty($pageConfig['variables'])) {


            foreach ($pageConfig['variables'] as $varname => $variable) {
                $variable['name'] = $varname;
                $variable['value'] = $page->getConfig('variables.' . $varname);
                $pageConfig['variables'][$varname] = $variable;
            }

        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render(null, [
                    'pageConfig' => $pageConfig
                ])
            ]
        ]);
    }
    
    
    /**
     * 
     */
    public function ajaxPanelContent(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Http\Get $get        
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        $view->set('page', $page);

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => 'tr[data-page]',
                'className' => 'active'
            ],
            'addClass' => [
                'selector' => 'tr[data-page="' . $page->getId() . '"]',
                'className' => 'active'
            ],
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }
    
  

    /**
     *
     */
    public function ajaxPanelDelete(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Http\Get $get
    ): Response
    {
        // Fetch page to delete
        $page = $pages->fetchById($get->get('pageId'));

        $view->set('page', $page);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxPanelTemplateCreate(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        $view->set('page', $page);

        $templateData = [
            'elements' => [],
            'widgets' => []
        ];

        // Fetch elements
        $elements = $contentElements->fetch([
            'where' => [
                'pageId' => $page->getId()
            ],
            'order' => [ 'orderId DESC', 'id ASC' ]
        ]);

        foreach ($elements as $element) {

            $data = $element->getData();

            if ($data['title'] == $page->getTitle()) {
                $data['title'] = '{page.title}';
            }

            $data['texts'] = [];

            // Fetch texts
            $result = $texts->fetch([
                'where' => [
                    new \Frootbox\Db\Conditions\Like('uid', $element->getUidBase() . '%')
                ]
            ]);

            foreach ($result as $text) {

                $textData = $text->getData();

                // Extract widgets
                preg_match_all('#<figure data-id="([0-9]+)"></figure>#', $textData['text'], $matches);

                foreach ($matches[1] as $widgetId) {

                    $widget = $widgets->fetchById($widgetId);

                    $templateData['widgets'][$widgetId] = $widget->getData();
                }

                $data['texts'][] = $textData;
            }

            $templateData['elements'][] = $data;
        }

        $view->set('templateData', json_encode($templateData));


        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxPanelTemplateImport(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));
        $view->set('page', $page);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSort (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        // Extract sitemap
        $data = json_decode($post->get('pageData'), true);

        // Fetch root node
        $result = $pages->fetch([
            'where' => [ 'parentId' => 0 ],
            'limit' => 1,
        ]);

        $rootPage = $result->current();

        function loop ( $pageset, $lft, $pages, $parentId ) {

            ++$lft;

            foreach ($pageset as $page) {

                $rgt = loop($page['children'][0], $lft, $pages, $page['id']);

                // Fetch page
                $xpage = $pages->fetchById($page['id']);

                $xpage->setLft($lft);
                $xpage->setRgt($rgt);
                $xpage->setParentId($parentId);

                $xpage->save([
                    'skipAlias' => true,
                ]);

                $lft = $rgt + 1;
            }

            return $lft;
        }

        $rgt = loop($data[0], 1, $pages, $rootPage->getId());

        // Update root node
        $rootPage->setLft(1);
        $rootPage->setRgt($rgt);
        $rootPage->save();

        return self::getResponse('json');
    }


    /**
     *
     */
    public function ajaxSortElements(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    ): Response
    {
        // Get starting orderId
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $elementId) {

            $element = $contentElements->fetchById($elementId);
            $element->setOrderId($orderId--);
            $element->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxTemplateImport(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Fetch page
        $page = $pages->fetchById($get->get('pageId'));

        $templateData = json_decode($post->get('template'), true);

        $db->transactionStart();

        foreach ($templateData['elements'] as $elementData) {

            // Extract texts data
            $texts = $elementData['texts'];
            unset($elementData['texts']);

            // Prepare element
            $class = $elementData['className'];
            $element = new $class($elementData);
            $element->setPageId($page->getId());

            $element = $contentElements->insert($element);

            //
            foreach ($texts as $text) {

                $segments = $element::extractUid($text['uid']);

                if (empty($segments['segment'])) {
                    d($text);
                }
                $text['uid'] = !empty($segments['segment']) ? $element->getUid($segments['segment']) : $segments['base'];

                if (preg_match_all('#<figure data-id="([\d]+)"><\/figure>#', $text['text'], $matches)) {

                    foreach ($matches[1] as $index => $widgetId) {

                        $widgetData = $templateData['widgets'][$widgetId];
                        $class = $widgetData['className'];
                        $widgetData['textUid'] = $text['uid'];

                        $widget = new $class($widgetData);
                        $widget = $widgets->insert($widget);

                        $text['text'] = str_replace($matches[0][$index], '<figure data-id="' . $widget->getId() . '"></figure>', $text['text']);
                    }
                }

                $text = new \Frootbox\Persistence\Content\Text($text);
                $text = $textsRepository->insert($text);
            }
        }

        $db->transactionCommit();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function sort(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): Response
    {
        $result = $pages->fetch([
            'where' => [ 'parentId' => 0 ],
            'limit' => 1
        ]);

        $page = $result->current();

        $view->set('page', $page);

        return self::response();
    }
}

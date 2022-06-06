<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\AliasManager;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
    ): Response
    {
        $post->require([ 'alias' ]);

        // Get alias
        $alias = $post->get('alias');
        $alias = str_replace(SERVER_PATH_PROTOCOL, '', $alias);
        $alias = trim($alias, '/');

        // Insert new alias
        $alias = $aliasesRepository->insert(new \Frootbox\Persistence\Alias([
            'alias' => $alias,
            'type' => 'Manual',
        ]));

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxCreateFromUrlsAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        $urls = $post->get('urls');
        $targets = $post->get('targets');

        foreach ($urls as $index => $url) {

            $url = trim($url, '/');

            if (empty($url)) {
                continue;
            }

            if (empty($targets[$index])) {
                continue;
            }

            // Check if alias exists
            $alias = $aliasesRepository->fetchOne([
                'where' => [
                    'alias' => $url,
                ],
            ]);

            if (!empty($alias)) {
                continue;
            }

            // Fetch page
            $page = $pagesRepository->fetchById($targets[$index]);

            $alias = $aliasesRepository->insert(new \Frootbox\Persistence\Alias([
                'alias' => $url,
                'type' => 'Manual',
                'status' => 301,
                'config' => [
                    'target' => $page->getAlias(),
                ],
            ]));
        }

        return self::getResponse('json', 200, [ ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));

        $alias->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => '[data-alias="' . $alias->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxImportAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        $aliases = $post->get('aliases');
        $targets = $post->get('targets');

        foreach ($aliases as $index => $alias) {

            $newAlias = str_replace(SERVER_PATH_PROTOCOL, '', $alias);
            $newAlias = trim($newAlias, '/');

            $target = str_replace(SERVER_PATH_PROTOCOL, '', $targets[$index]);
            $target = trim($target, '/');

            $nalias = $aliasesRepository->insert(new \Frootbox\Persistence\Alias([
                'alias' => $newAlias,
                'status' => 301,
                'type' => 'Manual',
                'config' => [
                    'target' => $target,
                ],
            ]));
        }

        unset($_SESSION['_tmp_alias_import_source']);

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));

        // Prepare alias
        $newAlias = $post->get('alias');
        $newAlias = str_replace(SERVER_PATH_PROTOCOL, '', $newAlias);
        $newAlias = trim($newAlias, '/');

        // Prepare target
        $target = $post->get('target');
        $target = str_replace(SERVER_PATH_PROTOCOL, '', $target);
        $target = trim($target, '/');

        // Update alias
        $alias->setAlias($newAlias);
        $alias->setStatus(301);
        $alias->addConfig([
            'target' => $target,
        ]);

        $alias->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateSeoAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));

        $alias->addConfig([
            'seo' => [
                'keywords' => $post->get('keywords'),
                'description' => $post->get('description'),
                'title' => $post->get('title'),
            ],
        ]);

        $alias->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::getResponse('html');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));
        $view->set('alias', $alias);

        return self::getResponse('html');
    }

    /**
     *
     */
    public function ajaxPurge301Action(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch([
            'where' => [ 'status' => 301 ]
        ]);

        $result->map('delete');

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));

        return self::getResponse('html', 200, [
            'alias' => $alias
        ]);
    }

    /**
     *
     */
    public function fixAction(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch();

        foreach ($result as $alias) {

            // Check item
            $class = $alias->getItemModel();

            if (empty($class)) {
                continue;
            }

            if (!class_exists($class)) {
                $alias->delete();
                continue;
            }

            $repository = $this->getDb()->getRepository($class);

            try {
                $item = $repository->fetchbyId($alias->getItemId());
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {
                $alias->delete();
            }
        }

        d("OK");

    }

    /**
     *
     */
    public function genericAction(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch([
            'where' => [
                'type' => 'Generic',
            ],
            'order' => [ 'alias ASC' ],
        ]);

        return self::getResponse('html', 200, [
            'aliases' => $result
        ]);
    }

    /**
     *
     */
    public function importAction(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
    ): Response
    {


        return self::getResponse('html', 200, [

        ]);
    }

    /**
     *
     */
    public function importPreviewAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Obtain import source
        $source = !empty($post->get('source')) ? $post->get('source') : $_SESSION['_tmp_alias_import_source'];

        // Obtain seperator
        $delimiter = !empty($post->get('seperator')) ? $post->get('seperator') : ',';

        // Parse input
        $lines = explode("\n", $source);
        $list = [];

        foreach ($lines as $line) {

            $data = str_getcsv($line, $delimiter);

            if (empty($data[0])) {
                continue;
            }

            $alias = trim($data[0], '/');
            $target = !empty($data[1]) ? trim($data[1], '/') : (string) null;

            if ($alias == $target) {
                continue;
            }

            $list[] = [
                'alias' => $alias,
                'target' => $target,
            ];
        }

        return self::getResponse('html', 200, [
            'aliases' => $list,
            'source' => $source,
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch([
            'where' => [ 'type' => 'Manual' ]
        ]);

        return self::getResponse('html', 200, [
            'aliases' => $result
        ]);
    }

    /**
     *
     */
    public function manualAction(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch([
            'where' => [ 'type' => 'Manual' ]
        ]);

        return self::getResponse('html', 200, [
            'aliases' => $result
        ]);
    }

    /**
     *
     */
    public function redirect301Action(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch aliases
        $result = $aliasesRepository->fetch([
            'where' => [ 'status' => 301 ]
        ]);

        return self::getResponse('html', 200, [
            'aliases' => $result
        ]);
    }

    /**
     *
     */
    public function searchTextsAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        // Fetch root page
        $rootPage = $pagesRepository->fetchOne([
            'where' => [
                'language' => 'de-DE',
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ],
        ]);

        // Generate sitemap tree
        $tree = $pagesRepository->getTree($rootPage->getId());

        // Fetch texts
        $result = $textsRepository->fetch();

        $list = [];
        $base = $post->get('base');
        $loop = 0;

        foreach($result as $text) {

            if (preg_match_all('#[\"\']+(' . $base . '(.*?))[\"\']+#s', $text->getText(), $matches)) {

                foreach ($matches[1] as $url) {

                    ++$loop;

                    $url = str_replace($base, '', $url);

                    if (in_array($url, $list)) {
                        continue;
                    }

                    // Check if alias exists
                    $alias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $url,
                        ],
                    ]);

                    if (!empty($alias)) {
                        continue;
                    }

                    $list[] = $url;
                }
            }
        }

        sort($list);

        return self::getResponse('html', 200, [
            'list' => $list,
            'tree' => $tree,
        ]);
    }

    /**
     *
     */
    public function uploadImportFileAction(): Response
    {
        $source = file_get_contents($_FILES['file']['tmp_name']);

        $_SESSION['_tmp_alias_import_source'] = $source;

        header('Location: ' . $this->getUri('importPreview'));
        exit;
    }
}

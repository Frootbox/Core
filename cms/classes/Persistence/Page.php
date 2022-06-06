<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Page extends \Frootbox\Persistence\RowModels\ConfigurableNestedSet implements Interfaces\MultipleAliases
{
    use Traits\Uid;
    use Traits\DummyImage;

    use Traits\Alias {
        Traits\Alias::getUri as getUriFromTrait;
    }

    protected $table = 'pages';
    protected $model = Repositories\Pages::class;

    protected $overrideIndexable = null;

    /**
     * Delete page
     */
    public function delete()
    {
        // Fetch content elements
        $elements = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $result = $elements->fetch([
            'where' => [
                'pageId' => $this->getId()
            ]
        ]);

        foreach ($result as $element) {
            $element->delete();
        }

        // Cleanup alias
        $this->removeAlias();

        // Cleanup blocks
        $blocks = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Blocks::class);
        $result = $blocks->fetchByQuery('SELECT * FROM blocks WHERE uid LIKE "' . $this->getUidBase() . '%"');
        $result->map('delete');

        // Cleanup files from edit mode
        $files = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);
        $result = $files->fetchByQuery('SELECT * FROM files WHERE uid LIKE "' . $this->getUidBase() . '%"');
        $result->map('delete');

        // Cleanup texts from edit mode
        $texts = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Texts::class);
        $result = $texts->fetchByQuery('SELECT * FROM content_texts WHERE uid LIKE "' . $this->getUidBase() . '%"');
        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getAlias($section = 'index', $language = null): ?string
    {
        if (MULTI_LANGUAGE or empty($this->data['alias'])) {

            $aliases = !empty($this->data['aliases']) ? json_decode($this->data['aliases'], true) : [];

            if ($language === null) {
                $language = GLOBAL_LANGUAGE;
            }

            if (!empty($aliases[$section][$language])) {
                return $aliases[$section][$language];
            }
        }

        return $this->data['alias'] ?? null;
    }

    /**
     *
     */
    public function getChildrenVisible()
    {
        // Fetch children
        $result = $this->getModel()->fetch([
            'where' => [
                'parentId' => $this->getId(),
                'visibility' => 'Public'
            ],
            'order' => [ 'lft ASC' ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        if (empty($this->data['aliases'])) {
            return [];
        }

        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'] ?? [];
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $list['index'][] = new Alias([
                    'language' => $language,
                    'pageId' => $this->getId(),
                    'visibility' => 2,
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [ new Alias([
                    'pageId' => $this->getId(),
                    'language' => $this->getLanguage(),
                    'visibility' => 2,
                ]) ],
            ];
        }
    }

    /**
     * Generate page alias
     */
    public function getNewAlias(): ?Alias
    {
        $record = [
            'pageId' => $this->getId(),
            'language' => $this->getLanguage(),
            'visibility' => 2,
        ];

        if ($this->getType() == 'Redirect') {

            $record['status'] = 301;
            $record['config'] = [
                'target' => $this->getConfig('redirect.target'),
            ];
        }

        return new Alias($record);
    }

    /**
     *
     */
    public function getLanguageShort(): string
    {
        return substr($this->getLanguage(), 0, 2);
    }

    /**
     *
     */
    public function getLayout()
    {
        return $this->getConfig('view.layout') ?? 'Default.html.twig';
    }

    /**
     *
     */
    public function getPageType(): string
    {
        return $this->config['pageType'] ?? 'Default';
    }

    /**
     *
     */
    public function getSockets(
        \Frootbox\Config\Config $config
    ): array
    {
        // Get page layout source
        $layoutFile = $config->get('layoutRootFolder') . $this->getLayout();

        $files = [
            [
                'type' => 'layout',
                'path' => $layoutFile
            ]
        ];

        $source = file_get_contents($layoutFile);

        if (preg_match('#{% extends "(.*?)" %}#i', $source, $match)) {

            $files[] = [
                'type' => 'page',
                'path' => $config->get('pageRootFolder') . $match[1]
            ];
        }

        $list = [ ];

        foreach ($files as $file) {

            if (!file_exists($file['path'])) {
                continue;
            }

            $source = file_get_contents($file['path']);

            preg_match_all('#<div.*?data-socket="(.*?)".*?></div>#', $source, $matches);

            foreach ($matches[1] as $index => $socketName) {

                $tagLine = $matches[0][$index];

                $soc = [
                    'socket' => $socketName
                ];

                preg_match_all('#data-([a-z]{1,})="(.*?)"#i', $tagLine, $attrMatches);

                // Extract sockets attributes
                foreach ($attrMatches[1] as $index => $attribute) {
                    $soc['attributes'][$attribute] = (string)$attrMatches[2][$index];
                }

                $list[$file['type']][] = $soc;
            }
        }

        // Fetch virtual grid sockets
        $elements = $this->getDb()->getModel(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $result = $elements->fetch([
            'where' => [
                'className' => \Frootbox\Persistence\Content\Elements\Grid::class,
                'pageId' => $this->getId()
            ]
        ]);

        if ($result->getCount() > 0) {

            $list['grid'] = [

            ];
        }

        foreach ($result as $grid) {

            foreach ($grid->getColumns() as $column) {

                $list['grid'][] = [
                    'socket' => $column['socket'],
                    'attributes' => [
                        'title' => $column['socket']
                    ]
                ];
            }
        }

        return $list;
    }

    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? parent::getTitle();
    }

    /**
     *
     */
    public function getTitleWithoutFallback($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? null;
    }

    /**
     *
     */
    public function getTree(): \Frootbox\Db\Result
    {
        $pagesRepository = $this->getModel();

        return $pagesRepository->getTree($this->getRootId());
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->config['pageType']) and $this->config['pageType'] == 'Redirect' and !empty($this->config['redirect']['target'])) {

            if (preg_match('#^fbx://page:([0-9]+)$#', $this->getConfig('redirect.target'), $match)) {

                try {

                    // Fetch target page
                    $model = $this->db->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
                    $page = $model->fetchById($match[1]);
                }
                catch ( \Exception $e ) {

                    return '#invalid-target-' . $match[1];
                }

                return $page->getUri($options);
            }

            return $this->config['redirect']['target'];
        }

        return $this->getUriFromTrait($options);
    }

    /**
     *
     */
    public function hasRestrictedAccess(): bool
    {
        return !empty($this->getConfig('security.password')) or !empty($this->getConfig('security.internal'));
    }

    /**
     *
     */
    public function isAccessGranted(): bool
    {
        if (!empty($this->getConfig('security.internal'))) {
            return IS_LOGGED_IN;
        }

        if (empty($_SESSION['security']['simplePasswords'][$this->getId()])) {
            return false;
        }

        return $_SESSION['security']['simplePasswords'][$this->getId()] == $this->getConfig('security.password');
    }

    /**
     *
     */
    public function isIndexable(): bool
    {
        if ($this->overrideIndexable !== null) {
            return $this->overrideIndexable;
        }

        return (empty($this->getConfig('seo.preventIndexing')) and $this->getType() != 'Error404');
    }

    /**
     *
     */
    public function setIndexable(bool $indeaxble): void
    {
        $this->overrideIndexable = $indeaxble;
    }
}

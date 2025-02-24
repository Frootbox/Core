<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence;

class Reference extends \Frootbox\Persistence\AbstractAsset implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\References::class;

    /**
     *
     */
    public function getImageMaps(): \Frootbox\Db\Result
    {
        $imageMapsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps::class);

        $imageMaps = $imageMapsRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
            'order' => [ 'orderId DESC' ],
        ]);

        return $imageMaps;
    }

    /**
     *
     */
    public function getImageMap(): ?\Frootbox\Ext\Core\Images\Plugins\References\Persistence\ImageMap
    {
        $imageMapsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps::class);

        $imageMaps = $imageMapsRepository->fetchOne([
            'where' => [
                'parentId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
        ]);

        return $imageMaps;
    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        if (empty($aliases['index'][DEFAULT_LANGUAGE]) and !empty($this->data['alias'])) {
            $aliases['index'][DEFAULT_LANGUAGE] = $this->data['alias'];
        }

        return $aliases['index'] ?? [];
    }

    /**
     *
     */
    public function getLocation(): ?\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Location
    {
        if (empty($this->getLocationId())) {
            return null;
        }

        // Fetch location
        $locationRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Locations::class);
        $location = $locationRepository->fetchById($this->getLocationId());

        return $location;
    }

    /**
     * Generate references alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noReferencesDetailPage')) and empty($this->getConfig('forceReferencesDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showReference',
                'referenceId' => $this->getId()
            ])
        ]);
    }

    /**
     * Generate aliases
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noReferencesDetailPage')) and empty($this->getConfig('forceReferencesDetailPage'))) {
            return [];
        }

        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $this->getPageId(),
                    'virtualDirectory' => [
                        $title,
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showReference',
                        'referenceId' => $this->getId()
                    ]),
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [ new \Frootbox\Persistence\Alias([
                    'pageId' => $this->getPageId(),
                    'language' => $this->getLanguage() ?? GLOBAL_LANGUAGE,
                    'virtualDirectory' => [
                        $this->getTitle()
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showReference',
                        'referenceId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
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
     * @return string|null
     */
    public function getUrl(): ?string
    {
        if (empty($this->getConfig('url'))) {
            return null;
        }

        return $this->getConfig('url');
    }

    /**
     * @return string|null
     */
    public function getUrl2(): ?string
    {
        if (empty($this->getConfig('url2'))) {
            return null;
        }

        return $this->getConfig('url2');
    }

    /**
     * @param bool $skipSubdomain
     * @return string|null
     */
    public function getUrlDomain(
        bool $skipSubdomain = false,
    ): ?string
    {
        if (empty($this->getConfig('url'))) {
            return null;
        }

        $info = parse_url($this->getConfig('url'));

        $host = $info['host'] ?? null;

        if ($skipSubdomain) {
            $host = str_replace('www.', '', $host);
        }

        return $host;
    }

    /**
     * @param bool $skipSubdomain
     * @return string|null
     */
    public function getUrl2Domain(
        bool $skipSubdomain = false,
    ): ?string
    {
        if (empty($this->getConfig('url2'))) {
            return null;
        }

        $info = parse_url($this->getConfig('url2'));

        $host = $info['host'] ?? null;

        if ($skipSubdomain) {
            $host = str_replace('www.', '', $host);
        }

        return $host;
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->getConfig('forceReferencesDetailPage'))) {
            return parent::getUri($options);
        }

        if (!empty($this->getConfig('link'))) {
            return $this->getConfig('link');
        }

        if (!empty($this->getConfig('noReferencesDetailPage'))) {
            return '';
        }

        return parent::getUri($options);
    }
}

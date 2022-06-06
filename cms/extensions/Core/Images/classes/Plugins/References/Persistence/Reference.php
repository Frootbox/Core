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
     * Generate references alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noReferencesDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
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
        if (!empty($this->getConfig('noReferencesDetailPage'))) {
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
     *
     */
    public function getUrlDomain(): ?string
    {
        if (empty($this->getConfig('url'))) {
            return null;
        }

        $info = parse_url($this->getConfig('url'));

        return $info['host'] ?? null;
    }
}

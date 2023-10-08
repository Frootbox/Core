<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence;

class Location extends \Frootbox\Persistence\AbstractLocation implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Visibility;
    use \Frootbox\Persistence\Traits\Uid;

    protected $model = Repositories\Locations::class;

    /**
     * Generate addresses alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                'orte',
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showLocation',
                'venueId' => $this->getId()
            ])
        ]);
    }

    /**
     * @return array
     */
    public function getNewAliases(): array
    {
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
                        'orte',
                        $title,
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showLocation',
                        'venueId' => $this->getId()
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
                        'orte',
                        $this->getTitle()
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showLocation',
                        'venueId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'];
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
}
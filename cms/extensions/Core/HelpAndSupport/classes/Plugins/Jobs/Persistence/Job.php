<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence;

class Job extends \Frootbox\Persistence\AbstractAsset implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Http\Traits\UrlSanitize;
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\Jobs::class;

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'] ?? [];
    }

    /**
     * @return \Frootbox\Ext\Core\Addresses\Persistence\Address|null
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getLocation(): ?\Frootbox\Ext\Core\Addresses\Persistence\Address
    {
        if (empty($this->getLocationId())) {
            return null;
        }

        // Obtain address
        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);

        return $addressesRepository->fetchById($this->getLocationId());
    }

    /**
     * Generate jobs alias
     *
     * @return \Frootbox\Persistence\Alias|null
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noJobsDetailPage')) and empty($this->getConfig('forceJobsDetailPage'))) {
            return null;
        }

        if (!empty($this->getConfig('link'))) {
            return null;
        }

        $title = $this->getTitle();

        if (!empty($this->getConfig('urlSuffixSubtitle'))) {

            $textRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Content\Repositories\Texts::class);
            $text = $textRepository->fetchByUid($this->getUid('title'));

            if (!empty($text) and !empty($text->getConfig('subtitle'))) {
                $title .= ' ' . $text->getConfig('subtitle');
            }
            else {
                $title .= ' ' . $this->getSubtitle();
            }

            $title = trim($title);
        }

        $virtualDirectory = [];

        if (!empty($this->getConfig('urlPrefixId'))) {
            $virtualDirectory[] = $this->getId();
        }

        if (!empty($this->getLocationId()) and empty($this->getConfig('urlSkipLocation'))) {
            $location = $this->getLocation();
            $virtualDirectory[] = $location->getTitle();
        }

        $virtualDirectory[] = $title;

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => $virtualDirectory,
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showJob',
                'jobId' => $this->getId()
            ])
        ]);
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noJobsDetailPage')) and empty($this->getConfig('forceJobsDetailPage'))) {
            return [];
        }

        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $vd = [];

                $location = $this->getLocation();

                if (!empty($location)) {
                    $vd[] = $location->getCity();
                }

                $vd[] = $title;

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $this->getPageId(),
                    'virtualDirectory' => $vd,
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showJob',
                        'jobId' => $this->getId(),
                    ]),
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [
                    new \Frootbox\Persistence\Alias([
                        'language' => GLOBAL_LANGUAGE,
                        'pageId' => $this->getPageId(),
                        'virtualDirectory' => [
                            $this->getTitle()
                        ],
                        'uid' => $this->getUid('alias'),
                        'payload' => $this->generateAliasPayload([
                            'action' => 'showJob',
                            'jobId' => $this->getId()
                        ]),
                    ]),
                ],
            ];
        }
    }

    /**
     * @return array
     */
    public function getSearchableTags(): array
    {
        $tags = [];

        foreach ($this->getTags() as $tag) {
            $tags[] = 'tag-' . $tag->getTag();
        }

        $tags[] = $this->getType();

        return $tags;
    }

    /**
     *
     */
    public function getType(): ?string
    {
        if (!empty($this->getConfig('type'))) {
            return $this->getConfig('type');
        }

        if (!empty($this->getConfig('typeId'))) {
            return $this->getConfig('typeId');
        }

        return null;
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->getConfig('forceJobsDetailPage'))) {
            return parent::getUri($options);
        }

        if (!empty($this->getConfig('link'))) {
            return $this->getConfig('link');
        }

        if (!empty($this->getConfig('noJobsDetailPage'))) {
            return '';
        }

        return parent::getUri($options);
    }
}

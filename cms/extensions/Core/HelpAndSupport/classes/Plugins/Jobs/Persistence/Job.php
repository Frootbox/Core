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
     *
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
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noJobsDetailPage')) and empty($this->getConfig('forceJobsDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
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

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Persistence;

class Event extends \Frootbox\Persistence\AbstractAsset implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Tags;

    use \Frootbox\Persistence\Traits\Alias{
        \Frootbox\Persistence\Traits\Alias::getUri as getUriFromTrait;
    }

    protected $model = Repositories\Events::class;

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
    public function getDateEnd(): \Frootbox\Dates\Date
    {
        return new \Frootbox\Dates\Date($this->data['dateEnd']);
    }

    /**
     *
     */
    public function getDateStart(): \Frootbox\Dates\Date
    {
        return new \Frootbox\Dates\Date($this->data['dateStart']);
    }

    /**
     *
     */
    public function getFreeSeats(): int
    {
        return (int) $this->getConfig('bookable.seats') - (int) $this->getConfig('bookable.bookedSeats');
    }

    /**
     *
     */
    public function getLink(): ?string
    {
        return $this->getConfig('link');
    }

    /**
     * Generate alias of event
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noIndividualDetailPage')) or !empty($this->getConfig('noEventsDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'language' => '',
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getDateStart()->format('%Y-%m-%d'),
                $this->getTitle()
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showEvent',
                'eventId' => $this->getId()
            ])
        ]);
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noIndividualDetailPage')) or !empty($this->getConfig('noEventsDetailPage'))) {
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
                        $this->getDateStart()->format('%Y-%m-%d'),
                        $title,
                    ],
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showEvent',
                        'eventId' => $this->getId(),
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
                            $this->getDateStart()->format('%Y-%m-%d'),
                            $this->getTitle()
                        ],
                        'payload' => $this->generateAliasPayload([
                            'action' => 'showEvent',
                            'eventId' => $this->getId()
                        ])
                    ]),
                ],
            ];
        }
    }

    /**
     *
     */
    public function getPrice(): ?float
    {
        $price = $this->getConfig('bookable.price');

        return $price !== null ? (float) $price : null;
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->getConfig('link'))) {
            return $this->getConfig('link');
        }

        return $this->getUriFromTrait($options);
    }

    /**
     *
     */
    public function getVenue(): ?Venue
    {
        if (!empty($this->getConfig('onlineOnly'))) {
            return null;
        }

        $record = [ ];

        foreach ($this->data as $key => $value) {

            if (substr($key, 0, 8) != 'location') {
                continue;
            }

            $record[lcfirst(substr($key, 8))] = $value;
        }

        if (empty($record['id'])) {

            if (!empty($this->getParentId())) {

                // Fetch venue
                $venuesRepository = new \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues($this->getDb());
                $venue = $venuesRepository->fetchById($this->getParentId());

                return $venue;
            }

            return null;
        }

        $venue = new Venue($record);

        return $venue;
    }

    /**
     *
     */
    public function hasDateEnd ( ): bool
    {
        return !empty($this->data['dateEnd']);
    }

    /**
     *
     */
    public function hasDateStart ( ): bool
    {
        return !empty($this->data['dateStart']);
    }

    /**
     *
     */
    public function hasOneDay ( ): bool
    {
        return (strftime('%d.%m.%Y', strtotime($this->data['dateStart'])) == strftime('%d.%m.%Y', strtotime($this->data['dateEnd'])));
    }

    /**
     *
     */
    public function hasUri(): bool
    {
        return !empty($this->getConfig('link')) or (empty($this->getConfig('noIndividualDetailPage')) and empty($this->getConfig('noEventsDetailPage')));
    }

    /**
     *
     */
    public function isBookable(): bool
    {
        $seatsFree = $this->getConfig('bookable.seats');

        if (strlen($seatsFree) > 0 and (int) $seatsFree === 0) {
            return false;
        }

        return true;
    }
}
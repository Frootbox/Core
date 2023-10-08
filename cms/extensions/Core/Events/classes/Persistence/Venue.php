<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Persistence;

class Venue extends \Frootbox\Persistence\AbstractLocation implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Uid;

    protected $model = Repositories\Venues::class;

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
                'veranstaltungsorte',
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showVenue',
                'venueId' => $this->getId()
            ])
        ]);
    }

    /**
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getBookings(): \Frootbox\Db\Result
    {
        // Generate sql
        $sql = 'SELECT
            b.id,
            b.config,
            e.dateStart as eventDateStart,
            e.dateEnd as eventDateEnd
        FROM 
            assets b,
            assets e
        WHERE
            (                
                b.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Plugins\\\\Booking\\\\Persistence\\\\Booking"
            ) AND
            (
                e.parentId = ' . $this->getId() . ' AND
                e.id = b.parentId
            )
        ORDER BY
            e.dateStart ASC';

        // Fetch bookings
        $bookingRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings::class);
        $result = $bookingRepository->fetchByQuery($sql);

        return $result;
    }

    public function getBookingsByDay(): array
    {
        $List = [];

        foreach ($this->getBookings() as $booking) {

            $day = explode(' ', $booking->getEventDateStart())[0];

            if (!isset($list[$day])) {
                $list[$day] = [];
            }

            $list[$day][] = $booking;
        }

        return $list;
    }

    /**
     *
     */
    public function getEvents(): \Frootbox\Db\Result
    {
        $sql = 'SELECT
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber,
            l.zipcode as locationZipcode,
            l.city as locationCity,
            l.phone as locationPhone,
            l.email as locationEmail,
            l.config as locationConfig,
            l.alias as locationAlias
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND  
            e.pluginId = ' . $this->getPluginId() . ' AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            )            
        ORDER BY
            e.dateStart ASC';

        $eventRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Events::class);

        return $eventRepository->fetchByQuery($sql);
    }

    /**
     * @return array
     */
    public function getNewAliases(): array
    {
        throw new \Exception("MULTI LANGUE MODE NOT SUPPORTED YET.");
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
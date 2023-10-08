<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\Persistence;

class Booking extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Bookings::class;

    /**
     * Get bookings event
     */
    public function getEvent (): \Frootbox\Ext\Core\Events\Persistence\Event
    {
        // Fetch event
        $eventsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Events::class);
        $event = $eventsRepository->fetchById($this->getParentId());

        return $event;
    }

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getPersons(): int
    {
        return (int) $this->getConfig('persons');
    }

    /**
     *
     */
    public function getTotal(): float
    {
        // Fetch event
        $event = $this->getEvent();

        return $this->getConfig('persons') * $event->getPrice();
    }
}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class SelfPickupTime extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\SelfPickupTime::class;

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTimeEnd(): string
    {
        $date = new \DateTime($this->getDateEnd());

        return $date->format('H:i');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTimeStart(): string
    {
        $date = new \DateTime($this->getDateStart());

        return $date->format('H:i');
    }

    /**
     * @param \DateTime $date
     * @return bool
     * @throws \Exception
     */
    public function isAvailableForDate(\DateTime $date): bool
    {
        if (empty($this->getConfig('Weekdays'))) {
            return false;
        }

        $xDate = new \DateTime($this->getDateStart());
        $date->setTime($xDate->format('H'), $xDate->format('i'));

        $diff = $date->diff(new \DateTime('now'));

        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);

        if ($hours < 0) {
            return false;
        }

        if (!empty($this->getConfig('LeadTime')) and $hours < (int) $this->getConfig('LeadTime')) {
            return false;
        }

        $weekdays = $this->getConfig('Weekdays');

        if (empty($weekdays[$date->format('w')])) {
            return false;
        }

        return true;
    }
}

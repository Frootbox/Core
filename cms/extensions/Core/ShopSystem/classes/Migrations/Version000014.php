<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings;

class Version000014 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert den Status der Buchungen.';

    /**
     *
     */
    public function up(
        Bookings $bookingRepository
    ): void
    {
        // Fetch all bookings
        $result = $bookingRepository->fetch();

        foreach ($result as $booking) {

            if (!empty($booking->getState())) {
                continue;
            }

            $booking->setState('Booked');
            $booking->save();
        }
    }
}

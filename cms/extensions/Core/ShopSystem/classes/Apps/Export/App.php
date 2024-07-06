<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Apps\Export;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function ajaxExportOrdersAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingRepository,
    ): void
    {
        $bookings = $bookingRepository->fetch([
            'order' => [ 'date DESC' ],
        ]);

        $fp = fopen('php://output', 'wb');
        fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        $line = [
            'Datum',
            'Status',
            'E-Mail',
        ];

        fputcsv($fp, $line);

        foreach ($bookings as $booking) {

            $date = new \DateTime($booking->getDate());
            $line = [
                $date->format('d.m.Y'),
                $booking->getState(),
                $booking->getConfig('personal.email'),
            ];

            fputcsv($fp, $line);
        }

        header("Content-Type: text/csv; charset=utf-8");
        header('Content-Disposition: attachment; filename="Buchungen-' . date('Y-m-d') . '.csv"');

        fclose($fp);


        exit;


        foreach ($bookingRepository->fetch() as $booking) {
            d($booking);
        }
    }

    /** 
     * 
     */
    public function indexAction(

    )
    {
        return self::getResponse();
    }
}

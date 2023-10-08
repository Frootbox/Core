<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Viewhelper;

class Additives extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getFromPositions' => [
            'positions',
        ],
    ];

    /**
     *
     */
    public function getFromPositionsAction(
        \Frootbox\Db\Result $positions,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository,
    ): array
    {
        $additives = [];

        foreach ($positions as $position) {
            foreach ($position->getPrices() as $price) {
                foreach ($price->getAdditives() as $additive) {

                    $additives[$additive->getSymbol()] = $additive;
                }
            }
        }

        ksort($additives);

        return $additives;
    }
}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Partials\CartProgress;

class Partial extends \Frootbox\View\Partials\AbstractPartial
{
    /**
     * @return string
     */
    protected function getPath(): string
    {
        return __DIR__ . '/';
    }

    public function onBeforeRendering(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    ): mixed
    {
        // Obtain checkout plugin
        $plugin = $this->getAttribute('plugin');

        // Obtain step
        $stepId = $this->getAttribute('step');

        if (empty($plugin->getConfig('ShowProgressBar'))) {
            return false;
        }

        $steps = [
            [
                'Key' => 'Index',
                'Uri' => $plugin->getActionUri('index'),
                'Title' => 'Start',
            ],
        ];

        // Check for free choice of delivery day
        foreach ($shopcart->getItems() as $item) {
            if (!empty($item->getProduct()->getConfig('freeChoiceOfDeliveryDay'))) {

                if ($shopcart->getShipping('type') == 'selfPickup') {

                    $steps[] = [
                        'Key' => 'ChoiceOfSelfPickup',
                        'Uri' => $plugin->getActionUri('choiceOfSelfPickup'),
                        'Title' => 'Abholdatum',
                    ];

                    $steps[] = [
                        'Key' => 'ChoiceOfSelfPickupTime',
                        'Uri' => $plugin->getActionUri('choiceOfSelfPickupTime'),
                        'Title' => 'Abholzeit',
                    ];
                }
                else {

                    $steps[] = [
                        'Key' => 'ChoiceOfDelivery',
                        'Uri' => $plugin->getActionUri('choiceOfDelivery'),
                        'Title' => 'Lieferdatum',
                    ];
                }


                break;
            }
        }

        $steps[] = [
            'Key' => 'Checkout',
            'Uri' => $plugin->getActionUri('checkout'),
            'Title' => 'Persönliche Daten',
        ];

        $steps[] = [
            'Key' => 'SelectPayment',
            'Uri' => $plugin->getActionUri('selectPayment'),
            'Title' => 'Zahlungsdaten',
        ];

        $steps[] = [
            'Key' => 'Review',
            'Uri' => $plugin->getActionUri('review'),
            'Title' => 'Abschluss',
        ];

        $hit = false;

        foreach ($steps as $index => $step) {

            $steps[$index]['Disabled'] = $hit;

            if ($step['Key'] == $stepId) {
                $steps[$index]['Active'] = true;
                $hit = true;
            }
        }

        return [
            'Steps' => $steps,
        ];
    }
}
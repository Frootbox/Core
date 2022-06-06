<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Viewhelper;

class Coupons extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getCouponForUid' => [
            'uid',
            'value'
        ]
    ];

    /**
     *
     */
    public function getCouponForUidAction(
        $uid,
        $value,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    )
    {
        // Fetch coupon
        $coupon = $couponsRepository->fetchOne([
            'where' => [
                'uid' => $uid
            ]
        ]);

        if (empty($coupon)) {

            // Obtain shop plugin
            $shopPlugin = $contentElements->fetchOne([
                'where' => [
                    'className' => 'Frootbox\\Ext\\Core\\ShopSystem\\Plugins\\ShopSystem\\Plugin'
                ]
            ]);

            $coupon = new \Frootbox\Ext\Core\ShopSystem\Persistence\Coupon([
                'pluginId' => $shopPlugin->getId(),
                'pageId' => $shopPlugin->getPageId(),
                'title' => 'automatisch generiert',
                'uid' => $uid,
                'config' => [
                    'amount' => 1,
                    'value' => $value,
                ],
            ]);

            $coupon = $couponsRepository->insert($coupon);
        }

        return $coupon;
    }
}

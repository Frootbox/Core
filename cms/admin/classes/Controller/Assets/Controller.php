<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-08-06
 */

namespace Frootbox\Admin\Controller\Assets;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{


    public function ajaxItemsSort(
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Model $model,
    ): \Frootbox\Admin\Controller\Response
    {
        $orderId = count($get->get('row')) + 1;

        $model->setTable('assets');

        foreach ($get->get('row') as $assetId) {

            // Fetch asset
            $asset = $model->fetchById($assetId);

            $asset->setOrderId($orderId--);
            $asset->save();
        }

        return self::getResponse('json', 200, []);
    }

    /**
     * @param \Frootbox\Db\Db $db
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Db\Model $model
     *
     * @return \Frootbox\Admin\Controller\Response
     *
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxSwitchVisibility(
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Model $model
    ): \Frootbox\Admin\Controller\Response
    {
        if (empty($get->get('repository'))) {

            // Fetch asset
            $model->setTable('assets');
            $row = $model->fetchById($get->get('assetId'));
        }
        else {

            // Fetch row from dedicated repository
            $repository = $db->getRepository($get->get('repository'));
            $row = $repository->fetchById($get->get('assetId'));
        }

        $row->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-asset="' . $row->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2',
            ],
            'addClass' => [
                'selector' => '.visibility[data-asset="' . $row->getId() . '"]',
                'className' => $row->getVisibilityString(),
            ],
        ]);
    }
}

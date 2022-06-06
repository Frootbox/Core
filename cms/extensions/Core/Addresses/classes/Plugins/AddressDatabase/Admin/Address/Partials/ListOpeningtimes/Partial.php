<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListOpeningtimes;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\OpeningTimes $openingTimesRepository,
    ): Response
    {
        // Obtain address
        $address = $this->getData('address');

        // Fetch openingtimes
        $result = $openingTimesRepository->fetch([
            'where' => [
                'parentId' => $address->getId(),
            ],
        ]);

        return new Response('html', 200, [
            'openingTimes' => $result,
        ]);
    }
}
<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Account;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxAddressCreateAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Create new address
        $address = new \Frootbox\Ext\Core\ShopSystem\Persistence\Address([
            'pageId' => $this->getPageId(),
            'pluginId' => $this->getId(),
            'title' => (string) null,
            'uid' => $session->getUser()->getUid('shop-addess'),
        ]);

        $addressesRepository->insert($address);

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('addresses'));
    }

    /**
     *
     */
    public function ajaxAddressDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        if ($address->getUidRaw() != $session->getUser()->getUid('shop-addess')) {
            throw new \Exception('AccessDenied');
        }

        $address->delete();

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('addresses'));
    }

    /**
     *
     */
    public function ajaxAddressUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        if ($address->getUidRaw() != $session->getUser()->getUid('shop-addess')) {
            throw new \Exception('AccessDenied');
        }

        // Update address
        $address->setTitle($post->get('title'));
        $address->setAddition($post->get('addition'));
        $address->setStreet($post->get('street'));
        $address->setStreetNumber($post->get('streetNumber'));
        $address->setZipcode($post->get('zipcode'));
        $address->setCity($post->get('city'));
        $address->save();

        return new \Frootbox\View\ResponseJson([

        ]);
    }

    /**
     * @access
     */
    public function accountAction(): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new \Frootbox\View\Response([

        ]);
    }

    /**
     *
     */
    public function addressEditAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
    )
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Fetch address
        $address = $addressesRepository->fetchById($this->getAttribute('addressId'));

        return new \Frootbox\View\Response([
            'address' => $address,
        ]);
    }

    /**
     *
     */
    public function addressesAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Fetch addresses
        $result = $addressesRepository->fetch([
            'where' => [
                'uid' => $session->getUser()->getUid('shop-addess'),
            ],
        ]);

        return new \Frootbox\View\Response([
            'addresses' => $result,
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        if (IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('account'));
        }

        return new \Frootbox\View\Response([

        ]);
    }

    /**
     * @param \Frootbox\Session $session
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @return Response
     * @throws \Exception
     */
    public function orderAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        $user = $session->getUser();

        $order = $bookingsRepository->fetchById($this->getAttribute('orderId'));

        if ($order->getUserId() != $user->getId()) {
            throw new \Exception('AccessDenied');
        }

        return new \Frootbox\View\Response([
            'booking' => $order,
        ]);
    }

    /**
     *
     */
    public function ordersAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Fetch orders
        $result = $bookingsRepository->fetch([
            'where' => [
                'userId' => $session->getUser()->getId(),
            ],
            'order' => [ 'date DESC' ],
        ]);

        return new \Frootbox\View\Response([
            'orders' => $result,
        ]);
    }
}

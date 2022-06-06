<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Navigations\Items\Address;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('addressId'))) {
            return '#unconfigured-navigation-item';
        }

        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
        $address = $addressesRepository->fetchById($this->getConfig('addressId'));

        return $address->getUri();

        $url = 'fbx://page:' . $this->getConfig('categoryId');

        if (!empty($this->getConfig('anchor'))) {
            $url .= '#' . $this->getConfig('anchor');
        }

        return $url;
    }

    /**
     *
     */
    public function getAddresses(): \Frootbox\Db\Result
    {
        // Fetch categories
        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
        $result = $addressesRepository->fetch();

        return $result;
    }

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
    public function initialize(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): void
    {
        // Fetch matching pages
        $result = $addressesRepository->fetch([
            'where' => [
                'title' => $this->getTitle(),
            ],
        ]);

        if ($result->getCount() == 1) {

            $category = $result->current();

            $this->addConfig([
                'addressId' => $category->getId(),
            ]);
            $this->save();
        }
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'addressId' => $post->get('addressId'),
        ]);
    }
}

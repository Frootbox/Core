<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Persistence;

class Address extends \Frootbox\Persistence\AbstractRow
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Config;
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Visibility;
    use \Frootbox\Persistence\Traits\Tags;

    protected $table = 'locations';
    protected $model = Repositories\Addresses::class;

    /**
     * @exposed
     */
    protected $title;

    /**
     * @exposed
     */
    protected $addition;

    /**
     * @exposed
     */
    protected $street;

    /**
     * @exposed
     */
    protected $streetNumber;

    /**
     * @exposed
     */
    protected $zipcode;

    /**
     * @exposed
     */
    protected $city;

    /**
     *
     */
    public function getFacebookDisplay(): ?string
    {
        $url = $this->getFacebook();
        $url = str_replace('https://www.facebook.com/', '', $url);
        $url = str_replace('www.facebook.com/', '', $url);

        return $url;
    }

    /**
     *
     */
    public function getFacebookExtern(): ?string
    {
        $url = $this->getFacebook();

        if (preg_match('#^www\.facebook\.com/#i', $url)) {
            $url = 'https://' . $url;
        }

        if (!preg_match('#^https://www\.facebook\.com/#i', $url)) {
            $url = 'https://www.facebook.com/' . $url;
        }

        return $url;
    }

    /**
     *
     */
    public function getInstagramDisplay(): ?string
    {
        $url = $this->getInstagram();
        $url = str_replace('https://www.instagram.com/', '', $url);
        $url = str_replace('www.instagram.com/', '', $url);

        return $url;
    }

    /**
     *
     */
    public function getInstagramExtern(): ?string
    {
        $url = $this->getInstagram();

        if (preg_match('#^www\.instagram\.com/#i', $url)) {
            $url = 'https://' . $url;
        }

        if (!preg_match('#^https://www\.instagram\.com/#i', $url)) {
            $url = 'https://www.instagram.com/' . $url;
        }

        return $url;
    }

    /**
     * Generate addresses alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noAddressDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showAddress',
                'addressId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getOpeningTimes(): \Frootbox\Db\Result
    {
        // Fetch opening times
        $openingTimesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\OpeningTimes::class);
        $result = $openingTimesRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getUrlCleaned(): string
    {
        $url = str_replace('https://', '', $this->getUrl());
        $url = str_replace('http://', '', $url);

        return $url;
    }

    /**
     *
     */
    public function getUrlDisplay(): string
    {
        $url = $this->getUrl();
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);

        return $url;
    }

    /**
     *
     */
    public function getUrlExtern(): ?string
    {
        $url = $this->getUrl();

        if (substr($url, 0, 4) == 'www.') {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Check if location is visible to user
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return ($this->getVisibility() >= (IS_EDITOR ? 1 : 2));
    }
}

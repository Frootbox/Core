<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait StandardUrls
{
    /**
     *
     */
    public function getPrivacyPolicyLink (
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
    ): ?string
    {

        if (!empty($config->get('general.urls.privacy'))) {
            return $config->get('general.urls.privacy');
        }

        $likes = [
            'de-DE' => '%datenschutz%',
            'en-GB' => '%privacy%',
        ];

        $match = $likes[GLOBAL_LANGUAGE] ?? '%datenschutz%';
        $match = '%datenschutz%';

        // Check for pages named like %datenschutz%
        $page = $pagesRepository->fetchOne([
            'where' => [
                new \Frootbox\Db\Conditions\Like('title', $match),
            ],
        ]);

        if ($page) {
            return $page->getUri();
        }

        return null;
    }

    /**
     *
     */
    public function getRightOfWithdrawalLink (
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): ?string
    {
        if (!empty($config->get('general.urls.withdrawal'))) {
            return $config->get('general.urls.withdrawal');
        }

        // Check for pages named like %widerruf%
        $page = $pagesRepository->fetchOne([
            'where' => [
                new \Frootbox\Db\Conditions\Like('title', '%widerruf%')
            ]
        ]);

        if ($page) {
            return $page->getUri();
        }

        return null;
    }
}

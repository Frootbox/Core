<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\NewsletterConnectors\Simple;

class Connector extends \Frootbox\Ext\Core\ShopSystem\NewsletterConnectors\Connector
{
    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): void
    {
        $data = $post->get('newsletter');

        if (empty($data['consent'])) {
            return;
        }

        $shopcart->setNewsletterConsent(true);
    }
}

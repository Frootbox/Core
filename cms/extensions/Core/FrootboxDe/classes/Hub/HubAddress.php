<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * Whether an address is local, so frootbox.de on the internet could not
 * reach it. The same rules as the service and Frootbox 2.
 */
final class HubAddress
{
    private const LOCAL_SUFFIXES = ['.localhost', '.test', '.local', '.internal', '.example', '.invalid', '.home.arpa'];

    public static function isLocal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return true;
        }

        $host = strtolower(trim($host, '[]'));

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        if ($host === 'localhost' || !str_contains($host, '.')) {
            return true;
        }

        foreach (self::LOCAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}

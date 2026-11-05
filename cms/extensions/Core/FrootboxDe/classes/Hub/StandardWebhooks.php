<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * Signatures after Standard Webhooks in the asymmetric form "v1a"
 * (Ed25519), as frootbox.de Connect v1 uses them. Keys are written
 * "whsk_" (secret, the 32 byte seed) and "whpk_" (public) with base64.
 * The same as Security\StandardWebhooks in Frootbox 2.
 */
final class StandardWebhooks
{
    public const SECRET_KEY_PREFIX = 'whsk_';
    public const PUBLIC_KEY_PREFIX = 'whpk_';
    public const PUBLIC_KEY_PATTERN = '~^whpk_[A-Za-z0-9+/]{43}=$~';
    public const TOLERANCE = 300;

    /**
     * @return array{secret: string, public: string}
     */
    public static function keyPair(): array
    {
        $secret = self::SECRET_KEY_PREFIX . base64_encode(random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES));

        return ['secret' => $secret, 'public' => self::publicKey($secret)];
    }

    public static function publicKey(string $secretKey): string
    {
        return self::PUBLIC_KEY_PREFIX . base64_encode(sodium_crypto_sign_publickey(self::keyPairOf($secretKey)));
    }

    public static function signContent(string $secretKey, string $content): string
    {
        return 'v1a,' . base64_encode(sodium_crypto_sign_detached($content, sodium_crypto_sign_secretkey(self::keyPairOf($secretKey))));
    }

    /**
     * @return array{webhook-id: string, webhook-timestamp: string, webhook-signature: string}
     */
    public static function headers(string $secretKey, string $id, string $body, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();

        return [
            'webhook-id' => $id,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => self::signContent($secretKey, $id . '.' . $timestamp . '.' . $body),
        ];
    }

    /**
     * @param list<string>          $publicKeys
     * @param array<string, string> $headers    webhook-id, webhook-timestamp, webhook-signature
     */
    public static function verify(array $publicKeys, array $headers, string $body, ?int $now = null): bool
    {
        $headers = array_change_key_case($headers);
        $id = (string) ($headers['webhook-id'] ?? '');
        $timestamp = (string) ($headers['webhook-timestamp'] ?? '');
        $signatures = (string) ($headers['webhook-signature'] ?? '');

        if ($id === '' || !ctype_digit($timestamp) || $signatures === '') {
            return false;
        }

        if (abs(($now ?? time()) - (int) $timestamp) > self::TOLERANCE) {
            return false;
        }

        $content = $id . '.' . $timestamp . '.' . $body;

        foreach ($publicKeys as $key) {
            foreach (explode(' ', $signatures) as $signature) {
                if (self::matches($key, $content, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function matches(string $publicKey, string $content, string $signature): bool
    {
        if (!str_starts_with($publicKey, self::PUBLIC_KEY_PREFIX) || !str_starts_with($signature, 'v1a,')) {
            return false;
        }

        $public = base64_decode(substr($publicKey, strlen(self::PUBLIC_KEY_PREFIX)), true);
        $raw = base64_decode(substr($signature, 4), true);

        if (!is_string($public) || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || !is_string($raw) || strlen($raw) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($raw, $content, $public);
    }

    private static function keyPairOf(string $secretKey): string
    {
        $seed = str_starts_with($secretKey, self::SECRET_KEY_PREFIX) ? base64_decode(substr($secretKey, strlen(self::SECRET_KEY_PREFIX)), true) : false;

        if (!is_string($seed) || strlen($seed) !== SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            throw new \InvalidArgumentException('A secret key is "whsk_" and the base64 of 32 bytes.');
        }

        return sodium_crypto_sign_seed_keypair($seed);
    }
}

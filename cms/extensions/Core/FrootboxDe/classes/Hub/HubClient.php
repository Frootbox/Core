<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * Talks to frootbox.de over HTTPS with cURL: no redirects, 15 seconds,
 * every request signed with the key of the website.
 */
class HubClient
{
    public const DEFAULT_URL = 'https://hub.frootbox.de';

    public function __construct(
        private readonly \Frootbox\Config\Config $config,
    ) {}

    /**
     * The hub from "Ext.Core.FrootboxDe.hubUrl" in localconfig.php, by
     * default hub.frootbox.de; http only for a hub on this computer.
     */
    public function url(): string
    {
        $url = rtrim((string) ($this->config->get('Ext.Core.FrootboxDe.hubUrl') ?? self::DEFAULT_URL), '/');

        if (preg_match('#^https?://[^/?\#]+$#', $url) !== 1 || (parse_url($url, PHP_URL_SCHEME) !== 'https' && !HubAddress::isLocal($url))) {
            throw HubException::badHubUrl($url);
        }

        return $url;
    }

    /**
     * The public keys of the hub, read over TLS.
     *
     * @return list<string>
     */
    public function keys(string $hubUrl): array
    {
        $data = $this->request('GET', $hubUrl . '/.well-known/frootbox/keys');
        $keys = [];

        foreach (is_array($data['keys'] ?? null) ? $data['keys'] : [] as $entry) {
            $key = is_array($entry) ? ($entry['key'] ?? null) : $entry;

            if (is_string($key) && preg_match(StandardWebhooks::PUBLIC_KEY_PATTERN, $key) === 1) {
                $keys[] = $key;
            }
        }

        if ($keys === []) {
            throw HubException::badAnswer('keys');
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<mixed>
     */
    public function send(string $secretKey, string $hubUrl, string $method, string $path, array $body = []): array
    {
        $json = json_encode((object) $body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $headers = StandardWebhooks::headers($secretKey, 'msg_' . bin2hex(random_bytes(12)), $json);

        return $this->request($method, $hubUrl . $path, ['Content-Type' => 'application/json'] + $headers, $json);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    protected function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $lines = ['Accept: application/json'];

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_USERAGENT => 'Frootbox 1 (frootbox.de Connect v1)',
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $content = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($content)) {
            throw HubException::unreachable($url, $error !== '' ? $error : 'keine Antwort');
        }

        $data = $content === '' ? [] : json_decode($content, true);

        if ($status >= 400) {
            $detail = is_array($data) && is_string($data['detail'] ?? null) ? $data['detail'] : (is_array($data) && is_string($data['title'] ?? null) ? $data['title'] : 'ohne Begründung');

            throw HubException::refused($status, $detail);
        }

        if ($status >= 300 || !is_array($data)) {
            throw HubException::badAnswer('JSON');
        }

        return $data;
    }
}

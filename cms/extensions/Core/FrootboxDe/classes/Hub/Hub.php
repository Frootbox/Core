<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * The connection of this website to frootbox.de ("frootbox.de Connect v1",
 * documentation/concepts/hub.md in the repository of Frootbox 2). Frootbox 1
 * speaks the same protocol as Frootbox 2; only the switch "status" exists,
 * packages and the marketplace are not for Frootbox 1, the sign-in comes
 * later.
 */
class Hub
{
    public const SYSTEM = 'frootbox1';
    public const ID = '[A-Za-z0-9_-]{8,100}';
    public const REQUEST_MINUTES = 15;
    public const STATUS_PATH = '/.well-known/frootbox/status';
    private const SEEN_SECONDS = 600;

    public function __construct(
        private readonly \Frootbox\Config\Config $config,
        private readonly State $state,
        private readonly HubClient $client,
    ) {}

    /**
     * The address of the website, from "Ext.Core.FrootboxDe.siteUrl" or the
     * request, without a slash at the end.
     */
    public function siteUrl(): ?string
    {
        $url = $this->config->get('Ext.Core.FrootboxDe.siteUrl');
        $url = is_string($url) && $url !== '' ? $url : (defined('SERVER_PATH_PROTOCOL') ? SERVER_PATH_PROTOCOL : '');

        return preg_match('#^https?://[^/?\#]+(/[^?\#]*)?$#', $url) === 1 ? rtrim($url, '/') : null;
    }

    public function siteName(): string
    {
        $name = $this->config->get('Ext.Core.FrootboxDe.name');

        return is_string($name) && trim($name) !== '' ? mb_substr(trim($name), 0, 200) : (string) parse_url((string) $this->siteUrl(), PHP_URL_HOST);
    }

    public static function version(): string
    {
        $configuration = require CORE_DIR . 'cms/extensions/Core/System/configuration.php';

        return (string) ($configuration['version'] ?? '1');
    }

    /**
     * Why the website cannot connect now, or null.
     */
    public function hint(): ?string
    {
        try {
            $this->check();
        }
        catch (HubException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    /**
     * @return array{site: string, hub: string}
     */
    public function check(): array
    {
        if (!function_exists('sodium_crypto_sign_detached') || !function_exists('curl_init')) {
            throw new HubException('PHP braucht die Erweiterungen sodium und curl.');
        }

        $site = $this->siteUrl() ?? throw new HubException('Die Adresse der Website ist unbekannt.');
        $hub = $this->client->url();

        if (HubAddress::isLocal($site) && !HubAddress::isLocal($hub)) {
            throw HubException::local($site);
        }

        return ['site' => $site, 'hub' => $hub];
    }

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->state->read();
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function connected(array $state): bool
    {
        return !empty($state['connectionId']);
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function pending(array $state): bool
    {
        return !self::connected($state) && !empty($state['requestId']) && (int) ($state['requestExpiresAt'] ?? 0) > time();
    }

    /**
     * What the screen in the backend shows.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        $state = $this->current();
        $connected = self::connected($state);
        $pending = self::pending($state);

        try {
            $hubUrl = $this->client->url();
        }
        catch (HubException) {
            $hubUrl = null;
        }

        return [
            'state' => $connected ? 'connected' : ($pending ? 'pending' : 'none'),
            'hubUrl' => $connected ? $state['hubUrl'] : $hubUrl,
            'siteUrl' => $this->siteUrl(),
            'hint' => $connected ? null : $this->hint(),
            'requestId' => $pending ? $state['requestId'] : null,
            'requestUrl' => $pending ? $state['hubUrl'] . '/connect/' . $state['requestId'] : null,
            'requestExpiresAt' => $pending ? (int) $state['requestExpiresAt'] : null,
            'connectionId' => $connected ? $state['connectionId'] : null,
            'organization' => $connected ? ($state['organization'] ?? null) : null,
            'connectedAt' => $connected ? ($state['connectedAt'] ?? null) : null,
            'status' => $connected && !empty($state['status']),
            'statusUrl' => $connected && !empty($state['status']) ? $this->siteUrl() . self::STATUS_PATH : null,
            'lastPoll' => $connected ? ($state['lastPoll'] ?? null) : null,
            'publicKey' => $state['publicKey'] ?? null,
            'file' => $this->state->file(),
        ];
    }

    /**
     * Opens a request at the hub; the browser goes on to the returned
     * address and comes back to $return with the code.
     */
    public function connect(bool $status, string $return): string
    {
        ['site' => $site, 'hub' => $hubUrl] = $this->check();

        if (!str_starts_with($return, $site . '/') || preg_match('~[\s\x00-\x1f]~', $return) === 1) {
            throw new HubException('Die Rücksprungadresse muss auf dieser Website liegen.');
        }

        $state = $this->identity($hubUrl);
        $answer = $this->client->send($state['secretKey'], $hubUrl, 'POST', '/connect/v1/requests', $this->describe($state, $site, $status) + ['return' => $return]);
        $id = $answer['id'] ?? null;

        if (!is_string($id) || preg_match('~^' . self::ID . '$~D', $id) !== 1) {
            throw HubException::badAnswer('id');
        }

        $this->state->update(static fn (array $state): array => [
            'requestId' => $id,
            'requestExpiresAt' => time() + self::REQUEST_MINUTES * 60,
            'requestStatus' => $status,
        ] + $state);

        return $hubUrl . '/connect/' . $id;
    }

    /**
     * Redeems the code from the hub: after the browser came back with
     * $request, or alone, a code "XXXX-XXXX" from the account area.
     */
    public function complete(string $code, ?string $request = null, bool $status = true): void
    {
        ['site' => $site, 'hub' => $hubUrl] = $this->check();

        if (preg_match('~^[A-Za-z0-9_-]{6,100}$~D', $code) !== 1) {
            throw new HubException('Der Code ist ungültig.');
        }

        if ($request === null) {
            $state = $this->identity($hubUrl);
        }
        else {
            $state = $this->current();

            if (self::connected($state)) {
                throw HubException::connected();
            }

            if (!self::pending($state) || !hash_equals((string) $state['requestId'], $request)) {
                throw HubException::noRequest();
            }

            $status = !empty($state['requestStatus']);
        }

        $token = $status ? self::token() : null;
        $access = $token === null ? null : ['url' => $site . self::STATUS_PATH, 'token' => $token];

        $answer = $request === null
            ? $this->client->send($state['secretKey'], $state['hubUrl'], 'POST', '/connect/v1/codes/' . rawurlencode($code), $this->describe($state, $site, $status) + ['status' => $access])
            : $this->client->send($state['secretKey'], $state['hubUrl'], 'POST', '/connect/v1/requests/' . rawurlencode($request) . '/complete', ['code' => $code, 'status' => $access]);

        $id = $answer['connection'] ?? null;
        $organization = $answer['organization'] ?? null;

        if (!is_string($id) || preg_match('~^' . self::ID . '$~D', $id) !== 1) {
            throw HubException::badAnswer('connection');
        }

        $this->state->update(static fn (array $state): array => [
            'requestId' => null,
            'requestExpiresAt' => null,
            'requestStatus' => null,
            'connectionId' => $id,
            'organization' => is_string($organization) ? mb_substr($organization, 0, 255) : null,
            'connectedAt' => date(DATE_ATOM),
            'status' => $status,
            'statusTokenHash' => $token === null ? null : hash('sha256', $token),
            'lastPoll' => null,
        ] + $state);
    }

    /**
     * Turns reading the status on or off. On makes a new token, off makes
     * the old one stop working at once, even if the hub cannot be told.
     */
    public function switchStatus(bool $status): ?string
    {
        $state = $this->current();

        if (!self::connected($state)) {
            throw HubException::notConnected();
        }

        if (!empty($state['status']) === $status) {
            return null;
        }

        $path = '/connect/v1/connections/' . rawurlencode((string) $state['connectionId']);

        if ($status) {
            $site = $this->siteUrl() ?? throw new HubException('Die Adresse der Website ist unbekannt.');
            $token = self::token();
            $this->client->send($state['secretKey'], $state['hubUrl'], 'PATCH', $path, ['switches' => self::switches(true), 'status' => ['url' => $site . self::STATUS_PATH, 'token' => $token]]);
            $this->state->update(static fn (array $state): array => ['status' => true, 'statusTokenHash' => hash('sha256', $token), 'lastPoll' => null] + $state);

            return null;
        }

        $this->state->update(static fn (array $state): array => ['status' => false, 'statusTokenHash' => null] + $state);

        try {
            $this->client->send($state['secretKey'], $state['hubUrl'], 'PATCH', $path, ['switches' => self::switches(false), 'status' => null]);
        }
        catch (HubException $exception) {
            return 'Der Status ist aus, frootbox.de wusste davon aber nichts: ' . $exception->getMessage();
        }

        return null;
    }

    /**
     * Forgets the connection and the keys of the hub. $forgetKey also
     * deletes the key pair, e.g. in a copy of the website.
     *
     * @return string|null a warning when the hub could not be told
     */
    public function disconnect(bool $notify = true, bool $forgetKey = false): ?string
    {
        $state = $this->current();

        if ($forgetKey) {
            $this->state->forget();
        }
        elseif ($state !== []) {
            $this->state->update(static fn (array $state): array => [
                'hubKeys' => [],
                'requestId' => null,
                'requestExpiresAt' => null,
                'requestStatus' => null,
                'connectionId' => null,
                'organization' => null,
                'connectedAt' => null,
                'status' => false,
                'statusTokenHash' => null,
                'lastPoll' => null,
            ] + $state);
        }

        if ($notify && self::connected($state)) {
            try {
                $this->client->send($state['secretKey'], $state['hubUrl'], 'DELETE', '/connect/v1/connections/' . rawurlencode((string) $state['connectionId']));
            }
            catch (HubException $exception) {
                return 'Die Verbindung ist getrennt, frootbox.de wusste davon aber nichts: ' . $exception->getMessage();
            }
        }

        return null;
    }

    /**
     * @param list<mixed> $keys
     */
    public function updateKeys(array $keys): void
    {
        $valid = array_values(array_unique(array_filter($keys, static fn (mixed $key): bool => is_string($key) && preg_match(StandardWebhooks::PUBLIC_KEY_PATTERN, $key) === 1)));

        if ($valid === [] || count($valid) !== count($keys) || count($valid) > 10) {
            throw new HubException('keys must be between 1 and 10 public keys "whpk_…".');
        }

        $this->state->update(static fn (array $state): array => ['hubKeys' => $valid] + $state);
    }

    /**
     * Whether the bearer token belongs to the status; notes the time.
     */
    public function allowsStatus(string $token): bool
    {
        $state = $this->current();
        $hash = $state['statusTokenHash'] ?? null;

        if (!self::connected($state) || empty($state['status']) || !is_string($hash) || !hash_equals($hash, hash('sha256', $token))) {
            return false;
        }

        $this->state->update(static fn (array $state): array => ['lastPoll' => date(DATE_ATOM)] + $state);

        return true;
    }

    /**
     * Each webhook-id of the hub counts once within ten minutes.
     */
    public function firstSeen(string $id): bool
    {
        $first = false;

        $this->state->update(static function (array $state) use ($id, &$first): array {
            $now = time();
            $seen = array_filter(is_array($state['seen'] ?? null) ? $state['seen'] : [], static fn (mixed $until): bool => is_int($until) && $until > $now);
            $first = !isset($seen[$id]);
            $seen[$id] = $now + self::SEEN_SECONDS;
            $state['seen'] = $seen;

            return $state;
        });

        return $first;
    }

    /**
     * @return array{status: bool, packages: bool, login: bool}
     */
    public static function switches(bool $status): array
    {
        return ['status' => $status, 'packages' => false, 'login' => false];
    }

    /**
     * Makes the key pair if there is none and reads the keys of the hub.
     *
     * @return array<string, mixed>
     */
    private function identity(string $hubUrl): array
    {
        $state = $this->current();

        if (self::connected($state)) {
            throw HubException::connected();
        }

        $keys = $this->client->keys($hubUrl);

        return $this->state->update(static function (array $state) use ($hubUrl, $keys): array {
            if (empty($state['secretKey'])) {
                $pair = StandardWebhooks::keyPair();
                $state['secretKey'] = $pair['secret'];
                $state['publicKey'] = $pair['public'];
                $state['createdAt'] = date(DATE_ATOM);
            }

            $state['hubUrl'] = $hubUrl;
            $state['hubKeys'] = $keys;

            return $state;
        });
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function describe(array $state, string $site, bool $status): array
    {
        return [
            'url' => $site,
            'name' => $this->siteName(),
            'system' => ['name' => self::SYSTEM, 'version' => self::version()],
            'publicKey' => $state['publicKey'],
            'switches' => self::switches($status),
        ];
    }

    private static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}

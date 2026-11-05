<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Routing;

use Frootbox\Ext\Core\FrootboxDe\Hub\Hub;
use Frootbox\Ext\Core\FrootboxDe\Hub\HubException;
use Frootbox\Ext\Core\FrootboxDe\Hub\StandardWebhooks;
use Frootbox\Ext\Core\FrootboxDe\Hub\StatusDocument;

/**
 * The addresses frootbox.de calls under /.well-known/frootbox/: verify,
 * disconnect and keys, each signed with a key of the hub, and the status
 * with the bearer token of the connection. They run past the site
 * password (GlobalPasswordRoute lets them through).
 */
class CallbackRoute extends \Frootbox\Routing\AbstractRoute
{
    public const VERIFY_LABEL = 'frootbox-connect-verify-v1';
    private const SIZE = 10000;

    protected function getMatchingRegex(): string
    {
        return '#^\.well-known/frootbox/(verify|disconnect|keys|status)/?(?:\?.*)?$#D';
    }

    public function performRouting(\DI\Container $container): void
    {
        preg_match($this->getMatchingRegex(), $this->getRequest()->getRequestTarget(), $match);

        $hub = $container->get(Hub::class);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($match[1] === 'status') {
                if ($method !== 'GET' && $method !== 'HEAD') {
                    $this->problem(405, 'method', 'Only GET.');
                }

                $this->status($hub, $container->get(StatusDocument::class));
            }

            if ($method !== 'POST') {
                $this->problem(405, 'method', 'Only POST.');
            }

            $this->callback($hub, $match[1]);
        }
        catch (HubException $exception) {
            $this->problem(503, 'unavailable', $exception->getMessage());
        }
    }

    private function status(Hub $hub, StatusDocument $document): never
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        $token = preg_match('~^Bearer\s+(\S+)$~i', $header, $match) === 1 ? $match[1] : '';

        if ($token === '' || !$hub->allowsStatus($token)) {
            header('WWW-Authenticate: Bearer');
            $this->problem(401, 'unauthorized', 'A valid bearer token is required.');
        }

        // Wrapped like an answer of the REST API of Frootbox 2.
        $this->json(200, ['data' => $document->build()]);
    }

    private function callback(Hub $hub, string $name): never
    {
        $body = (string) file_get_contents('php://input', false, null, 0, self::SIZE + 1);

        if (strlen($body) > self::SIZE) {
            $this->problem(413, 'too-large', 'The message is too large.');
        }

        $state = $hub->current();
        $keys = is_array($state['hubKeys'] ?? null) ? array_values(array_filter($state['hubKeys'], 'is_string')) : [];

        if ($keys === []) {
            $this->problem(404, 'not-connected', 'The website is not connected.');
        }

        $headers = [
            'webhook-id' => (string) ($_SERVER['HTTP_WEBHOOK_ID'] ?? ''),
            'webhook-timestamp' => (string) ($_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? ''),
            'webhook-signature' => (string) ($_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? ''),
        ];

        if (!StandardWebhooks::verify($keys, $headers, $body)) {
            $this->problem(401, 'signature', 'The signature does not belong to a key of the hub, or the message is too old.');
        }

        if (!$hub->firstSeen($headers['webhook-id'])) {
            $this->problem(409, 'replay', 'This message came already.');
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            $this->problem(400, 'json', 'The body is no JSON object.');
        }

        if ($name === 'verify') {
            $this->verify($hub, $state, $data);
        }

        if ($name === 'disconnect') {
            $id = $data['connection'] ?? null;

            if (!Hub::connected($state) || !is_string($id) || !hash_equals((string) $state['connectionId'], $id)) {
                $this->problem(404, 'unknown', 'There is no connection with this id.');
            }

            $hub->disconnect(notify: false);
            $this->empty();
        }

        $keys = $data['keys'] ?? null;
        $keys = is_array($keys) ? array_map(static fn (mixed $entry): mixed => is_array($entry) ? ($entry['key'] ?? null) : $entry, array_values($keys)) : null;

        if ($keys === null || !Hub::connected($state)) {
            $this->problem(400, 'input', 'keys must be a list of public keys.');
        }

        try {
            $hub->updateKeys($keys);
        }
        catch (HubException $exception) {
            $this->problem(422, 'unprocessable', $exception->getMessage());
        }

        $this->empty();
    }

    /**
     * @param array<string, mixed> $state
     * @param array<mixed>         $data
     */
    private function verify(Hub $hub, array $state, array $data): never
    {
        $id = $data['id'] ?? null;
        $challenge = $data['challenge'] ?? null;

        if (!is_string($id) || !is_string($challenge) || preg_match('~^[A-Za-z0-9_-]{16,128}$~D', $challenge) !== 1) {
            $this->problem(400, 'input', 'id and challenge are required, the challenge 16 to 128 characters of base64url.');
        }

        $known = (Hub::pending($state) && hash_equals((string) $state['requestId'], $id))
            || (Hub::connected($state) && hash_equals((string) $state['connectionId'], $id));

        if (!$known || empty($state['secretKey'])) {
            $this->problem(404, 'unknown', 'There is no open request or connection with this id.');
        }

        $this->json(200, ['signature' => StandardWebhooks::signContent((string) $state['secretKey'], self::VERIFY_LABEL . ':' . $id . ':' . $challenge)]);
    }

    private function problem(int $status, string $code, string $detail): never
    {
        $this->json($status, [
            'type' => 'https://frootbox.de/problems/' . $code,
            'title' => $detail,
            'status' => $status,
            'detail' => $detail,
        ], 'application/problem+json');
    }

    /**
     * @param array<mixed> $data
     */
    private function json(int $status, array $data, string $type = 'application/json'): never
    {
        $this->headers($status);
        header('Content-Type: ' . $type . '; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    private function empty(): never
    {
        $this->headers(204);
        exit;
    }

    private function headers(int $status): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
    }
}

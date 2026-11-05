<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Apps\Connection;

use Frootbox\Admin\Controller\Response;
use Frootbox\Ext\Core\FrootboxDe\Hub\Hub;
use Frootbox\Ext\Core\FrootboxDe\Hub\HubException;
use Frootbox\Ext\Core\FrootboxDe\Hub\StatusDocument;

/**
 * Backend screen "frootbox.de": connect, turn the status on or off,
 * disconnect, and show exactly what frootbox.de reads. Only for
 * SuperAdmins. Plain forms with POST and a token in the session; each
 * action goes back to the screen with a message.
 */
class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function indexAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        Hub $hub,
        StatusDocument $document,
    ): Response {
        $allowed = $this->allowed($session);
        $details = $allowed ? $hub->details() : null;

        return self::getResponse('html', 200, [
            'allowed' => $allowed,
            'details' => $details,
            'document' => $allowed ? json_encode($document->build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'token' => $this->token(),
            'returned' => [
                'request' => is_string($get->get('request')) ? $get->get('request') : null,
                'code' => is_string($get->get('code')) ? $get->get('code') : null,
            ],
            'message' => is_string($get->get('message')) ? $get->get('message') : null,
            'error' => is_string($get->get('error')) ? $get->get('error') : null,
        ]);
    }

    public function connectAction(\Frootbox\Http\Post $post, \Frootbox\Session $session, Hub $hub): never
    {
        $this->guard($post, $session);

        try {
            $target = $hub->connect(!empty($post->get('status')), $this->absolute($this->getUri('index')));
        }
        catch (HubException $exception) {
            $this->back(['error' => $exception->getMessage()]);
        }

        header('Location: ' . $target, true, 303);
        exit;
    }

    public function completeAction(\Frootbox\Http\Post $post, \Frootbox\Session $session, Hub $hub): never
    {
        $this->guard($post, $session);
        $request = trim((string) $post->get('request'));

        try {
            $hub->complete(trim((string) $post->get('code')), $request === '' ? null : $request, !empty($post->get('status')));
        }
        catch (HubException $exception) {
            $this->back(['error' => $exception->getMessage()]);
        }

        $this->back(['message' => 'Die Website ist mit frootbox.de verbunden.']);
    }

    public function switchAction(\Frootbox\Http\Post $post, \Frootbox\Session $session, Hub $hub): never
    {
        $this->guard($post, $session);
        $status = !empty($post->get('status'));

        try {
            $warning = $hub->switchStatus($status);
        }
        catch (HubException $exception) {
            $this->back(['error' => $exception->getMessage()]);
        }

        $this->back($warning !== null ? ['error' => $warning] : ['message' => $status ? 'frootbox.de darf den Status lesen.' : 'frootbox.de liest den Status nicht mehr.']);
    }

    public function disconnectAction(\Frootbox\Http\Post $post, \Frootbox\Session $session, Hub $hub): never
    {
        $this->guard($post, $session);

        try {
            $warning = $hub->disconnect(true, !empty($post->get('forgetKey')));
        }
        catch (HubException $exception) {
            $this->back(['error' => $exception->getMessage()]);
        }

        $this->back($warning !== null ? ['error' => $warning] : ['message' => 'Die Verbindung zu frootbox.de ist getrennt.']);
    }

    private function allowed(\Frootbox\Session $session): bool
    {
        $user = $session->getUser();

        return $user !== null && $user->getType() === 'SuperAdmin';
    }

    private function token(): string
    {
        if (empty($_SESSION['frootboxDe']['token']) || !is_string($_SESSION['frootboxDe']['token'])) {
            $_SESSION['frootboxDe']['token'] = bin2hex(random_bytes(16));
        }

        return $_SESSION['frootboxDe']['token'];
    }

    private function guard(\Frootbox\Http\Post $post, \Frootbox\Session $session): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !hash_equals($this->token(), (string) $post->get('token'))) {
            $this->back(['error' => 'Die Anfrage ist abgelaufen. Bitte noch einmal versuchen.']);
        }

        if (!$this->allowed($session)) {
            $this->back(['error' => 'Die Verbindung zu frootbox.de verwalten nur SuperAdmins.']);
        }
    }

    /**
     * @param array<string, string> $query
     */
    private function back(array $query): never
    {
        header('Location: ' . $this->getUri('index', $query), true, 303);
        exit;
    }

    private function absolute(string $uri): string
    {
        // The hub appends ?request=…&code=…; the session id has no place in it.
        $uri = preg_replace('~([?&])' . preg_quote(session_name(), '~') . '=[^&]*&?~', '$1', $uri) ?? $uri;

        return rtrim($uri, '?&');
    }
}

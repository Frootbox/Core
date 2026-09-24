<?php

namespace Frootbox\Ext\Core\System\Routing;

class GlobalPasswordRoute extends \Frootbox\Routing\AbstractRoute
{
    protected function getMatchingRegex(): string
    {
        return '#^#';
    }

    public function performRouting(\Frootbox\Session $session): void
    {
        $hash = (string) ($this->configuration->get('globalPassword.hash') ?? '');

        if (!$this->configuration->get('globalPassword.enabled')) {
            return;
        }

        if ($hash === '') {
            http_response_code(503);
            exit('Der globale Passwort-Schutz ist nicht vollständig konfiguriert.');
        }

        // An authenticated editor can administer the site while the front end is locked.
        if (defined('IS_EDITOR') && IS_EDITOR) {
            return;
        }

        $requestPath = trim($this->request->getRequestTarget(), '/');
        $loginPath = 'global-password';
        $authorized = hash_equals(
            hash('sha256', $hash),
            (string) ($_SESSION['globalPassword']['authorizedHash'] ?? '')
        );

        if ($requestPath === $loginPath) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->submitPassword($hash);
            }

            if ($authorized) {
                $this->redirectToReturnPath();
            }

            $this->renderForm();
        }

        if ($authorized) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? SERVER_PATH;
        if (str_starts_with($uri, '/') && !str_starts_with($uri, '//')) {
            $_SESSION['globalPassword']['returnPath'] = $uri;
        }

        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || str_starts_with($requestPath, 'ajax/') || str_starts_with($requestPath, 'api/')) {
            http_response_code(401);
            exit('Passwort erforderlich.');
        }

        header('Location: ' . SERVER_PATH . $loginPath, true, 302);
        exit;
    }

    private function submitPassword(string $hash): void
    {
        $submittedToken = (string) ($_POST['token'] ?? '');
        $sessionToken = (string) ($_SESSION['globalPassword']['formToken'] ?? '');

        if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            http_response_code(400);
            exit('Ungültige Anfrage.');
        }

        $attempts = $_SESSION['globalPassword']['attempts'] ?? [];
        $attempts = array_values(array_filter($attempts, static fn ($time) => $time > time() - 60));

        if (count($attempts) >= 5) {
            http_response_code(429);
            exit('Zu viele Versuche. Bitte später erneut versuchen.');
        }

        if (!password_verify((string) ($_POST['password'] ?? ''), $hash)) {
            $attempts[] = time();
            $_SESSION['globalPassword']['attempts'] = $attempts;
            $this->renderForm(true);
        }

        session_regenerate_id(true);
        $_SESSION['globalPassword']['authorizedHash'] = hash('sha256', $hash);
        unset($_SESSION['globalPassword']['attempts'], $_SESSION['globalPassword']['formToken']);
        $this->redirectToReturnPath();
    }

    private function redirectToReturnPath(): void
    {
        $target = (string) ($_SESSION['globalPassword']['returnPath'] ?? SERVER_PATH);
        unset($_SESSION['globalPassword']['returnPath']);

        if (!str_starts_with($target, '/') || str_starts_with($target, '//') || preg_match('/[\r\n]/', $target)) {
            $target = SERVER_PATH;
        }

        header('Location: ' . $target, true, 303);
        exit;
    }

    private function renderForm(bool $error = false): void
    {
        $_SESSION['globalPassword']['formToken'] ??= bin2hex(random_bytes(32));

        http_response_code($error ? 401 : 200);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');

        $action = htmlspecialchars(SERVER_PATH . 'global-password', ENT_QUOTES, 'UTF-8');
        $token = htmlspecialchars($_SESSION['globalPassword']['formToken'], ENT_QUOTES, 'UTF-8');
        $message = $error ? '<p role="alert">Das Passwort ist nicht korrekt.</p>' : '';

        echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Passwort erforderlich</title>';
        echo '<style>body{font:1rem/1.5 system-ui,sans-serif;max-width:30rem;margin:12vh auto;padding:0 1.5rem;color:#1d2935}input,button{font:inherit;padding:.7rem;width:100%;box-sizing:border-box}button{margin-top:1rem;cursor:pointer}label{display:block;margin:1rem 0 .4rem}</style></head><body>';
        echo '<main><h1>Passwort erforderlich</h1><p>Bitte geben Sie das Passwort ein, um diese Website aufzurufen.</p>' . $message;
        echo '<form method="post" action="' . $action . '"><input type="hidden" name="token" value="' . $token . '"><label for="password">Passwort</label><input id="password" name="password" type="password" autocomplete="current-password" required autofocus><button type="submit">Website öffnen</button></form></main></body></html>';
        exit;
    }
}

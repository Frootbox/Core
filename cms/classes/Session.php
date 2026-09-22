<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-06-21
 */

namespace Frootbox;

/**
 * 
 */
class Session {
    
    protected $userId = null;
    protected $user = null;
    protected $usersRepository;

    protected $sessionName;

    protected bool $secureCookies;

    /**
     * 
     */
    public function __construct(
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Users $users
    )
    {
        $this->usersRepository = $users;

        // Set session name
        $this->sessionName = $config->get('session.name') ?? 'frootbox-cms';
        $this->secureCookies = $config->get('session.secureCookies') !== false;

        if ($config->get('session.disableCookies')) {
            ini_set('session.use_trans_sid','1');
            ini_set('session.use_cookies', '0');

            define('SESSION_TRANS_ID', true);
        }
        else {
            define('SESSION_TRANS_ID', false);
        }

        session_name($this->sessionName);

        if (!$config->get('session.disableCookies')) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $this->secureCookies,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        // Start session
        if ($config->get('session.disableCookies')) {
            session_start(['use_only_cookies' => 0, 'use_trans_sid' => 1]);
        }
        else {
            session_start();
        }

        if (!empty($_SESSION['user']['id'])) {
            $this->userId = $_SESSION['user']['id'];

            define('USER_ID', $this->userId);
        }
        else {
            define('USER_ID', null);
        }

    }

    /**
     *
     */
    public function getSessionName ( ) : string
    {

        return $this->sessionName;
    }

    /**
     * Delete the persistent auto-login cookie.
     */
    public function deleteAutoLoginCookie(): void
    {
        $this->setAutoLoginCookie('', $_SERVER['REQUEST_TIME'] - (3600 * 24 * 365));
        unset($_COOKIE['fbxAutoLogin']);
    }

    /**
     * Set the persistent auto-login cookie with the same security attributes as
     * the session cookie.
     */
    public function setAutoLoginCookie(string $value, int $expires): void
    {
        setcookie('fbxAutoLogin', $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $this->secureCookies,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     *
     */
    public function getUser ( ): ?\Frootbox\Persistence\User {

        if (empty($this->userId)) {
            return null;
        }

        if ($this->user === null) {

            $this->user = $this->usersRepository->fetchById($this->userId);
        }

        return $this->user;
    }

    /**
     *
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     *
     */
    public function isLoggedIn(): bool
    {
        return !empty($this->userId);
    }

    /**
     *
     */
    public function logout(): Session
    {
        // Kill user session
        $this->user = null;
        $this->userId = null;

        unset($_SESSION['user']);

        // Remove auto-login cookie
        $this->deleteAutoLoginCookie();

        return $this;
    }
    
    /**
     * 
     */
    public function setUser ( \Frootbox\Persistence\User $user ) {

        $this->user = $user;
        $this->userId = $user->getId();
        
        $_SESSION['user']['id'] = $this->userId;
        $_SESSION['user']['type'] = $user->getType();

        return $this;
    }
}

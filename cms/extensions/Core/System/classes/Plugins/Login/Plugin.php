<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Plugins\Login;

use Frootbox\View\Response;
use Frootbox\View\ResponseRedirect;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'intern',
        'passwordReset',
    ];

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxPasswordResetAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
    ): \Frootbox\View\Response
    {
        $post->require([ 'pw1', 'pw2' ]);

        if ($post->get('pw1') != $post->get('pw2')) {
            throw new \Exception('Die Passwörter stimmen nicht überein.');
        }

        // Fetch user
        $user = $usersRepository->fetchOne([
            'where' => [
                'email' => $get->get('login'),
            ],
        ]);

        if (!$user) {
            throw new \Frootbox\Exceptions\NotFound('User');
        }

        // Validate token
        $user->validateSecureToken($get->get('token'));

        $user->setPassword($post->get('pw1'));
        $user->save();

        // Fetch target plugin
        $url = $this->getActionUri('index', [ 'login' => $get->get('login') ]);

        return new ResponseRedirect($url);
    }

    /**
     *
     */
    public function ajaxSubmitAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
    ): Response
    {
        $post->require([ 'username', 'password' ]);

        // Fetch user
        $user = $usersRepository->fetchOne([
            'where' => [
                'email' => $post->get('username'),
            ],
        ]);

        if (empty($user)) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        if (!password_verify($post->get('password'), $user->getPassword())) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        $session->setUser($user);

        if (empty($this->getConfig('pageId'))) {
            $url = $this->getActionUri('intern');
        }
        else {

            // Fetch target page
            $page = $pageRepository->fetchById($this->getConfig('pageId'));
            $url = $page->getUri();
        }

        return new ResponseRedirect($url);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
    ): Response
    {
        if (IS_LOGGED_IN and !IS_EDITOR) {

            if (!empty($this->getConfig('pageId'))) {

                // Fetch target page
                $page = $pagesRepository->fetchById($this->getConfig('pageId'));

                return new \Frootbox\View\ResponseRedirect($page->getUri());
            }
            else {

                return new \Frootbox\View\ResponseRedirect($this->getActionUri('intern'));
            }
        };

        return new \Frootbox\View\Response;
    }

    /**
     *
     */
    public function internAction(

    ): Response
    {
        if (!IS_LOGGED_IN) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new Response();
    }
}

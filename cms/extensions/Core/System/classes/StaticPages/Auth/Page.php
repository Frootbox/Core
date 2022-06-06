<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\StaticPages\Auth;

use Frootbox\View\Response;

class Page
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . '/';
    }

    /**
     *
     */
    public function ajaxLogin(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        $post->require([ 'login', 'password' ]);

        // Fetch user
        $user = $users->fetchOne([
            'where' => [
                'email' => ($post->get('login') ?? $get->get('login'))
            ],
        ]);

        if (!$user) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        if (!password_verify($post->get('password'), $user->getPassword())) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        $session->setUser($user);

        return new \Frootbox\View\ResponseJson([

        ]);
    }

    /**
     * 
     */
    public function password(
        \Frootbox\Http\Get $get
    ): Response
    {
        return new Response([
            'pageId' => $get->get('pageId'),
            'referer' => $get->get('referer'),
        ]);
    }

    /**
     *
     */
    public function setPassword(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post
    ): Response
    {
        $_SESSION['security']['simplePasswords'][$get->get('pageId')] = $post->get('password');

        header('Location: ' . $get->get('referer'));
        exit;
    }

    /**
     *
     */
    public function logout(
        \Frootbox\Http\Get $get
    ): Response
    {
        $_SESSION['user'] = null;

        $redirect = !empty($get->get('redirect')) ? $get->get('redirect') : SERVER_PATH_PROTOCOL;

        header('Location: ' . $redirect);
        exit;
    }
}

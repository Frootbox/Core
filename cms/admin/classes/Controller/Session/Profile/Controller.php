<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-11-21
 */

namespace Frootbox\Admin\Controller\Session\Profile;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Session $session
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\AccessDenied
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate input
        $post->require([
            'email',
        ]);

        // Fetch user
        $user = $session->getUser();

        // Verify update action
        if (!password_verify($post->get('recentPassword'), $user->getPassword())) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        // Set user login
        $user->setEmail($post->get('email'));

        // Set new password
        if (!empty($post->get('newPassword'))) {
            $user->setPassword($post->get('newPassword'));
        }

        // Store user data
        $user->save();

        return self::getResponse('json');
    }

    /**
     * 
     */
    public function index ( ) {

        return self::response();
    }
}
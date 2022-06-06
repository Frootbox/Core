<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-11-21
 */

namespace Frootbox\Admin\Controller\Session\Profile;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController {


    /**
     *
     */
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session
    ) {


        // Validate input
        $post->require([
            'email'
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
            $passwordHash = password_hash($post->get('newPassword'), PASSWORD_DEFAULT);

            $user->setPassword($passwordHash);
        }


        // Store user data
        $user->save();


        return self::response('json');
    }
    
    
    /**
     * 
     */
    public function index ( ) {

        return self::response();
    }
}
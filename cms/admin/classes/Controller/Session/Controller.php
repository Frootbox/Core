<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-06-18
 */

namespace Frootbox\Admin\Controller\Session;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Interfaces\ResponseInterface;
use Frootbox\View\ResponseRedirect;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxCheckLogin(
        \Frootbox\Session $session,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        return self::getResponse('json', 200, [
            'isLoggedIn' => $session->isLoggedIn(),
            'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Session\Partials\RefreshLogin::class, [

            ])
        ]);
    }

    /**
     * 
     */
    public function ajaxLogin(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        \Frootbox\Admin\Persistence\DbBackup $dbBackup,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        // Fetch user
        $result = $users->fetch([
            'where' => [
                'email' => ($post->get('login') ?? $get->get('login'))
            ],
            'limit' => 1
        ]);

        if ($result->getCount() == 0) {
            
            // Check if there is any user
            $result = $users->fetch([
                'limit' => 1
            ]);
            
            if ($result->getCount() > 0) {
                throw new \Frootbox\Exceptions\AccessDenied();
            }
            
            // There is no user, insert first one
            $passwordHash = password_hash($post->get('password'), PASSWORD_DEFAULT);
            
            $user = $users->insert(new \Frootbox\Persistence\User([
                'type' => 'SuperAdmin',
                'email' => ($post->get('login') ?? $get->get('login')),
                'password' => $passwordHash,
            ]));
        }
        else {
            
            $user = $result->current();
        }
        
        
        if (!password_verify($post->get('password'), $user->getPassword())) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }
        
        $session->setUser($user);
        
        
        if (!empty($post->get('stayLoggedIn'))) {
            
            $expireDate = date('Y-m-d H:i:s', time() + (3600 * 24 * 30));
                        
            $hash = $session->getSessionName() . '-' . $user->getEmail() . '-' . $user->getPassword() . '-' . $user->getId() . '-' . $expireDate;
            $payload = [
                'userId' => $user->getId(),
                'valid' => $expireDate,
                'hash' => hash('sha512', $hash)
            ];
            
            // Write permanent auto login cookie for one year
            // TODO Make this configurable later
            setcookie('fbxAutoLogin', json_encode($payload), $_SERVER['REQUEST_TIME'] + (3600 * 24 * 365), '/');
        }

        // Auto backup after login
        $dbBackup->snapshot();
        $dbBackup->cleanSnapshots([
            'maxage' => (3600 * 24 * 60)
        ]);

        switch($get->get('action')) {

            default:
            case 'redirect':

                $payload = [
                    'redirect' => (!empty($get->get('redirect')) ? \Frootbox\Admin\Front::getUriFromString($get->get('redirect')) : \Frootbox\Admin\Front::getUri('Dashboard')),
                ];
                break;

            case 'modalDismiss':

                $payload = [
                    'modalDismiss' => true
                ];
                break;
        }

        return self::getResponse('json', 200, $payload);
    }

    /**
     *
     */
    public function ajaxLogout(
        \Frootbox\Session $session
    ): Response
    {
        // Log out user
        $session->logout();

        return self::getResponse('json', 200, [
            'redirect' => \Frootbox\Admin\Front::getUri('Session', 'login')
        ]);
    }

    /**
     *
     */
    public function ajaxModalLogin(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function ajaxPasswordRequest(
        \Frootbox\Http\Post $post,
        \Frootbox\Mailer $mailer,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        // Validate required input
        $post->require([ 'login' ]);

        // Fetch user
        $user = $users->fetchOne([
            'where' => [
                'email' => $post->get('login')
            ]
        ]);

        if ($user) {

            $mailer->renderBody(CORE_DIR . 'cms/admin/resources/private/views/mail/PasswordLost.html.twig', [
                'url' => \Frootbox\Admin\Front::getUri('Session', 'passwordReset', [ 'login' => $user->getEmail(), 'token' => $user->getSecureToken() ])
            ]);
            $mailer->addRecipient($user->getEmail());
            $mailer->setSubject('Passwort vergessen');

            $mailer->send();
        }

        return self::getResponse('json', 200, [
            'redirect' => \Frootbox\Admin\Front::getUri('Session', 'passwordRequested')
        ]);
    }

    /**
     *
     */
    public function ajaxPasswordSet(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Users $users,
    ): Response
    {
        // Validate required input
        $get->require([ 'login', 'token' ]);
        $post->require([ 'passwd1', 'passwd1' ]);

        // Validate passwords
        if ($post->get('passwd1') != $post->get('passwd2')) {
            throw new \Frootbox\Exceptions\InputInvalid('PasswordsDiffer');
        }

        // Fetch user
        $user = $users->fetchOne([
            'where' => [
                'email' => $get->get('login')
            ]
        ]);

        if (!$user) {
            throw new \Frootbox\Exceptions\NotFound('User');
        }

        // Validate token
        $user->validateSecureToken($get->get('token'));

        $user->setPassword($post->get('passwd1'));
        $user->save();

        return self::getResponse('json', 200, [
            'redirect' => \Frootbox\Admin\Front::getUri('Session', 'login', [
                'login' => $user->getEmail()
            ])
        ]);
    }

    /**
     *
     */
    public function forceLogin(
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Admin\Persistence\DbBackup $dbBackup,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
    ): ResponseRedirect
    {
        // Auth request
        if ($configuration->get('api.token') != $get->get('token')) {
            throw new \Exception('Token mismatch');
        }

        // Fetch user
        $user = $usersRepository->fetchOne([
            'where' => [
                'email' => $get->get('email'),
            ],
        ]);

        if (!$user) {

            $user = new \Frootbox\Persistence\User([
                'email' => $get->get('email'),
                'type' => 'Editor',
            ]);

            $user = $usersRepository->insert($user);
        }

        $session->setUser($user);

        // Auto backup after login
        $dbBackup->snapshot();
        $dbBackup->cleanSnapshots([
            'maxage' => (3600 * 24 * 60)
        ]);

        header('Location: ' . \Frootbox\Admin\Front::getUri('Dashboard'));
        exit;
    }

    /**
     * 
     */
    public function login(
        \Frootbox\Session $session
    ): Response
    {
        if ($session->isLoggedIn()) {
            return self::redirect('Dashboard');
        }

        return self::getResponse();
    }

    /**
     *
     */
    public function passwordLost(

    ): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function passwordReset(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        // Validate required input
        $get->require([ 'login', 'token' ]);

        // Fetch user
        $user = $users->fetchOne([
            'where' => [
                'email' => $get->get('login')
            ]
        ]);

        if (!$user) {
            throw new \Frootbox\Exceptions\NotFound('User');
        }

        // Validate token
        $user->validateSecureToken($get->get('token'));

        return self::getResponse();
    }

    /**
     *
     */
    public function passwordRequested(): Response
    {
        return self::getResponse();
    }
}

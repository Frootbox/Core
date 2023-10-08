<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\UserManager\Apps\UserManager;

use Frootbox\Admin\Controller\Response;
use Frootbox\Session;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * Get public path of app
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalUserComposeAction(): Response
    {
        return self::getResponse();
    }

    /**
     * 
     */
    public function ajaxModalUserEditAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        
        $user = $users->fetchById($get->get('userId'));
        
        $view->set('user', $user);
        
        return self::getResponse();
    }
    
    /**
     *
     */
    public function ajaxModalUserImportAction(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function ajaxUserActivateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Users $users,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Validate required input
        $get->require([ 'userId' ]);

        // Fetch user
        $user = $users->fetchOne([
            'where' => [
                'id' => $get->get('userId')
            ]
        ]);

        if (!$user) {
            throw new \Frootbox\Exceptions\NotFound('User not found.');
        }

        if ($user->getType() == 'User') {

            $plugin = $contentElements->fetchOne([
                'where' => [
                    'className' => \Frootbox\Ext\Core\System\Plugins\Login\Plugin::class,
                ],
            ]);

            if (!$plugin) {
                throw new \Exception('Login Plugin not installed.');
            }

            $url = $plugin->getActionUri('passwordReset', [ 'login' => $user->getEmail(), 'token' => $user->getSecureToken() ], [ 'absolute' => true, ]);
        }
        else {
            $url = \Frootbox\Admin\Front::getUri('Session', 'passwordReset', [ 'login' => $user->getEmail(), 'token' => $user->getSecureToken() ]);
        }

        try {
            $view->set('url', $url);
            $view->set('serverpath', SERVER_PATH_PROTOCOL);
            $view->set('title', 'Zugang aktivieren');

            $viewFile = $this->getPath() . 'resources/private/builder/mail/PasswordActivate.html.twig';

            $source = $view->render($viewFile);

            $mail = new \Frootbox\Mail\Envelope;
            $mail->setSubject('Zugang aktivieren');
            $mail->setBodyHtml($source);

            $mail->clearTo();
            $mail->addTo($user->getEmail());

            $mailTransport->send($mail);
        }
        catch ( \Exception $e ) {
            d($e);
        }


        return self::getResponse('json', 200, [
            'success' => 'Der Zugang wurde gesendet an ' . $user->getEmail(),
        ]);
    }

    /**
     * 
     */
    public function ajaxUserCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Users $users,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Check required fields
        $post->require([
            'email'
        ]);

        // Insert new user
        $user = $users->insert(new \Frootbox\Persistence\User([
            'email' => $post->get('email'),
            'type' => $get->get('type')
        ]));

        switch ($get->get('type')) {

            case 'SuperAdmin':
                $selector = '#superadminReceiver';
                break;

            case 'Admin':
                $selector = '#adminReceiver';
                break;

            case 'Editor':
                $selector = '#editorReceiver';
                break;

            default:
                $selector = '#userReceiver';
                break;
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => $selector,
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                    'type' => $get->get('type'),
                    'highlight' => $user->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxUserDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Users $users,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch user
        $user = $users->fetchById($get->get('userId'));

        $user->delete();

        switch ($user->getType()) {

            case 'SuperAdmin':
                $selector = '#superadminReceiver';
                break;

            case 'Admin':
                $selector = '#adminReceiver';
                break;

            default:
                $selector = '#userReceiver';
                break;
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => $selector,
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                    'type' => $user->getType(),
                ])
            ],
            'modalDismiss' => true,
        ]);
    }
    
    /**
     *
     */
    public function ajaxUserImportAction(
        \Frootbox\Persistence\Repositories\Users $users
    )
    {
        // Obtain import source
        $source = file_get_contents($_FILES['file']['tmp_name']);
        $data = json_decode($source, true);
    }

    /**
     * 
     */
    public function ajaxUserUpdateAction(
        \Frootbox\Session $session,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Users $users,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        $post->require([ 'email' ]);

        // Fetch user
        $user = $users->fetchById($get->get('userId'));

        // Validate users login
        if ($user->getEmail() != $post->get('email')) {

            // Check if login exists
            $check = $users->fetchOne([
                'where' => [
                    'email' => $post->get('email')
                ]
            ]);

            if (!empty($check)) {
                throw new \Exception('A user with this email already exists.');
            }
        }

        // Validate users role
        $role = '\\Frootbox\\Persistence\\User::ROLE_' . strtoupper($post->get('role'));
        $targetRole = constant($role);

        $role = '\\Frootbox\\Persistence\\User::ROLE_' . strtoupper($session->getUser()->getType());
        $userRole = constant($role);

        if ($targetRole > $userRole) {

            if ($targetRole != \Frootbox\Persistence\User::ROLE_SUPERADMIN) {
                throw new \Exception('Uprading users role is not allowed.');
            }

            $check = $users->fetchOne([
                'where' => [
                    'type' => 'SuperAdmin'
                ]
            ]);

            if (!empty($check)) {
                throw new \Exception('Uprading users role is not allowed.');
            }
        }

        // Update users info
        $user->setType($post->get('role'));
        $user->setEmail($post->get('email'));
        $user->setFirstName($post->get('firstName'));
        $user->setLastName($post->get('lastName'));

        // Update users password
        if (!empty($post->get('password'))) {
            $user->setPassword($post->get('password'));
        }
        
        $user->save();

        return self::getResponse('json', 200, [
            'replacements' => [
                [
                    'selector' => '#superadminReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                        'type' => 'SuperAdmin',
                        'highlight' => $user->getId(),
                    ]),
                ],
                [
                    'selector' => '#adminReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                        'type' => 'Admin',
                        'highlight' => $user->getId(),
                    ]),
                ],
                [
                    'selector' => '#editorReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                        'type' => 'Editor',
                        'highlight' => $user->getId(),
                    ]),
                ],
                [
                    'selector' => '#userReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers::class, [
                        'type' => 'User',
                        'highlight' => $user->getId(),
                    ]),
                ],
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function exportAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Users $users
    )
    {

        // Fetch user
        $user = $users->fetchById($get->get('userId'));


        // Generate payload
        $payload = [
            'fbxType' => 'Import',
            'sections' => [
                [
                    'adapter' => \Frootbox\Ext\Core\UserManager\Utilities\Import\User::class,
                    'items' => [
                        $user->getData()
                    ]
                ]
            ]
        ];

        http_response_code(200);

        header('Content-disposition: attachment; filename=export.json');
        header('Content-type: application/json');

        die(json_encode($payload));
    }

    /** 
     * 
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }    
}

<?php 
/**
 * 
 */

namespace Frootbox\Admin;

/**
 * 
 */
class Dispatcher
{
    protected $request;
    
    protected $controller;
    protected $action;

    protected $publicAction = [
        'Session/ajaxCheckLogin',
        'Session/ajaxLogin',
        'Session/ajaxModalLogin',
        'Session/ajaxPasswordRequest',
        'Session/ajaxPasswordSet',
        'Session/forceLogin',
        'Session/passwordLost',
        'Session/passwordReset',
        'Session/passwordRequested',
        'Editor/top'
    ];

    /**
     * 
     */
    public function __construct(
        \Frootbox\Http\Request $request,
        \Frootbox\Http\Get $get,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Users $users
    )
    {
        $this->request = $request;
        
        $path = $this->request->getVirtualPath();

        // Try auto login via cookie
        if (!$session->isLoggedIn() and !empty($_COOKIE['fbxAutoLogin'])) {

            try {

                $data = json_decode($_COOKIE['fbxAutoLogin'], true);

                // Check if data is still valid
                if (strtotime(str_replace('+', ' ', $data['valid'])) - time() > 0) {

                    $user = $users->fetchbyId('asdsad'.$data['userId']);

                    $hash = $session->getSessionName() . '-' . $user->getEmail() . '-' . $user->getPassword() . '-' . $user->getId() . '-' . $data['valid'];
                    $hash = hash('sha512', $hash);

                    // If hash is valid, login user
                    if ($hash == $data['hash']) {
                        $session->setUser($user);
                    }
                }
                else {
                    setcookie('fbxAutoLogin', 'xxx', $_SERVER['REQUEST_TIME'] - (3600 * 24 * 365), '/');
                }
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {

                // Ignore any exceptions and move on
                // but clean the autologin-cookie

                setcookie('fbxAutoLogin', 'xxx', $_SERVER['REQUEST_TIME'] - (3600 * 24 * 365), '/');
            }
        }

        // Perform public accessibly action
        if (!$session->isLoggedIn() and !in_array($path, $this->publicAction)) {


            if (strpos($path, '/ajax') !== false) {
                throw new \Frootbox\Exceptions\NotLoggedIn();
            }

            $path = 'Session/login';

            if (empty($get->get('redirect'))) {
                $get->set('redirect', $_SERVER['REQUEST_URI']);
            }
        }

        if (empty($path)) {
            $path = 'Session/login';
        }
        
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }
        
        $segments = explode('/', $path);
        
        if (count($segments) == 1) {
            $segments[] = 'index';
        }
                
        $this->action = array_pop($segments);
        $this->controller = implode('/', $segments);
    }

    /**
     * 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 
     */
    public function dispatch()
    {
        $controllerName = $this->getController();

        if (preg_match('#^Ext\/(.*?)\/(.*?)\/(.*?)$#', $controllerName, $match)) {
            $controllerClass = '\Frootbox\Ext\\' . $match[1] . '\\' . $match[2] . '\\Admin\Controller\\' . str_replace('/', '\\', $match[3]) . '\\Controller';
        }
        else {
            $controllerClass = '\Frootbox\Admin\Controller\\' . str_replace('/', '\\', $controllerName) . '\\Controller';
        }

        return [
            'controller' => $controllerClass,
            'action' => $this->getAction(),
            'method' => $this->request->getMethod()
        ];
    }
}

<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Editing\Routing;

class EditorRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^edit#';
    }

    /**
     *
     */
    public function performRouting(
        \Frootbox\Config\Config $config,
        \Frootbox\Session $session,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        // Inject editing post route when logged in
        if ($session->isLoggedIn()) {

            $postRoutes = $config->get('postroutes') ? $config->get('postroutes')->getData() : [ ];

            $postRoutes[] = [ 'route' => \Frootbox\Ext\Core\Editing\Routing\EditorPostRoute::class ];

            $config->append([
                'postroutes' => $postRoutes
            ]);

            define('EDITING', true);
            $view->set('editing', true);
        }

        // Strip edit/ section from request
        $request = $this->getRequest();
        $requestTarget = $request->getRequestTarget();

        $request->setRequestTarget(substr($requestTarget, 5));
    }
}

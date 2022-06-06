<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\UserManager\Apps\UserManager\Partials\ListUsers;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Admin\View $view
     * @param \Frootbox\Persistence\Repositories\Users $users
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Users $users
    ):void
    {
        // Fetch users
        $result = $users->fetch([
            'where' => [
                'type' => $this->getData('type')
            ],
            'order' => [ 'email ASC' ]
        ]);
                
        $view->set('users', $result);
    }
}

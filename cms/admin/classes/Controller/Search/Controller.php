<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-06-18
 */

namespace Frootbox\Admin\Controller\Search;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     * 
     */
    public function ajaxSearchLinks(
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        

        $searchAdapters = [
            GlobalAdapters\Pages::class
        ];

        $sql = [ ];

        foreach ($searchAdapters as $adapterClass) {

            $adapter = new $adapterClass;

            $sql[] = $adapter->getSql($get->get('q'));
        }

        $query = implode(' UNION ', $sql);

        $stmt = $db->query($query);
        $stmt->execute();

        $result = $stmt->fetchAll();

        return self::getResponse('json', 200, [
            'result' => $result
        ]);
    }


    /**
     *
     */
    public function ajaxSearchPages(
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Repositories\Users $users
    ): Response
    {
        $searchAdapters = [
            GlobalAdapters\Pages::class
        ];

        $sql = [ ];

        foreach ($searchAdapters as $adapterClass) {

            $adapter = new $adapterClass;

            $sql[] = $adapter->getSql($get->get('q'));
        }

        $query = implode(' UNION ', $sql);

        $stmt = $db->query($query);
        $stmt->execute();

        $result = $stmt->fetchAll();

        return self::getResponse('json', 200, [
            'result' => $result
        ]);
    }


    /**
     *
     */
    public function ajaxLogout ( \Frootbox\Session $session ) {

        $session->logout();

        return self::response('json', 200, [
            'redirect' => \Frootbox\Admin\Front::getUri('Session', 'login')
        ]);
    }


    /**
     *
     */
    public function ajaxModalLogin ( ) {

        return self::response();
    }


    /**
     * 
     */
    public function login ( \Frootbox\Session $session ) : Response {

        if ($session->isLoggedIn()) {
            return self::redirect('Dashboard');
        }

        return self::response();
    }    
}
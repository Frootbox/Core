<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\Gallery\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController {

    /**
     * Get controllers root path
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function ajaxUpdateAction (
        \Frootbox\Http\Post $post
    )
    {

        d($post);
    }
    
    
    /**
     * 
     */
    public function ajaxCategoryDetailsAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories
    ) 
    {
        
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));
        $view->set('category', $category);
        
        return self::response('plain');
    }
    

    /**
     *
     */
    public function indexAction (

    ): \Frootbox\Admin\Controller\Response
    {

        return self::response();
    }
}
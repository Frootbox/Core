<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController {
    
    /**
     *
     */
    public function getPath ( ) : string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
    
    
    /**
     * 
     */
    public function ajaxModalDetailsAction ( 
        
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers,
        \Frootbox\Admin\View $view
        
        ) {
        
        
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));
        
        $view->set('teaser', $teaser);
            
        
        return self::response('plain');
    }
    
    
    /**
     * 
     */
    public function ajaxDeleteAction ( \Frootbox\Http\Get $get, \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers, \Frootbox\Admin\Viewhelper\GeneralPurpose $gp ) {
        
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));
        
        $teaser->delete();
        
        
        return self::response('json', 200, [
            'replace' => [
                'selector' => '#teaserReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers::class, [
                   // 'highlight' => $child->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }
    
    
    /**
     * 
     */
    public function ajaxUpdateAction (  \Frootbox\Http\Get $get, \Frootbox\Http\Post $post, \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers, \Frootbox\Admin\Viewhelper\GeneralPurpose $gp ) {
                
        
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));
        
        $teaser->setData($post->get('teaser'));

        $teaser->addConfig([
            'pageType' => $post->get('type'),
            'redirect' => [
                'target' => $post->get('target')
            ]
        ]);

        $teaser->save();
        
        
        return self::response('json', 200, [
            'replace' => [
                'selector' => '#teaserReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers::class, [
                    // 'highlight' => $child->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }
    
    
    /**
     * 
     */
    public function indexAction ( ) {
                        
        return self::response();
    }
}
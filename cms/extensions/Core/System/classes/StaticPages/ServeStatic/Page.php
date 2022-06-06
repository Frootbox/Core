<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\StaticPages\ServeStatic;

class Page {
    
    
    /**
     * 
     */
    public function serve(
        \Frootbox\ConfigStatics $configStatics,
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get
    )
    {
        $token = $get->get('t');
        $file = $_SESSION['staticfilemap'][$token];
        $da = explode('.', $file);
        $ext = array_pop($da);

        $contentTypes = [
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'jpg' => 'image/jpg'
        ];

        header('Content-type: ' . $contentTypes[$ext]);
        readfile($file);
        exit;
    }
}

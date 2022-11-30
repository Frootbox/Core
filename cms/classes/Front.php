<?php
/**
 * 
 */

namespace Frootbox;

class Front
{
    protected $config;
    
    /**
     * Base autoloader
     */
    public static function autoload($class)
    {
        $fclass = str_replace('Frootbox\\', '', $class);
                
        $file = CORE_DIR . 'cms/classes/' . str_replace('\\', '/', $fclass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * 
     */
    public static function init(array $params = null)
    {
        define('CORE_DIR', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);

        // Register autoloader
        spl_autoload_register('Frootbox\Front::autoload');
        
        // Build dependency injection container
        $builder = new \DI\ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addDefinitions(CORE_DIR . 'appconfig.php');

        $container = $builder->build();
        
        $configuration = $container->get(\Frootbox\Config\Config::class);

        define('FILES_DIR', $configuration->get('filesRootFolder'));
        define('PUBLIC_DIR', $configuration->get('publicCacheDir'));

        $base = dirname($_SERVER['SCRIPT_NAME']);
        $base = str_replace('\\', '/', $base);
        $base = str_replace('/cms/admin', '', $base);
        $base = str_replace('/cms/tools', '', $base);
        $base = str_replace('//', '/', $base . '/');

        define('SERVER_PATH', $base);

        $host = !empty($configuration->get('general.host.domain')) ? $configuration->get('general.host.domain') : $_SERVER['HTTP_HOST'];

        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';

        /*
        if (!empty($configuration->get('general.host.forceSSL'))) {
            $protocol = 'https';
        }
        else {
            $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        }
        */

        define('IS_SSL', $protocol === 'https');

        define('SERVER_PATH_PROTOCOL', $protocol . '://' . $host . SERVER_PATH);
        define('SERVER_NAME', $protocol . '://' . $host);

        return $container;
    }
}

<?php 
/**
 * 
 */

return [
    'config.file' => CORE_DIR . 'localconfig.php',
    \Frootbox\Db\Dbms\Interfaces\Dbms::class => function ( $c ) {
        
        $class = '\Frootbox\Db\Dbms\\' . ucfirst($c->get(\Frootbox\Config\Config::class)->get('database.dbms'));
        
        return $c->get($class);
    },
    \Frootbox\View\Engines\Interfaces\Engine::class => function ( $c ) {

        $config = $c->get(\Frootbox\Config\Config::class);
        $class = '\Frootbox\View\\Engines\\' . ucfirst($config->get('view.engine') ?? 'Twig');

        // $config->get('cacheRootFolder')

        $view = $c->get($class);
        $view->setContainer($c);

        if (defined('GLOBAL_LANGUAGE')) {

            // Replace translations
            $translationFactory = $c->get(\Frootbox\TranslatorFactory::class);
            $translator = $translationFactory->get(GLOBAL_LANGUAGE);


            // TODO move to re-usable twig extension later
            $filter = new \Twig\TwigFilter('translate', function ($string) use ($translator) {
                return $translator->translate($string);
            });
            $view->addFilter($filter);
        }

        return $view;
    },
    \Frootbox\Mail\Transports\Interfaces\TransportInterface::class => function ( $c ) {

        $config = $c->get(\Frootbox\Config\Config::class);

        $class = '\\Frootbox\\Mail\\Transports\\' . ucfirst($config->get('mail.transport') ?? 'Smtp');

        $transport = $c->get($class);

        return $transport;
    },
];
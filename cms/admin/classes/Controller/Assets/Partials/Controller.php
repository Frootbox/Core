<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2020-07-29
 */

namespace Frootbox\Admin\Controller\Assets\Partials;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{

    /**
     *
     */
    public function genericAction(
        \Frootbox\Http\Get $get,
        \DI\Container $container
    ): Response
    {

        $partialClass = $get->get('partial');

        $partial = new $partialClass;

        $response = $container->call([ $partial, $get->get('action') ]);

        return $response;
    }

    /**
     *
     */
    public function index(): Response
    {
        return self::getResponse();
    }
}

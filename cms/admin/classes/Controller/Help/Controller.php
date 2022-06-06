<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2020-11-08
 */

namespace Frootbox\Admin\Controller\Help;


use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function index(

    ): Response
    {
        return self::getResponse();
    }
}

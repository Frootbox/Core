<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Formatter extends AbstractViewhelper
{

    /**
     *
     */
    public function phoneClean ( $phoneNumber )
    {

        $string = preg_replace('#[^0-9.]#', '', $phoneNumber);

        return $string;
    }

}
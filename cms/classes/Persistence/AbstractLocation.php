<?php
/**
 *
 */

namespace Frootbox\Persistence;

abstract class AbstractLocation extends \Frootbox\Persistence\AbstractRow
{
    protected $table = 'locations';

    use \Frootbox\Persistence\Traits\Config;
}
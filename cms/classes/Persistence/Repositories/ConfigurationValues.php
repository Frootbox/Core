<?php 
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 * 
 */
class ConfigurationValues extends \Frootbox\Db\Model
{
    protected $table = 'configuration_values';
    protected $class = \Frootbox\Persistence\ConfigurationValue::class;
}

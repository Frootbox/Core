<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2022-04-21
 */

namespace Frootbox\Persistence;

class ConfigurationValue extends \Frootbox\Persistence\AbstractRow
{
    protected $table = 'configuration_values';
    protected $model = Repositories\ConfigurationValues::class;

}

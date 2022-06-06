<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Content\Repositories;

/**
 *
 */
class Widgets extends \Frootbox\Db\Model
{
    protected $table = 'content_widgets';
    protected $class = \Frootbox\Persistence\Content\Widget::class;
}

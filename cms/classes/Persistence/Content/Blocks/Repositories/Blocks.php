<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Content\Blocks\Repositories;

/**
 *
 */
class Blocks extends \Frootbox\Db\Model
{
    protected $table = 'blocks';
    protected $class = \Frootbox\Persistence\Content\Block::class;
}

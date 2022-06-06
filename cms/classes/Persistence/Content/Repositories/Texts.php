<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Content\Repositories;

/**
 *
 */
class Texts extends \Frootbox\Db\Model
{
    use \Frootbox\Persistence\Repositories\Traits\Uid;

    protected $table = 'content_texts';
    protected $class = \Frootbox\Persistence\Content\Text::class;
}

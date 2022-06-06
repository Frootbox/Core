<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Content\Elements\Repositories;

/**
 *
 */
class Texts extends \Frootbox\Db\Model {

    protected $table = 'content_elements';
    protected $class = \Frootbox\Persistence\Content\Elements\Text::class;
}
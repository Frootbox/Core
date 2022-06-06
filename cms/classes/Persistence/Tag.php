<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Tag extends AbstractRow
{
    protected $table = 'tags';
    protected $model = Repositories\Tags::class;
}

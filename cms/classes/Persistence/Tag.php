<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Tag extends AbstractRow
{
    protected $table = 'tags';
    protected $model = Repositories\Tags::class;

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return substr($this->getTag(), 0, 1) != '_';
    }
}

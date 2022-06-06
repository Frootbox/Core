<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Grid extends AbstractRow implements Interfaces\ContentElement {

    protected $table = 'content_elements';
    protected $model = Repositories\ContentElements::class;
}

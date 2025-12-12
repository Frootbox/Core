<?php
/**
 *
 */

namespace Frootbox\Persistence;

abstract class AbstractPerson extends \Frootbox\Persistence\AbstractRow
{
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Visibility;
    use \Frootbox\Persistence\Traits\Config;

    protected $table = 'persons';

    /**
     * @return string
     */
    public function getName(): string
    {
        return trim($this->getTitle() . ' ' . $this->getFirstName() . ' ' . $this->getLastName());
    }

    /**
     * @return string
     */
    public function getNameOnly(): string
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }
}

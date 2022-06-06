<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Persistence;

class Venue extends \Frootbox\Persistence\AbstractLocation implements \Frootbox\Persistence\Interfaces\MultipleAliases
{


    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        // TODO: Implement getNewAlias() method.
    }

    public function getNewAliases(): array
    {

    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'];
    }

    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? parent::getTitle();
    }

    /**
     *
     */
    public function getTitleWithoutFallback($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? null;
    }
}
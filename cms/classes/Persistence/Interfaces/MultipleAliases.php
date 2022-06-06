<?php
/**
 *
 */

namespace Frootbox\Persistence\Interfaces;

interface MultipleAliases
{
    /**
     *
     */
    public function getLanguageAliases(): array;

    /**
     *
     */
    public function getNewAliases(): array;
}

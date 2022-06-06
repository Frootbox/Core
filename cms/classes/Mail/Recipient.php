<?php
/**
 *
 */

namespace Frootbox\Mail;

class Recipient
{
    protected $address;
    protected $name;

    /**
     *
     */
    public function __construct(string $address, string $name = null)
    {
        $this->name = $name;
        $this->address = $address;
    }

    /**
     *
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     *
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
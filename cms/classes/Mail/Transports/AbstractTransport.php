<?php
/**
 *
 */

namespace Frootbox\Mail\Transports;

abstract class AbstractTransport implements \Frootbox\Mail\Transports\Interfaces\TransportInterface
{
    protected $fromAddress = null;
    protected $fromName = null;

    /**
     *
     */
    public function __construct(
        protected \Frootbox\Config\Config $config,
    )
    { }

    /**
     *
     */
    public function getFromAddress(): ?string
    {
        if (!empty($this->fromAddress)) {
            return $this->fromAddress;
        }

        return $this->config->get('mail.defaults.from.address');
    }

    /**
     *
     */
    public function getFromName(): ?string
    {
        if (!empty($this->fromName)) {
            return $this->fromName;
        }

        return $this->config->get('mail.defaults.from.name');
    }

    /**
     *
     */
    public function setFrom(string $address, string $name = null): void
    {
        $this->fromAddress = $address;
        $this->fromName = $name;
    }
}

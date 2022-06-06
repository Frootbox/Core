<?php
/**
 *
 */

namespace Frootbox\Mail\Transports\Interfaces;

interface TransportInterface
{
    /**
     * @param \Frootbox\Mail\Envelope $envelope
     * @return void
     */
    public function send(\Frootbox\Mail\Envelope $envelope): void;

    /**
     *
     */
    public function setFrom(string $address, string $name = null): void;
}
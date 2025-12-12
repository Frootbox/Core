<?php
/**
 *
 */

namespace Frootbox\Api;

class Response
{
    public function __construct(
        protected array $payload = [],
    )
    { }

    public function getPayload(): array
    {
        return $this->payload;
    }
}

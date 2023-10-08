<?php
/**
 *
 */

namespace Frootbox\View;

class ResponseJson extends Response
{
    protected $data = [];

    /**
     *
     */
    public function __construct(array $data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }


    }

    /**
     *
     */
    public function getDefaultHeaders(): array
    {
        return [
            'Content-Type: application/json; charset=UTF-8',
            'Content-Length: ' . strlen($this->getBody())
        ];
    }

    /**
     *
     */
    public function getBody(): string
    {
        return json_encode($this->data, JSON_HEX_QUOT | JSON_HEX_TAG);
    }

    /**
     *
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     *
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

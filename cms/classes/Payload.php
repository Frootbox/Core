<?php
/**
 *
 */

namespace Frootbox;

class Payload
{
    protected $signingToken;
    protected $data = [ ];

    /**
     *
     */
    public function __construct()
    {
        $this->signingToken = SIGNING_TOKEN;
    }

    /**
     *
     */
    public function addData(array $data): Payload
    {
        $this->data = array_replace_recursive($this->data, $data);

        return $this;
    }

    /**
     *
     */
    public function clear(): Payload
    {
        $this->data = [ ];

        return $this;
    }

    /**
     *
     */
    public function export(): array
    {
        $data = $this->data;

        ksort($data);

        $capsule = [
            'token' => $this->signingToken,
            'payload' => $data
        ];

        $token = md5(http_build_query($capsule));

        $payload = $data;
        $payload['token'] = $token;

        return $payload;
    }

    /**
     *
     */
    public function validate(array $data)
    {
        if (empty($data['token'])) {
            throw new \Frootbox\Exceptions\InputInvalid('Input token missing.');
        }

        $givenToken = $data['token'];
        unset($data['token']);

        ksort($data);

        $capsule = [
            'token' => $this->signingToken,
            'payload' => $data
        ];

        $generatedToken = md5(http_build_query($capsule));

        if ($generatedToken != $givenToken) {
            throw new \Frootbox\Exceptions\InputInvalid('Input token validation failed.');
        }
    }
}

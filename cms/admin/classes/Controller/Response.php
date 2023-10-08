<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller;

/**
 * 
 */
class Response {
    
    protected $type = 'html';
    protected $status = 200;
    protected $statusGroup = 200;
    protected $message = 'OK';
    protected $headers = [ ];
    protected $file = null;

    protected $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'xxx',
        202 => 'xxx',
        203 => 'xxx',
        204 => 'xxx',
        205 => 'xxx',
        206 => 'xxx',
        207 => 'xxx',
        208 => 'xxx',
        226 => 'xxx',

        300 => 'xxx',
        301 => 'xxx',
        302 => 'Found (Moved Temporarily)',
        303 => 'xxx',
        304 => 'xxx',
        305 => 'xxx',
        306 => 'xxx',
        307 => 'xxx',
        308 => 'xxx',

        400 => 'xxx',
        401 => 'xxx',
        402 => 'xxx',
        403 => 'xxx',
        404 => 'xxx',
    ];


    /**
     *
     */
    public function __construct ( string $type = 'html', int $status = 200, $body = null, array $headers = null ) {

        $this->type = $type;
        $this->body = $body;

        $this->status = $status;
        $this->statusGroup = floor($this->status / 100) * 100;

        if ($headers !== null) {
            $this->headers = $headers;
        }
    }

    /**
     *
     */
    public function getBody(): ?string
    {
        switch ( $this->type) {

            default:
            case 'html':

                if (is_array($this->body)) {
                    return null;
                }

                return $this->body;

            case 'json':

                return json_encode($this->body, JSON_HEX_QUOT | JSON_HEX_TAG);
        }
    }

    /**
     *
     */
    public function getBodyData(): ?array
    {
        if (!is_array($this->body)) {
            return null;
        }

        return $this->body;
    }

    /**
     *
     */
    public function getHeaders ( )
    {
        return $this->headers;
    }


    /**
     *
     */
    public function getHttpStatusHeader ( ): string {

        return 'HTTP/1.1 ' . $this->status . ' ' . $this->statusCodes[$this->status];
    }


    /**
     *
     */
    public function getStatus ( ): int {

        return $this->status;
    }


    /**
     *
     */
    public function getStatusGroup ( ): int {

        return $this->statusGroup;
    }

    
    /**
     * 
     */
    public function getType ( ) {
        
        return $this->type;
    }

    /**
     * @param array $bodyData
     * @return void
     */
    public function setBodyData(array $bodyData): void
    {
        $this->body = $bodyData;
    }
}
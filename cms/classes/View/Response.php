<?php
/**
 *
 */

namespace Frootbox\View;

class Response {

    protected $body = null;
    protected $data = [];
    protected $headers = [];

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
    public function getAdditionalHeaders(): array
    {
        return $this->headers;
    }

    /**
     *
     */
    public function getBody(): ?string
    {
        return $this->body;
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
    public function getDefaultHeaders(): array
    {
        return [
            'Content-Type: text/html; charset=UTF-8',
            'Content-Length: ' . strlen($this->getBody())
        ];
    }

    /**
     *
     */
    public function getHeaders(): array
    {
        return array_merge($this->getDefaultHeaders(), $this->getAdditionalHeaders());
    }

    /**
     *
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     *
     */
    public static function createFromJsonResponse(Response $response): Response
    {
        $data = $response->getData();

        // Compose html
        $html = '<html>
                    <head>
                        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
                        <style>
                            .message {
                                margin: 50px 0;
                                padding: 10px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="row">
                                <div class="col-12">';

        if (!empty($data['success'])) {
            $html .= '<div class="message border border-success text-center">' . $data['success'] . '</div>';
        }

        if (!empty($data['continue'])) {
            $html .= '<p><a href="' . $data['continue'] . '">fortfahren</a></p>';
        }

        if ($response instanceof \Frootbox\View\ResponseRedirect) {

            $html .= '<meta http-equiv="refresh" content="0;url=' . $response->gettarget(). '"></meta>';
            $html .= '<a href="' . $response->gettarget(). '">Sie werden weitergeleitet.</a>';
        }

        $html .= '</div>
                        </div>
                    </div>
                </body></html>';

        $response = new self;
        $response->setBody($html);

        return $response;
    }
}

<?php

namespace GoCardlessPro\Core;

/**
 * @package GoCardlessPro
 * @subpackage Core
 */
class ApiResponse
{
    /**
     * @var array All the HTTP headers with lowercased keys
     */
    public $headers;

    /**
     * @var int HTTP status of the response
     */
    public $status_code;

    /**
     * @var object Full decoded JSON body
     */
    public $body;

    public function __construct($response)
    {
        $this->headers = $response->getHeaders();
        $this->status_code = $response->getStatusCode();
        $this->body = json_decode($response->getBody());
    }
}

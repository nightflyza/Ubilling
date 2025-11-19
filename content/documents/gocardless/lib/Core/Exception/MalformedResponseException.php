<?php

namespace GoCardlessPro\Core\Exception;

class MalformedResponseException extends GoCardlessProException
{
    private $response;

    public function __construct($message, $response)
    {
        $this->response = $response;
        parent::__construct($message);
    }


    public function response()
    {
        return $this->response;
    }
};

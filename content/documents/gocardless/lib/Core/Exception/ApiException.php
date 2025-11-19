<?php

namespace GoCardlessPro\Core\Exception;

class ApiException extends GoCardlessProException
{
    public $api_error;
    private $api_response;

    /**
     * @param ApiResponse $api_response the response from the GoCardless API
     */
    public function __construct($api_response)
    {
        $this->api_response = $api_response;
        $this->api_error = $api_response->body->error;
        parent::__construct($this->getErrorMessage(), $this->api_error->code);
    }

    /**
     * @param string $error_type the error type returned by the GoCardless API
     * @return ApiException the exception corresponding to the supplied error type
     */
    public static function getErrorForType($error_type)
    {
        switch($error_type) {
        case 'gocardless':
            return 'GoCardlessInternalException';
        case 'invalid_api_usage':
            return 'InvalidApiUsageException';
        case 'invalid_state':
            return 'InvalidStateException';
        case 'validation_failed':
            return 'ValidationFailedException';
        }

        throw new GoCardlessProException('Invalid error type ' . $error_type);
    }

    public function getType()
    {
        return $this->api_error->type;
    }

    public function getErrors()
    {
        if (property_exists($this->api_error, 'errors')) {
            return $this->api_error->errors;
        }

        return array();
    }

    public function getDocumentationUrl()
    {
        return $this->api_error->documentation_url;
    }

    public function getRequestId()
    {
        return $this->api_error->request_id;
    }

    public function getApiResponse()
    {
        return $this->api_response;
    }

    protected function getErrorMessage()
    {
        if (!is_array($this->getErrors())) {
            return $this->api_error->message;
        }

        $error_messages = array_map(array($this, 'extractErrorMessage'), $this->getErrors());
        $error_messages = array_filter(
            $error_messages,
            function ($m) {
                return $m != $this->api_error->message;
            }
        );

        if (count($error_messages) > 0) {
            return $this->api_error->message . ' (' . implode($error_messages, ", ") . ')';
        } else {
            return $this->api_error->message;
        }
    }

    protected function extractErrorMessage($error)
    {
        return $error->message;
    }
}

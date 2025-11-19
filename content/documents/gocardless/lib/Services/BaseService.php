<?php

namespace GoCardlessPro\Services;

use GoCardlessPro\Core\Exception\GoCardlessPro;
use GoCardlessPro\Core\ListResponse;

/**
 * Base service class for all resource services.
 */
abstract class BaseService
{
    /**
     * @var \GoCardlessPro\Core\ApiClient Internal API client for making API requests.
     */
    protected $api_client;

    /**
     * @var string The key to envelope and unenvelope API requests/responses.
     */
    protected $envelope_key;

    /**
     * @var string class to instantiate for each returned resource
     */
    protected $resource_class;

    /**
     * Constructor for all base services, passes in the internal http client.
     *
     * @param \GoCardlessPro\Core\ApiClient $api_client ApiClient object.
     */
    public function __construct($api_client)
    {
        $this->api_client = $api_client;
    }

    /**
     * Handles functions in the API that are normally PHP reserved words. For
     * example `list`.
     *
     * @param string   $name The name of the function
     * @param string[] $args any arguments to the intended function
     */
    public function __call($name, $args)
    {
        $attemptName = '_do' . ucfirst($name);
        if (method_exists($this, $attemptName)) {
            return call_user_func_array(array($this, $attemptName), $args);
        }
        trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
    }

    /**
     * Takes a raw response and returns either an instantiated resource or a
     * ListResponse
     *
     * @param array $response The raw API response
     *
     * @return ListResponse|\GoCardlessPro\Resources\BaseResource
     */
    protected function getResourceForResponse($response)
    {
        $api_response = new \GoCardlessPro\Core\ApiResponse($response);
        $unenveloped_body = $this->getUnenvelopedBody($api_response->body);

        if(is_array($unenveloped_body)) {
            return new ListResponse($unenveloped_body, $this->resource_class, $api_response);
        } else {
            $rclass = $this->resource_class;
            return new $rclass($unenveloped_body, $api_response);
        }
    }

    /**
     * @param object $body The decoded JSON body of the response
     *
     * @return object The body, unenveloped
     */
    protected function getUnenvelopedBody($body)
    {
        if(isset($body->{$this->envelope_key})) {
            return $body->{$this->envelope_key};
        }

        return $body->data;
    }

}

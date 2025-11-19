<?php

namespace GoCardlessPro;

/**
 * Factory for building a configured Guzzle retry middleware for retrying failed requests
 */
class RetryMiddlewareFactory
{

    const MAX_AUTOMATIC_TIMEOUT_RETRIES = 3;
    const RETRY_DELAY = 500;
    const ACTIONS_PATH_REGEX = '/\/actions\/[a-z]+\z/';

    /**
     * Builds an appropriately configured RetryMiddleware to retry failed requests
     * @return GuzzleHttp\RetryMiddleware
     */
    public static function buildMiddleware()
    {
        return \GuzzleHttp\Middleware::retry(self::buildRetryDecider(), self::buildRetryDelay());
    }

    /**
     * Internal function for building a retry decider for the Guzzle Retry middleware
     * @return callable A function called to decide whether to retry a request
     */
    private static function buildRetryDecider()
    {
        return function (
            $retries,
            \GuzzleHttp\Psr7\Request $request,
            \GuzzleHttp\Psr7\Response $response = null,
            \GuzzleHttp\Exception\RequestException $exception = null
        ) {
            if ($retries >= self::MAX_AUTOMATIC_TIMEOUT_RETRIES) {
                return false;
            }

            if (!self::isConnectionError($exception) && !self::isRetryableServerError($response)) {
                return false;
            }

            if ($request->getMethod() == "GET" || $request->getMethod() == "PUT") {
                return true;
            }

            $path = $request->getUri()->getPath();

            if ($request->getMethod() == "POST") {
                if (!preg_match(self::ACTIONS_PATH_REGEX, $path)) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * Internal function for setting the delay for the Guzzle Retry middleware
     * @return callable A function called to decide how long to delay before a retry
     */
    private static function buildRetryDelay()
    {
        return function (
            $numberOfRetries
        ) {
            return self::RETRY_DELAY;
        };
    }

    /**
     * Internal function for determining if a request hit a connection error
     * @return boolean
    */
    private static function isConnectionError(\GuzzleHttp\Exception\RequestException $exception = null)
    {
        return $exception instanceof \GuzzleHttp\Exception\ConnectException;
    }

    /**
     * Internal function for determining if a response was a 5XX indicating a problem on
     * GoCardless' end, where a retry is likely to resolve the problem (e.g. 504 Gateway
     * Timeout)
     * @return boolean
     */
    private static function isRetryableServerError(\GuzzleHttp\Psr7\Response $response)
    {
        if ($response) {
            $statusCode = $response->getStatusCode();
            return $statusCode > 500 && $statusCode < 600;
        } else {
            return false;
        }
    }
}

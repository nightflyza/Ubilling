<?php

namespace GoCardlessPro;

/**
 * Class containing a collection of functions for validating and parsing GoCardless webhooks
 */
class Webhook
{
    const INVALID_SIGNATURE_MESSAGE = "This webhook doesn't appear to be a genuine " .
                                      "webhook from GoCardless, because the signature " .
                                      "header doesn't match the signature computed with" .
                                      " your webhook endpoint secret.";

    /**
     * Validates that a webhook was genuinely sent by GoCardless using `isValidSignature`,
     * and then parses it into an array of `GoCardlessPro::Resources::Event`
     * objects representing each event included in the webhook.
     *
     * @param  string $request_body            the request body
     * @param  string $signature_header        the signature included in the request, found in the `Webhook-Signature` header
     *     `Webhook-Signature` header
     * @param  string $webhook_endpoint_secret the webhook endpoint secret for your webhook
     *     endpoint, as configured in your GoCardless Dashboard
     * @return GoCardlessPro\Resources\Event[] the events included in the
     *     webhook
     * @raises GoCardlessPro\Core\Exception\InvalidSignatureException if the
     *     signature header specified does not match the signature computed using the
     *     request body and webhook endpoint secret
     */
    public static function parse($request_body, $signature_header, $webhook_endpoint_secret)
    {
        if (self::isSignatureValid($request_body, $signature_header, $webhook_endpoint_secret)) {
            $events = json_decode($request_body)->events;
            return array_map('self::buildEvent', $events);
        } else {
            throw new Core\Exception\InvalidSignatureException(self::INVALID_SIGNATURE_MESSAGE);
        }
    }

    /**
     * Validates that a webhook was genuinely sent by GoCardless by computing its
     * signature using the body and your webhook endpoint secret, and comparing that with
     * the signature included in the `Webhook-Signature` header.
     *
     * @param  string $request_body            the request body
     * @param  string $signature_header        the signature included in the request, found in the `Webhook-Signature` header
     *     `Webhook-Signature` header
     * @param  string $webhook_endpoint_secret the webhook endpoint secret for your webhook
     *     endpoint, as configured in your GoCardless Dashboard
     * @return boolean whether the webhook's signature is valid
     */
    public static function isSignatureValid($request_body, $signature_header, $webhook_endpoint_secret)
    {
        $computed_signature = hash_hmac("sha256", $request_body, $webhook_endpoint_secret);
        return hash_equals($computed_signature, $signature_header);
    }

    /**
     * Internal function for converting a parsed stdObject into an event resource
     */
    private static function buildEvent($event) 
    {
        return new Resources\Event($event);
    }
}

<?php

require_once 'ApiClient.php';

class ViberApiClient extends ApiClient {

    protected $messages = array();
    protected $sender;

    public function __construct($api_key, $sender, $source = null) {
        $this->sender = $sender;
        parent::__construct($api_key, $source);
    }

    private function getStatus($endpoint) {
        try {
            $resp = $this->sendRequest($endpoint);
        } catch (\Exception $e) {
            $error = 'Request failed (code: ' . $e->getCode() . '): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp, true);
        return $result;
    }

    public function getStatusByReference($reference) {
        return $this->getStatus('viber/reference/' . $reference);
    }

    public function getStatusById($message_id) {
        return $this->getStatus('viber/' . $message_id);
    }

    public function getPrices($tariff = NULL) {
        try {
            $resp = $this->sendRequest('viber/prices' . ($tariff !== NULL ? ('/' . $tariff) : ''));
        } catch (\Exception $e) {
            $error = 'Request failed (code: ' . $e->getCode() . '): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp, true);
        return $result;
    }

    public function clearMessages() {
        $this->messages = array();
    }

    /**
     * param $to is an array of ['msisdn' => $msisdn, 'reference' => $reference], where 'reference' is optional
     * @param $to
     * @param $text
     * @param $alpha_name
     * @param array $viber_options
     * @param bool $is_promotional
     * @param string $callback_url
     */
    public function addMessage($to, $text, $viber_options = array(), $alpha_name = null, $is_promotional = true, $callback_url = '') {
        $alpha_name = $alpha_name ?: $this->sender;
        $message = array();
        $message['to'] = $to;
        $message['text'] = $text;
        $message['alpha_name'] = $alpha_name;
        if (!$is_promotional)
            $message['is_promotional'] = $is_promotional;
        if ($callback_url != '')
            $message['callback_url'] = $callback_url;
        if (count($viber_options) > 0)
            $message['options']['viber'] = $viber_options;
        $this->messages[] = $message;
    }

    public function getMessagesPrice($validity = 86400, $tariff = NULL) {
        return $this->sendMessages($validity, $tariff, true);
    }

    /**
     * @param int $validity
     * @param null $tariff
     * @param bool $only_price
     * @return mixed
     */
    public function sendMessages($validity = 86400, $tariff = NULL, $only_price = false) {
        if (count($this->messages) == 0)
            return array('error' => 'No messages to send');
        $message = array();
        $message['validity'] = $validity;
        if ($tariff !== NULL)
            $message['tariff'] = $tariff;
        $message['messages'] = $this->messages;
        $endpoint = $only_price ? 'viber/price' : 'viber/create';
        try {
            $resp = $this->sendRequest($endpoint, json_encode($message), 'PUT');
        } catch (\Exception $e) {
            $error = 'Request failed (code: ' . $e->getCode() . '): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp, true);
        return $result;
    }

}

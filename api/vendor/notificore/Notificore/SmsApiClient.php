<?php
require_once 'ApiClient.php';

class SmsApiClient extends ApiClient {

    private $sender;
    private $tariff;

    public function __construct($api_key, $sender, $tariff=null, $source = null)
    {
        $this->sender = $sender;
        $this->tariff = $tariff;
        parent::__construct($api_key, $source);
    }

    private function getStatus ($endpoint)
    {
        try {
            $resp = $this->sendRequest($endpoint);
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }

    public function getStatusByReference ($reference)
    {
        return $this->getStatus ('sms/reference/' . $reference);
    }

    public function getStatusById ($message_id)
    {
        return $this->getStatus ('sms/' . $message_id);
    }

    public function getTaskStatus ($task_id)
    {
        try {
            $resp = $this->sendRequest('sms/task/' . $task_id);
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }

    public function getPrices($tariff=NULL)
    {
        try {
            $resp = $this->sendRequest('sms/prices' . ($tariff !== NULL ? ('/' . $tariff) : ''));
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }

    public function getPrice ($msisdn, $originator, $body, $reference, $validity=72, $tariff=NULL)
    {
        $originator = $originator ?: $this->sender;
        $tariff = $tariff ?: $this->tariff;
        return $this->sendSms ($msisdn, $body, $reference, $validity, $tariff, $originator, true);
    }

    public function sendSms ($msisdn, $body, $reference, $validity=72, $tariff=NULL, $originator = null, $only_price=false)
    {
        $originator = $originator ?: $this->sender;
        $tariff = $tariff ?: $this->tariff;
        $message = array();
        $message['destination'] = 'phone';
        $message['msisdn'] = $msisdn;
        $message['originator'] = $originator;
        $message['body'] = $body;
        $message['reference'] = $reference;
        $message['validity'] = $validity;
        if ($tariff !== NULL)
            $message['tariff'] = $tariff;
        $endpoint = $only_price ? 'sms/price' : 'sms/create';
        try {
            $resp = $this->sendRequest($endpoint,json_encode($message),'PUT');
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
           return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }

    public function getTaskPrice ($msisdns, $body, $validity=72, $tariff=NULL, $originator=NULL)
    {
        return $this->sendTask ($msisdns, $body, $validity, $tariff, $originator, true);
    }
    /**
     * sends the sms text to array of destination numbers
     * $msisdns are array of [$msisdn, $reference]
     * @param $msisdns
     * @param $originator
     * @param $body
     * @param int $validity
     * @param null $tariff
     * @param $only_price
     *
     * @return array
     */
    public function sendTask ($msisdns, $body, $validity=72, $tariff=NULL, $originator=NULL, $only_price=false)
    {
        $originator = $originator ?: $this->sender;
        $message = array();
        $message['destination'] = 'phones';
        $message['phones'] = $msisdns;
        $message['originator'] = $originator;
        $message['body'] = $body;
        $message['validity'] = $validity;
        if ($tariff !== NULL)
            $message['tariff'] = $tariff;
        $endpoint = $only_price ? 'sms/price' : 'sms/create';
        try {
            $resp = $this->sendRequest($endpoint,json_encode($message),'PUT');
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
            return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }

    public function getMultiPrice ($messages, $validity=72, $tariff=NULL)
    {
        return $this->sendSmsMulti ($messages, $validity, $tariff, true);
    }

    /**
     * sends a few sms with different senders / originators
     * @param $messages
     * @param int $validity
     * @param null $tariff
     * @param $only_price
     *
     * @return array
     */
    public function sendSmsMulti ($messages, $validity=72, $tariff=NULL, $only_price=false)
    {
        foreach ($messages as &$msg)
            if(!isset($msg['originator']) && $this->sender)
                $msg['originator'] = $this->sender;
        $message = array();
        $message['destination'] = 'individual';
        $message['phones'] = $messages;
        $message['validity'] = $validity;
        if ($tariff !== NULL)
            $message['tariff'] = $tariff;
        $endpoint = $only_price ? 'sms/price' : 'sms/create';
        try {
            $resp = $this->sendRequest($endpoint,json_encode($message),'PUT');
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
           return array('error' => $error);
        }
        $result = json_decode($resp,true);
        return $result;
    }
}
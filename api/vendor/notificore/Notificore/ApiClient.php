<?php

class ApiClient {

    const API_URL = 'https://api.notificore.com/v1.0/';
    
    protected $api_url;
    protected $api_key;
    protected $logger;

    public function __construct($api_key, $api_source = null)
    {
        $this->api_key = $api_key;
        if(!$api_source) {
            $this->api_source = 'Notificore PHP Library';
        } else $this->api_source = $api_source;
    }

    /**
     * @param $resource_url
     * @param null|string|array $post_data
     * @param null $custom_request
     * @return mixed
     * @throws \Exception
     */
    public function sendRequest ($resource_url, $post_data=NULL, $custom_request=NULL) {
        $client = curl_init();
        if ($post_data === NULL || !is_array($post_data))
            curl_setopt($client,CURLOPT_HTTPHEADER,array('X-API-KEY: '.$this->api_key, 'X-API-SOURCE: ' . $this->api_source, 'Content-type: text/json; charset=utf-8'));
        else
            curl_setopt($client,CURLOPT_HTTPHEADER,array('X-API-KEY: '.$this->api_key, 'X-API-SOURCE: ' . $this->api_source));
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_FOLLOWLOCATION, false);
        if ($custom_request !== NULL)
            curl_setopt($client, CURLOPT_CUSTOMREQUEST, $custom_request);
        curl_setopt($client, CURLOPT_URL, self::API_URL . $resource_url);
        if ($post_data !== NULL AND $custom_request === NULL)
            curl_setopt($client, CURLOPT_POST, true);
		if ($post_data !== NULL)
			curl_setopt($client, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($client);
        if (!$result) {
            throw new Exception (curl_error($client), curl_errno($client));
        } else
            return $result;
    }

    public function addLog ($message) {

    }

    public function getBalance () {
        try {
            $resp = $this->sendRequest('common/balance');
        } catch (Exception $e) {
            $error = 'Request failed (code: ' .$e->getCode() .'): ' . $e->getMessage();
            $this->addLog($error);
            throw new Exception($error, -1);
        }
        $result = json_decode($resp,true);
        return $result;
    }
}

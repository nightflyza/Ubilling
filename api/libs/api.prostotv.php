<?php

namespace UTG;

/**
 * ProstoTV API PHP client
 */
class ProstoTV {

    /**
     * Contains current instance login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Contains current instance password
     *
     * @var string
     */
    protected $password = '';

    /**
     * Contains last request status code
     *
     * @var int
     */
    protected $status = 0;

    /**
     * Contains last request error
     *
     * @var int
     */
    protected $error = 0;

    /**
     * Contains current instance temporary token
     *
     * @var string
     */
    protected $token = '';

    /**
     * Contains basic API URL
     *
     * @var string
     */
    protected $url = 'https://api.prosto.tv/v1/';

    /**
     * Thats constructor. What did you expect there?
     * 
     * @param string $login
     * @param string $password
     * @param string $url
     */
    public function __construct($login, $password, $url = null) {
        if ($url) {
            $this->url = $url;
        }
        $this->login = $login;
        $this->password = $password;
        $this->token = null;
    }

    /**
     * Magic getter for last request status or error properties
     * 
     * @param string $name
     * 
     * @return int
     */
    public function __get($name) {
        switch ($name) {
            case 'status':
                return ($this->status);
                break;
            case 'error':
                return ($this->error);
                break;
        }
    }

    /**
     * Performs some GET request to remote API
     * 
     * @param string $resource
     * 
     * @return string/bool
     */
    public function get($resource) {
        return ($this->request('GET', $resource));
    }

    /**
     * Performs some POST request to remote API
     * 
     * @param string $resource
     * @param array $data
     * 
     * @return string
     */
    public function post($resource, $data = array()) {
        return ($this->request('POST', $resource, $data));
    }

    /**
     * Performs PUT request to some remote API resource
     * 
     * @param string $resource
     * @param array $data
     * 
     * @return string
     */
    public function put($resource, $data = array()) {
        return ($this->request('PUT', $resource, $data));
    }

    /**
     * Performs DELETE request to some remote API resource
     * 
     * @param string $resource
     * 
     * @return string
     */
    public function delete($resource) {
        return ($this->request('DELETE', $resource));
    }

    /**
     * Pushes request of some specified method to remote API
     * 
     * @param string $method
     * @param string $resource
     * @param array $data
     * 
     * @return array/boolean on error
     */
    protected function request($method, $resource, $data = array()) {
        if (!$this->token && ($method != 'POST' || $resource != '/tokens')) {
            $this->getToken();
        }

        $context = array('http' => array(
                'method' => $method,
                'header' => array("Content-Type: application/json; charset=utf-8"),
                'ignore_errors' => true,
                'timeout' => 60,
        ));

        if ($this->token) {
            $context['http']['header'][] = "Authorization: Bearer " . $this->token;
        }

        if ($method != 'GET') {
            $context['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($context);

        try {
            $content = file_get_contents($this->url . ltrim($resource, '/ '), false, $context);
            $content = json_decode($content, true);
        } catch (Exception $e) {
            $this->error = $e;
            return (false);
        }

        $answer = explode(' ', $http_response_header[0]);
        $this->status = intval($answer[1]);
        if (in_array($this->status, array(200, 201))) {
            $this->error = null;
            return ($content);
        } else {
            $this->error = $content;
            return (false);
        }
    }

    /**
     * Sets temporary token to current instance
     * 
     * @return void
     */
    protected function getToken() {
        $data = $this->request('POST', '/tokens', array('login' => $this->login, 'password' => $this->password));
        $this->token = $data['token'];
    }

}

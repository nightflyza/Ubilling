<?php

/**
 * YouTV API PHP client
 */
class YouTV {

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
     * Contains current instance dealer id
     *
     * @var string
     */
    protected $dealerID = '';

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
    protected $url = 'https://api.youtv.com.ua';

    /**
     * low level debug flag
     *
     * @var bool
     */
    protected $debugFlag=false;

    /**
     * Some predefined stuff
     */
    const LOG_PATH = 'exports/ytv_debug.log';

    /**
     * Thats constructor. What did you expect there?
     * 
     * @param string $login
     * @param string $password
     * @param string $url
     * @param bool $debugFlag
     */
    public function __construct($login, $password, $dealerID, $debugFlag=false) {

        $this->debugFlag = $debugFlag;
        $this->dealerID = $dealerID;
        $this->login = $login;
        $this->password = $password;
        

        // Getting auth token
        $this->getToken();
    }

    /**
     * Sending request to API
     * 
     * @param string $method
     * @param string $resource
     * @param array $data
     * 
     * @return mixed
     */
    public function sendRequest($method, $resource, $data = array())
    {

        $url = $this->url . $resource;
        $remote = new OmaeUrl($url);
        $remote->setTimeout(3);
        $ubVer = @file_get_contents('RELEASE');
        $agent = 'UbillingYouTVClient/' . trim($ubVer);
        $remote->setUserAgent($agent);
        $remote->setOpt(CURLOPT_VERBOSE, false);
        $remote->setOpt(CURLOPT_CUSTOMREQUEST, $method);
        $remote->setOpt(CURLINFO_HEADER_OUT, true);

        $remote->dataHeader('Accept', 'application/vnd.youtv.v9+json');
        $remote->dataHeader('Device-UUID', '98765432100');
        $remote->dataHeader('Accept-Language', 'uk');

        if (!empty($this->token)) {
            $remote->dataHeader('Authorization', 'Bearer ' . $this->token);
        }

        if ($method == 'POST') {
            $remote->setOpt(CURLOPT_POST, true);
            $remote->setOpt(CURLOPT_POSTFIELDS, $data);
        }

        $result = $remote->response();
        $response = json_decode($result, true);
        $httpCode = $remote->httpCode();
        $httpError = '';
        $remoteError = $remote->error();
        if (!empty($remoteError)) {
            if (isset($remoteError['errormessage'])) {
                $httpError = $remoteError['errormessage'];
            }
        }

        $this->status = $httpCode;
        $this->error = $httpError;


        //low level debug logging
        if ($this->debugFlag) {
            $logString=curdatetime().' => ';
            $logString.='URL: '.$url. ' LOGIN: '.$this->login. ' HTTP CODE: '.$httpCode . ' HTTP ERROR: '.$httpError;
            file_put_contents(self::LOG_PATH, $logString.PHP_EOL, FILE_APPEND);
            file_put_contents(self::LOG_PATH, json_encode($response, JSON_PRETTY_PRINT).PHP_EOL, FILE_APPEND);
            file_put_contents(self::LOG_PATH, '==================' . PHP_EOL, FILE_APPEND);
        }

        return $response;
    }

    /**
     * Retreiving auth token
     */
    private function getToken()
    {
        $data = array(
            'email'    => $this->login,
            'password' => $this->password
        );

        $response = $this->sendRequest('POST', '/dealer/auth', $data);

        if (isset($response['token'])) {
            $this->token = $response['token'];
        } else {
            if (isset($response['data']) and isset($response['data']['token'])) {
                $this->token = $response['data']['token'];
            }
        }
    }


    /**
     * Getting tariffs list
     *
     * @return mixed
     */
    public function getPrices()
    {
        $resource = '/dealer/' . $this->dealerID . '/prices';

        return $this->sendRequest('GET', $resource);
    }


    /**
     * New user creation
     *
     * @param $external_id
     * @param $name
     * @param $email
     * @param $password
     * @return mixed
     */
    public function createUser($external_id, $name, $email, $password)
    {
        $resource = '/dealer/' . $this->dealerID . '/users';

        $data = array(
            'name'        => $name,
            'email'       => $email,
            'external_id' => $external_id,
            'password'    => $password
        );

        return $this->sendRequest('POST', $resource, $data);
    }


    /**
     * Getting all dealer users list
     *
     * @return mixed
     */
    public function getUsers()
    {
        $resource = '/dealer/' . $this->dealerID . '/users';

        return $this->sendRequest('GET', $resource);
    }

    /**
     * Getting user by id
     *
     * @param $user_id
     * @return mixed
     */
    public function getUser($user_id)
    {
        $resource = '/dealer/' . $this->dealerID . '/users/'.$user_id;

        return $this->sendRequest('GET', $resource);
    }

    /**
     * Searching user by external_id
     *
     * @param $user_id
     * 
     * @return mixed
     */
    public function getUserByExternalId($external_id)
    {
        $resource = '/dealer/' . $this->dealerID . '/users/external-user-id/'.$external_id;

        return $this->sendRequest('GET', $resource);
    }


    /**
     * Subscription activation for user
     * 
     * @param $user_id
     * @param $price_id
     * @param $days
     * 
     * @return mixed
     */
    public function subscriptions($user_id, $price_id, $days = 365)
    {
        $resource = '/dealer/' . $this->dealerID . '/subscriptions';

        $data = array(
            'user_id'  => $user_id,
            'price_id' => $price_id,
            'days'     => $days
        );

        return $this->sendRequest('POST', $resource, $data);
    }

    /**
     * User blocking
     *     Result 1 - everything is ok
     *     Result 0 - already blocked
     *
     * @param $user_id
     * 
     * @return mixed
     */
    public function blockUser($user_id)
    {
        $resource = '/dealer/' . $this->dealerID . '/users/'.$user_id.'/block';

        return $this->sendRequest('PUT', $resource);
    }

}

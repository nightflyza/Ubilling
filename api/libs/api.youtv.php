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
     * Thats constructor. What did you expect there?
     * 
     * @param string $login
     * @param string $password
     * @param string $url
     */
    public function __construct($login, $password, $dealerID) {

        $this->dealerID = $dealerID;
        $this->login = $login;
        $this->password = $password;

        // Получим токен
        $this->getToken();
    }


    public function sendRequest($method, $resource, $data = array())
    {

        $url = $this->url . $resource;

        $headers = array(
            "Accept: application/vnd.youtv.v8+json",
            "Device-UUID: 98765432100",
            "Accept-Language: ru"
        );

        if (!empty($this->token)) {
            $headers[] = "Authorization: Bearer " . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $result = curl_exec($ch);
        $response = json_decode($result, true);

        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch); // Deprecated in PHP 8.5
        }

        return $response;
    }

    /**
     * Получаем токен
     */
    private function getToken()
    {
        $data = array(
            'email'    => $this->login,
            'password' => $this->password
        );
        $response = $this->sendRequest('POST', '/auth/login', $data);

        if (isset($response['token'])) {
            $this->token = $response['token'];
        }
    }


    /**
     * Список тарифов
     *
     * @return mixed
     */
    public function getPrices()
    {
        $resource = '/dealer/' . $this->dealerID . '/prices';

        return $this->sendRequest('GET', $resource);
    }


    /**
     * Создание пользователя.
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
     * Получение всех пользователей дилера
     *
     * @return mixed
     */
    public function getUsers()
    {
        $resource = '/dealer/' . $this->dealerID . '/users';

        return $this->sendRequest('GET', $resource);
    }

    /**
     * Получение абонента
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
     * Поиск абонента по external_id
     *
     * @param $user_id
     * @return mixed
     */
    public function getUserByExternalId($external_id)
    {
        $resource = '/dealer/' . $this->dealerID . '/users/external-user-id/'.$external_id;

        return $this->sendRequest('GET', $resource);
    }


    /**
     * Активация подписки для пользователя.
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
     * Блокировка пользователя
     *     Результат 1 - всё ок
     *     Результат 0 - уже заблокирован
     *
     * @param $user_id
     * @return mixed
     */
    public function blockUser($user_id)
    {
        $resource = '/dealer/' . $this->dealerID . '/users/'.$user_id.'/block';

        return $this->sendRequest('PUT', $resource);
    }

}

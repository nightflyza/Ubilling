<?php

/**
 * Askozia PBX incoming calls processing class
 */
class AskoziaNum {

    /**
     * Telepathy object placeholder
     *
     * @var object
     */
    protected $telepathy = '';

    /**
     * System cache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Mobile number placeholder
     *
     * @var string
     */
    protected $number = '';

    /**
     * Userdata caching time in seconds
     */
    const CACHE_TIME = 3600;

    /**
     * Log path
     */
    const LOG_PATH = 'content/documents/askozianum.log';

    /**
     * Default calls logging table
     */
    const LOG_TABLE = 'callshist';

    /**
     * Debug/Log flag
     */
    const DEBUG = true;

    /**
     * Creates new AskoziaNum instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initTelepathy();
        $this->initCache();
    }

    /**
     * Sets current mobile number
     * 
     * @param string $number
     * 
     * @return void
     */
    public function setNumber($number) {
        $this->number = $number;
    }

    /**
     * Inits telepathy object instance
     * 
     * @return void
     */
    protected function initTelepathy() {
        $this->telepathy = new Telepathy(false, true, false, true, false);
        $this->telepathy->usePhones();
    }

    /**
     * Inits system cache object instance for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Saves incoming call into database
     * 
     * @return void
     */
    protected function saveCallsHist($date, $number, $login = '') {
        $login = mysql_real_escape_string($login);
        $number = mysql_real_escape_string($number);
        $number = trim($number);
        $login = trim($login);
        $query = "INSERT INTO `" . self::LOG_TABLE . "` (`id`,`date`,`number`,`login`) VALUES "
                . "(NULL, '" . $date . "', '" . $number . "','" . $login . "');";
        nr_query($query);
    }

    /**
     * Saves some data to log
     * 
     * @param int $reply
     * @param string $login
     * 
     * @return void
     */
    protected function log($reply, $login) {
        if (self::DEBUG) {
            $curdateTime = curdatetime();
            $logData = $curdateTime . ' NUMBER: ' . $this->number . ' REPLY: ' . $reply . ' LOGIN: ' . $login . "\n";
            file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
            $this->saveCallsHist($curdateTime, $this->number, $login);
        }
    }

    /**
     * Returns some state int for some user if he is detected by mobile phone
     * Can return cash balance also. In that case a serialized and base64 encoded array
     * with user acc state and acc cash balance is returned
     *
     * @param $ignoreCache
     * @param $getMoney
     *
     * 0 - user not found
     * 1 - user found and have positive balance
     * 2 - user found and have negative balance
     * 3 - user found and accoun is frozen or something like that
     * 
     * @return mixed
     */
    protected function getReply($ignoreCache = false, $getMoney = false) {
        $detectedLogin = $this->telepathy->getByPhone($this->number, true, true);
        $askReply = '0';
        $askReplyArr = array();

        if (!empty($detectedLogin)) {

            $userData = $this->cache->get('ASKUSERDATA', self::CACHE_TIME);
            if (empty($userData) or $ignoreCache) {
                $userData = array();
                $userDataRaw = simple_queryall("SELECT `login`,`Cash`,`Credit`,`Passive`,`Down`,`AlwaysOnline`,`Fee` from `users` LEFT JOIN (SELECT `name`,`Fee` FROM `tariffs`) as T on (`users`.`Tariff`=`T`.`name`)");
                if (!empty($userDataRaw)) {
                    foreach ($userDataRaw as $io => $each) {
                        $userData[$each['login']] = $each;
                    }
                }
                $this->cache->set('ASKUSERDATA', $userData, self::CACHE_TIME);
            }
            if (isset($userData[$detectedLogin])) {
                $userData = $userData[$detectedLogin];
                if ($userData['Cash'] >= '-' . $userData['Credit']) {
                    $askReply = '1';
                } else {
                    $askReply = '2';
                }
                if (($userData['Passive'] == 1) OR ( $userData['Down'] == 1) OR ( $userData['AlwaysOnline'] == 0)) {
                    $askReply = '3';
                }

                if ($getMoney) {
                    $askReplyArr[] = $askReply;
                    $askReplyArr[] = round($userData['Cash'], 2);
                    $askReplyArr[] = round($userData['Fee'], 2);
                    $askReply = base64_encode(serialize($askReplyArr));
                }
            }
        }
        $this->log((($getMoney) ? print_r($askReplyArr, true) : $askReply), $detectedLogin);
        return ($askReply);
    }

    /**
     * Returns parsed calls log
     * 
     * @return array
     */
    public function parseLog() {
        $result = array();
        if (file_exists(self::LOG_PATH)) {
            $rawData = file_get_contents(self::LOG_PATH);
            $rawData = explodeRows($rawData);
            $count = 0;
            if (!empty($rawData)) {
                foreach ($rawData as $io => $line) {
                    if (!empty($line)) {
                        $line = explode(' ', $line);
                        $result[$count]['date'] = $line[0];
                        $result[$count]['time'] = $line[1];
                        $result[$count]['number'] = $line[3];
                        $result[$count]['reply'] = $line[5];
                        $result[$count]['login'] = @$line[7];
                        $count++;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders reply for Askozia external AGI application
     * 
     * @return void
     */
    public function renderReply($asteriskGet = false, $ignoreCache = false, $getMoney = false) {
        if (!$asteriskGet) {
            die($this->getReply($ignoreCache, $getMoney));
        } else {
            $this->getReply($ignoreCache, $getMoney);
        }
    }

}

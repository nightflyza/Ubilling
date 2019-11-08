<?php

/*
 * Base class prophetic guessing login by the address/surname/realname
 */

class Telepathy {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available user address
     *
     * @var array
     */
    protected $alladdress = array();

    /**
     * Contains all available users realnames
     *
     * @var array
     */
    protected $allrealnames = array();

    /**
     * Contains preprocessed users surnames
     *
     * @var array
     */
    protected $allsurnames = array();

    /**
     * Contains all available user mobiles
     *
     * @var array
     */
    protected $allMobiles = array();

    /**
     * Contains all available additional user mobiles
     *
     * @var array
     */
    protected $allExtMobiles = array();

    /**
     * Contains all available user phones
     *
     * @var array
     */
    protected $allPhones = array();

    /**
     * Contains all available user mobiles with doubles
     *
     * @var array
     */
    protected $allMobilesFull = array();

    /**
     * Contains all available additional user mobiles with doubles
     *
     * @var array
     */
    protected $allExtMobilesFull = array();

    /**
     * Contains all available user phones with doubles
     *
     * @var array
     */
    protected $allPhonesFull = array();

    /**
     * Case sensitivity flag
     *
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * Cached address usage flag
     *
     * @var bool
     */
    protected $cachedAddress = true;

    /**
     * Use phones caching or not?
     *
     * @var bool
     */
    protected $cachedPhones = false;

    /**
     * Return only uniq login when telepaty by phones
     *
     * @var bool
     */
    protected $uniqLogin = false;

    /**
     * City display flag
     *
     * @var array
     */
    protected $citiesAddress = false;

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains users previously detected by phone number as number=>login
     *
     * @var array
     */
    protected $phoneTelepathyCache = array();

    /**
     * Contains phone data caching time in seconds
     */
    const PHONE_CACHE_TIME = 86400;

    /**
     * Creates new telepathy instance
     * 
     * @param bool $caseSensitive
     * @param bool $cachedAddress
     * @param bool $citiesAddress
     * @param bool $cachedPhones
     * @param bool $uniqLogin
     * 
     * @return void
     */
    public function __construct($caseSensitive = false, $cachedAddress = true, $citiesAddress = false, $cachedPhones = false, $uniqLogin = false) {
        $this->caseSensitive = $caseSensitive;
        $this->cachedAddress = $cachedAddress;
        $this->citiesAddress = $citiesAddress;
        $this->cachedPhones = $cachedPhones;
        $this->uniqLogin = $uniqLogin;
        $this->loadConfig();
        $this->initCache();
        $this->loadAddress();

        if (!$this->caseSensitive) {
            $this->addressToLowerCase();
        }
        if (!empty($this->alladdress)) {
            $this->alladdress = array_flip($this->alladdress);
        }
    }

    /**
     * Loads system alter.ini config into protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system cache
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads cached address data to private data property 
     * 
     * @return void
     */
    protected function loadAddress() {
        if (!$this->citiesAddress) {
            if ($this->cachedAddress) {
                $this->alladdress = zb_AddressGetFulladdresslistCached();
            } else {
                $this->alladdress = zb_AddressGetFulladdresslist();
            }
        } else {
            $this->alladdress = zb_AddressGetFullCityaddresslist();
        }
    }

    /**
     * Loads all user realnames from database into private prop
     * 
     * @return void
     */
    protected function loadRealnames() {
        $this->allrealnames = zb_UserGetAllRealnames();
    }

    /**
     * Normalizes mobile number to +380 format. 
     * May be not acceptable for countries other than Ukraine.
     * 
     * @param string $mobile
     * 
     * @return string/void on error
     */
    protected function normalizePhoneFormat($mobile) {
        $mobile = vf($mobile, 3);
        $len = strlen($mobile);
//all is ok
        if ($len != 12) {
            switch ($len) {
                case 11:
                    $mobile = '3' . $mobile;
                    break;
                case 10:
                    $mobile = '38' . $mobile;
                    break;
                case 9:
                    $mobile = '380' . $mobile;
                    break;
            }
        }

        $newLen = strlen($mobile);
        if ($newLen == 12) {
            $mobile = '+' . $mobile;
        } else {
            $mobile = '';
        }


        return ($mobile);
    }

    /**
     * Loads all existing phone data into protected props for further usage
     * 
     * @return void
     */
    public function usePhones() {
        //init previously detected phones cache
        $this->phoneTelepathyCache = $this->cache->get('PHONETELEPATHY', self::PHONE_CACHE_TIME);
        //loading user phones data
        if ($this->cachedPhones) {
            $allPhoneData = $this->cache->get('PHONEDATA', self::PHONE_CACHE_TIME);
            if (empty($allPhoneData)) {
                $allPhoneData = zb_UserGetAllPhoneData();
                $this->cache->set('PHONEDATA', $allPhoneData, self::PHONE_CACHE_TIME);
            }
        } else {
            $allPhoneData = zb_UserGetAllPhoneData();
        }
        if (!empty($allPhoneData)) {
            foreach ($allPhoneData as $login => $each) {
                $cleanMobile = vf($each['mobile'], 3);
                if (!empty($cleanMobile)) {
                    if ($this->uniqLogin) {
                        $this->allMobilesFull[$cleanMobile][] = $login;
                    } else {
                        $this->allMobiles[$cleanMobile] = $login;
                    }
                }

                $cleanPhone = vf($each['phone'], 3);
                if (!empty($cleanPhone)) {
                    if ($this->uniqLogin) {
                        $this->allMobilesFull[$cleanPhone][] = $login;
                    } else {
                        $this->allMobiles[$cleanPhone] = $login;
                    }
                }
            }
        }
        //additional mobiles loading if enabled
        if ($this->altCfg['MOBILES_EXT']) {

            if ($this->cachedPhones) {
                $allExtTmp = $this->cache->get('EXTMOBILES', self::PHONE_CACHE_TIME);
                if (empty($allExtTmp)) {
                    $extMob = new MobilesExt();
                    $allExtTmp = $extMob->getAllMobilesUsers();
                    $this->cache->set('EXTMOBILES', $allExtTmp, self::PHONE_CACHE_TIME);
                }
            } else {
                $extMob = new MobilesExt();
                $allExtTmp = $extMob->getAllMobilesUsers();
            }

            if (!empty($allExtTmp)) {
                foreach ($allExtTmp as $eachExtMobile => $login) {
                    $cleanExtMobile = vf($eachExtMobile, 3);
                    if ($this->uniqLogin) {
                        $this->allMobilesFull[$cleanExtMobile][] = $login;
                    } else {
                        $this->allMobiles[$cleanExtMobile] = $login;
                    }
                }
            }
        }
    }

    /**
     * Preprocess all user surnames into usable data
     * 
     * @return void
     */
    protected function surnamesExtract() {
        if (!empty($this->allrealnames)) {
            foreach ($this->allrealnames as $login => $realname) {
                $raw = explode(' ', $realname);
                if (!empty($raw)) {
                    $this->allsurnames[$login] = $raw[0];
                }
            }
        }
    }

    /**
     * external passive constructor for name realname login detection
     * 
     * @return void
     */
    public function useNames() {
        $this->loadRealnames();
        $this->surnamesExtract();

        if (!empty($this->allrealnames)) {
            $this->allrealnames = array_flip($this->allrealnames);
        }

        if (!empty($this->allrealnames)) {
            $this->allsurnames = array_flip($this->allsurnames);
        }
    }

    /**
     * preprocess available address data into lower case
     * 
     * @return void
     */
    protected function addressToLowerCase() {
        global $ubillingConfig;
        $alterconf = $ubillingConfig->getAlter();

        $cacheTime = $alterconf['ADDRESS_CACHE_TIME'];
        $cacheTime = time() - ($cacheTime * 60);
        if (!$this->citiesAddress) {
            $cacheName = 'exports/fulladdresslistlowercache.dat';
        } else {
            $cacheName = 'exports/fullcityaddresslistlowercache.dat';
        }
        $updateCache = false;
        if (file_exists($cacheName)) {
            $updateCache = false;
            if ((filemtime($cacheName) > $cacheTime)) {
                $updateCache = false;
            } else {
                $updateCache = true;
            }
        } else {
            $updateCache = true;
        }

        if (($alterconf['ADDRESS_CACHE_TIME']) AND ( $this->cachedAddress)) {
            if ($updateCache) {
                $tmpArr = array();
                if (!empty($this->alladdress)) {
                    foreach ($this->alladdress as $eachlogin => $eachaddress) {
                        $tmpArr[$eachlogin] = strtolower_utf8($eachaddress);
                    }
                    $this->alladdress = $tmpArr;
                    $tmpArr = array();
                }
                //store property to cache
                $cacheStoreData = serialize($this->alladdress);
                file_put_contents($cacheName, $cacheStoreData);
                $cacheStoreData = '';
            } else {
                $rawCacheData = file_get_contents($cacheName);
                $rawCacheData = unserialize($rawCacheData);
                $this->alladdress = $rawCacheData;
                $rawCacheData = array();
            }
        } else {
            $tmpArr = array();
            if (!empty($this->alladdress)) {
                foreach ($this->alladdress as $eachlogin => $eachaddress) {
                    $tmpArr[$eachlogin] = strtolower_utf8($eachaddress);
                }
                $this->alladdress = $tmpArr;
                $tmpArr = array();
            }
        }
    }

    /**
     * detects user login by its address
     * 
     * @param string $address address to guess
     * 
     * @return string
     */
    public function getLogin($address) {
        if (!$this->caseSensitive) {
            $address = strtolower_utf8($address);
        }

        if (isset($this->alladdress[$address])) {
            return ($this->alladdress[$address]);
        } else {
            return(false);
        }
    }

    /**
     * returns user login by surname
     * 
     * @param string $surname
     * 
     * @return string
     */
    public function getBySurname($surname) {
        if (isset($this->allsurnames[$surname])) {
            return ($this->allsurnames[$surname]);
        } else {
            return(false);
        }
    }

    /**
     * Get user login by some phone number
     * 
     * @param string $phoneNumber
     * @param bool $onlyMobile
     * @param bool $normalizeMobile
     * 
     * @return string
     */
    public function getByPhone($phoneNumber, $onlyMobile = false, $normalizeMobile = false) {
        $result = '';
        /**
         * Come with us speeding through the night
         * As fast as any bird in flight
         * Silhouettes against the Mother Moon
         * We will be there
         * I think it's a bad idea to normalize the phone by code, since this piece of code works current for Ukraine
         */
        $phoneNumber = ($normalizeMobile) ? $this->normalizePhoneFormat($phoneNumber) : $phoneNumber;
        if (!empty($phoneNumber)) {
            if (!$onlyMobile) {
                if (!empty($this->allPhones) and !$this->uniqLogin) {
                    foreach ($this->allPhones as $baseNumber => $userLogin) {
                        if (ispos((string) $phoneNumber, (string) $baseNumber)) {
                            $result = $userLogin;
                        }
                    }
                }
            }

            if (!empty($this->allExtMobiles) and !$this->uniqLogin) {
                foreach ($this->allExtMobiles as $baseNumber => $userLogin) {
                    if (ispos((string) $phoneNumber, (string) $baseNumber)) {
                        $result = $userLogin;
                    }
                }
            }

            if (!empty($this->allMobiles) and !$this->uniqLogin) {
                foreach ($this->allMobiles as $baseNumber => $userLogin) {
                    if (ispos((string) $phoneNumber, (string) $baseNumber)) {
                        $result = $userLogin;
                    }
                }
            }

            if ($this->uniqLogin) {
                $resultTempUniq = array_merge_recursive($this->allPhonesFull, $this->allExtMobilesFull, $this->allMobilesFull);
                // Try remove duplicate phone and mobile from one users
                foreach ($resultTempUniq as $phone => $dataArr) {
                    $rawArr = array_unique($dataArr);
                    if (count($rawArr) == 1 and substr($phone, -10) == substr($phoneNumber, -10)) {
                        $result = $rawArr[0];
                        return ($result);
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Get user login by some phone number. After all calls you must finalize cache with savePhoneTelepathyCache().
     * 
     * @param string $phoneNumber
     * @param bool $onlyMobile
     * @param bool $normalizeMobile
     * 
     * @return string
     */
    public function getByPhoneFast($phoneNumber, $onlyMobile = false, $normalizeMobile = false) {
        $result = '';
        if (isset($this->phoneTelepathyCache[$phoneNumber])) {
            $result = $this->phoneTelepathyCache[$phoneNumber];
        } else {
            $detectedLogin = $this->getByPhone($phoneNumber, $onlyMobile, $normalizeMobile);
            $result=$detectedLogin;
                $this->phoneTelepathyCache[$phoneNumber] = $detectedLogin;
        }
        return ($result);
    }

    /**
     * Saves previously detected by phone logins cache
     * 
     * @return void
     */
    public function savePhoneTelepathyCache() {
        $this->cache->set('PHONETELEPATHY', $this->phoneTelepathyCache, self::PHONE_CACHE_TIME);
    }
    
    /**
     * Cleans phone telepathy cache
     * 
     * @return void
     */
    public function flushPhoneTelepathyCache() {
        $this->cache->delete('PHONETELEPATHY');
        
    }

}

?>
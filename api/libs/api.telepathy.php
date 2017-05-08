<?php

/*
 * Base class prophetic guessing login by the address/surname/realname
 */

class Telepathy {

    protected $alladdress = array();
    protected $allrealnames = array();
    protected $allsurnames = array();
    protected $caseSensitive = false;
    protected $cachedAddress = true;
    protected $citiesAddress = false;
    protected $useraddress = '';

    /**
     * Creates new telepathy instance
     * 
     * @param bool $caseSensitive
     * @param bool $cachedAddress
     * @param bool $citiesAddress
     * 
     * @return void
     */
    public function __construct($caseSensitive = false, $cachedAddress = true, $citiesAddress = false) {
        $this->caseSensitive = $caseSensitive;
        $this->cachedAddress = $cachedAddress;
        $this->citiesAddress = $citiesAddress;
        $this->loadAddress();

        if (!$this->caseSensitive) {
            $this->addressToLowerCase();
        }
        if (!empty($this->alladdress)) {
            $this->alladdress = array_flip($this->alladdress);
        }
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

}

?>
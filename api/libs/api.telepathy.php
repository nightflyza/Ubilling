<?php

/*
 * Base class prophetic guessing login by the address
 */

class Telepathy {

    protected $alladdress = array();
    protected $caseSensitive = false;
    protected $cachedAddress = true;
    protected $useraddress = '';

    public function __construct($caseSensitive = false, $cachedAddress = true) {
        $this->loadAddress();
        $this->caseSensitive = $caseSensitive;
        $this->cachedAddress = $cachedAddress;
        if (!$this->caseSensitive) {
            $this->addressToLowerCase();
        }

        if (!empty($this->alladdress)) {
            $this->alladdress = array_flip($this->alladdress);
        }
    }

    /*
     * Loads cached address data to private data property 
     * 
     * @return void
     */

    protected function loadAddress() {
        if ($this->cachedAddress) {
            $this->alladdress = zb_AddressGetFulladdresslistCached();
        } else {
            $this->alladdress = zb_AddressGetFulladdresslist();
        }
    }

    /*
     * preprocess available address data into lower case
     * 
     * @return void
     */

    protected function addressToLowerCase() {
        global $ubillingConfig;
        $alterconf = $ubillingConfig->getAlter();

        $cacheTime = $alterconf['ADDRESS_CACHE_TIME'];
        $cacheTime = time() - ($cacheTime * 60);
        $cacheName = 'exports/fulladdresslistlowercache.dat';
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
            $cacheStoreData=  serialize($this->alladdress);
            file_put_contents($cacheName, $cacheStoreData);
            $cacheStoreData='';
            
           } else {
               $rawCacheData=  file_get_contents($cacheName);
               $rawCacheData= unserialize($rawCacheData);
               $this->alladdress=$rawCacheData;
               $rawCacheData=array();
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

    /*
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

}

?>
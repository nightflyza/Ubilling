<?php

class SMSDirections {
    /**
     * UbillingCache instance placeholder
     *
     * @var null
     */
    protected $ubCache = null;

    /**
     * $directionsCache array from UbillingCache
     *
     * @var array
     */
    protected $directionsCache = array();

    public function __construct() {
        $this->ubCache = new UbillingCache();
        $this->directionsCache = $this->ubCache->get('SMS_SERVICES_DIRECTIONS');
    }

    /**
     * Returns SMS service ID as a direction from cache
     *
     * @param $keyType
     * @param $entity
     *
     * @return int
     */
    public function getDirection($keyType, $entity) {
        return ( isset($this->directionsCache[$keyType][$entity]) ) ? $this->directionsCache[$keyType][$entity] : 0;
    }

    /**
     * Returns SMS service name by it's ID from cache
     * Recommended to use in a big message sets instead of zb_getSMSServiceNameByID()
     *
     * @param int $smsServiceId
     *
     * @return string
     */
    public function getDirectionNameById($smsServiceId = 0) {
        return ( isset($this->directionsCache['service_id_name'][$smsServiceId]) ) ? $this->directionsCache['service_id_name'][$smsServiceId] : '';
    }
}

?>
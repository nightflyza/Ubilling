<?php

/**
 * Flexible SMS routing implementation
 */
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

    /**
     * Placeholder for SMS_SERVICES_BINDINGS_CACHE_LIFETIME from alter.ini
     *
     * @var int
     */
    protected $directionsCacheLifeTime = 1800;


    public function __construct() {
        global $ubillingConfig;
        $this->ubCache = new UbillingCache();

        if ($ubillingConfig->getAlterParam('SMS_SERVICES_BINDINGS_CACHE_LIFETIME')) {
            $this->directionsCacheLifeTime = $ubillingConfig->getAlterParam('SMS_SERVICES_BINDINGS_CACHE_LIFETIME');
        }

        $thisInstance = $this;
        $this->directionsCache = $this->ubCache->getCallback('SMS_SERVICES_DIRECTIONS', function () use ($thisInstance) {
                                                                    return ( $thisInstance->getSMSServicesDirectionsData() );
                                                            }, $this->directionsCacheLifeTime);
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

    /**
     * Returns SMS services bindings suitable for caching
     *
     * @return array
     */
    public function getSMSServicesDirectionsData() {
        $dirsCache = array();

        $queryBindings = 'SELECT * FROM sms_services_relations;';
        $queriedBindings = nr_query($queryBindings);

        if (!empty($queriedBindings)) {
            $fetch_assoc = ($queriedBindings instanceof mysqli_result) ? 'mysqli_fetch_assoc' : 'mysql_fetch_assoc';

            while ($row = $fetch_assoc($queriedBindings)) {
                if (!empty($row['user_login'])) {
                    $dirsCache['user_login'][$row['user_login']] = $row['sms_srv_id'];
                }

                if (!empty($row['employee_id'])) {
                    $dirsCache['employee_id'][$row['employee_id']] = $row['sms_srv_id'];
                }
            }
        }

        $queryServices = 'SELECT * FROM sms_services;';
        $queriedServices = nr_query($queryServices);

        if (!empty($queriedServices)) {
            $fetch_assoc = ($queriedServices instanceof mysqli_result) ? 'mysqli_fetch_assoc' : 'mysql_fetch_assoc';

            while ($row = $fetch_assoc($queriedServices)) {
                $dirsCache['service_id_name'][$row['id']] = $row['name'];

                if ($row['default_service']) {
                    $dirsCache['service_id_name'][0] = $row['name'];
                }
            }
        }

        return ($dirsCache);
    }

    public function refreshCacheForced() {
        $this->ubCache->set('SMS_SERVICES_DIRECTIONS', $this->getSMSServicesDirectionsData(), $this->directionsCacheLifeTime);
        $this->directionsCache = $this->ubCache->get('SMS_SERVICES_DIRECTIONS', $this->directionsCacheLifeTime);
    }
}

?>
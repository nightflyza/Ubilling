<?php

class SMSDirections {
    public function getDirection($KeyType, $Entity) {
        // later we'll make it fine and smooth with cache
        //$UBCache = new UbillingCache();
        $DirectionsCache = array();

        $Query = 'select * from sms_services_relations;';
        $Queried = nr_query($Query);

        if ( !empty($Queried) ) {
            $fetch_assoc = ($Queried instanceof mysqli_result) ? 'mysqli_fetch_assoc' : 'mysql_fetch_assoc';

            while ($Row = $fetch_assoc($Queried)) {
                if ( !empty($Row['user_login']) ) {
                    $DirectionsCache['user_login'][$Row['user_login']] = $Row['sms_srv_id'];
                }

                if ( !empty($Row['employee_id']) ) {
                    $DirectionsCache['employee_id'][$Row['employee_id']] = $Row['sms_srv_id'];
                }
            }
        }

        return ( isset($DirectionsCache[$KeyType][$Entity]) ) ? $DirectionsCache[$KeyType][$Entity] : 0;
    }
}

?>
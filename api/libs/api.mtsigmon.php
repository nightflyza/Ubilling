<?php

    /**
     * MikroTik/UBNT signal monitoring class
     */
    class MTSIGMON {

        /**
         * Returns array of monitored MikroTik devices with MTSIGMON label and enabled SNMP
         * 
         * @return array
         */
        function getDevices() {
            $query = "SELECT * from `switches` WHERE `desc` LIKE '%MTSIGMON%' AND `snmp` != ''";
            $alldevices = simple_queryall($query);
            $result = array();

            if (!empty($alldevices)) {
                foreach ($alldevices as $io => $eachdevice) {
                    $result[$eachdevice['id']]['ip'] = $eachdevice['ip'];
                    $result[$eachdevice['id']]['location'] = $eachdevice['location'];
                    $result[$eachdevice['id']]['community'] = $eachdevice['snmp'];
                }
            }

            return ($result);
        }

        /**
         * Returns array of MAC=>Signal data for some MikroTik/UBNT device
         * 
         * @param string $ip
         * @param string $community
         * @return array
         */
        function deviceQuery($ip, $community) {
            $oid = '.1.3.6.1.4.1.14988.1.1.1.2.1.3';
            $mask_mac = false;
            $ubnt_shift = 0;
            $result = array();
            $rawsnmp = array();

            $snmp = new SNMPHelper();
            $snmp->setBackground(false);
            $snmp->setMode('native');
            $tmpSnmp = $snmp->walk($ip, $community, $oid, false);

            // Returned string '.1.3.6.1.4.1.14988.1.1.1.2.1.3 = '
            // in AirOS 5.6 and newer
            if ($tmpSnmp === "$oid = ") {
                $oid = '.1.3.6.1.4.1.41112.1.4.7.1.3.1';
                $tmpSnmp = $snmp->walk($ip, $community, $oid, false);
                $ubnt_shift = 1;
            }

            if (!empty($tmpSnmp) and ( $tmpSnmp !== "$oid = ")) {
                $explodeData = explodeRows($tmpSnmp);
                if (!empty($explodeData)) {
                    foreach ($explodeData as $io => $each) {
                        $explodeRow = explode(' = ', $each);
                        if (isset($explodeRow[1])) {
                            $rawsnmp[$explodeRow[0]] = $explodeRow[1];
                        }
                    }
                }
            }

            if (!empty($rawsnmp)) {
                if (is_array($rawsnmp)) {
                    foreach ($rawsnmp as $indexOID => $rssi) {
                        $oidarray = explode(".", $indexOID);
                        $end_num = sizeof($oidarray) + $ubnt_shift;
                        $mac = '';

                        for ($counter = 2; $counter < 8; $counter++) {
                            $temp = sprintf('%02x', $oidarray[$end_num - $counter]);

                            if (($counter < 5) && $mask_mac)
                                $mac = ":xx$mac";
                            else if ($counter == 7)
                                $mac = "$temp$mac";
                            else
                                $mac = ":$temp.$mac";
                        }


                        $mac = str_replace('.', '', $mac);
                        $mac = trim($mac);
                        $rssi = str_replace('INTEGER:', '', $rssi);
                        $rssi = trim($rssi);
                        $result[$mac] = $rssi;
                    }
                }
            }

            return ($result);
        }

    }

?>
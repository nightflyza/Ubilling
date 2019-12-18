<?php

//MikroTik dynamic shaper
if ($_GET['action'] == 'mikrotikdnshaper') {
    if ($alterconf['DSHAPER_ENABLED']) {
        $Now = date('H:i:s');

        $DNDataQuery = "SELECT `usr_nh`.*, `nas`.`nasip`, `nas`.`options`, 
                                                   `speeds`.`speeddown`, `speeds`.`speedup`,  `speeds`.`burstdownload`, `speeds`.`burstupload`, `speeds`.`bursttimedownload`, `speeds`.`burstimetupload`, 
                                                   `dshpt`.`threshold1`, `dshpt`.`threshold2`, `dshpt`.`speed` 
                                            FROM (
                                                    SELECT `users`.`login`, `users`.`ip`, `users`.`Tariff`, `nh`.`netid` 
                                                    FROM `users` 
                                                    LEFT JOIN `nethosts` AS `nh` ON `users`.`ip` = `nh`.`ip`
                                                    WHERE !`users`.`Down` AND !( `users`.`Cash` < -(`users`.`Credit`) )
                                                    ) AS `usr_nh` 
                                            LEFT JOIN `nas` ON `usr_nh`.`netid` = `nas`.`netid`
                                            LEFT JOIN `dshape_time` AS `dshpt` ON `usr_nh`.`Tariff` = `dshpt`.`tariff`
                                            LEFT JOIN `speeds` ON `usr_nh`.`Tariff` = `speeds`.`tariff`
                                            WHERE `nas`.`nastype` = 'mikrotik' AND `dshpt`.`speed` IS NOT NULL AND '" . $Now . "' BETWEEN `dshpt`.`threshold1` AND `dshpt`.`threshold2`;";

        $DNData = simple_queryall($DNDataQuery);

        if (!empty($DNData)) {
            $UsersCnt = count($DNData);
            $RouterOSAPI = new RouterOS();
            $Action = '';

            foreach ($DNData as $eachrow => $eachlogin) {
                $MTikNasOpts = base64_decode($eachlogin['options']);
                $MTikNasOpts = unserialize($MTikNasOpts);
                $UseNewConnType = ( isset($MTikNasOpts['use_new_conn_mode']) && $MTikNasOpts['use_new_conn_mode'] ) ? true : false;
                $apiPort = ( !empty($MTikNasOpts['apiport']) ) ? $MTikNasOpts['apiport'] : 8728;

                $RouterOSAPI->connect($eachlogin['nasip'], $MTikNasOpts['username'], $MTikNasOpts['password'], $UseNewConnType, $apiPort);

                if ($RouterOSAPI->connected) {
                    if (isset($_GET['param']) && ($_GET['param'] == 'downshift')) {
                        $Action = 'DOWNSHIFT';

                        if (empty($eachlogin['burstimetupload']) or empty($eachlogin['bursttimedownload'])
                                or empty($eachlogin['burstupload']) or empty($eachlogin['burstdownload'])) {

                            $Template = array('.id' => '',
                                'max-limit' => $eachlogin['speedup'] . 'k/' . $eachlogin['speeddown'] . 'k'
                            );
                        } else {
                            $Template = array('.id' => '',
                                'max-limit' => $eachlogin['speedup'] . 'k/' . $eachlogin['speeddown'] . 'k',
                                'burst-limit' => $eachlogin['burstupload'] . 'k/' . $eachlogin['burstdownload'] . 'k',
                                'burst-threshold' => ($eachlogin['speedup'] * 0.8) . 'k/' . ($eachlogin['speeddown'] * 0.8) . 'k',
                                'burst-time' => $eachlogin['burstimetupload'] . '/' . $eachlogin['bursttimedownload']
                            );
                        }
                    } else {
                        if (empty($eachlogin['burstimetupload']) or empty($eachlogin['bursttimedownload'])
                                or empty($eachlogin['burstupload']) or empty($eachlogin['burstdownload'])) {

                            $Template = array('.id' => '',
                                'max-limit' => $eachlogin['speedup'] . 'k/' . $eachlogin['speed'] . 'k'
                            );
                        } else {
                            $Template = array('.id' => '',
                                'max-limit' => $eachlogin['speedup'] . 'k/' . $eachlogin['speed'] . 'k',
                                'burst-limit' => $eachlogin['burstupload'] . 'k/' . $eachlogin['speed'] . 'k',
                                'burst-threshold' => ($eachlogin['speedup'] * 0.8) . 'k/' . ($eachlogin['speed'] * 0.8) . 'k',
                                'burst-time' => $eachlogin['burstimetupload'] . '/' . $eachlogin['bursttimedownload']
                            );
                        }
                    }

                    $Entries = $RouterOSAPI->command('/queue/simple/print', array('.proplist' => '.id', '?name' => '' . trim($eachlogin['login']) . ''));

                    if (!empty($Entries)) {
                        foreach ($Entries as $Entry) {
                            $Template['.id'] = $Entry['.id'];
                            $MTikReply = $RouterOSAPI->command('/queue/simple/set', $Template);
                        }
                    }
                }
            }

            log_register('MT_DN_SHAPER ' . $Action . ' done to `' . $UsersCnt . '` users');
            die('OK:MT_DN_SHAPER');
        } else {
            die('OK:MT_DN_SHAPER_NO_USERS_TO_PROCESS');
        }
    }
}
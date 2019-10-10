<?php
if ($_GET['action'] == 'stgfeecharge2mysql') {
    global $ubillingConfig;
    $ubCache = new UbillingCache();

    $feeChargeData = array();
    $stglog = $ubillingConfig->getAlterParam('STG_LOG_PATH');
    $billingConf = $ubillingConfig->getBilling();
    $sudo = $billingConf['SUDO'];
    $cat = $billingConf['CAT'];
    $grep = $billingConf['GREP'];
    $exportsPath = str_ireplace('modules/remoteapi', '', dirname(__FILE__)) . 'exports/tmp_fee_charge';

    $command = $sudo . ' ' . $cat . ' ' . $stglog . ' | ' . $grep . ' "fee charge" > ' . $exportsPath;
    shell_exec($command);

    $tQuery = "DROP TABLE IF EXISTS `stg_fee_charge_tab`";
    nr_query($tQuery);

    $tQuery = "CREATE TABLE IF NOT EXISTS `stg_fee_charge_tab` (
                    `date` varchar(10),
                    `time` varchar(10),
                    `f3` varchar(5),
                    `f4` varchar(20), 
                    `f5` varchar(20),
                    `ip` varchar(20),
                    `f7` varchar(20),
                    `login` varchar(50), 
                    `f9` varchar(20),
                    `f10` varchar(20),
                    `f11` varchar(20),
                    `f12` varchar(20),
                    `ffrom` varchar(20),
                    `f14` varchar(5),
                    `fto` varchar(20),
                    `f16` varchar(20),
                    `f17` varchar(20),
                    `f18` varchar(20), 
                    KEY `date` (`date`),
                    KEY `time` (`time`), 
                    KEY dt (date,time), 
                    KEY login(login)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    nr_query($tQuery);

    $tQuery = "LOAD DATA LOCAL INFILE '" . $exportsPath ."' INTO TABLE `stg_fee_charge_tab` FIELDS TERMINATED BY ' '";
    nr_query($tQuery);

    $tQuery = "SELECT `max_date`, `user_login`, REPLACE(ffrom, \"'\", \"\") `balance_from`, REPLACE(fto, \"'\", \"\") `balance_to`
                      FROM 
                          (SELECT CONCAT(`date`, ' ', `time`) AS `dat`, REPLACE(REPLACE(`login`, \"'\", \"\"), \":\", \"\") AS `user_login`, `login`, `ffrom`, `fto` 
                              FROM `stg_fee_charge_tab`) AS `ttb` 
                                  INNER JOIN 
                                      (SELECT MAX(CONCAT(`date`, ' ', `time`)) AS `max_date`, `login` 
                                          FROM `stg_fee_charge_tab` GROUP BY `login`) AS `ttb2` 
                                    ON `ttb`.`login` = `ttb2`.`login` and `ttb`.`dat` = `ttb2`.`max_date`";

    /*$tQuery = "SELECT `ttb2`.`max_date`, REPLACE(REPLACE(`ttb`.`login`, \"'\", \"\"), \":\", \"\") AS `user_login`, REPLACE(ffrom, \"'\", \"\") `balance_from`, REPLACE(fto, \"'\", \"\") `balance_to`
                    FROM `stg_fee_charge_tab` AS `ttb` 
                        INNER JOIN  
                                (SELECT MAX(CONCAT(`date`, ' ', `time`)) AS `max_date`, `login` FROM `stg_fee_charge_tab` GROUP BY `login`) AS `ttb2` 
                            ON `ttb`.`login` = `ttb2`.`login` and  CONCAT(`ttb`.`date`, ' ', `ttb`.`time`) = `ttb2`.`max_date`";
    */
    $result = simple_queryall($tQuery);

    if (!empty($result)) {
        foreach ($result as $item) {
            $feeChargeData[$item['user_login']]['max_date'] = $item['max_date'];
            $feeChargeData[$item['user_login']]['balance_from'] = trim($item['balance_from'], ".:'");
            $feeChargeData[$item['user_login']]['balance_to'] = trim($item['balance_to'], ".:'");
        }
    }

    $ubCache->set('STG_FEE_CHARGE', $feeChargeData);

    $tQuery = "DROP TABLE IF EXISTS `stg_fee_charge_tab`";
    nr_query($tQuery);

    unlink($exportsPath);
}
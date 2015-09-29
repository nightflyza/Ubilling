<?php

if (cfr('MIKMIGR')) {
    $greed = new Avarice();
    $beggar = $greed->runtime('MIKMIGR');
    $mik = new mikbill();
    if (!empty($beggar)) {
        foreach ($beggar['CERT'] as $each) {
            eval($each);
        }
        if (file_exists($beggar['DUMP'])) {
            if (isset($_POST['netnum'])) {
                rcms_redirect("?module=mikbill_migration&netnum=" . $_POST['netnum']);
            }
            if (isset($_GET['netnum'])) {
                show_window('networks', $mik->web_MikbillMigrationNetworksForm($_GET['netnum']));
                $converts = array('db_user', 'db_pass', 'db_host', 'db_name', 'tariff_period', 'network');
                if (isset($_POST['network'])) {
                    if (wf_CheckPost($converts)) {
                        $mik->ConvertMikBill($_POST['db_user'], $_POST['db_pass'], $_POST['db_host'], $_POST['db_name'], $_POST['network'], $_POST['tariff_period']);
                        rcms_redirect("?module=mikbill_migration&success=1");
                    } else {
                        rcms_redirect("?module=mikbill_migration&notall=1");
                    }
                }
            } else {
                show_window('Networks number', $mik->web_MikbillMigrationNetnumForm());
            }
            if (isset($_GET['notall'])) {
                show_error("No all of required fields is filled");
            }
            if (isset($_GET['success'])) {
                show_success("sql dump generated find it in ./content/backups/sql/ub_dump.sql");
            }
        } else {
            show_error('ub.sql not found');
        }
    } else {
        show_error(__('No license key available'));
    }
} else {
    show_error(__('Access denied'));
}
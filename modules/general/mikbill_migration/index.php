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

            show_window('', $mik->web_MikbillMigrationNetworksForm());
            $converts = array('db_user', 'db_pass', 'db_host', 'db_name', 'tariff_period', 'login_as_pass');
            if (wf_CheckPost($converts)) {
                $result = $mik->ConvertMikBill($_POST['db_user'], $_POST['db_pass'], $_POST['db_host'], $_POST['db_name'], $_POST['tariff_period'], $_POST['login_as_pass']);
                if (empty($result)) {
                    rcms_redirect("?module=mikbill_migration&success=1");
                } else {
                    $warn = __('We have found some non unique IP addresses and excluded duplicates from processing');
                    foreach ($result as $login => $s_login) {
                        $warn .= wf_tag('br', true);
                        $warn .= __('Login');
                        $warn .= ': ' . $s_login;
                        $warn .= ' -> ' . $login;
                    }

                    show_warning($warn);
                }
            }
            if (isset($_GET['success'])) {
                show_success(__("SQL dump was generated. You can find it in" . " billing/content/backups/sql/ub.sql."));
            }
        } else {
            show_error(__('File not found' . ':ub.sql'));
        }
    } else {
        show_error(__('No license key available'));
    }
} else {
    show_error(__('Access denied'));
}
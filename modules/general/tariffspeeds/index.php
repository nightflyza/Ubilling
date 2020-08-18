<?php

if (cfr('TARIFFSPEED')) {
    $altCfg = $ubillingConfig->getAlter();
    if (isset($_GET['tariff'])) {
        // show speed editor and create speed if need
        $tariff = mysql_real_escape_string($_GET['tariff']);
        $existingspeeds = zb_TariffGetAllSpeeds();
        if (!isset($existingspeeds[$tariff]['speeddown'])) {
            if ($altCfg['BURST_ENABLED']) {
                zb_TariffCreateSpeed($tariff, 0, 0, 0, 0, 0, 0);
            } else {
                zb_TariffCreateSpeed($tariff, 0, 0);
            }
            $existingspeeds = zb_TariffGetAllSpeeds();
        }
        $fieldnames = array('fieldname1' => __('Down speed Kbit/s'), 'fieldname2' => __('Up speed Kbit/s'));
        $fieldkeys = array('fieldkey1' => 'newspeeddown', 'fieldkey2' => 'newspeedup');
        $olddata[1] = $existingspeeds[$tariff]['speeddown'];
        $olddata[2] = $existingspeeds[$tariff]['speedup'];
        if ($altCfg['BURST_ENABLED']) {
            $fieldnames['fieldname3'] = (__('Burst Download speed'));
            $fieldnames['fieldname4'] = (__('Burst Upload speed'));
            $fieldnames['fieldname5'] = (__('Burst Download Time speed'));
            $fieldnames['fieldname6'] = (__('Burst Upload Time speed'));
            $fieldkeys['fieldkey3'] = ('newburstdownload');
            $fieldkeys['fieldkey4'] = ('newburstupload');
            $fieldkeys['fieldkey5'] = ('newbursttimedownload');
            $fieldkeys['fieldkey6'] = ('newburstimetupload');
            $olddata[3] = $existingspeeds[$tariff]['burstdownload'];
            $olddata[4] = $existingspeeds[$tariff]['burstupload'];
            $olddata[5] = $existingspeeds[$tariff]['bursttimedownload'];
            $olddata[6] = $existingspeeds[$tariff]['burstimetupload'];
            show_window(__('Edit speed') . ' ' . $tariff, web_EditorSixStringDataForm($fieldnames, $fieldkeys, $olddata));
        } else {
            show_window(__('Edit speed') . ' ' . $tariff, web_EditorTwoStringDataForm($fieldnames, $fieldkeys, $olddata));
        }
        show_window('', wf_BackLink("?module=tariffspeeds", 'Back', true));
        // if all ok save speed
        if ((isset($_POST['newspeeddown'])) AND ( isset($_POST['newspeedup']))) {
            zb_TariffDeleteSpeed($tariff);
            $newSpeedDown = trim($_POST['newspeeddown']);
            $newSpeedUp = trim($_POST['newspeedup']);
            $newBurstDown = isset($_POST['newburstdownload']) ? trim($_POST['newburstdownload']) : '';
            $newBurstUp = isset($_POST['newburstupload']) ? trim($_POST['newburstupload']) : '';
            $newBurstTimeDown = isset($_POST['newbursttimedownload']) ? trim($_POST['newbursttimedownload']) : '';
            $newBurstTimeUp = isset($_POST['newburstimetupload']) ? trim($_POST['newburstimetupload']) : '';
            zb_TariffCreateSpeed($tariff, $newSpeedDown, $newSpeedUp, $newBurstDown, $newBurstUp, $newBurstTimeDown, $newBurstTimeUp, $newBurstTimeUp);
            rcms_redirect("?module=tariffspeeds&tariff=" . $tariff);
        }
    } else {
        //deleting speed
        if (wf_CheckGet(array('deletespeed'))) {
            zb_TariffDeleteSpeed($_GET['deletespeed']);
            rcms_redirect("?module=tariffspeeds");
        }
        show_window(__('Tariff speeds'), web_TariffSpeedLister());
        show_window('', wf_BackLink('?module=tariffs'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

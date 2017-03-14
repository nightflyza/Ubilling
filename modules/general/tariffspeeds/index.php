<?php

if (cfr('TARIFFSPEED')) {
    if (isset($_GET['tariff'])) {
        // show speed editor and create speed if need
        $tariff = mysql_real_escape_string($_GET['tariff']);
        $existingspeeds = zb_TariffGetAllSpeeds();
        if (!isset($existingspeeds[$tariff]['speeddown'])) {
            zb_TariffCreateSpeed($tariff, 0, 0, 0, 0, 0, 0);
            $existingspeeds = zb_TariffGetAllSpeeds();
        }
        $fieldnames = array('fieldname1' => __('Down speed Kbit/s'), 'fieldname2' => __('Up speed Kbit/s'), 'fieldname3' => __('Burst Speed Download'), 'fieldname4' => __('Burst Speed Upload'), 'fieldname5' => __('Burst Time Download'), 'fieldname6' => __('Burst Time Upload'));
        $fieldkeys = array('fieldkey1' => 'newspeeddown', 'fieldkey2' => 'newspeedup', 'fieldkey3' => 'newburstdownload', 'fieldkey4' => 'newburstupload', 'fieldkey5' => 'newbursttimedownload', 'fieldkey6' => 'newburstimetupload');
        $olddata[1] = $existingspeeds[$tariff]['speeddown'];
        $olddata[2] = $existingspeeds[$tariff]['speedup'];
        $olddata[3] = $existingspeeds[$tariff]['burstdownload'];
        $olddata[4] = $existingspeeds[$tariff]['burstupload'];
        $olddata[5] = $existingspeeds[$tariff]['bursttimedownload'];
        $olddata[6] = $existingspeeds[$tariff]['burstimetupload'];
        show_window(__('Edit speed') . ' ' . $tariff, web_EditorSixStringDataForm($fieldnames, $fieldkeys, $olddata));
        show_window('', wf_Link("?module=tariffspeeds", 'Back', true, 'ubButton'));
        // if all ok save speed
        if ((isset($_POST['newspeeddown'])) AND ( isset($_POST['newspeedup']))) {
            zb_TariffDeleteSpeed($tariff);
            $newSpeedDown = trim($_POST['newspeeddown']);
            $newSpeedUp = trim($_POST['newspeedup']);
            $newBurstDown = trim($_POST['newburstdownload']);
            $newBurstUp = trim($_POST['newburstupload']);
            $newBurstTimeDown = trim($_POST['newbursttimedownload']);
            $newBurstTimeUp = trim($_POST['newburstimetupload']);
            zb_TariffCreateSpeed($tariff, $newSpeedDown, $newSpeedUp, $newBurstDown, $newBurstUp, $newBurstTimeDown,$newBurstTimeUp,$newBurstTimeUp);
            rcms_redirect("?module=tariffspeeds");
        }
        
    } else {
        //deleting speed
        if (wf_CheckGet(array('deletespeed'))) {
              zb_TariffDeleteSpeed($_GET['deletespeed']);
              rcms_redirect("?module=tariffspeeds");
        }
        show_window(__('Tariff speeds'), web_TariffSpeedLister());
        show_window('', wf_Link('?module=tariffs', __('Back'), false, 'ubButton'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

<?php

if (cfr('TARIFFSPEED')) {
    if (isset($_GET['tariff'])) {
        // show speed editor and create speed if need
        $tariff = loginDB_real_escape_string($_GET['tariff']);
        $existingspeeds = zb_TariffGetAllSpeeds();
        if (!isset($existingspeeds[$tariff]['speeddown'])) {
            zb_TariffCreateSpeed($tariff, 0, 0);
            $existingspeeds = zb_TariffGetAllSpeeds();
        }
        $fieldnames = array('fieldname1' => __('Down speed Kbit/s'), 'fieldname2' => __('Up speed Kbit/s'));
        $fieldkeys = array('fieldkey1' => 'newspeeddown', 'fieldkey2' => 'newspeedup');
        $olddata[1] = $existingspeeds[$tariff]['speeddown'];
        $olddata[2] = $existingspeeds[$tariff]['speedup'];
        show_window(__('Edit speed') . ' ' . $tariff, web_EditorTwoStringDataForm($fieldnames, $fieldkeys, $olddata));
        show_window('', wf_BackLink("?module=tariffspeeds", 'Back', true));
        // if all ok save speed
        if ((isset($_POST['newspeeddown'])) AND ( isset($_POST['newspeedup']))) {
            zb_TariffDeleteSpeed($tariff);
            $newSpeedDown = trim($_POST['newspeeddown']);
            $newSpeedUp = trim($_POST['newspeedup']);
            zb_TariffCreateSpeed($tariff, $newSpeedDown, $newSpeedUp);
            rcms_redirect("?module=tariffspeeds");
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

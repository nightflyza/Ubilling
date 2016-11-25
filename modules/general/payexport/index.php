<?php

if (cfr('PAYEXPORT')) {

    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");

    if ($alter_conf['EXPORT_ENABLED']) {
        $export_filepath = 'exports/';
        $export_fileext = '.export';
        if (wf_CheckGet(array('dlexf'))) {
            zb_DownloadFile($export_filepath . vf($_GET['dlexf'], 3) . $export_fileext, 'default');
        }

        show_window(__('Export payments data'), zb_ExportForm());

        if ((isset($_POST['fromdate'])) AND ( isset($_POST['todate']))) {
            $from_date = $_POST['fromdate'];
            $to_date = $_POST['todate'];

            //export types
            //xml
            if ($alter_conf['EXPORT_FORMAT'] == 'xml') {
                $export_result = zb_ExportPayments($from_date, $to_date);
            }

            //dbf
            if ($alter_conf['EXPORT_FORMAT'] == 'dbf') {
                //need to be written
            }


            $export_filename = time();
            $exported_link = wf_Link('?module=payexport&dlexf=' . $export_filename, wf_img('skins/icon_download.png') . ' ' . __('Exported data download'), false, 'ubButton');
            file_write_contents($export_filepath . $export_filename . $export_fileext, $export_result);

            show_window('', $exported_link);
        }
    } else {
        show_error(__('Payments export not enabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

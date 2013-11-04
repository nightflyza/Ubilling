<?php
if (cfr('BACKUP')) {
set_time_limit (0);

if (isset ($_POST['createbackup'])) {
    if (isset($_POST['imready'])) {
        zb_backup_tables();
        
    } else {
        show_error(__('You are not mentally prepared for this'));
    }
}


if (wf_CheckGet(array('download'))) {
    if (cfr('ROOT')) {
        $filePath=  base64_decode($_GET['download']);
        zb_DownloadFile($filePath);
        
    } else {
        show_window(__('Error'), __('Access denied'));
    }
}

function web_AvailableDBBackupsList() {
    $backupsPath=DATA_PATH.'backups/sql/';
    $availbacks=  rcms_scandir($backupsPath);
    $result=__('No existing DB backups here');
    if (!empty($availbacks)) {
        $cells=  wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Size'));
        $cells.= wf_TableCell(__('Filename'));
        $rows= wf_TableRow($cells, 'row1');
        
        foreach ($availbacks as $eachDump) {
            $fileDate=  filectime($backupsPath.$eachDump);
            $fileDate= date("Y-m-d H:i:s", $fileDate);
            $fileSize= filesize($backupsPath.$eachDump);
            $fileSize= stg_convert_size($fileSize);
            $downloadLink=  wf_Link('?module=backups&download='.  base64_encode($backupsPath.$eachDump), $eachDump, false, '');
            
            $cells=  wf_TableCell($fileDate);
            $cells.= wf_TableCell($fileSize);
            $cells.= wf_TableCell($downloadLink);
            $rows.= wf_TableRow($cells, 'row3'); 
        }
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
    }
    
    return ($result);
}

function web_ConfigsUbillingList() {
    $downloadable=array(
        'config/billing.ini',
        'config/mysql.ini',
        'config/alter.ini',
        'config/ymaps.ini',
        'config/catv.ini',
        'config/dhcp/global.template',
        'config/dhcp/subnets.template',
        'config/dhcp/option82.template',
        'userstats/config/mysql.ini',
        'userstats/config/userstats.ini',
        'userstats/config/tariffmatrix.ini'
    );
    
    
    if (!empty($downloadable)) {
        $cells=  wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Size'));
        $cells.= wf_TableCell(__('Filename'));
        $rows= wf_TableRow($cells, 'row1');
        
        foreach ($downloadable as $eachConfig) {
            if (file_exists($eachConfig)) {
            $fileDate=  filectime($eachConfig);
            $fileDate= date("Y-m-d H:i:s", $fileDate);
            $fileSize= filesize($eachConfig);
            $fileSize= stg_convert_size($fileSize);
            $downloadLink=  wf_Link('?module=backups&download='.  base64_encode($eachConfig), $eachConfig, false, '');
            
            $cells=  wf_TableCell($fileDate);
            $cells.= wf_TableCell($fileSize);
            $cells.= wf_TableCell($downloadLink);
            $rows.= wf_TableRow($cells, 'row3'); 
            } else {
                $cells=  wf_TableCell('');
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell($eachConfig);
                $rows.= wf_TableRow($cells, 'row3'); 
            }
        }
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
    }
    
    return ($result);
}



//tables cleanup
if (wf_CheckGet(array('tableclean'))) {
    zb_DBTableCleanup($_GET['tableclean']);
    rcms_redirect("?module=backups");
}

    

show_window(__('Create backup'), web_BackupForm());
show_window(__('Available database backups'), web_AvailableDBBackupsList());
show_window(__('Important Ubilling configs'), web_ConfigsUbillingList());
show_window(__('Database cleanup'),web_DBCleanupForm());


} else {
      show_error(__('You cant control this module'));
}

?>

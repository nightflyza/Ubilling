<?php
$result='';
if (cfr('USERPROFILE')) {
    if (wf_CheckGet(array('tariff'))) {
        $tariffName=  mysql_real_escape_string($_GET['tariff']);
        $tariffInfo='';
        if ($tariffName=='*_NO_TARIFF_*') {
            $messages=new UbillingMessageHelper();
            $tariffInfo=$messages->getStyledMessage(__('No tariff'), 'warning');
        } else {
            $tariffPrice= zb_TariffGetPrice($tariffName);
            $tariffPeriods=  zb_TariffGetPeriodsAll();
            $tariffSpeeds=  zb_TariffGetAllSpeeds();
            $speedDown= (isset($tariffSpeeds[$tariffName])) ? $tariffSpeeds[$tariffName]['speeddown'] : __('No');
            $speedUp= (isset($tariffSpeeds[$tariffName])) ? $tariffSpeeds[$tariffName]['speedup'] : __('No');
            
            $period=(isset($tariffPeriods[$tariffName])) ? __($tariffPeriods[$tariffName]) : __('No') ;
            
            $cells=  wf_TableCell(__('Tariff'),'','row2');
            $cells.= wf_TableCell($tariffName);
            $rows= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Download speed'),'','row2');
            $cells.= wf_TableCell($speedDown);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Upload speed'),'','row2');
            $cells.= wf_TableCell($speedUp);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Period'),'','row2');
            $cells.= wf_TableCell($period);
            $rows.= wf_TableRow($cells, 'row3');
        
            $tariffInfo=  wf_TableBody($rows, '100%', 0, '');
        }
        $result=  wf_modalOpened(__('Tariff info'), $tariffInfo, '300', '200');
    } else {
        $result=__('Strange exeption');
    }
    
} else {
    $result=__('Access denied');
}

die($result);

?>
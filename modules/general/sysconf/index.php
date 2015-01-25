<?php
if (cfr('SYSCONF')) {

 $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
 $alteropts=  rcms_parse_ini_file(CONFIG_PATH."optsaltcfg");
 
 $dbconf=rcms_parse_ini_file(CONFIG_PATH."mysql.ini");
 $dbopts=  rcms_parse_ini_file(CONFIG_PATH."optsdbcfg");
 
 $billingconf=rcms_parse_ini_file(CONFIG_PATH."billing.ini");
 $billopts=  rcms_parse_ini_file(CONFIG_PATH."optsbillcfg");
 
 $catvconf=rcms_parse_ini_file(CONFIG_PATH."catv.ini");
 $catvopts=  rcms_parse_ini_file(CONFIG_PATH."optscatvcfg");
 
 $ymconf=rcms_parse_ini_file(CONFIG_PATH."ymaps.ini");
 $ymopts=  rcms_parse_ini_file(CONFIG_PATH."optsymcfg");
 
 $photoconf=  rcms_parse_ini_file(CONFIG_PATH."photostorage.ini");
 $photoopts= rcms_parse_ini_file(CONFIG_PATH."optsphotocfg");

if ($alterconf['PASSWORDSHIDE']) {
    $hide_passwords=true;
} else {
    $hide_passwords=false;
}

$dbcell= web_ConfigEditorShow('mysqlini', $dbconf, $dbopts);
$billcell=web_ConfigEditorShow('billingini', $billingconf, $billopts);
$altercell=web_ConfigEditorShow('alterini', $alterconf, $alteropts);
$catvcell=web_ConfigEditorShow('catvini', $catvconf, $catvopts);
$ymcells=  web_ConfigEditorShow('ymaps', $ymconf, $ymopts);
$photocells= web_ConfigEditorShow('photostorage', $photoconf, $photoopts);

$header_ub=wf_tag('h2',false).__('Ubilling setup').  wf_tag('h2',true);
$cells=  wf_TableCell($header_ub.$dbcell.$billcell.$catvcell.$ymcells.$photocells,'','','valign="top"');
$header_alter=wf_tag('h2',false).__('Custom features').  wf_tag('h2',true);
$cells.=wf_TableCell($header_alter.$altercell,'','','valign="top"');
$rows=  wf_TableRow($cells);

$grid= wf_TableBody($rows, '100%', 0, '');

   

show_window(__('System settings'),$grid);  
    
} else {
      show_error(__('You cant control this module'));
}

?>

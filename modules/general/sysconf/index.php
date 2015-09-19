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

$grid=  wf_tag('script');
$grid.='$(function() {
    $( "#tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
    $( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
  });';
$grid.=wf_tag('script',true);
$grid.=wf_tag('style');
$grid.='
  .ui-tabs-vertical { width: auto;}
  .ui-tabs-vertical .ui-tabs-nav { padding: .2em .1em .2em .2em; float: left; }
  .ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0; }
  .ui-tabs-vertical .ui-tabs-nav li a { display:block; }
  .ui-tabs-vertical .ui-tabs-nav li.ui-tabs-active { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; }
  .ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: left; width: 40em;}
  ';

$grid.=wf_tag('style',true);


$grid.=wf_tag('div', false, '', 'id="tabs"');
$grid.=wf_tag('ul');
$grid.=web_ConfigGetTabsControls($dbopts). web_ConfigGetTabsControls($billopts).web_ConfigGetTabsControls($alteropts);
$grid.=web_ConfigGetTabsControls($catvopts).web_ConfigGetTabsControls($ymopts). web_ConfigGetTabsControls($photoopts);
$grid.=wf_tag('ul',true);
$grid.=  $dbcell.$billcell.$catvcell.$ymcells.$photocells.$altercell;
$grid.=wf_tag('div',true).  wf_CleanDiv();
   

show_window(__('System settings'),$grid);  
    
} else {
      show_error(__('You cant control this module'));
}

?>

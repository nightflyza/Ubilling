<?php
// check for right of current admin on this module
if (cfr('CARDS')) {
   $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
   if ($altcfg['PAYMENTCARDS_ENABLED']) {
    
    if (isset($_POST['gencount'])) {
        $cards=zb_CardGenerate($_POST['gencount'], $_POST['genprice']);
        $generated='<textarea cols="80" rows="20">'.$cards.'</textarea>';
        show_window(__('Cards generated'),$generated);  
    }
    
    //mass actions
    if (isset($_POST['cardactions'])) {
    zb_CardsMassactions();
    }
    
    //if clean brute IP
    if (isset($_GET['cleanip'])) {
        zb_CardBruteCleanIP($_GET['cleanip']);
        rcms_redirect("?module=cards");
    }
    
    //total cleanup action
    if (wf_CheckGet(array('cleanallbrutes'))) {
        zb_CardBruteCleanupAll();
        rcms_redirect("?module=cards");
    }
    
    
    show_window(__('Cards generation'), web_CardsGenerateForm());
    show_window(__('Cards search'), web_CardsSearchForm());
   
    if (!wf_CheckPost(array('cardsearch'))) {
        show_window(__('Available payment cards'),web_CardsShow());
        web_CardShowBrutes();
        
    } else {
        show_window(__('Search results'), web_CardsSearchBySerial($_POST['cardsearch']));
        show_window('', wf_Link("?module=cards", __('Back'),false,'ubButton'));
    }
    
   } else {
       show_window(__('Error'), __('This module is disabled'));
   }
    
} else {
      show_error(__('You cant control this module'));
}

?>

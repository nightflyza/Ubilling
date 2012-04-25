<?php
// check for right of current admin on this module
if (cfr('CARDS')) {
   
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
    
    
    show_window(__('Cards generation'), web_CardsGenerateForm());
    show_window(__('Available payment cards'),web_CardsShow());
    show_window(__('Bruteforce attempts'),web_CardShowBrutes());
    
} else {
      show_error(__('You cant control this module'));
}

?>

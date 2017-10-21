<?php

// check for right of current admin on this module
if (cfr('CARDS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['PAYMENTCARDS_ENABLED']) {

        if (isset($_POST['card_create'])) {
            $cards = zb_CardGenerate($_POST['card_create']);
            show_window('', wf_modalOpened(__('Cards generated'), $cards, '500', '600'));
        }

        //cards print
        if (isset($_POST['cardactions']) && $_POST['cardactions'] == 'caprint') {
            $ids = http_build_query(array('id' => array_keys($_POST['_cards'])));
            rcms_redirect(sprintf("?module=printcards&action=list&%s", $ids));
        }

        //mass actions
        if (isset($_POST['cardactions'])) {
            zb_CardsMassactions();
        }

        if (isset($_POST['card_edit']) && isset($_POST['card_edit']['part'])) {
            $editCard = $_POST['card_edit'];
            zb_CardChange($editCard['part'], $editCard['selling'], $editCard['id']);
            rcms_redirect("?module=cards");
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

        // Check cards for dublicate
        show_window(__('There are duplicate serial numbers of cards'), zb_GetCardDublicate());

        show_window(__('Cards generation'), web_CardsGenerateForm());
        show_window(__('Create print card'), wf_Link("?module=printcards&action=setting", web_edit_icon().' '.__('Edit'), true, 'ubButton'));
        show_window(__('Cards search'), web_CardsSearchForm());

        if (!wf_CheckPost(array('card_search'))) {
            show_window(__('Available payment cards'), web_CardsShow());
            web_CardShowBrutes();
        } else {
            show_window(__('Search results'), web_CardsSearch($_POST['card_search']));
            show_window('', wf_Link("?module=cards", __('Back'), false, 'ubButton'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

<?php

if (cfr('PRINTCARD')) {
    if ($ubillingConfig->getAlterParam('PAYMENTCARDS_ENABLED')) {
        // create form
        if (extension_loaded('gd')) {

            function settingTemplate() {
                if (wf_CheckPost(array('back'))) {
                    rcms_redirect("?module=cards");
                }

                if (wf_CheckPost(array('delete'))) {
                    web_DeleteImege();
                }

                if (wf_CheckPost(array('print_card')) && wf_CheckPost(array('save'))) {
                    zb_SaveCardPrint($_POST['print_card']);
                    web_CreateTemplateCardPrint();
                }

                if (wf_CheckPost(array('upload'))) {
                    web_UploadFileCopy($_FILES['filename']['tmp_name']);
                }

                show_window(__('Create print card'), web_PrintCardCreateForm());
            }

            function cardsList() {
                if (wf_CheckGet(array('id'))) {
                    show_window(__('Card for print'), web_PrintCardLister($_GET['id']));
                }
            }

            // card for print
            function printCards() {
                $cardList = web_GenerateImages($_GET['id']);
                web_CreatePdf($cardList);
            }

            // card for page
            function pageCards() {
                print(web_PageCard($_GET['id']));
                die;
            }

            switch (true) {
                case array_key_exists('action', $_GET) && $_GET['action'] == 'setting':
                    settingTemplate();
                    break;
                case array_key_exists('action', $_GET) && $_GET['action'] == 'list':
                    cardsList();
                    break;
                case array_key_exists('action', $_GET) && $_GET['action'] == 'print':
                    printCards();
                    break;
                case array_key_exists('action', $_GET) && $_GET['action'] == 'page':
                    pageCards();
                    break;
                default:
                    show_error(__('You cant control this action'));
            }
        } else {
            show_error(__('You need install php extension GD'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}

<?php
// check for right of current admin on this module
if (cfr('SELLING')) {
    if (isset($_POST['new_selling']['name'])) {
        $name = $_POST['new_selling']['name'];
        unset($_POST['new_selling']['name']);
        if (!empty($name)) {
            zb_CreateSellingData($name, $_POST['new_selling']);
            rcms_redirect('?module=selling');
        } else {
            show_error(__('Empty selling name'));
        }
    }
    
    if (isset($_GET['action'])) {
        if (isset($_GET['id'])) {
            $sellingId = $_GET['id'];

            if ($_GET['action'] == 'delete') {
                zb_DeleteSellingData($sellingId);
                rcms_redirect('?module=selling');
            }

            if ($_GET['action'] == 'edit') {
                if (isset($_POST['edit_selling'])) {
                    zb_UpdateSellingData($sellingId, $_POST['edit_selling']);
                    rcms_redirect('?module=selling');
                }
                show_window(__('Edit Selling'), web_SellingEditForm($sellingId));
            }
        }
    }
    // create form
    if (!wf_CheckGet(array('action'))) {
        show_window(__('Create new selling'), web_SellingCreateForm());
    }
    //list
    show_window(__('Available selling'), web_SellingLister());
    
} else {
      show_error(__('You cant control this module'));
}


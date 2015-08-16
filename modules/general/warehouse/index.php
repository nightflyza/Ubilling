<?php

$warehouse=new Warehouse();

show_window('', $warehouse->renderPanel());

//categories management
if (wf_CheckGet(array('categories'))) {
    //new category creation
    if (wf_CheckPost(array('newcategory'))) {
        $warehouse->categoriesCreate($_POST['newcategory']);
        rcms_redirect($warehouse::URL_ME.'&'.$warehouse::URL_CATEGORIES);
    }
    //category deletion
    if (wf_CheckGet(array('deletecategory'))) {
        $deletionResult=$warehouse->categoriesDelete($_GET['deletecategory']);
        if ($deletionResult) {
            rcms_redirect($warehouse::URL_ME.'&'.$warehouse::URL_CATEGORIES);
        } else {
            show_error(__('You cant do this'));
        }
    }
    //category editing 
    if (wf_CheckPost(array('editcategoryname','editcategoryid'))) {
        $warehouse->categoriesSave();
        rcms_redirect($warehouse::URL_ME.'&'.$warehouse::URL_CATEGORIES);
    }
    
    show_window(__('Categories'), $warehouse->categoriesAddForm());
    show_window(__('Available categories'), $warehouse->categoriesRenderList());
    show_window('', wf_Link($warehouse::URL_ME, __('Back'), false, 'ubButton'));
}

//item types management
if (wf_CheckGet(array('itemtypes'))) {
    show_window(__('Warehouse item types'), $warehouse->itemtypesCreateForm());
    show_window(__('Available item types'), $warehouse->itemtypesRenderList());
    show_window('', wf_Link($warehouse::URL_ME, __('Back'), false, 'ubButton'));
}

?>
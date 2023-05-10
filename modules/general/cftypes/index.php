<?php

if (cfr('CFTYPES')) {

    $customFields = new CustomFields();

    //type deletion
    if (ubRouting::checkGet($customFields::ROUTE_DELETE)) {
        $customFields->deleteType(ubRouting::get($customFields::ROUTE_DELETE));
        ubRouting::nav($customFields::URL_ME);
    }

    //new type creation
    if (ubRouting::checkPost(array($customFields::PROUTE_NEWTYPE, $customFields::PROUTE_NEWNAME))) {
        $customFields->createType(ubRouting::post($customFields::PROUTE_NEWTYPE), ubRouting::post($customFields::PROUTE_NEWNAME));
        ubRouting::nav($customFields::URL_ME);
    }

    //catch editing form
    if (ubRouting::checkPost($customFields::PROUTE_EDID)) {
        $customFields->saveType();
        ubRouting::nav($customFields::URL_ME . '&' . $customFields::ROUTE_EDIT . '=' . ubRouting::post($customFields::PROUTE_EDID));
    }

    if (ubRouting::checkGet($customFields::ROUTE_EDIT)) {
        //type editing form
        show_window(__('Edit custom field type'), $customFields->renderTypeEditForm(ubRouting::get($customFields::ROUTE_EDIT)));
    } else {
        //rendering existing types list
        show_window(__('Available custom profile field types'), $customFields->renderTypesList());
        show_window(__('Create new field type'), $customFields->renderTypeCreationForm());
    }
} else {
    show_error(__('You cant control this module'));
}



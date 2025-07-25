<?php

if (cfr('KATOTTG')) {
if ($ubillingConfig->getAlterParam('KATOTTG_ENABLED')) {

    $kat=new KATOTTG(true);

    if (ubRouting::checkGet($kat::ROUTE_DELETE)) {
        $kat->deleteKatottgEntity(ubRouting::get($kat::ROUTE_DELETE));
    }

    if (ubRouting::checkGet($kat::ROUTE_EDIT)) {
     //TODO
    }
    
    if (ubRouting::checkGet($kat::ROUTE_CREATE_AUTO) or ubRouting::checkGet($kat::ROUTE_CREATE_MANUAL)) {
        $kat->createKatottgEntity();
    }

    if (ubRouting::checkPost($kat::PROUTE_BIND_KAT) and ubRouting::checkPost($kat::PROUTE_BIND_CITY)) {
        $kat->bindCityToKatottg(ubRouting::post($kat::PROUTE_BIND_KAT),ubRouting::post($kat::PROUTE_BIND_CITY));
    }

    if (ubRouting::checkGet($kat::ROUTE_UNBIND_CITY)) {
        $kat->unbindCityFromKatottg(ubRouting::get($kat::ROUTE_UNBIND_CITY));
    }

   

    
   show_window('',$kat->renderModuleControls()); 

   if (ubRouting::checkGet($kat::ROUTE_CREATE_AUTO)) {
    show_window(__('Create'),$kat->renderCreateFormAuto());
   }

   if (ubRouting::checkGet($kat::ROUTE_CREATE_MANUAL)) {
    show_window(__('Create'),$kat->renderCreateFormManual());
   }

   if (ubRouting::checkGet($kat::ROUTE_LIST)) {
    show_window(__('Available locations').' '.__('KATOTTG'),$kat->renderKatottgList());
    $bindingForm = $kat->renderCityBindingForm();
    if (!empty($bindingForm)) {
        show_window(__('City binding'),$bindingForm);
    }
    show_window(__('City binding list'),$kat->renderCityBindingList());

   }

   if (ubRouting::checkGet($kat::ROUTE_STREET_BIND)) {
    show_window(__('Streets binding'),$kat->renderStreetBindingForm());
}

  
} else {
    show_error(__('This module is disabled'));
}
} else {
    show_error(__('Access denied'));
}
<?php

if (cfr('SWITCHM')) {

    $switchModels = new SwitchModels(); 

    //creatin new model
    if (ubRouting::checkPost($switchModels::PROUTE_NEWNAME)) {
        $modelName = ubRouting::post($switchModels::PROUTE_NEWNAME);
        $modelPorts = ubRouting::post($switchModels::PROUTE_NEWPORTS);
        $modelSnmpTemplate = ubRouting::post($switchModels::PROUTE_NEWSNMPTPL);
        $creationResult = $switchModels->create($modelName, $modelPorts, $modelSnmpTemplate);
        if (empty($creationResult)) {
            ubRouting::nav($switchModels::URL_ME);
        } else {
            show_error(__('Something went wrong') . ': ' . $creationResult);
        }
    }

    //deleting existing model
    if (ubRouting::checkGet($switchModels::ROUTE_DELETE)) {
        $deletionResult = $switchModels->delete(ubRouting::get($switchModels::ROUTE_DELETE));
        if (empty($deletionResult)) {
            ubRouting::nav($switchModels::URL_ME);
        } else {
            show_error(__('Something went wrong') . ': ' . $deletionResult);
        }
    }

    //changing existing model
    if (ubRouting::checkPost($switchModels::PROUTE_EDITNAME) and ubRouting::checkGet($switchModels::ROUTE_EDIT)) {
        $editId = ubRouting::get($switchModels::ROUTE_EDIT, 'int');
        $modelName = ubRouting::post($switchModels::PROUTE_EDITNAME);
        $modelPorts = ubRouting::post($switchModels::PROUTE_EDITPORTS);
        $modelSnmpTemplate = ubRouting::post($switchModels::PROUTE_EDITSNMPTPL);

        $updateResult = $switchModels->update($editId, $modelName, $modelPorts, $modelSnmpTemplate);
        if (empty($updateResult)) {
            ubRouting::nav($switchModels::URL_ME . '&' . $switchModels::ROUTE_EDIT . '=' . $editId);
        } else {
            show_error(__('Something went wrong') . ': ' . $updateResult);
        }
    }

    //show creation form
    if(ubRouting::checkGet($switchModels::ROUTE_CREATE)) {
            $creationForm = $switchModels->renderCreateForm();
            show_window(__('Create') . ' ' . __('Equipment models'), $creationForm);
            show_window('', wf_BackLink($switchModels::URL_ME));
    }

    //show editing form
    if(ubRouting::checkGet($switchModels::ROUTE_EDIT)) {
        //show editing form
        $editId = ubRouting::get($switchModels::ROUTE_EDIT, 'int');
        $modelData = $switchModels->getData($editId);
        $currentModelName = $modelData['modelname'];

        $editForm = $switchModels->renderEditForm($editId);
        show_window(__('Edit') . ' ' . __('Equipment models') . ': ' . $currentModelName, $editForm);
        show_window('', wf_BackLink($switchModels::URL_ME));
    }

    //listing available models
    if (!ubRouting::get($switchModels::ROUTE_EDIT) and !ubRouting::get($switchModels::ROUTE_CREATE)) {
        show_window('', $switchModels->renderNavLinks());
        show_window(__('Equipment models'), $switchModels->renderList());
    }
    


} else {
    show_error(__('Access denied'));
}
<?php

if (cfr('SWITCHM')) {

    $switchModels = new SwitchModels(); 

    //creatin new model
    if (ubRouting::checkPost('newsm')) {
        $modelName = ubRouting::post('newsm');
        $modelPorts = ubRouting::post('newsmp');
        $modelSnmpTemplate = ubRouting::post('newsst');
        $creationResult = $switchModels->create($modelName, $modelPorts, $modelSnmpTemplate);
        if (empty($creationResult)) {
            ubRouting::nav('?module=switchmodels');
        } else {
            show_error(__('Something went wrong') . ': ' . $creationResult);
        }
    }

    //deleting existing model
    if (ubRouting::checkGet('deletesm')) {
        $deletionResult = $switchModels->delete(ubRouting::get('deletesm'));
        if (empty($deletionResult)) {
            ubRouting::nav('?module=switchmodels');
        } else {
            show_error(__('Something went wrong') . ': ' . $deletionResult);
        }
    }

    //changing existing model
    if (ubRouting::checkPost('editmodelname') and ubRouting::checkGet('edit')) {
        $editId = ubRouting::get('edit', 'int');
        $modelName = ubRouting::post('editmodelname');
        $modelPorts = ubRouting::post('editports');
        $modelSnmpTemplate = ubRouting::post('editsnmptemplate');

        $updateResult = $switchModels->update($editId, $modelName, $modelPorts, $modelSnmpTemplate);
        if (empty($updateResult)) {
            ubRouting::nav('?module=switchmodels&edit=' . $editId);
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
    if(ubRouting::checkGet('edit')) {
        //show editing form
        $editId = ubRouting::get('edit', 'int');
        $modelData = $switchModels->getData($editId);
        $currentModelName = $modelData['modelname'];

        $editForm = $switchModels->renderEditForm($editId);
        show_window(__('Edit') . ' ' . __('Equipment models') . ': ' . $currentModelName, $editForm);
        show_window('', wf_BackLink('?module=switchmodels'));
    }

    //listing available models
    if (!ubRouting::get('edit') and !ubRouting::get($switchModels::ROUTE_CREATE)) {
        show_window('', $switchModels->renderNavLinks());
        show_window(__('Equipment models'), $switchModels->renderList());
    }
    


} else {
    show_error(__('Access denied'));
}
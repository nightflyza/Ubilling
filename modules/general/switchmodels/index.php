<?php

if (cfr('SWITCHM')) {
    //creatin new model
    if (ubRouting::checkPost('newsm')) {
        ub_SwitchModelAdd(ubRouting::post('newsm'), ubRouting::post('newsmp'), ubRouting::post('newsst'));
        ubRouting::nav('?module=switchmodels');
    }

    //deleting existing model
    if (ubRouting::checkGet('deletesm')) {
        ub_SwitchModelDelete(ubRouting::get('deletesm'));
        ubRouting::nav('?module=switchmodels');
    }

    //listing available models
    if (!ubRouting::get('edit')) {
        $navlinks = wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create'), web_SwitchModelAddForm(), 'ubButton', '420', '250');
        $navlinks .= wf_Link('?module=switches', wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');

        show_window('', $navlinks);
        show_window(__('Equipment models'), web_SwitchModelsShow());
    } else {
        //show editing form
        $editId = ubRouting::get('edit', 'int');

        //if someone post changes
        if (ubRouting::checkPost('editmodelname')) {
            simple_update_field('switchmodels', 'modelname', ubRouting::post('editmodelname'), "WHERE `id`='" . $editId . "' ");
            if (ubRouting::checkPost('editports')) {
                simple_update_field('switchmodels', 'ports', ubRouting::post('editports'), "WHERE `id`='" . $editId . "' ");
            }

            simple_update_field('switchmodels', 'snmptemplate', ubRouting::post('editsnmptemplate'), "WHERE `id`='" . $editId . "' ");

            log_register('SWITCHMODEL CHANGE [' . $editId . ']');
            ubRouting::nav('?module=switchmodels');
        }

        $modeldata = zb_SwitchModelGetData($editId);
        $allSnmpTemplates = zb_SwitchModelsSnmpTemplatesGetAll();

        $editinputs = wf_TextInput('editmodelname', 'Model', $modeldata['modelname'], true, '20');
        $editinputs .= wf_TextInput('editports', 'Ports', $modeldata['ports'], true, '5');
        $editinputs .= wf_Selector('editsnmptemplate', $allSnmpTemplates, 'SNMP template', $modeldata['snmptemplate']);
        $editinputs .= wf_delimiter();
        $editinputs .= wf_Submit('Save');
        $editform = wf_Form('', 'POST', $editinputs, 'glamour');
        show_window(__('Edit') . ' ' . __('Equipment models') . ': ' . $modeldata['modelname'], $editform);
        show_window('', wf_BackLink('?module=switchmodels'));
    }
} else {
    show_error(__('Access denied'));
}
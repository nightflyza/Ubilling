<?php

if (cfr('SWITCHM')) {
    //creatin new model
    if (wf_CheckPost(array('newsm'))) {
        ub_SwitchModelAdd($_POST['newsm'], $_POST['newsmp'], $_POST['newsst']);
        rcms_redirect("?module=switchmodels");
    }

    //deleting existing model
    if (isset($_GET['deletesm'])) {
        if (!empty($_GET['deletesm'])) {
            ub_SwitchModelDelete($_GET['deletesm']);
            rcms_redirect("?module=switchmodels");
        }
    }
    //listing available models
    if (!isset($_GET['edit'])) {
        $navlinks = wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create'), web_SwitchModelAddForm(), 'ubButton', '420', '250');
        $navlinks.=wf_Link('?module=switches', wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');

        show_window('', $navlinks);
        show_window(__('Available switch models'), web_SwitchModelsShow());
    } else {
        //show editing form
        $editid = vf($_GET['edit'], 3);

        //if someone post changes
        if (wf_CheckPost(array('editmodelname'))) {
            simple_update_field('switchmodels', 'modelname', $_POST['editmodelname'], "WHERE `id`='" . $editid . "' ");
            if (wf_CheckPost(array('editports'))) {
              simple_update_field('switchmodels', 'ports', $_POST['editports'], "WHERE `id`='" . $editid . "' ");
            }
                        
            simple_update_field('switchmodels', 'snmptemplate', $_POST['editsnmptemplate'], "WHERE `id`='" . $editid . "' ");
            
            log_register("SWITCHMODEL CHANGE " . $editid);
            rcms_redirect("?module=switchmodels");
        }

        $modeldata = zb_SwitchModelGetData($editid);
        $allSnmpTemplates = zb_SwitchModelsSnmpTemplatesGetAll();

        $editinputs = wf_TextInput('editmodelname', 'Model', $modeldata['modelname'], true, '20');
        $editinputs.=wf_TextInput('editports', 'Ports', $modeldata['ports'], true, '5');
        $editinputs.=wf_Selector('editsnmptemplate', $allSnmpTemplates, 'SNMP template', $modeldata['snmptemplate']);
        $editinputs.=wf_delimiter();
        $editinputs.=wf_Submit('Save');
        $editform = wf_Form('', 'POST', $editinputs, 'glamour');
        show_window(__('Switch model edit'), $editform);
        show_window('', wf_Link('?module=switchmodels', 'Back', true, 'ubButton'));
    }
} else {
    show_error(__('Access denied'));
}
?>
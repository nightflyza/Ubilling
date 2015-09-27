<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['SWITCH_AUTOCONFIG']) {
    $swLogin = new SwitchLogin();
    if (cfr(SwitchLogin::MODULE)) {
        if (wf_CheckGet(array('ajax'))) {
            if ($_GET['ajax'] == 'snmp') {
                $swLogin->SwLoginAddSnmpForm();
            }
            if ($_GET['ajax'] == 'connect') {
                $swLogin->SwLoginAddConnForm();
            }
            if ($_GET['ajax'] == 'snmp_edit') {
                $swLogin->SwLoginEditSnmpForm($_GET['edit']);
            }
            if ($_GET['ajax'] == 'connect_edit') {
                $swLogin->SwLoginEditConnForm($_GET['edit']);
            }
        }
        if (!isset($_GET['edit'])) {
            $megaForm = wf_AjaxLoader();
            $megaForm.= wf_AjaxLink(SwitchLogin::MODULE_URL . '&ajax=snmp', 'SNMP', 'megaContainer1', false, 'ubButton');
            $megaForm.= wf_AjaxLink(SwitchLogin::MODULE_URL . '&ajax=connect', 'Connect', 'megaContainer1', false, 'ubButton');
            $megaForm.= wf_tag('div', false, '', 'id="megaContainer1"') . wf_tag('div', true);
            show_window(__("Switches login data"), $megaForm);
            $swLogin->ShowSwAllLogin();
        } else {
            $megaEditForm = wf_AjaxLoader();
            $megaEditForm.= wf_AjaxLink(SwitchLogin::MODULE_URL . '&edit=' . $_GET['edit'] . '&ajax=snmp_edit', 'SNMP', 'megaContainer1', false, 'ubButton');
            $megaEditForm.= wf_AjaxLink(SwitchLogin::MODULE_URL . '&edit=' . $_GET['edit'] . '&ajax=connect_edit', 'Connect', 'megaContainer1', false, 'ubButton');
            $megaEditForm.= wf_tag('div', false, '', 'id="megaContainer1"') . wf_tag('div', true);
            show_warning(__("Are you sure that you want to change switch login data") . "?");            
            show_window(__("Switches login data"), $megaEditForm);
            $back = wf_Link(SwitchLogin::MODULE_URL, __('Back'), false, 'ubButton');
            show_window('', $back);
        }
        
        if (isset($_POST['add'])) {
            $params = array('swmodel', 'SwMethod');
            if (wf_CheckPost($params)) {
                $model = $_POST['swmodel'];
                $snmpTemplate = $_POST['snmptemplate'];
                $login = $_POST['SwLogin'];
                $pass = $_POST['SwPass'];
                $method = $_POST['SwMethod'];
                $community = $_POST['RwCommunity'];
                $enable = $_POST['Enable'];
                $swLogin->SwLoginAdd($model, $login, $pass, $method, $community, $enable, $snmpTemplate);
                rcms_redirect(SwitchLogin::MODULE_URL);
            }
        }
        if (isset($_GET['delete'])) {
            $swLogin->SwLoginDelete($_GET['delete']);
            rcms_redirect(SwitchLogin::MODULE_URL);
        }
        if (isset($_POST['edit'])) {
            $swLogin->SwLoginEditQuery($_POST['swmodel'], $_POST['EditSwLogin'], $_POST['EditSwPass'], $_POST['EditConn'], $_POST['EditRwCommunity'], $_POST['EditEnable'], $_POST['snmptemplate'], $_GET['edit']);
            rcms_redirect(SwitchLogin::MODULE_URL);
        }
    } else {
        show_error('You cant control this module');
    }
} else {
    show_error("SWITCH_AUTOCONFIG is disabled");
}
?>

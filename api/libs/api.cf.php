<?php

/**
 * Returns editing controller for CF assigned to user
 * 
 * @param string $login Existing Ubilling user login
 * @param string $type Type of CF to return control
 * @param int    $typeid Type ID for change
 * 
 * @return string
 */
function cf_TypeGetController($login, $type, $typeid) {
    $type = vf($type);
    $typeid = vf($typeid);
    $login = mysql_real_escape_string($login);
    $result = '';
    if ($type == 'VARCHAR') {
        $inputs = wf_HiddenInput('modtype', $typeid);
        $inputs .= wf_HiddenInput('login', $login);
        $inputs .= wf_TextInput('content', '', '', false, 20);
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TRIGGER') {
        $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
        $inputs = wf_HiddenInput('modtype', $typeid);
        $inputs .= wf_HiddenInput('login', $login);
        $inputs .= wf_Selector('content', $triggerOpts, '', '', false);
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TEXT') {
        $inputs = wf_HiddenInput('modtype', $typeid);
        $inputs .= wf_HiddenInput('login', $login);
        $inputs .= wf_TextArea('content', '', '', true, '45x5');
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, '');
    }
    return ($result);
}

/**
 * Returns search controller for CFs assigned to user
 * 
 * @param string $type Type of CF to return control
 * @param int    $typeid Type ID for change
 * 
 * @return string
 */
function cf_TypeGetSearchControl($type, $typeid) {
    $type = vf($type);
    $typeid = vf($typeid);
    $result = '';
    if ($type == 'VARCHAR') {
        $inputs = wf_HiddenInput('cftypeid', $typeid);
        $inputs .= wf_TextInput('cfquery', '', '', false, 20);
        $inputs .= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TRIGGER') {
        $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
        $inputs = wf_HiddenInput('cftypeid', $typeid);
        $inputs .= wf_Selector('cfquery', $triggerOpts, '', '', false);
        $inputs .= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TEXT') {
        $inputs = wf_HiddenInput('cftypeid', $typeid);
        $inputs .= wf_TextInput('cfquery', '', '', false, 20);
        $inputs .= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, '');
    }
    return ($result);
}

/**
 * Sets some CF content to user with override of old value
 * 
 * @param int     $typeid  Existing CF type ID
 * @param string  $login   Existing Ubilling user login
 * @param string  $content Content that will be set for user into CF
 * 
 * @return void
 */
function cf_FieldSet($typeid, $login, $content) {
    $typeid = vf($typeid);
    $login = mysql_real_escape_string($login);
    $content = mysql_real_escape_string($content);
    cf_FieldDelete($login, $typeid);
    $query = "INSERT INTO `cfitems` (`id` ,`typeid` ,`login` ,`content`) VALUES (NULL , '" . $typeid . "', '" . $login . "', '" . $content . "');";
    nr_query($query);
    if (strlen($content) < 20) {
        $logcontent = $content;
    } else {
        $logcontent = substr($content, 0, 20) . '..';
    }

    log_register("CF SET (" . $login . ") TYPE [" . $typeid . "]" . " ON `" . $logcontent . "`");
}

/**
 * Deletes some CF content for user in database
 * 
 * @param string  $login   Existing Ubilling user login
 * @param int     $typeid  Existing CF type ID
 * 
 * @return void
 */
function cf_FieldDelete($login, $typeid) {
    $typeid = vf($typeid);
    $login = mysql_real_escape_string($login);
    $query = "DELETE from `cfitems` WHERE `typeid`='" . $typeid . "' AND `login`='" . $login . "'";
    nr_query($query);
}

/**
 * Gets CF content assigned for user in database
 * 
 * @param string  $login   Existing Ubilling user login
 * @param int     $typeid  Existing CF type ID
 * 
 * @return string
 */
function cf_FieldGet($login, $typeid) {
    $typeid = vf($typeid);
    $login = mysql_real_escape_string($login);
    $result = '';
    $query = "SELECT `content` from `cfitems` WHERE `login`='" . $login . "' AND `typeid`='" . $typeid . "'";
    $content = simple_query($query);
    if (!empty($content)) {
        $result = $content['content'];
    }
    return ($result);
}

/**
 * Gets all available CF fields content assigned with users from database
 * 
 * @return array
 */
function cf_FieldsGetAll() {
    $result = array();
    $query = "SELECT * from `cfitems`";
    $content = simple_queryall($query);
    if (!empty($content)) {
        $result = $content;
    }
    return ($result);
}

/**
 * Shows CF editor controller for some login
 * 
 * @param string $login Existing user login
 * 
 * @return void
 */
function cf_FieldEditor($login) {
    global $billing, $ubillingConfig;
    $alter_conf = $ubillingConfig->getAlter();
    $result = '';
    //edit routine 
    if (isset($_POST['modtype'])) {
        cf_FieldSet($_POST['modtype'], $_POST['login'], $_POST['content']);

        //need to reset user after change?
        if ($alter_conf['RESETONCFCHANGE']) {
            $billing->resetuser($login);
            log_register('RESET User (' . $login . ')');
        }
    }
    $alltypes = cf_TypeGetAll();
    $login = mysql_real_escape_string($login);

    if (!empty($alltypes)) {

        $cells = wf_TableCell(__('Field name'));
        $cells .= wf_TableCell(__('Current value'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($alltypes as $io => $eachtype) {
            $cells = wf_TableCell($eachtype['name']);
            $cells .= wf_TableCell(cf_FieldDisplay($eachtype['type'], cf_FieldGet($login, $eachtype['id'])));
            $cells .= wf_TableCell(cf_TypeGetController($login, $eachtype['type'], $eachtype['id']));
            $rows .= wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0, '');

        show_window(__('Additional profile fields'), $result);
    }
}

/**
 * Deletes all of CF intems in database associated with some login
 * 
 * @param string $login Existing user login
 * 
 * @return void
 */
function cf_FlushAllUserCF($login) {
    $login = mysql_real_escape_string($login);
    $query = "DELETE from `cfitems` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("CF FLUSH (" . $login . ")");
}

<?php

/**
 * Returns all available CF types from database
 * 
 * @return array
 */
function cf_TypeGetAll() {
    $query = "SELECT * from `cftypes`";
    $result = simple_queryall($query);
    return($result);
}

/**
 * Gets CF type data by typeID
 * 
 * @param int $typeid Existing CF type database ID
 * 
 * @return array
 */
function cf_TypeGetData($typeid) {
    $typeid = vf($typeid, 3);
    $query = "SELECT * from `cftypes` WHERE `id`='" . $typeid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Flushes all of assigned to users CFs from database
 * 
 * @param int $cftypeid Existing CF type database ID
 * 
 * @return void
 */
function cf_TypeFlush($cftypeid) {
    $cftypeid = vf($cftypeid);
    $query = "DELETE from `cfitems` WHERE `typeid`='" . $cftypeid . "'";
    nr_query($query);
    log_register("CFTYPE FLUSH [" . $cftypeid . "]");
}

/**
 * Deletes CF type from database by its ID and flushes assigned
 * 
 * @param int $cftypeid Existing CF type database ID
 * 
 * @return void
 */
function cf_TypeDelete($cftypeid) {
    $cftypeid = vf($cftypeid);
    $query = "DELETE from `cftypes` WHERE `id`='" . $cftypeid . "'";
    nr_query($query);
    log_register("CFTYPE DELETE [" . $cftypeid . "]");
    cf_TypeFlush($cftypeid);
}

/**
 * Creates new CF type in database
 * 
 * @param string $newtype Type of the CF (VARCHAR, TRIGGER, TEXT)
 * @param string $newname Name of the custom field for display
 * 
 * @return void
 */
function cf_TypeAdd($newtype, $newname) {
    $newtype = vf($newtype);
    $newname = mysql_real_escape_string($newname);
    if ((!empty($newname)) AND ( !empty($newtype))) {
        $query = "INSERT INTO `cftypes` (`id` ,`type` ,`name`) VALUES (NULL , '" . $newtype . "', '" . $newname . "');";
        nr_query($query);
        log_register("CFTYPE ADD `" . $newtype . "` `" . $newname . "`");
    }
}

/**
 * Returns Custom Field creation form 
 * 
 * @return string
 */
function cf_TypeAddForm() {
    $types = array(
        'VARCHAR' => 'VARCHAR',
        'TRIGGER' => 'TRIGGER',
        'TEXT' => 'TEXT',
    );

    $inputs = wf_Selector('newtype', $types, __('Field type'), '', true);
    $inputs.= wf_TextInput('newname', __('Field name'), '', true, '15');
    $inputs.= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');
    return($form);
}

/**
 * Returns CF type edit form
 * 
 * @param int $typeid Existing CF type ID
 * 
 * @return void
 */
function cf_TypeEditForm($typeid) {
    $typeid = vf($typeid, 3);
    $typedata = cf_TypeGetData($typeid);
    $current_type = $typedata['type'];
    $current_name = $typedata['name'];
    $availtypes = array('VARCHAR' => 'VARCHAR', 'TRIGGER' => 'TRIGGER', 'TEXT' => 'TEXT');

    $editinputs = wf_HiddenInput('editid', $typeid);
    $editinputs.=wf_Selector('edittype', $availtypes, 'Field type', $current_type, true);
    $editinputs.=wf_TextInput('editname', 'Field name', $current_name, true);
    $editinputs.=wf_Submit('Edit');
    $editform = wf_Form('', 'POST', $editinputs, 'glamour');
    show_window(__('Edit custom field type'), $editform);
    show_window('', wf_BackLink('?module=cftypes'));
}

/**
 * Return displayable list of available CF types with some controls
 * 
 * @return string
 */
function cf_TypesShow() {
    //construct editor
    $titles = array(
        'ID',
        'Field type',
        'Field name'
    );
    $keys = array(
        'id',
        'type',
        'name'
    );
    $alldata = cf_TypeGetAll();
    $module = 'cftypes';
    //show it
    $result = web_GridEditor($titles, $keys, $alldata, $module, true, true);
    return($result);
}

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
        $inputs.= wf_HiddenInput('login', $login);
        $inputs.= wf_TextInput('content', '', '', false, 20);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TRIGGER') {
        $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
        $inputs = wf_HiddenInput('modtype', $typeid);
        $inputs.= wf_HiddenInput('login', $login);
        $inputs.= wf_Selector('content', $triggerOpts, '', '', false);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TEXT') {
        $inputs = wf_HiddenInput('modtype', $typeid);
        $inputs.= wf_HiddenInput('login', $login);
        $inputs.= wf_TextArea('content', '', '', true, '25x5');
        $inputs.= wf_Submit(__('Save'));
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
        $inputs.= wf_TextInput('cfquery', '', '', false, 20);
        $inputs.= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TRIGGER') {
        $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
        $inputs = wf_HiddenInput('cftypeid', $typeid);
        $inputs.= wf_Selector('cfquery', $triggerOpts, '', '', false);
        $inputs.= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, '');
    }

    if ($type == 'TEXT') {
        $inputs = wf_HiddenInput('cftypeid', $typeid);
        $inputs.= wf_TextInput('cfquery', '', '', false, 20);
        $inputs.= wf_Submit(__('Search'));
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
 * Returns preformatted view of CF content preprocessed by its type
 * 
 * @param string $type Type of the data (VARCHAR, TRIGGER,TEXT)
 * @param string $data Data of CF
 * 
 * @return string
 */
function cf_FieldDisplay($type, $data) {
    if ($type == 'TRIGGER') {
        $data = web_bool_led($data);
    }
    if ($type == 'TEXT') {
        $data = nl2br($data);
    }
    return ($data);
}

/**
 * Shows CF editor controller for some login
 * 
 * @param string $login Existing user login
 * 
 * @return void
 */
function cf_FieldEditor($login) {
    global $billing,$ubillingConfig;
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
        $cells.= wf_TableCell(__('Current value'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($alltypes as $io => $eachtype) {
            $cells = wf_TableCell($eachtype['name']);
            $cells.= wf_TableCell(cf_FieldDisplay($eachtype['type'], cf_FieldGet($login, $eachtype['id'])));
            $cells.= wf_TableCell(cf_TypeGetController($login, $eachtype['type'], $eachtype['id']));
            $rows.= wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0, '');

        show_window(__('Additional profile fields'), $result);
    }
}

/**
 * Returns CFs listing for some login
 * 
 * @param string $login Existing user login
 * 
 * @return string
 */
function cf_FieldShower($login) {
    $alltypes = cf_TypeGetAll();
    $login = mysql_real_escape_string($login);
    $result = '';
    if (!empty($alltypes)) {
        $rows = '';
        foreach ($alltypes as $io => $eachtype) {

            $cells = wf_TableCell($eachtype['name'], '30%', 'row2');
            $cells.= wf_TableCell(cf_FieldDisplay($eachtype['type'], cf_FieldGet($login, $eachtype['id'])), '', 'row3');
            $rows.= wf_TableRow($cells);
        }

        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return($result);
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

?>

<?php

if ($system->checkForRight('SQLCONSOLE')) {

    $alterconf = $ubillingConfig->getAlter();

    /**
     * Returns PHP code template by key name
     * 
     * @param string $templatekey
     * @return array
     */
    function zb_PhpConsoleGetTemplate($templatekey) {
        $templatedata = zb_StorageGet($templatekey);
        $result = unserialize($templatedata);
        return ($result);
    }

    /**
     * Creates new PHP code template
     * 
     * @param string $name
     * @param string $body
     * 
     * @return void
     */
    function zb_PhpConsoleCreateTemplate($name, $body) {
        $key = 'PHPCONSOLETEMPLATE:' . zb_rand_string(16);
        $newtemplatedata = array();
        $newtemplatedata['name'] = $name;
        $newtemplatedata['body'] = $body;
        $value = serialize($newtemplatedata);
        zb_StorageSet($key, $value);
    }

    /**
     * Renders code template creation form
     * 
     * @return string
     */
    function web_PhpConsoleTemplateCreateForm() {
        $inputs = wf_TextInput('newtemplatename', __('Template name'), '', true, "30");
        $inputs.= wf_TextArea('newtemplatebody', '', '', true, '80x10');
        $inputs.=wf_Submit('Create template');
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result.=wf_Link("?module=sqlconsole&devconsole=true", 'Back', true, 'ubButton');
        return ($result);
    }

    /**
     * Renders code template editing form
     * 
     * @param string $templatekey
     * 
     * @return string
     */
    function web_PhpConsoleTemplateEditForm($templatekey) {
        $rawtemplate = zb_PhpConsoleGetTemplate($templatekey);
        $templatename = $rawtemplate['name'];
        $templatebody = $rawtemplate['body'];
        $inputs = wf_TextInput('edittemplatename', __('Template name'), $templatename, true, "30");
        $inputs.= wf_TextArea('edittemplatebody', '', $templatebody, true, '80x10');
        $inputs.=wf_Submit('Edit');
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result.=wf_Link("?module=sqlconsole&devconsole=true", 'Back', true, 'ubButton');
        return ($result);
    }

    /**
     * Returns list of available PHP code templates
     * 
     * @return string
     */
    function web_PhpConsoleShowTemplates() {
        $alltemplatekeys = zb_StorageFindKeys('PHPCONSOLETEMPLATE:');

        $tablecells = wf_TableCell(__('Template'), '80%');
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($alltemplatekeys)) {
            foreach ($alltemplatekeys as $eachtemplatekey) {
                $templatearray = zb_PhpConsoleGetTemplate($eachtemplatekey['key']);
                $templatename = $templatearray['name'];
                $templatebody = $templatearray['body'];
                //show code template
                $runlink = wf_JSAlert('?module=sqlconsole&devconsole=true&runtpl=' . $eachtemplatekey['key'], $templatename, 'Insert this template into PHP console');
                $tablecells = wf_TableCell($runlink);
                $actionlinks = wf_JSAlert('?module=sqlconsole&devconsole=true&deltemplate=' . $eachtemplatekey['key'], web_delete_icon(), 'Are you serious');
                $actionlinks.=wf_Link('?module=sqlconsole&devconsole=true&edittemplate=' . $eachtemplatekey['key'], web_edit_icon());
                $tablecells.=wf_TableCell($actionlinks);
                $tablerows.=wf_TableRow($tablecells, 'row3');
            }
        }


        $createlink = __('Available code templates') . ' ' . wf_Link("?module=sqlconsole&devconsole=true&templateadd=true", wf_img("skins/icon_add.gif", __('Create')), false);
        $result = $createlink . ' ' . wf_TableBody($tablerows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Code templates management
     */
    
    
    // creating template 
    if (wf_CheckPost(array('newtemplatename', 'newtemplatebody'))) {
        zb_PhpConsoleCreateTemplate($_POST['newtemplatename'], $_POST['newtemplatebody']);
        log_register("DEVCONSOLE TEMPLATE CREATE");
        rcms_redirect("?module=sqlconsole&devconsole=true");
    }

    // deleting template 
    if (wf_CheckGet(array('deltemplate'))) {
        zb_StorageDelete($_GET['deltemplate']);
        log_register("DEVCONSOLE TEMPLATE DELETE");
        rcms_redirect("?module=sqlconsole&devconsole=true");
    }

    // editing template
    if (wf_CheckPost(array('edittemplatename', 'edittemplatebody'))) {
        zb_StorageDelete($_GET['edittemplate']);
        zb_PhpConsoleCreateTemplate($_POST['edittemplatename'], $_POST['edittemplatebody']);
        log_register("DEVCONSOLE TEMPLATE EDIT");
        rcms_redirect("?module=sqlconsole&devconsole=true");
    }


//construct query forms
    $sqlinputs = wf_Link("?module=sqlconsole", 'SQL Console', false, 'ubButton');
    $sqlinputs.=wf_Link("?module=sqlconsole&devconsole=true", 'PHP Console', false, 'ubButton');
    if (cfr('ROOT')) {
        $sqlinputs.=wf_Link("?module=migration", __('Migration'), false, 'ubButton');
        $sqlinputs.=wf_Link("?module=migration2", __('Migration') . ' 2', false, 'ubButton');
        if (cfr('MIKMIGR')) {
            $sqlinputs.=wf_Link("?module=mikbill_migration", __('Migration') . ' mikbill', true, 'ubButton');
        }
    }
    if (wf_CheckPost(array('sqlq'))) {
        if ($alterconf['DEVCON_SQL_KEEP']) {
            $startQuery = trim($_POST['sqlq']);
        } else {
            $startQuery = '';
        }
    } else {
        $startQuery = '';
    }
    $sqlinputs.=wf_TextArea('sqlq', '', $startQuery, true, '80x10');
    $sqlinputs.=wf_CheckInput('tableresult', 'Display query result as table', true, false);
    $sqlinputs.=wf_Submit('Process query');
    $sqlform = wf_Form('', 'POST', $sqlinputs, 'glamour');

    $phpinputs = wf_Link("?module=sqlconsole", 'SQL Console', false, 'ubButton');
    $phpinputs.=wf_Link("?module=sqlconsole&devconsole=true", 'PHP Console', false, 'ubButton');
    if (cfr('ROOT')) {
        $phpinputs.=wf_Link("?module=migration", 'Migration', false, 'ubButton');
        $phpinputs.=wf_Link("?module=migration2", __('Migration') . ' 2', false, 'ubButton');
    }
    if (cfr('MIKMIGR')) {
        $sqlinputs.=wf_Link("?module=mikbill_migration", __('Migration') . ' mikbill', true, 'ubButton');
    }
//is template run or clear area?
    if (wf_CheckGet(array('runtpl'))) {
        $rawtemplate = zb_PhpConsoleGetTemplate($_GET['runtpl']);
        $runcode = $rawtemplate['body'];
    } else {
        $runcode = '';
        if ($alterconf['DEVCON_SQL_KEEP']) {
            if (wf_CheckPost(array('phpq'))) {
                $runcode = $_POST['phpq'];
            }
        } else {
            $runcode = '';
        }
    }
    $phpinputs.=wf_TextArea('phpq', '', $runcode, true, '80x10');
    $phpinputs.=wf_CheckInput('phphightlight', 'Hightlight this PHP code', true, true);
    $phpinputs.=wf_Submit('Run this code inside framework');
    $phpform = wf_Form('?module=sqlconsole&devconsole=true', 'POST', $phpinputs, 'glamour');

//php console grid assemble
    $phpcells = wf_TableCell($phpform, '50%', '', 'valign="top"');
    if (wf_CheckGet(array('templateadd'))) {
        //show template creation form
        $phpcells.= wf_TableCell(web_PhpConsoleTemplateCreateForm(), '50%', '', 'valign="top"');
    } else {
        if (wf_CheckGet(array('edittemplate'))) {
            //show template edit form
            $phpcells.=wf_TableCell(web_PhpConsoleTemplateEditForm($_GET['edittemplate']), '50%', '', 'valign="top"');
        } else {
            //show template list
            $phpcells.= wf_TableCell(web_PhpConsoleShowTemplates(), '50%', '', 'valign="top"');
        }
    }

    $phprows = wf_TableRow($phpcells);
    $phpgrid = wf_TableBody($phprows, '100%', '0', '');

//show needed form
    if (!isset($_GET['devconsole'])) {
        show_window(__('SQL Console'), $sqlform);
    } else {
        show_window(__('Developer Console'), $phpgrid);
    }

// SQL console processing
    if (isset($_POST['sqlq'])) {
        $newquery = trim($_POST['sqlq']);

        if (!empty($newquery)) {
            $stripquery = substr($newquery, 0, 70) . '..';
            log_register('SQLCONSOLE ' . $stripquery);
            ob_start();

            // commented due Den1xxx patch
            // $query_result=simple_queryall($newquery);
            $queried = mysql_query($newquery);
            if ($queried === false) {
                ob_end_clean();
                return show_window('SQL ' . __('Result'), wf_tag('b') . __('Wrong query') . ':' . wf_tag('b', true) . wf_delimiter() . $newquery);
            } else {
                while (@$row = mysql_fetch_assoc($queried)) {
                    $query_result[] = $row;
                }

                $sqlDebugData = ob_get_contents();
                ob_end_clean();
                log_register('SQLCONSOLE QUERYDONE');
                if ($alterconf['DEVCON_VERBOSE_DEBUG']) {
                    show_window(__('Console debug data'), $sqlDebugData);
                }
            } //end of wrong query exeption patch
            if (!empty($query_result)) {
                if (!isset($_POST['tableresult'])) {
                    //raw array result
                    $vdump = var_export($query_result, true);
                } else {
                    //show query result as table
                    $tablerows = '';
                    foreach ($query_result as $eachresult) {
                        $tablecells = wf_TableCell('');
                        $tablecells.=wf_TableCell('');
                        $tablerows.=wf_TableRow($tablecells, 'row1');
                        foreach ($eachresult as $io => $key) {
                            $tablecells = wf_TableCell($io);
                            $tablecells.=wf_TableCell($key);
                            $tablerows.=wf_TableRow($tablecells, 'row3');
                        }
                    }
                    $vdump = wf_TableBody($tablerows, '100%', '0', '');
                }
            } else {
                $vdump = __('Query returned empty result');
            }
        } else {
            $vdump = __('Empty query');
        }

        show_window(__('Result'), '<pre>' . $vdump . '</pre>');
    }


//PHP console processing
    if (isset($_POST['phpq'])) {
        $phpcode = trim($_POST['phpq']);
        if (!empty($phpcode)) {
            //show our code for debug
            if (isset($_POST['phphightlight'])) {
                show_window(__('Running this'), highlight_string('<?php' . "\n" . $phpcode . "\n" . '?>', true));
            }
            //executing it
            $stripcode = substr($phpcode, 0, 70) . '..';
            log_register('DEVCONSOLE ' . $stripcode);
            ob_start();
            eval($phpcode);
            $debugData = ob_get_contents();
            ob_end_clean();
            if ($alterconf['DEVCON_VERBOSE_DEBUG']) {
                show_window(__('Console debug data'), wf_tag('pre') . $debugData) . wf_tag('pre', true);
            }
            log_register('DEVCONSOLE DONE');
        } else {
            show_window(__('Result'), __('Empty code part received'));
        }
    }
} else {
    show_error(__('Access denied'));
}
?>
<?php

if ($system->checkForRight('SQLCONSOLE')) {

    $alterconf = $ubillingConfig->getAlter();
    $punchScriptsAvail = zb_CheckTableExists('punchscripts');
    if ($punchScriptsAvail) {
        $onePunch = new OnePunch();

        //new script creation
        if (wf_CheckPost(array('newscriptalias', 'newscriptname', 'newscriptcontent'))) {
            $punchCreateResult = $onePunch->createScript($_POST['newscriptalias'], $_POST['newscriptname'], $_POST['newscriptcontent']);
            if (!empty($punchCreateResult)) {
                show_error($punchCreateResult);
            } else {
                rcms_redirect($onePunch::URL_DEVCON);
            }
        }

        //existing script deletion
        if (wf_CheckGet(array('delscript'))) {
            $punchDeleteResult = $onePunch->deleteScript($_GET['delscript']);
            if (!empty($punchDeleteResult)) {
                show_error($punchDeleteResult);
            } else {
                rcms_redirect($onePunch::URL_DEVCON);
            }
        }

        //editing existing script
        if (wf_CheckPost(array('editscriptid', 'editscriptoldalias', 'editscriptname', 'editscriptalias', 'editscriptcontent'))) {
            $onePunch->saveScript();
            rcms_redirect($onePunch::URL_DEVCON . '&editscript=' . $_POST['editscriptalias']);
        }

        //migrating old code templates from storage
        if (wf_CheckGet(array('importoldcodetemplates'))) {
            $onePunch->importOldTemplates();
            rcms_redirect($onePunch::URL_DEVCON);
        }
    }

    //module controls
    $devConControls = '';
    $devConControls .= wf_Link("?module=sqlconsole", wf_img('skins/icon_restoredb.png') . ' ' . __('SQL Console'), false, 'ubButton');
    $devConControls .= wf_Link("?module=sqlconsole&devconsole=true", wf_img('skins/icon_php.png') . ' ' . __('PHP Console'), false, 'ubButton');
    $migrationControls = '';
    if (cfr('ROOT')) {
        $migrationControls .= wf_Link("?module=migration", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration'), false, 'ubButton');
        $migrationControls .= wf_Link("?module=migration2", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration') . ' 2', false, 'ubButton');
        $migrationControls .= wf_Link("?module=migration2_exten", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration live (occupancy & tags)'), false, 'ubButton');
        $migrationControls .= wf_Link("?module=migration2_ukv", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration') . ' 2 UKV', false, 'ubButton');
    }
    if (cfr('MIKMIGR')) {
        $migrationControls .= wf_Link("?module=mikbill_migration", wf_img('skins/ukv/dollar.png') . ' ' . __('Migration') . ' MikBiLL', false, 'ubButton');
    }

    $devConControls .= wf_modalAuto(wf_img('skins/icon_puzzle.png') . ' ' . __('Migration'), __('Migration'), $migrationControls, 'ubButton');
    $devConControls .= wf_tag('br');

//construct query forms
    $sqlinputs = $devConControls;
    if (wf_CheckPost(array('sqlq'))) {
        if ($alterconf['DEVCON_SQL_KEEP']) {
            $startQuery = trim($_POST['sqlq']);
        } else {
            $startQuery = '';
        }
    } else {
        $startQuery = '';
    }
    $sqlinputs .= wf_TextArea('sqlq', '', $startQuery, true, '80x10');
    $sqlinputs .= wf_CheckInput('tableresult', 'Display query result as table', true, false);
    $sqlinputs .= wf_CheckInput('truetableresult', 'Display query result as table with fields', true, false);
    $sqlinputs .= wf_Submit('Process query');
    $sqlform = wf_Form('', 'POST', $sqlinputs, 'glamour');

    $phpinputs = $devConControls;

//is template run or clear area?
    if (wf_CheckGet(array('runscript'))) {
        if ($punchScriptsAvail) {
            $runcode = $onePunch->getScriptContent($_GET['runscript']);
        } else {
            $runcode = '';
        }
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
    $phpinputs .= wf_TextArea('phpq', '', $runcode, true, '80x10');
    $phpinputs .= wf_CheckInput('phphightlight', 'Hightlight this PHP code', true, true);
    $phpinputs .= wf_Submit('Run this code inside framework');
    $phpform = wf_Form('?module=sqlconsole&devconsole=true', 'POST', $phpinputs, 'glamour');

//php console grid assemble
    $phpcells = wf_TableCell($phpform, '50%', '', 'valign="top"');
    if (wf_CheckGet(array('scriptadd'))) {

        //show script creation form
        if ($punchScriptsAvail) {
            $punchCreateForm = $onePunch->renderCreateForm();
        } else {
            $punchCreateForm = '';
        }
        //override devconsole forms with script creation interface
        $phpcells = wf_TableCell($punchCreateForm, '100%', '', 'valign="top"');
    } else {
        if (wf_CheckGet(array('editscript'))) {
            //show scripts edit form
            if ($punchScriptsAvail) {
                $punchEditForm = $onePunch->renderEditForm($_GET['editscript']);
            } else {
                $punchEditForm = '';
            }
            //override devconsole forms with script editing interface
            $phpcells = wf_TableCell($punchEditForm, '100%', '', 'valign="top"');
        } else {
            //show scripts list
            if ($punchScriptsAvail) {
                $punchScriptsList = $onePunch->renderScriptsList();
                $punchScriptsList .= wf_tag('br');
                $punchScriptsList .= wf_Link($onePunch::URL_DEVCON . '&scriptadd=true', web_icon_create() . ' ' . __('Create') . ' ' . __('One-Punch') . ' ' . __('Script'), true, 'ubButton');
            } else {
                $punchScriptsList = '';
            }

            $phpcells .= wf_TableCell($punchScriptsList, '50%', '', 'valign="top"');
        }
    }

    $phprows = wf_TableRow($phpcells);
    $phpgrid = wf_TableBody($phprows, '100%', '0', '');

//show needed form
    if (!isset($_GET['devconsole'])) {
        show_window(__('SQL Console'), $sqlform);
    } else {
        $devConWindowTitle = __('Developer Console');
        if (ubRouting::checkGet('editscript')) {
            $devConWindowTitle .= ': ' . __('Edit') . ' ' . __('One-Punch') . ' ' . __('Script');
        }

        if (ubRouting::checkGet('scriptadd')) {
            $devConWindowTitle .= ': ' . __('Create') . ' ' . __('One-Punch') . ' ' . __('Script');
        }
        show_window($devConWindowTitle, $phpgrid);
    }

// SQL console processing
    if (isset($_POST['sqlq'])) {
        $newquery = trim($_POST['sqlq']);
        $recCount = 0; //preventing notices on empty queries
        $vdump = ''; //used for storing query executing result
        $query_result = array(); //executed query result shall to be there
        if (!empty($newquery)) {
            $stripquery = substr($newquery, 0, 70) . '..';
            log_register('SQLCONSOLE ' . $stripquery);
            ob_start();

            if (!extension_loaded('mysql')) {
                $queried = mysqli_query($loginDB, $newquery);
            } else {
                $queried = mysql_query($newquery);
            }
            if ($queried === false) {
                ob_end_clean();
                return(show_error(wf_tag('b') . __('Wrong query') . ': ' . wf_tag('b', true) . $newquery));
            } else {
                if (!extension_loaded('mysql')) {
                    while (@$row = mysqli_fetch_assoc($queried)) {
                        $query_result[] = $row;
                    }
                } else {
                    while (@$row = mysql_fetch_assoc($queried)) {
                        $query_result[] = $row;
                    }
                }

                $sqlDebugData = ob_get_contents();
                ob_end_clean();
                log_register('SQLCONSOLE QUERYDONE');
                if ($alterconf['DEVCON_VERBOSE_DEBUG']) {
                    show_window(__('Console debug data'), $sqlDebugData);
                }
            } //end of wrong query exeption patch
            if (!empty($query_result)) {
                $recCount = count($query_result);

                if (!isset($_POST['tableresult']) and ! isset($_POST['truetableresult'])) {
                    //raw array result
                    $vdump = var_export($query_result, true);
                } elseif (isset($_POST['truetableresult'])) {
                    //show query result as table with fields
                    $tablecells = '';
                    $tablerows = '';
                    $fieldNames = array_keys($query_result[0]);

                    if (!empty($fieldNames)) {
                        $fieldsCnt = count($fieldNames);

                        foreach ($fieldNames as $fieldName) {
                            $tablecells .= wf_TableCell($fieldName);
                        }
                        $tablerows .= $tablecells;
                        $tablecells = '';

                        foreach ($query_result as $eachresult) {
                            for ($k = 0; $k < $fieldsCnt; $k++) {
                                $tablecells .= wf_TableCell('');
                            }
                            $tablerows .= wf_TableRow($tablecells, 'row1');
                            $tablecells = '';

                            foreach ($eachresult as $io => $key) {
                                $tablecells .= wf_TableCell($key);
                            }
                            $tablerows .= wf_TableRow($tablecells, 'row3');
                            $tablecells = '';
                        }
                    }

                    $vdump = wf_TableBody($tablerows, '100%', '0', '');
                } else {
                    //show query result as table
                    $tablerows = '';
                    foreach ($query_result as $eachresult) {
                        $tablecells = wf_TableCell('');
                        $tablecells .= wf_TableCell('');
                        $tablerows .= wf_TableRow($tablecells, 'row1');
                        foreach ($eachresult as $io => $key) {
                            $tablecells = wf_TableCell($io);
                            $tablecells .= wf_TableCell($key);
                            $tablerows .= wf_TableRow($tablecells, 'row3');
                        }
                    }
                    $vdump = wf_TableBody($tablerows, '100%', '0', '');
                }
            }
        }

        show_window(__('Result'), wf_tag('pre') . $vdump . wf_tag('pre', 'true'));
        //rendering query status here
        if (empty($newquery)) {
            show_warning(__('Empty query'));
        } else {
            if ($queried !== false) {
                show_info(__('SQL Query') . ': ' . $newquery);
            }

            if (empty($query_result)) {
                show_warning(__('Query returned empty result'));
            } else {
                show_success(__('Returned records count') . ': ' . $recCount);
            }
        }
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
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
?>
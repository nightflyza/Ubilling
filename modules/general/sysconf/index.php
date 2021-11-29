<?php

if (cfr('SYSCONF')) {

    /**
     * Renders list with some controls of available editable config presets
     * 
     * @param array $editableConfigs
     * 
     * @return string
     */
    function web_RenderEditableConfigPresetsForm($editableConfigs) {
        $result = '';
        $messages = new UbillingMessageHelper();
        if (!empty($editableConfigs)) {
            $cells = wf_TableCell(__('Path'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($editableConfigs as $eachPath => $eachName) {
                $cells = wf_TableCell($eachPath);
                $cells .= wf_TableCell($eachName);
                $actLinks = wf_JSAlert('?module=sysconf&delconfpath=' . base64_encode($eachPath), web_delete_icon(), $messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, '');
        }

        $inputs = wf_TextInput('newconfpath', __('Path'), '', false, 10) . ' ';
        $inputs .= wf_TextInput('newconfname', __('Name'), '', false, 10) . ' ';
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    //getting some editable configs presets
    $editableConfigsPresetsPath = DATA_PATH . '/documents/editableconfigs/settings.dat';
    if (file_exists($editableConfigsPresetsPath)) {
        //loading presets
        $editableConfigs = file_get_contents($editableConfigsPresetsPath);
        $editableConfigs = json_decode($editableConfigs, true);
    } else {
        //creating new default presets
        $editableConfigs = array(
            CONFIG_PATH . 'alter.ini' => 'alter.ini',
            CONFIG_PATH . 'mysql.ini' => 'mysql.ini',
            CONFIG_PATH . 'billing.ini' => 'billing.ini',
            CONFIG_PATH . 'ymaps.ini' => 'ymaps.ini',
            CONFIG_PATH . 'config.ini' => 'config.ini',
        );
        file_put_contents($editableConfigsPresetsPath, json_encode($editableConfigs));
    }

    //deleting presets if required
    if (wf_CheckGet(array('delconfpath'))) {
        $pathToDelete = base64_decode($_GET['delconfpath']);
        if (isset($editableConfigs[$pathToDelete])) {
            unset($editableConfigs[$pathToDelete]);
            file_put_contents($editableConfigsPresetsPath, json_encode($editableConfigs));
            log_register('SYSCONF DELETE PRESET `' . $pathToDelete . '`');
            rcms_redirect('?module=sysconf');
        }
    }

    //creating some new presets
    if (wf_CheckPost(array('newconfpath', 'newconfname'))) {
        $createConfPath = $_POST['newconfpath'];
        $createConfName = $_POST['newconfname'];
        if (!isset($editableConfigs[$createConfPath])) {
            if (file_exists($createConfPath)) {
                $editableConfigs[$createConfPath] = $createConfName;
                file_put_contents($editableConfigsPresetsPath, json_encode($editableConfigs));
                log_register('SYSCONF CREATE PRESET `' . $createConfPath . '`');
                rcms_redirect('?module=sysconf');
            } else {
                show_error(__('File not exist') . ': ' . $createConfPath);
            }
        }
    }

    $configsList = '';
    if (!empty($editableConfigs)) {
        foreach ($editableConfigs as $eachConfigPath => $eachConfigName) {
            $configsList .= wf_Link('?module=sysconf&editconfig=' . base64_encode($eachConfigPath), web_edit_icon() . ' ' . $eachConfigName, false, 'ubButton') . ' ';
        }
    }

    //appending presets controls
    $configsList .= wf_modalAuto(web_icon_extended() . ' ' . __('Settings'), __('Settings'), web_RenderEditableConfigPresetsForm($editableConfigs), 'ubButton');
    //appending crontab editor link
    $crontabEditor = wf_Link(CrontabEditor::URL_ME, wf_img('skins/clock.png') . ' ' . __('Crontab editor'), true, 'ubButton');
    show_window(__('Edit'), $configsList . $crontabEditor);

    if (wf_CheckGet(array('editconfig'))) {
        $editingConfigPath = base64_decode($_GET['editconfig']);
        if (file_exists($editingConfigPath)) {
            if (wf_CheckPost(array('editfilepath', 'editfilecontent'))) {
                $changedFilePath = $_POST['editfilepath'];
                if (file_exists($changedFilePath)) {
                    $canUpdate = false;
                    if (!is_writable($changedFilePath)) {
                        show_error(__('File is not writable') . ': ' . $changedFilePath);
                        show_warning(__('Trying to set write permissions for') . ' ' . $changedFilePath . ' ' . __('to fix this issue'));
                        zb_fixAccessRights($changedFilePath);
                        if (is_writable($changedFilePath)) {
                            $canUpdate = true;
                            show_success(__('Success! Config file') . ' ' . $changedFilePath . ' ' . __('now is writable'));
                        } else {
                            $canUpdate = false;
                            show_error(__('Seems like we failed with making this file writable'));
                        }
                    } else {
                        $canUpdate = true;
                    }
                    //saving results into file
                    if ($canUpdate) {
                        $newFileContent = $_POST['editfilecontent'];
                        $newFileContent = str_replace("\r\n", PHP_EOL, $newFileContent); // setting unix-line EOL.
                        file_put_contents($changedFilePath, $newFileContent);
                        log_register('SYSCONF UPDATE FILE `' . $changedFilePath . '`');
                    } else {
                        log_register('SYSCONF UPDATE FAIL `' . $changedFilePath . '`');
                    }
                }
            }
            $editingConfigContent = file_get_contents($editingConfigPath);
            show_window(__('Change') . ' ' . basename($editingConfigPath), web_FileEditorForm($editingConfigPath, $editingConfigContent));
        } else {
            show_error(__('File not exist') . ': ' . $editingConfigPath);
        }
        show_window('', wf_BackLink('?module=sysconf'));
    } else {
        $alterconf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
        $alteropts = rcms_parse_ini_file(CONFIG_PATH . 'optsaltcfg');

        $dbconf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
        $dbopts = rcms_parse_ini_file(CONFIG_PATH . 'optsdbcfg');

        $billingconf = rcms_parse_ini_file(CONFIG_PATH . 'billing.ini');
        $billopts = rcms_parse_ini_file(CONFIG_PATH . 'optsbillcfg');

        $catvconf = rcms_parse_ini_file(CONFIG_PATH . 'catv.ini');
        $catvopts = rcms_parse_ini_file(CONFIG_PATH . 'optscatvcfg');

        $ymconf = rcms_parse_ini_file(CONFIG_PATH . 'ymaps.ini');
        $ymopts = rcms_parse_ini_file(CONFIG_PATH . 'optsymcfg');

        $photoconf = rcms_parse_ini_file(CONFIG_PATH . 'photostorage.ini');
        $photoopts = rcms_parse_ini_file(CONFIG_PATH . 'optsphotocfg');

        if ($alterconf['PASSWORDSHIDE']) {
            $hide_passwords = true;
        } else {
            $hide_passwords = false;
        }

        $configOptionsMissed = '';

        $dbcell = web_ConfigEditorShow('mysqlini', $dbconf, $dbopts);
        $billcell = web_ConfigEditorShow('billingini', $billingconf, $billopts);
        $altercell = web_ConfigEditorShow('alterini', $alterconf, $alteropts);
        $catvcell = web_ConfigEditorShow('catvini', $catvconf, $catvopts);
        $ymcells = web_ConfigEditorShow('ymaps', $ymconf, $ymopts);
        $photocells = web_ConfigEditorShow('photostorage', $photoconf, $photoopts);

        $grid = wf_tag('script');
        $grid .= '$(function() {
    $( "#tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
    $( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
  });';
        $grid .= wf_tag('script', true);
        $grid .= wf_tag('style');
        $grid .= file_get_contents('skins/tabs_v.css');
        $grid .= wf_tag('style', true);
        $grid .= wf_tag('div', false, '', 'id="tabs"');
        $grid .= wf_tag('ul');
        $grid .= web_ConfigGetTabsControls($dbopts) . web_ConfigGetTabsControls($billopts) . web_ConfigGetTabsControls($alteropts);
        $grid .= web_ConfigGetTabsControls($catvopts) . web_ConfigGetTabsControls($ymopts) . web_ConfigGetTabsControls($photoopts);
        $grid .= wf_tag('ul', true);
        $grid .= $dbcell . $billcell . $catvcell . $ymcells . $photocells . $altercell;
        $grid .= wf_tag('div', true) . wf_CleanDiv();

        if (!empty($configOptionsMissed)) {
            show_window('', $configOptionsMissed);
        }
        show_window(__('System settings'), $grid);
    }
} else {
    show_error(__('You cant control this module'));
}
?>

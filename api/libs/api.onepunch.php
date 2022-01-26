<?php

/**
 * Allows you to be an Saitama!
 */
class OnePunch {

    /**
     * Contains available punch scripts as alias=>data
     *
     * @var array
     */
    protected $punchScripts = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Placeholder for ONEPUNCH_DEFAULT_SORT_FIELD
     *
     * @var string
     */
    protected $defaultSortField = '';

    /**
     * System config object placeholder
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Contains default devconsole URL
     */
    const URL_DEVCON = '?module=sqlconsole&devconsole=true';

//     ⠀⠀⠀⣠⣶⡾⠏⠉⠙⠳⢦⡀⠀⠀⠀⢠⠞⠉⠙⠲⡀⠀
//    ⠀⠀⠀⣴⠿⠏⠀⠀⠀⠀⠀⠀⢳⡀⠀⡏⠀⠀⠀⠀⠀⢷
//    ⠀⠀⢠⣟⣋⡀⢀⣀⣀⡀⠀⣀⡀⣧⠀⢸⠀⠀⠀⠀⠀ ⡇
//    ⠀⠀⢸⣯⡭⠁⠸⣛⣟⠆⡴⣻⡲⣿⠀⣸⠀⠀OK⠀ ⡇
//    ⠀⠀⣟⣿⡭⠀⠀⠀⠀⠀⢱⠀⠀⣿⠀⢹⠀⠀⠀⠀⠀ ⡇
//    ⠀⠀⠙⢿⣯⠄⠀⠀⠀⢀⡀⠀⠀⡿⠀⠀⡇⠀⠀⠀⠀⡼
//    ⠀⠀⠀⠀⠹⣶⠆⠀⠀⠀⠀⠀⡴⠃⠀⠀⠘⠤⣄⣠⠞⠀
//    ⠀⠀⠀⠀⠀⢸⣷⡦⢤⡤⢤⣞⣁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
//    ⠀⠀⢀⣤⣴⣿⣏⠁⠀⠀⠸⣏⢯⣷⣖⣦⡀⠀⠀⠀⠀⠀⠀
//    ⢀⣾⣽⣿⣿⣿⣿⠛⢲⣶⣾⢉⡷⣿⣿⠵⣿⠀⠀⠀⠀⠀⠀
//    ⣼⣿⠍⠉⣿⡭⠉⠙⢺⣇⣼⡏⠀⠀⠀⣄⢸⠀⠀⠀⠀⠀⠀
//    ⣿⣿⣧⣀⣿………⣀⣰⣏⣘⣆⣀⠀⠀

    /**
     * Creates new object instance
     * 
     * @param string alias only one alias to load
     * 
     * @return void
     */
    public function __construct($alias = '') {
        $this->loadOptions();
        $this->initMessages();
        $this->loadScripts($alias);
    }

    /**
     * Inits system message helper object instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Load required configs and sets some properties depends by options
     * 
     * @return void
     */
    protected function loadOptions() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $customSortField = $this->ubConfig->getAlterParam('ONEPUNCH_DEFAULT_SORT_FIELD');
        if ($customSortField) {
            $this->defaultSortField = $customSortField;
        }
    }

    /**
     * Loads existing punch scripts from database
     * 
     * @param string $alias
     * 
     * @return void
     */
    protected function loadScripts($alias = '') {
        $alias = vf($alias);
        $where = (!empty($alias)) ? "WHERE `alias`='" . $alias . "'" : '';
        $orderBy = (empty($this->defaultSortField) ? '' : " ORDER BY `" . $this->defaultSortField . "` ASC ");
        $query = "SELECT * from `punchscripts` " . $where . $orderBy;
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->punchScripts[$each['alias']] = $each;
            }
        }
    }

    /**
     * Returns array of loaded scripts as alias=>scriptData
     * 
     * @return array
     */
    public function getAllScripts() {
        return($this->punchScripts);
    }

    /**
     * Checks is some script name unused?
     * 
     * @param sring $alias
     * 
     * @return bool false - script exists, true - alias free.
     */
    public function checkAlias($alias) {
        $alias = vf($alias);
        $result = true;
        if (isset($this->punchScripts[$alias])) {
            $result = false;
        }
        return ($result);
    }

    /**
     * Renders new script creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $inputs = '';
        $namePreset = (wf_CheckPost(array('newscriptname'))) ? $_POST['newscriptname'] : '';
        $aliasPreset = (wf_CheckPost(array('newscriptalias'))) ? $_POST['newscriptalias'] : '';
        $contentPreset = (wf_CheckPost(array('newscriptcontent'))) ? $_POST['newscriptcontent'] : '';
        // sanjou! hisshou! shijou saikyou
        // nan dattenda? FURASUTOREESHON ore wa tomaranai
        $inputs .= wf_TextInput('newscriptname', __('Name'), $namePreset, true, 30);
        $inputs .= wf_TextInput('newscriptalias', __('Alias'), $aliasPreset, true, 15, 'alphanumeric');
        $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="newscriptcontent" cols="145" rows="30" spellcheck="false"');
        $inputs .= $contentPreset;
        $inputs .= wf_tag('textarea', true);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_DEVCON);
        return ($result);
    }

    /**
     * Renders script editing form
     * 
     * @param string $alias
     * 
     * @return string
     */
    public function renderEditForm($alias) {
        $result = '';
        $alias = vf($alias);
        if (isset($this->punchScripts[$alias])) {
            $inputs = '';
            $scriptData = $this->punchScripts[$alias];
            $namePreset = $scriptData['name'];
            $aliasPreset = $scriptData['alias'];
            $contentPreset = $scriptData['content'];
            $scriptId = $scriptData['id'];
            $inputs .= wf_HiddenInput('editscriptid', $scriptId);
            $inputs .= wf_HiddenInput('editscriptoldalias', $aliasPreset);
            $inputs .= wf_TextInput('editscriptname', __('Name'), $namePreset, true, 30);
            $inputs .= wf_TextInput('editscriptalias', __('Alias'), $aliasPreset, true, 15, 'alphanumeric');
            $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="editscriptcontent" cols="145" rows="30" spellcheck="false"');
            $inputs .= $contentPreset;
            $inputs .= wf_tag('textarea', true);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_delimiter();
            $result .= wf_BackLink(self::URL_DEVCON);
        }
        return ($result);
    }

    /**
     * Creates new script in database
     * 
     * @param string $alias
     * @param string $name
     * @param string $content
     * 
     * @return void/string on error
     */
    public function createScript($alias, $name, $content) {
        $result = '';
        $alias = vf($alias);
        $name = mysql_real_escape_string($name);
        $content = mysql_real_escape_string($content);
        if ($this->checkAlias($alias)) {
            $query = "INSERT INTO `punchscripts` (`id`,`alias`,`name`,`content`) VALUES ";
            $query .= "(NULL,'" . $alias . "' ,'" . $name . "','" . $content . "');";
            nr_query($query);
            log_register('ONEPUNCH CREATE ALIAS `' . $alias . '`');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Script with this alias already exists');
            log_register('ONEPUNCH ALIAS `' . $alias . '` FAIL');
        }
        return ($result);
    }

    /**
     * Deletes some script from database by his alias
     * 
     * @param string $alias
     * 
     * @return void/string on error
     */
    public function deleteScript($alias) {
        $result = '';
        $alias = vf($alias);
        if (isset($this->punchScripts[$alias])) {
            $query = "DELETE FROM `punchscripts` WHERE `alias`='" . $alias . "';";
            nr_query($query);
            log_register('ONEPUNCH DELETE ALIAS `' . $alias . '`');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Script with this alias not exists');
        }
        return ($result);
    }

    /**
     * Saves script data into database
     * 
     * @return void
     */
    public function saveScript() {
        if (wf_CheckPost(array('editscriptid', 'editscriptoldalias', 'editscriptname', 'editscriptalias', 'editscriptcontent'))) {
            $scriptId = vf($_POST['editscriptid'], 3);
            $newScriptAlias = vf($_POST['editscriptalias']);
            $oldScriptAlias = vf($_POST['editscriptoldalias']);
            $newScriptName = $_POST['editscriptname'];
            $newScriptContent = $_POST['editscriptcontent'];
            if (isset($this->punchScripts[$oldScriptAlias])) {
                $scriptData = $this->punchScripts[$oldScriptAlias];
                $where = "WHERE `id`='" . $scriptId . "';";
                if ($scriptData['alias'] != $newScriptAlias) {
                    if ($this->checkAlias($newScriptAlias)) {
                        simple_update_field('punchscripts', 'alias', $newScriptAlias, $where);
                        log_register('ONEPUNCH CHANGE ALIAS `' . $oldScriptAlias . '` ON `' . $newScriptAlias . '`');
                    } else {
                        log_register('ONEPUNCH ALIAS `' . $newScriptAlias . '` FAIL');
                    }
                }

                if ($scriptData['name'] != $newScriptName) {
                    simple_update_field('punchscripts', 'name', $newScriptName, $where);
                    log_register('ONEPUNCH CHANGE NAME `' . $oldScriptAlias . '`');
                }

                if ($scriptData['content'] != $newScriptContent) {
                    simple_update_field('punchscripts', 'content', $newScriptContent, $where);
                    log_register('ONEPUNCH CHANGE CONTENT `' . $oldScriptAlias . '`');
                }
            }
        }
    }

    /**
     * Performs old dev console code templates
     * 
     * @return void
     */
    public function importOldTemplates() {
        $keyMask = 'PHPCONSOLETEMPLATE:';
        $allTemplateKeys = zb_StorageFindKeys($keyMask);

        if (!empty($allTemplateKeys)) {
            foreach ($allTemplateKeys as $eachTemplateKey) {
                $newAlias = str_replace($keyMask, '', $eachTemplateKey['key']);
                $templateRaw = zb_StorageGet($eachTemplateKey['key']);
                @$templateData = unserialize($templateRaw);
                if (!empty($templateData)) {
                    if ((isset($templateData['name'])) AND ( isset($templateData['body']))) {
                        if ($this->checkAlias($newAlias)) {
                            //alias not exists yet
                            $this->createScript($newAlias, $templateData['name'], $templateData['body']);
                            //flush old code template
                            zb_StorageDelete($eachTemplateKey['key']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders list of available punch scripts with some controls
     * 
     * @return string
     */
    public function renderScriptsList() {
        $result = '';
        if (!empty($this->punchScripts)) {
            $cells = wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Alias'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->punchScripts as $io => $each) {
                $runLink = wf_JSAlert(self::URL_DEVCON . '&runscript=' . $each['alias'], $each['name'], 'Insert this template into PHP console');
                $cells = wf_TableCell($runLink);
                $cells .= wf_TableCell($each['alias']);
                $actLinks = wf_JSAlert(self::URL_DEVCON . '&delscript=' . $each['alias'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_JSAlert(self::URL_DEVCON . '&editscript=' . $each['alias'], web_edit_icon(), $this->messages->getEditAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('No available code templates'), 'warning');
            $result .= wf_tag('br');
            $result .= wf_JSAlertStyled(self::URL_DEVCON . '&importoldcodetemplates=true', wf_img('skins/shovel.png') . ' ' . __('Import old code templates if available'), $this->messages->getEditAlert(), 'ubButton');
            $result .= wf_tag('br');
        }
        return ($result);
    }

    /**
     * Returns executable content of existing punch script
     * 
     * @param string $alias
     * 
     * @return string
     */
    public function getScriptContent($alias) {
        $alias = vf($alias);
        $result = '';
        if (isset($this->punchScripts[$alias])) {
            $result .= $this->punchScripts[$alias]['content'];
        }
        return ($result);
    }

}

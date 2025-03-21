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
    protected $ubConfig = '';

    /**
     * Punch scripts database abstraction layer placeholder
     *
     * @var object
     */
    protected $punchDb = '';

    /**
     * Fancy code editor enable flag
     *
     * @var bool
     */
    protected $cmFlag = false;

    /**
     * Some predefined URLs, routes, tables etc...
     */
    const URL_DEVCON = '?module=sqlconsole&devconsole=true';
    const URL_HELPER = '?module=codebrowser';
    const TABLE_DATASOURCE = 'punchscripts';

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
        $this->initDatabase();
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
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->punchDb = new NyanORM(self::TABLE_DATASOURCE);
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
        $this->cmFlag = ($this->ubConfig->getAlterParam('ONEPUNCH_CM')) ? true : false;
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
        $alias = ubRouting::filters($alias, 'callback', 'vf');
        if (!empty($alias)) {
            $this->punchDb->where('alias', '=', $alias);
        }
        if (!empty($this->defaultSortField)) {
            $this->punchDb->orderBy($this->defaultSortField, 'ASC');
        }
        $this->punchScripts = $this->punchDb->getAll('alias');
    }

    /**
     * Returns array of loaded scripts as alias=>scriptData
     * 
     * @return array
     */
    public function getAllScripts() {
        return ($this->punchScripts);
    }

    /**
     * Checks is some script alias unused?
     * 
     * @param sring $alias
     * 
     * @return bool false - script exists, true - alias free.
     */
    protected function checkAlias($alias) {
        $alias = ubRouting::filters($alias, 'callback', 'vf');
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

        $namePreset = (ubRouting::checkPost('newscriptname')) ? ubRouting::post('newscriptname') : '';
        $aliasPreset = (ubRouting::checkPost('newscriptalias')) ? ubRouting::post('newscriptalias') : '';
        $contentPreset = (ubRouting::checkPost('newscriptcontent')) ? ubRouting::post('newscriptcontent') : '';
        // sanjou! hisshou! shijou saikyou
        // nan dattenda? FURASUTOREESHON ore wa tomaranai
        $inputs .= wf_TextInput('newscriptname', __('Name'), $namePreset, true, 30);
        $inputs .= wf_TextInput('newscriptalias', __('Alias'), $aliasPreset, true, 15, 'alphanumeric');
        if ($this->cmFlag) {
            $cmirr = new CMIRR();
            $inputs .= $cmirr->getEditorArea('newscriptcontent', $contentPreset);
        } else {
            $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="newscriptcontent" cols="145" rows="30" spellcheck="false"');
            $inputs .= $contentPreset;
            $inputs .= wf_tag('textarea', true);
        }
        $inputs .= wf_Submit(__('Create'));
        $formStyle = ($this->cmFlag) ? '' : 'glamour';
        $result .= wf_Form('', 'POST', $inputs, $formStyle);
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_DEVCON) . ' ';
        $result .= wf_Link(self::URL_HELPER, wf_img('skins/question.png') . ' ' . __('Available classes and functions directory'), false, 'ubButton', 'target="_blank"');
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
        $alias = ubRouting::filters($alias, 'callback', 'vf');
        if (isset($this->punchScripts[$alias])) {
            $inputs = '';
            $scriptData = $this->punchScripts[$alias];
            $namePreset = $scriptData['name'];
            $aliasPreset = $scriptData['alias'];
            $contentPreset = htmlentities($scriptData['content'], ENT_COMPAT, "UTF-8");
            $scriptId = $scriptData['id'];
            $inputs .= wf_HiddenInput('editscriptid', $scriptId);
            $inputs .= wf_HiddenInput('editscriptoldalias', $aliasPreset);
            $inputs .= wf_TextInput('editscriptname', __('Name'), $namePreset, true, 30);
            $inputs .= wf_TextInput('editscriptalias', __('Alias'), $aliasPreset, true, 15, 'alphanumeric');
            if ($this->cmFlag) {
                $cmirr = new CMIRR();
                $inputs .= $cmirr->getEditorArea('editscriptcontent', $contentPreset);
            } else {
                $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="editscriptcontent" cols="145" rows="30" spellcheck="false"');
                $inputs .= $contentPreset;
                $inputs .= wf_tag('textarea', true);
            }

            $inputs .= wf_Submit(__('Save'));
            $formStyle = ($this->cmFlag) ? '' : 'glamour';
            $result .= wf_Form('', 'POST', $inputs, $formStyle);
            $result .= wf_delimiter();
            $result .= wf_BackLink(self::URL_DEVCON) . ' ';
            $result .= wf_Link(self::URL_HELPER, wf_img('skins/question.png') . ' ' . __('Available classes and functions directory'), false, 'ubButton', 'target="_blank"');
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
        $alias = ubRouting::filters($alias, 'callback', 'vf');
        $name = ubRouting::filters($name, 'mres');
        $content = ubRouting::filters($content, 'mres');
        if ($this->checkAlias($alias)) {
            $this->punchDb->data('alias', $alias);
            $this->punchDb->data('name', $name);
            $this->punchDb->data('content', $content);
            $this->punchDb->create();
            log_register('ONEPUNCH CREATE ALIAS `' . $alias . '`');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Script with this alias already exists');
            log_register('ONEPUNCH CREATE ALIAS `' . $alias . '` FAIL');
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
        $alias = ubRouting::filters($alias, 'callback', 'vf');
        if (isset($this->punchScripts[$alias])) {
            $this->punchDb->where('alias', '=', $alias);
            $this->punchDb->delete();
            log_register('ONEPUNCH DELETE ALIAS `' . $alias . '`');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Script with this alias not exists');
            log_register('ONEPUNCH DELETE ALIAS `' . $alias . '` FAIL');
        }
        return ($result);
    }

    /**
     * Saves script data into database
     * 
     * @return void
     */
    public function saveScript() {
        if (ubRouting::checkPost(array('editscriptid', 'editscriptoldalias', 'editscriptname', 'editscriptalias', 'editscriptcontent'))) {
            $scriptId = ubRouting::post('editscriptid', 'int');
            $newScriptAlias = ubRouting::post('editscriptalias', 'callback', 'vf');
            $oldScriptAlias = ubRouting::post('editscriptoldalias', 'callback', 'vf');
            $newScriptName = ubRouting::post('editscriptname', 'mres');
            $newScriptContent = ubRouting::post('editscriptcontent', 'mres');
            if (isset($this->punchScripts[$oldScriptAlias])) {
                $scriptData = $this->punchScripts[$oldScriptAlias];

                if ($scriptData['alias'] != $newScriptAlias) {
                    if ($this->checkAlias($newScriptAlias)) {
                        $this->punchDb->where('id', '=', $scriptId);
                        $this->punchDb->data('alias', $newScriptAlias);
                        $this->punchDb->save();
                        log_register('ONEPUNCH CHANGE ALIAS `' . $oldScriptAlias . '` ON `' . $newScriptAlias . '`');
                    } else {
                        log_register('ONEPUNCH CHANGE ALIAS `' . $newScriptAlias . '` FAIL');
                    }
                }

                if ($scriptData['name'] != $newScriptName) {
                    $this->punchDb->where('id', '=', $scriptId);
                    $this->punchDb->data('name', $newScriptName);
                    $this->punchDb->save();
                    log_register('ONEPUNCH CHANGE NAME `' . $oldScriptAlias . '`');
                }

                if ($scriptData['content'] != $newScriptContent) {
                    $this->punchDb->where('id', '=', $scriptId);
                    $this->punchDb->data('content', $newScriptContent);
                    $this->punchDb->save();
                    log_register('ONEPUNCH CHANGE CONTENT `' . $oldScriptAlias . '`');
                }
            }
        }
    }

    /**
     * Performs old dev console code templates migration into one-punch scripts
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
                    if ((isset($templateData['name'])) and (isset($templateData['body']))) {
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
        $alias = ubRouting::filters($alias, 'callback', 'vf');
        $result = '';
        if (isset($this->punchScripts[$alias])) {
            $result .= $this->punchScripts[$alias]['content'];
        }
        return ($result);
    }

    /**
     * Checks is some script alias exists?
     * 
     * @param string $alias
     * 
     * @return bool 
     */
    public function isAliasFree($alias) {
        return ($this->checkAlias($alias));
    }

    /**
     * Installs some third-party script
     * 
     * @param array $scriptData
     * 
     * @return void/string on error
     */
    public function installScript($scriptData) {
        $result = '';
        if (is_array($scriptData)) {
            if (isset($scriptData['alias']) and isset($scriptData['name']) and isset($scriptData['content'])) {
                $alias = $scriptData['alias'];
                $name = $scriptData['name'];
                $content = $scriptData['content'];
                if (!empty($alias) and !empty($name) and !empty($content)) {
                    if ($this->isAliasFree($alias)) {
                        $result .= $this->createScript($alias, $name, $content);
                    } else {
                        $result .= __('One-punch') . ' ' . __('Alias') . ' ' . _('already exists');
                    }
                } else {
                    $result .= __('One-punch') . ' ' . __('script') . ' ' . _('is corrupted');
                }
            } else {
                $result .= __('One-punch') . ' ' . __('script') . ' ' . _('is corrupted');
            }
        } else {
            $result .= __('One-punch') . ' ' . __('script') . ' ' . _('is corrupted');
        }
        return ($result);
    }
}

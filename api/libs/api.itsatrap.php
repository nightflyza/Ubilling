<?php

class ItSaTrap {

    /**
     * Contains SNMP data log path or 
     *
     * @var string
     */
    protected $dataSource = '';

    /**
     * Contains billing.ini config file as key=>value 
     *
     * @var array
     */
    protected $billingCfg = array();

    /**
     * Contains default limit of lines received from local data source
     *
     * @var int
     */
    protected $lineLimit = 200;

    /**
     * Contains available trap types as id=>data
     *
     * @var array
     */
    protected $allTrapTypes = array();

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Trap types data model placeholder
     *
     * @var object
     */
    protected $trapTypesDb = '';

    /**
     * key-value storage key of file path/URL of traps source
     */
    const DATA_SOURCE_KEY = 'ITSATRAPSOURCE';

    /**
     * key-value storage key of lines parse limit of traps data source
     */
    const DATA_LINES_KEY = 'ITSATRAPLINES';

    /**
     * Contains control module basic URL
     */
    const URL_ME = '?module=itsatrap';

    /**
     * Contains configuration controller URL
     */
    const URL_CONFIG = '&config=true';

    /**
     * Contains raw data controller URL
     */
    const URL_RAW = '&rawdata=true';

    /**
     * Contains processed data controller URL
     */
    const URL_AJTRAPS = '&ajaxtrapslist=true';

    /**
     * Contains siwtch by IP search URL
     */
    const URL_SWITCHSRCH = '?module=switches&gotoswitchbyip=';

    /**
     * Contains database table name of available trap types settings
     */
    const TABLE_TYPES = 'traptypes';

    public function __construct() {
        $this->loadConfig();
        $this->initMessages();
        $this->initTrapTypesDb();
        $this->loadTrapTypes();
    }

    /**
     * Loads some configuration files and options for further usage
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->dataSource = zb_StorageGet(self::DATA_SOURCE_KEY);
        $lineLimitCfg = zb_StorageGet(self::DATA_LINES_KEY);
        if (!empty($lineLimitCfg)) {
            $this->lineLimit = $lineLimitCfg;
        }
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Inits system messages object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits trap types data model in protected property for further usage
     * 
     * @return void
     */
    protected function initTrapTypesDb() {
        $this->trapTypesDb = new NyanORM(self::TABLE_TYPES);
    }

    /**
     * Loads existing SNMP trap types configuration from database
     * 
     * @return void
     */
    protected function loadTrapTypes() {
        $this->allTrapTypes = $this->trapTypesDb->getAll('id');
    }

    /**
     * Returns raw data from data source if defined
     * 
     * @return string
     */
    public function getRawData() {
        $result = '';
        if (!empty($this->dataSource)) {
            if (ispos($this->dataSource, 'http')) {
                $result = file_get_contents($this->dataSource);
            } else {
                $command = $this->billingCfg['SUDO'] . ' ' . $this->billingCfg['TAIL'] . ' -n ' . $this->lineLimit . ' ' . $this->dataSource;
                $result = shell_exec($command);
            }
        }
        return($result);
    }

    /**
     * Returns module configuration form
     * 
     * @return string
     */
    public function renderConfigForm() {
        $result = '';
        $inputs = wf_TextInput('newdatasource', __('Data source file path or URL'), $this->dataSource, true, 40);
        $inputs .= wf_TextInput('newlineslimit', __('Lines limit for processing'), $this->lineLimit, true, 4, 'digits');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Saves data source configuration if its changed
     * 
     * @return void
     */
    public function saveBasicConfig() {
        $newDataSource = ubRouting::post('newdatasource', 'mres');
        if ($newDataSource != $this->dataSource) {
            zb_StorageSet(self::DATA_SOURCE_KEY, $newDataSource);
            log_register('ITSATRAP CHANGE DATASOURCE');
        }
        $newLinesLimit = ubRouting::post('newlineslimit', 'int');
        if ($newLinesLimit != $this->lineLimit) {
            zb_StorageSet(self::DATA_LINES_KEY, $newLinesLimit);
            log_register('ITSATRAP CHANGE LIMIT `' . $newLinesLimit . '`');
        }
    }

    /**
     * Renders available trap types list with some controls
     * 
     * @return string
     */
    public function renderTrapTypesList() {
        $result = '';
        if (!empty($this->allTrapTypes)) {
            $cells = wf_TableCell('ID');
            $cells .= wf_TableCell(__('Filter'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Color'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allTrapTypes as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['match']);
                $cells .= wf_TableCell($this->colorize($each['color'], $each['color']));
                $trapControls = wf_JSAlert(self::URL_ME . self::URL_CONFIG . '&deletetrapid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $trapControls .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['name'], $this->renderTrapEditForm($each['id']));
                $cells .= wf_TableCell($trapControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Performs data coloring if some custom color set
     * 
     * @param string $data
     * @param string $color
     * 
     * @return string
     */
    protected function colorize($data, $color = '') {
        $result = $data;
        if (!empty($color)) {
            $result = wf_tag('font', false, '', 'color="' . $color . '"') . $data . wf_tag('font', true);
        }
        return($result);
    }

    /**
     * Render new trap type creation form
     * 
     * @return string
     */
    public function renderTrapCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newname', __('Name'), '', true, 20);
        $inputs .= wf_TextInput('newmatch', __('Filter'), '', true, 20);
        $inputs .= wf_ColPicker('newcolor', __('Color'), '', true, 8);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders existing trap type editing form
     * 
     * @param int $trapTypeId
     * 
     * @return string
     */
    protected function renderTrapEditForm($trapTypeId) {
        $result = '';
        if (isset($this->allTrapTypes[$trapTypeId])) {
            $trapData = $this->allTrapTypes[$trapTypeId];
            $inputs = wf_HiddenInput('edittraptypeid', $trapTypeId);
            $inputs .= wf_TextInput('editname', __('Name'), $trapData['name'], true, 20);
            $inputs .= wf_TextInput('editmatch', __('Filter'), $trapData['match'], true, 20);
            $inputs .= wf_TextInput('editcolor', __('Color'), $trapData['color'], true, 8); //some issues with colpicker in modal windows
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Creates new trap type in database
     * 
     * @return void
     */
    public function createTrapType() {
        if (ubRouting::checkPost(array('newname', 'newmatch', 'newcolor'))) {
            $newNameF = ubRouting::post('newname', 'mres');
            $newMatchF = ubRouting::post('newmatch', 'mres');
            $newColorF = ubRouting::post('newcolor', 'mres');

            $this->trapTypesDb->data('match', $newMatchF);
            $this->trapTypesDb->data('name', $newNameF);
            $this->trapTypesDb->data('color', $newColorF);
            $this->trapTypesDb->create();
            $newId = $this->trapTypesDb->getLastId();
            log_register('ITSATRAP CREATE TRAPTYPE [' . $newId . '] `' . ubRouting::post('newname') . '`');
        }
    }

    /**
     * Saves existing trap type changes into database
     * 
     * @return void
     */
    public function saveTrapType() {
        if (ubRouting::checkPost(array('edittraptypeid', 'editname', 'editmatch', 'editcolor'))) {
            $editId = ubRouting::post('edittraptypeid', 'int');
            if (isset($this->allTrapTypes[$editId])) {
                $newNameF = ubRouting::post('editname', 'mres');
                $newMatchF = ubRouting::post('editmatch', 'mres');
                $newColorF = ubRouting::post('editcolor', 'mres');

                $this->trapTypesDb->data('match', $newMatchF);
                $this->trapTypesDb->data('name', $newNameF);
                $this->trapTypesDb->data('color', $newColorF);
                $this->trapTypesDb->where('id', '=', $editId);
                $this->trapTypesDb->save();
                log_register('ITSATRAP CHANGE TRAPTYPE [' . $editId . ']');
            }
        }
    }

    /**
     * Deletes existing trap type from database
     * 
     * @param int $trapTypeId
     * 
     * @return void/string on error
     */
    public function deleteTrapType($trapTypeId) {
        $result = '';
        $trapTypeId = ubRouting::filters($trapTypeId, 'int');
        if (isset($this->allTrapTypes[$trapTypeId])) {
            $this->trapTypesDb->where('id', ' = ', $trapTypeId);
            $this->trapTypesDb->delete();

            log_register('ITSATRAP DELETE TRAPTYPE [' . $trapTypeId . ']');
        } else {
            $result .= __('Something went wrong') . ': EX_TRAPID_NOT_EXISTS';
        }
        return($result);
    }

    /**
     * Renders module control panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, web_icon_search() . ' ' . __('Events'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . self::URL_RAW, wf_img('skins/icon_raw.gif') . ' ' . __('RAW') . ' ' . __('Data'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . self::URL_CONFIG, web_icon_extended() . ' ' . __('Settings'), false, 'ubButton') . ' ';
        return($result);
    }

    /**
     * Renders raw data received from data source
     * 
     * @return string
     */
    public function renderRawData() {
        $result = '';
        $lineCount = 0;
        if (!empty($this->dataSource)) {
            $rawData = $this->getRawData();
            if (!empty($rawData)) {
                $rawData = explodeRows($rawData);

                $cells = wf_TableCell(__('Number'));
                $cells .= wf_TableCell(__('Data'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($rawData as $io => $eachLine) {
                    $cells = wf_TableCell($lineCount);
                    $cells .= wf_TableCell($eachLine);
                    $rows .= wf_TableRow($cells, 'row5');
                    $lineCount++;
                }

                $result = wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Data source file path or URL') . ' ' . __('is empty'), 'error');
        }
        return($result);
    }

    /**
     * Renders preprocessed trap events container
     * 
     * @return string
     */
    public function renderTrapEventsList() {
        $result = '';
        if (!empty($this->dataSource)) {
            $columns = array('Date', 'IP', 'Event', 'Actions');
            $opts = '"order": [[ 0, "desc" ]]';
            $result .= wf_JqDtLoader($columns, self::URL_ME . self::URL_AJTRAPS, false, __('Events'), 100, $opts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Data source file path or URL') . ' ' . __('is empty'), 'error');
        }
        return($result);
    }

    /**
     * Returns preprocessed current trap events data
     * 
     * @return void
     */
    public function ajTrapList() {
        $json = new wf_JqDtHelper();
        $rawData = $this->getRawData();
        $data = array();
        if (!empty($rawData)) {
            $rawData = explodeRows($rawData);
            if (!empty($rawData)) {
                foreach ($rawData as $io => $eachLine) {
                    if (!empty($eachLine)) {
                        $ip = zb_ExtractIpAddress($eachLine);
                        if (!empty($ip)) {
                            $line = explode(' ', $eachLine);
                            $dateF = $line[0] . ' ' . $line[1];
                            $dateF = trim($dateF);
                            if (@zb_checkDate($line[0])) {
                                //ok seems it normal log trap record
                                if (!empty($this->allTrapTypes)) {
                                    foreach ($this->allTrapTypes as $ia => $eachTrapType) {
                                        if (ispos($eachLine, $eachTrapType['match'])) {
                                            $data[] = $dateF;
                                            $ipControls = '';
                                            $ipControls .= wf_Link(self::URL_SWITCHSRCH . $ip, web_edit_icon(__('Go to switch')));
                                            $ipControls .= wf_Link('http://' . $ip, wf_img('skins/ymaps/globe.png', __('Go to the web interface')), false, '', 'target="_BLANK"') . ' ';
                                            $data[] = $ip;
                                            $data[] = $this->colorize($eachTrapType['name'], $eachTrapType['color']);
                                            $data[] = $ipControls;
                                            $json->addRow($data);
                                            unset($data);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $json->getJson();
    }

}

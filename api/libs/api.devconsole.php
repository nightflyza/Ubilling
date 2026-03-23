<?php

/**
 * Developers console implementation
 */
class DevConsole {

    /**
     * Contains alter config a key=>value
     */
    protected $altCfg = array();

    /**
     * Protected OnePunch scripts object instance
     *
     * @var object
     */
    protected $onePunch = '';

    /**
     * CodeMirror editor enabled flag
     *
     * @var bool
     */
    protected $cmirrFlag = false;

    /**
     * CodeMirror editor default style
     *
     * @var string
     */
    protected $cmirrStyle = '
    #editor-container {
                width: 100% !important;
                border: 1px solid #ccc;
            }

            .CodeMirror {
                width: 100% !important;
                font-size: 16px;
            }
    ';

    /**
     * Some predefined stuff like routes here
     */
    const URL_ME = '?module=sqlconsole';
    const URL_DEVCON = '?module=sqlconsole&devconsole=true';
    const URL_OP_MANAGE = '?module=sqlconsole&devconsole=true&manageonepunch=true';
    const OPTION_KEEP = 'DEVCON_SQL_KEEP';
    const OPTION_DEBUG = 'DEVCON_VERBOSE_DEBUG';
    const OPTION_CM = 'DEVCON_CM';

    const ROUTE_PHP_CON = 'devconsole';
    const ROUTE_OP_RUN = 'runscript';
    const ROUTE_OP_CREATE = 'scriptadd';
    const ROUTE_OP_DELETE = 'delscript';
    const ROUTE_OP_EDIT = 'editscript';
    const ROUTE_OP_IMPORT = 'importoldcodetemplates';
    const ROUTE_OP_MANAGE = 'manageonepunch';


    /**
     * forms inputs post-routes here
     */
    const PROUTE_SQL = 'sqlq';
    const PROUTE_PHP = 'phpq';

    const PROUTE_OPN_NAME = 'newscriptname';
    const PROUTE_OPN_ALIAS = 'newscriptalias';
    const PROUTE_OPN_CONTENT = 'newscriptcontent';
    const PROUTE_OPE_ID = 'editscriptid';
    const PROUTE_OPE_OLDALIAS = 'editscriptoldalias';
    const PROUTE_OPE_NAME = 'editscriptname';
    const PROUTE_OPE_ALIAS = 'editscriptalias';
    const PROUTE_OPE_CONTENT = 'editscriptcontent';
    const PROUTE_HLIGHT = 'phphightlight';
    const PROUTE_TABLE = 'tableresult';
    const PROUTE_TRUETABLE = 'truetableresult';
    const PROUTE_SQL_DISPLAY = 'sqldisplay';
    const SQL_DISPLAY_RAW = 'raw';
    const SQL_DISPLAY_TABLE = 'table';
    const SQL_DISPLAY_TRUETABLE = 'truetable';


    /**
     * Plagued by doubt that it can be done
     * In a culture so committed to temporal pleasure and distraction
     * Should I flee into lifelong mountain retreat?
     */
    public function __construct() {
        $this->loadConfigs();
        if (ubRouting::checkGet(self::ROUTE_PHP_CON) or ubRouting::checkGet(self::ROUTE_OP_MANAGE) or ubRouting::checkGet(array(self::ROUTE_OP_RUN, self::ROUTE_OP_CREATE, self::ROUTE_OP_DELETE, self::ROUTE_OP_EDIT, self::ROUTE_OP_IMPORT))) {
            $this->initOnePunch();
        }

        if (isset($this->altCfg[self::OPTION_CM])) {
            if ($this->altCfg[self::OPTION_CM]) {
                $this->cmirrFlag = true;
            } 
        }
    }


    /**
     * Loads required configs data
     *
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Initializes the OnePunch object
     * 
     * @return void
     */
    protected function initOnePunch() {
        $this->onePunch = new OnePunch();
    }

    /**
     * Renders the controls for the developers console
     *
     * @return string 
     */
    protected function renderControls() {
        $result = '';
        $migrationControls = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/icon_restoredb.png') . ' ' . __('SQL Console'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_PHP_CON . '=true', wf_img('skins/icon_php.png') . ' ' . __('PHP Console'), false, 'ubButton');
        $result .= wf_Link(self::URL_OP_MANAGE, wf_img('skins/script16.png') . __('One-Punch scripts'), false, 'ubButton');
        if (cfr('ROOT')) {
            $migrationControls .= wf_Link("?module=unicornteleport", wf_img('skins/teleport16.png') . ' ' . __('Unicorn Teleport'), false, 'ubButton');
            $migrationControls .= wf_Link("?module=migration", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration'), false, 'ubButton');
            $migrationControls .= wf_Link("?module=migration2", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration') . ' 2', false, 'ubButton');
            $migrationControls .= wf_Link("?module=migration2_exten", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration live (occupancy & tags)'), false, 'ubButton');
            $migrationControls .= wf_Link("?module=migration2_ukv", wf_img('skins/icon_puzzle.png') . ' ' . __('Migration') . ' 2 UKV', false, 'ubButton');
        }

        if (cfr('MIKMIGR')) {
            $migrationControls .= wf_Link("?module=mikbill_migration", wf_img('skins/ukv/dollar.png') . ' ' . __('Migration') . ' MikBiLL', false, 'ubButton');
        }

        $result .= wf_modalAuto(wf_img('skins/icon_puzzle.png') . ' ' . __('Migration'), __('Migration'), $migrationControls, 'ubButton');
        $result .= wf_tag('br');
        return ($result);
    }

    /**
     * Renders the SQL console form
     *
     * This method generates the HTML form for the SQL console, including input fields for the SQL query,
     * and options for displaying the query result as a table
     *
     * @return string
     */
    public function renderSqlForm() {
        $startQuery = '';
        $result = '';
        $sqlinputs = $this->renderControls();
        $displayMode = ubRouting::post(self::PROUTE_SQL_DISPLAY);
        if ($displayMode !== self::SQL_DISPLAY_TABLE and $displayMode !== self::SQL_DISPLAY_TRUETABLE) {
            $displayMode = self::SQL_DISPLAY_RAW;
        }
        if (ubRouting::checkPost(self::PROUTE_SQL)) {
            if ($this->altCfg[self::OPTION_KEEP]) {
                $startQuery = ubRouting::post(self::PROUTE_SQL, 'callback', 'trim');
            }
        }

        $formClass = ($this->cmirrFlag) ? '' : 'glamour';

        if ($this->cmirrFlag) {
            $cmirr = new CMIRR();
            $cmirr->setStyle($this->cmirrStyle);
            $cmirr->setMode('text/x-sql');
            $cmirr->setHintOptions('sql');
            $sqlinputs .= $cmirr->getEditorArea(self::PROUTE_SQL, $startQuery, 40, 5);
        } else {
            $sqlinputs .= wf_TextArea(self::PROUTE_SQL, '', $startQuery, true, '110x10');
        }

        
        $sqlinputs .= wf_RadioInput(self::PROUTE_SQL_DISPLAY, __('Show as raw array'), self::SQL_DISPLAY_RAW, true, ($displayMode === self::SQL_DISPLAY_RAW));
        $sqlinputs .= wf_RadioInput(self::PROUTE_SQL_DISPLAY, __('Display query result as table'), self::SQL_DISPLAY_TABLE, true, ($displayMode === self::SQL_DISPLAY_TABLE));
        $sqlinputs .= wf_RadioInput(self::PROUTE_SQL_DISPLAY, __('Display query result as table with fields'), self::SQL_DISPLAY_TRUETABLE, true, ($displayMode === self::SQL_DISPLAY_TRUETABLE));
        $sqlinputs .= wf_Submit('Process query');
        $result = wf_Form('', 'POST', $sqlinputs, $formClass);
        return ($result);
    }

    /**
     * Renders the PHP form for the dev console.
     *
     * This method generates the HTML code for the PHP form in the dev console.
     * It includes the necessary inputs, such as text areas and checkboxes, for
     * running and highlighting PHP code.
     *
     * @return string
     */
    public function renderPhpForm() {
        $result = '';
        $runCode = '';
        $phpinputs = $this->renderControls();
        $phphightlightFlag = (ubRouting::checkPost(self::PROUTE_HLIGHT)) ? true : false;

        //is this template run or clear area?
        if (ubRouting::checkGet(self::ROUTE_OP_RUN)) {
            $this->initOnePunch();
            $runCode = htmlentities($this->onePunch->getScriptContent(ubRouting::get(self::ROUTE_OP_RUN)), ENT_COMPAT, "UTF-8");
        } else {
            if ($this->altCfg[self::OPTION_KEEP]) {
                if (ubRouting::checkPost(self::PROUTE_PHP)) {
                    $runCode = ubRouting::post(self::PROUTE_PHP);
                }
            }
        }

        $formClass = ($this->cmirrFlag) ? '' : 'glamour';
        if ($this->cmirrFlag) {
            $cmirr = new CMIRR();
            $cmirr->setStyle($this->cmirrStyle);
            $cmirr->setMode('text/x-php');
            $phpinputs .= $cmirr->getEditorArea(self::PROUTE_PHP, $runCode, 40, 5);
        } else {
            $phpinputs .= wf_TextArea(self::PROUTE_PHP, '', $runCode, true, '110x10');
        }

        $phpinputs .= wf_CheckInput(self::PROUTE_HLIGHT, 'Hightlight this PHP code', true, $phphightlightFlag);
        $phpinputs .= wf_Submit('Run this code inside framework');
        $result .= wf_Form(self::URL_DEVCON, 'POST', $phpinputs, $formClass);
        return ($result);
    }


    /**
     * Renders the PHP interfaces grid for the dev console.
     *
     * @return string
     */
    public function renderPhpInterfaces() {
        $result = '';
        if (ubRouting::checkGet(self::ROUTE_OP_MANAGE)) {
            if (ubRouting::checkGet(array(self::ROUTE_OP_CREATE))) {
                $result .= $this->onePunch->renderCreateForm(self::URL_OP_MANAGE);
            } else {
                if (ubRouting::checkGet(self::ROUTE_OP_EDIT)) {
                    $result .= $this->onePunch->renderEditForm($_GET['editscript'], self::URL_OP_MANAGE);
                } else {
                    $result .= wf_BackLink(self::URL_DEVCON);
                    $result .= wf_Link(self::URL_OP_MANAGE . '&scriptadd=true', web_icon_create() . ' ' . __('Create') . ' ' . __('One-Punch') . ' ' . __('Script'), true, 'ubButton');
                    $result .= wf_delimiter(0);

                    $result .= $this->onePunch->renderScriptsList(self::URL_OP_MANAGE);
                    
                }
            }
        } else {
            $result .= $this->renderPhpForm();
        }
        return ($result);
    }

    /**
     * Renders quick one-punch scripts list for PHP console page
     *
     * @return string
     */
    public function renderPhpScriptsQuickList() {
        $result = '';
        $result .= $this->onePunch->renderScriptsLauncher(self::URL_DEVCON);
        return ($result);
    }


    /**
     * Executes an SQL query and displays the result.
     *
     * This method checks if the SQL query is received through the POST request and then attempts to execute it.
     * If the query is not empty, it logs the query and starts buffering the output.
     * Depending on the PHP extension loaded (mysql or mysqli), it executes the query and fetches the result.
     * If the query execution fails, it returns an error message.
     * If the query execution is successful, it stores the query debug data and displays it if the corresponding option is enabled.
     * It then renders the query result based on the selected options: raw array result, table with fields, or table without fields.
     * Finally, it displays the query result, query status, and the number of returned records.
     *
     * @return void
     */
    public function executeSqlQuery() {
        //here we go?
        if (ubRouting::checkPost(self::PROUTE_SQL)) {
            if (!extension_loaded('mysql')) {
                global $loginDB; //that instance initialized outside
            }
            $newquery = ubRouting::post(self::PROUTE_SQL, 'callback', 'trim');
            $recCount = 0; //preventing notices on empty queries
            $vdump = ''; //used for storing query executing result
            $query_result = array(); //executed query result shall to be there

            //trying to execute received SQL query
            if (!empty($newquery)) {
                $stripquery = substr($newquery, 0, 70) . '..';
                log_register('SQLCONSOLE ' . $stripquery);
                ob_start();

                if (!extension_loaded('mysql')) {
                    mysqli_report(0);
                    $queried = mysqli_query($loginDB, $newquery);
                } else {
                    $queried = mysql_query($newquery);
                }
                if ($queried === false) {
                    ob_end_clean();
                    return (show_error(wf_tag('b') . __('Wrong query') . ': ' . wf_tag('b', true) . $newquery));
                } else {
                    if (!extension_loaded('mysql')) {
                        mysqli_report(0);
                        if ($queried !== true) {
                            while (@$row = mysqli_fetch_assoc($queried)) {
                                $query_result[] = $row;
                            }
                        }
                    } else {
                        while (@$row = mysql_fetch_assoc($queried)) {
                            $query_result[] = $row;
                        }
                    }

                    $sqlDebugData = ob_get_contents();
                    ob_end_clean();
                    log_register('SQLCONSOLE QUERYDONE');
                    if ($this->altCfg[self::OPTION_DEBUG]) {
                        show_window(__('Console debug data'), $sqlDebugData);
                    }
                }
                    // trying to render SQL query execution results depends on selected options
                if (!empty($query_result)) {
                    $recCount = count($query_result);
                    $displayMode = ubRouting::post(self::PROUTE_SQL_DISPLAY);
                    if ($displayMode !== self::SQL_DISPLAY_TABLE and $displayMode !== self::SQL_DISPLAY_TRUETABLE) {
                        //raw array result (default)
                        $vdump = htmlspecialchars(var_export($query_result, true));
                    } else {
                        if ($displayMode === self::SQL_DISPLAY_TRUETABLE) {
                        //show query result as table with fields
                        $tablecells = '';
                        $tablerows = '';
                        $fieldNames = array_keys($query_result[0]);

                        if (!empty($fieldNames)) {
                            $fieldsCnt = count($fieldNames);

                            foreach ($fieldNames as $fieldName) {
                                $tablecells .= wf_TableCell($fieldName,'','row1');
                            }
                            $tablerows .= $tablecells;
                            $tablecells = '';

                            foreach ($query_result as $eachresult) {
                                for ($k = 0; $k < $fieldsCnt; $k++) {
                                    $tablecells .= wf_TableCell('');
                                }
                               // $tablerows .= wf_TableRow($tablecells, 'row2');
                                $tablecells = '';

                                foreach ($eachresult as $io => $key) {
                                    $tablecells .= wf_TableCell(htmlspecialchars($key));
                                }
                                $tablerows .= wf_TableRow($tablecells, 'row5');
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
                                $tablecells .= wf_TableCell(htmlspecialchars($key));
                                $tablerows .= wf_TableRow($tablecells, 'row3');
                            }
                        }
                        $vdump = wf_TableBody($tablerows, '100%', '0', '');
                    }
                }
                }
            }

            
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

            //rendering records if available
            show_window(__('Result'), wf_tag('pre') . $vdump . wf_tag('pre', 'true'));

        }
    }

    /**
     * Displays the debug data in the PHP console if enabled with option.
     *
     * @param string $debugData The debug data to be displayed.
     * 
     * @return void
     */
    public function showDebugData($debugData) {
        if ($this->altCfg[self::OPTION_DEBUG]) {
            show_window(__('Console debug data'), wf_tag('pre') . $debugData . wf_tag('pre', true));
        }
    }

    /**
     * Displays the highlighted PHP code in a window if the POST request matches the specified route.
     *
     * @param string $phpCode The PHP code to be highlighted and displayed.
     * 
     * @return void
     */
    public function showCodeHighlight($phpCode) {
        if (ubRouting::checkPost(self::PROUTE_HLIGHT)) {
            $code = '<?php' . PHP_EOL . PHP_EOL;
            $code .= $phpCode . PHP_EOL . PHP_EOL;
            $code .= '?>';
            show_window(__('Running this'), highlight_string($code, true));
        }
    }
}

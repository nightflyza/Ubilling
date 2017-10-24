<?php

class Polls {

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains Poll ID from $_GET
     *
     * @var string
     */
    protected $poll_id  = '';

    /**
     * Contains admns Name as admin_login => admin_name
     *
     * @var array
     */
    protected $adminsName = array();

    /**
     * Contains all polls as id => array (title, enabled, start_date, end_date, params,[admin])
     *
     * @var array
     */
    protected $pollsAvaible = array();

    /**
     * Contains all polls options as poll_id => array (id => [text])
     *
     * @var array
     */
    protected $pollsOptions = array();

    /**
     * Contains poll options count as poll_id => count
     *
     * @var array
     */
    protected $pollsOptionsCount = array();

    /**
     * Contains all polls votes as [poll_id] => Array ( parametr => Array ( [login] => $value))
     *
     * @var array
     */
    protected $pollsVotes = array();

    /**
     * Contains poll votes count as poll_id => count
     *
     * @var array
     */
    protected $pollsVotesCount = array();

    /**
     * Contains votes count by options as option_id => count
     *
     * @var array
     */
    protected $pollsOptionVotesCount = array();

    /**
     * Contains java scipt for dynamic add and remove input form field
     *
     * @var void
     */
    protected $pollJavaScript = '';

    /**
     * Contains STYLE design for form
     *
     * @var void
     */
    protected $pollCss = '';

    /**
     * Polls caching time
     *
     * @var int
     */
    protected $cacheTime = 2592000; //month by default

    const URL_ME = '?module=polls';

    public function __construct() {
        $this->initMessages();
        $this->setLogin();
        $this->initCache();
        $this->setPollId();
        $this->loadAdminsName();
        $this->loadAvaiblePollsCached();
        $this->loadPollsOptionsCached();
        $this->loadPollsVotesCached();
        if ( ! wf_CheckGet(array('ajaxavaiblepolls'))) {
            $this->loadPollsVotesCached();
        }
    }

    /**
     * 
     * 
     * @return string
     */
    protected function loadPollJavaScript($input_name = 'val', $label = 'Variant', $value = '', $width = 120) {
        $this->pollJavaScript = '
                            <script type="text/javascript">
                            // Function add input field in form
                            function addField () {
                                var telnum = parseInt($("#add_field_area").find("div.add:last").attr("id").slice(3))+1;
                                $("div#add_field_area").append(\' \
                                                                <div id="add\'+telnum+\'" class="add"> \
                                                                <label>' . __($label) . ' №\'+telnum+\'</label> \
                                                                <input type="text" width="' . $width . '" name="' . $input_name . '[]" id="' . $input_name . '"  value="' . $value . '"/> \
                                                                <div class="deletebutton" onclick="deleteField(\'+telnum+\');"></div> \
                                                                </div> \
                                                            \');
                            }
                            // Function remove input field in form
                            function deleteField (id) {
                                $(\'div#add\'+id).remove();
                            }
                            </script>
        ';
    }

    /**
     * Load style for form
     * 
     * @return string
     */
    protected function loadPollCss() {
        $this->pollCss = "
                    <style>
                    input {
                        height: 20px;
                        margin: 5px;
                        width:400px;
                    }
                    .addbutton {
                        text-align: center;
                        vertical-align:middle;
                        font-size: 13px;
                        width: 283px;
                        border: 1px solid #70A9FD;
                        -webkit-border-radius: 7px;
                        -moz-border-radius: 7px;
                        border-radius: 7px;
                        cursor: pointer;
                        margin: 2px 0 0 110px;
                        color: #326DC5;
                        padding: 4px;
                        background-color:#BED6FF;
                    }

                    .deletebutton {
                        width: 20px;
                        height: 22px;
                        cursor: pointer;
                        margin: 5px;
                        display:inline-block;
                        background: url(skins/icon_del.gif) repeat;
                        background-position: center center;
                        background-repeat: no-repeat;
                        position:absolute;
                        top: 1px;
                        left: 480px;
                    }

                    .add {
                        position:relative;
                    }

                    .createbutton {
                        text-align: center;
                        vertical-align:middle;
                        font-size: 13px;
                        width: 293px;
                        -webkit-border-radius: 7px;
                        -moz-border-radius: 7px;
                        border-radius: 7px;
                        cursor: pointer;
                        margin: 20px 0 0 110px;
                        border: 1px solid #378137;
                        color: #fff;
                        padding: 4px;
                        height: 40px;;
                        background-color: #46a546;
                    }
                    </style>
        ";
    }

    /**
     * Inits system messages helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

     /**
     * Sets current user login
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Initalizes system cache object
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Initalizes $poll_id
     * 
     * @return void
     */
    protected function setPollId() {
        if (wf_CheckGet(array('poll_id'))) {
            $this->poll_id = vf($_GET['poll_id']);
        }
    }

    /**
     * Loads admis Name
     * 
     * @return void
     */
    protected function loadAdminsName() {
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        if ( ! empty($employeeLogins)) {
            foreach ($employeeLogins as $login => $name){
                $this->adminsName[$login] = $name;
            }
        }
    }

    /**
     * Init admin Name
     * 
     * @param string $admin
     * @return void
     */
    protected function initAdminName($admin) {
        $result = '';
        if ( ! empty($admin)) {
            $result = (isset($this->adminsName[$admin])) ? $this->adminsName[$admin] : $admin;
        }
        return ($result);
    }

     /**
     * Loads All avaible Polls from cache
     * 
     * @return array
     */
    protected function loadAvaiblePollsCached() {
        $obj = $this;
        $polls_arr = $this->cache->getCallback('POLLS', function() use ($obj) {
                    return ($obj->loadAvaiblePolls());
                    }, $this->cacheTime);
        if ( ! empty($polls_arr)) {
            foreach ($polls_arr as $key => $data) {
                $this->pollsAvaible[$data['id']]['title'] = $data['title'];
                $this->pollsAvaible[$data['id']]['enabled'] = $data['enabled'];
                $this->pollsAvaible[$data['id']]['start_date'] = $data['start_date'];
                $this->pollsAvaible[$data['id']]['end_date'] = $data['end_date'];
                $this->pollsAvaible[$data['id']]['params'] = $data['params'];
                $this->pollsAvaible[$data['id']]['admin'] = $data['admin'];
            }
        }
    }

     /**
     * Loads All avaible Polls from databases
     * 
     * @return array
     */
    public function loadAvaiblePolls() {
        $query = "SELECT * FROM `polls` ORDER BY `id` ASC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Loads all avaible polls options from cache
     * 
     * @return array pollsOptions
     * @return array pollsOptionsCount
     */
    protected function loadPollsOptionsCached() {
        $obj=$this;
        $option_arr = $this->cache->getCallback('POLLS_OPTIONS', function() use ($obj) {
                    return ($obj->loadPollsOptions());
                    }, $this->cacheTime);
        if ( ! empty($option_arr)) {
            foreach ($option_arr as $data) {
                $this->pollsOptions[$data['poll_id']][$data['id']] = $data['text'];
            }
            foreach ($this->pollsOptions as $id => $data) {
                $this->pollsOptionsCount[$id] = count($data);
            }
        }
    }

    /**
     * Loads all avaible polls options from databases
     * 
     * @return array pollsOptions
     * @return array pollsOptionsCount
     */
    public function loadPollsOptions() {
        $query = "SELECT * FROM `polls_options` ORDER BY `id` ASC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Check for last cache data and if need clean
     * 
     * @return void
     */
    protected function pollsVotesCacheInfoClean($poll_id) {
        $query = "SELECT `id` FROM `polls_votes` WHERE `poll_id` = '" . $poll_id . "' ORDER BY `id` DESC LIMIT 1";
        $last_db_uniqueid = simple_query($query);
        $last_cache_id = $this->cache->get('POLL_' . $poll_id . '_VOTES_LAST', $this->cacheTime);
        if ($last_db_uniqueid != $last_cache_id) {
            $this->cache->delete('POLL_' . $poll_id . '_VOTES', $this->cacheTime);
            $this->cache->set('POLL_' . $poll_id . '_VOTES_LAST', $last_db_uniqueid, $this->cacheTime);
        }
    }

     /**
     * Loads all avaible votes result from cache
     * 
     * @return array pollsVotes
     * @return array pollsVotesCount
     * @return array pollsOptionVotesCount
     */
    protected function loadPollsVotesCached($poll_id = '') {
        // Initialises poll_id
        $poll_id = ($poll_id) ? $poll_id : $this->poll_id;

        if (isset($this->pollsAvaible[$poll_id])) {
            // Check for needed cache by poll_id
            $this->pollsVotesCacheInfoClean($poll_id);
            $obj=$this;
            $votes_arr = $this->cache->getCallback('POLL_' . $poll_id . '_VOTES', function() use ($poll_id,$obj) {
                        return ($obj->loadPollsVotes($poll_id));
                        }, $this->cacheTime);
            if ( ! empty($votes_arr)) {
                foreach ($votes_arr as $data) {
                    $this->pollsVotes[$data['poll_id']]['id'][$data['login']] = $data['id'];
                    $this->pollsVotes[$data['poll_id']]['option_id'][$data['login']] = $data['option_id'];
                    $this->pollsVotes[$data['poll_id']]['date'][$data['login']] = $data['date'];
                }
                // Count poll votes
                    $this->pollsVotesCount[$data['poll_id']] = count($this->pollsVotes[$data['poll_id']]['id']);
                // Count votes by options
                    $this->pollsOptionVotesCount[$data['poll_id']] = array_count_values($this->pollsVotes[$data['poll_id']]['option_id']);
            }
        }
    }

     /**
     * Loads all avaible votes result from databases
     * 
     * @return array polls_votes
     */
    public function loadPollsVotes($poll_id) {
        $query = "SELECT * FROM `polls_votes` WHERE `poll_id` = '" . $poll_id . "'";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Create poll on database
     * 
     * @param int $title, $status, $startDateTime, $endDateTime, $endDateTime, $parametr = ''
     * @return void
     */
    protected function createPoll($title, $status, $startDateTime, $endDateTime, $parametr = '') {
        $poll_id = '';
        $query = "INSERT INTO `polls` (`id`, `title`, `enabled`, `start_date`, `end_date`, `params`, `admin`)
                  VALUES (NULL, '" . mysql_real_escape_string($title) . "', '" . $status . "', '" . $startDateTime . "', '" . $endDateTime . "', '" . mysql_real_escape_string($parametr) . "', '" . mysql_real_escape_string($this->myLogin) . "')";
        nr_query($query);
        $query_poll_id = "SELECT LAST_INSERT_ID() as id";
        $poll_id = simple_query($query_poll_id);
        $poll_id = $poll_id['id'];
        $this->cache->delete('POLLS', $this->cacheTime);
        return ($poll_id);
    }

    /**
     * Change poll data on database
     * 
     * @param int $poll_id, array $new_poll_data
     * @return void
     */
    protected function editPoll($poll_id, $new_poll_data) {
        $old_poll_data =  $this->pollsAvaible[$poll_id];
        $diff_data = array_diff_assoc($new_poll_data, $old_poll_data);
        if ( ! empty($diff_data)) {
            foreach ($diff_data as $field => $value) {
                simple_update_field('polls', $field, mysql_real_escape_string($value), "WHERE `id`='" . $poll_id . "'");
            }
            $this->cache->delete('POLLS', $this->cacheTime);
            log_register('POLL UPDATE [' . $poll_id . '] `' . $this->pollsAvaible[$poll_id]['title'] . '`');
        }
    }

    /**
     * Delete poll  from database
     * 
     * @param int $poll_id
     * @return void
     */
    protected function deletePoll($poll_id) {
        $this->deletePollOptions($poll_id);
        $query = "DELETE FROM `polls` WHERE `id` ='" . $poll_id . "'";
        nr_query($query);
        $this->cache->delete('POLLS', $this->cacheTime);
    }

    /**
     * Delete polls options  from database
     * 
     * @param int $poll_id
     * @return void
     */
    protected function deletePollOptions($poll_id) {
        $this->deletePollVotes($poll_id);
        $query = "DELETE FROM `polls_options` WHERE `poll_id` ='" . $poll_id . "'";
        nr_query($query);
        $this->cache->delete('POLLS_OPTIONS', $this->cacheTime);
    }

    /**
     * Delete polls votes from database
     * 
     * @param int $poll_id
     * @return void
     */
    protected function deletePollVotes($poll_id) {
        $query = "DELETE FROM `polls_votes` WHERE `poll_id` ='" . $poll_id . "'";
        nr_query($query);
        $this->cache->delete('POLL_' . $poll_id . '_VOTES', $this->cacheTime);
        $this->cache->delete('POLL_' . $poll_id . '_VOTES_LAST', $this->cacheTime);
    }

    /**
     * Change poll options on database
     * 
     * @param int $poll_id, array $poll_options
     * @return void
     */
    protected function editPollConfigs($poll_id, $poll_options) {
        $update_cache = FALSE;
        if (! isset ($this->pollsOptions[$poll_id])) {
            foreach ($poll_options as $value) {
                $query = "  INSERT INTO `polls_options` (`id`, `poll_id`, `text`)
                            VALUES (NULL, '" . $poll_id . "', '" . mysql_real_escape_string($value) . "')";
                nr_query($query);
            }
            $update_cache = TRUE;
            log_register('POLL OPTIONS CREATE [' . $poll_id . ']');
        } else {
            $need_create = array_diff_key($poll_options, $this->pollsOptions[$poll_id]);
                if ($need_create) {
                    foreach ($need_create as $value) {
                        $query = "  INSERT INTO `polls_options` (`id`, `poll_id`, `text`)
                                    VALUES (NULL, '" . $poll_id . "', '" . mysql_real_escape_string($value) . "')";
                        nr_query($query);
                    }
                    $update_cache = TRUE;
                }
            // Search options that need delete from database. Return as $key => $id (id option on database)
            $need_delete = array_keys(array_diff_key($this->pollsOptions[$poll_id], $poll_options));
                if ($need_delete) {
                    foreach ($need_delete as $id) {
                        $query = "DELETE from `polls_options` WHERE `id`='" . $id . "';";
                        nr_query($query);
                        $query_votes = "DELETE from `polls_votes` WHERE `option_id`='" . $id . "';";
                        nr_query($query_votes);
                    }
                    $update_cache = TRUE;
                }
            $need_update = array_diff_assoc($poll_options, $need_create, $this->pollsOptions[$poll_id]);
            if ($need_update) {
                foreach ($need_update as $id => $value) {
                        simple_update_field('polls_options', 'text', mysql_real_escape_string($value), "WHERE `id`='" . $id . "'");
                }
                $update_cache = TRUE;
            }
        }
        // Delete Options cache if need
        if ($update_cache) {
            $this->cache->delete('POLLS_OPTIONS', $this->cacheTime);
        }
    }

    /**
     * Render poll status
     * 
     * @param int $poll_id
     * @return void
     */
    protected function renderPollStatus($poll_id = '') {
        $result = '';
        // Initialises poll_id
        $poll_id = ($poll_id) ? $poll_id : $this->poll_id;

        if (isset($this->pollsAvaible[$poll_id])) {
            if ($this->pollsAvaible[$poll_id]['enabled'] == 0 AND mktime() < strtotime($this->pollsAvaible[$poll_id]['end_date'])) {
                $result = wf_img('skins/icon_inactive.gif') . ' ' . __('Disabled');
            } elseif ($this->pollsAvaible[$poll_id]['enabled'] AND mktime() < strtotime($this->pollsAvaible[$poll_id]['start_date'])) {
                $result = wf_img('skins/yellow_led.png') . ' '.  __('Not yet started');
            } elseif (mktime() > strtotime($this->pollsAvaible[$poll_id]['end_date'])) {
                $result = wf_img('skins/icon_active2.gif') . ' ' . __('Finished');
            } elseif ($this->pollsAvaible[$poll_id]['enabled'] AND mktime() > strtotime($this->pollsAvaible[$poll_id]['start_date']) AND mktime() < strtotime($this->pollsAvaible[$poll_id]['end_date'])) {
                $result = wf_img('skins/icon_active.gif') . ' ' . __('In progress');
            }
        }
        return ($result);
    }

    /**
     * Renders Poll data
     * 
     * @param int $poll_id
     * @return string
     */
    protected function renderPollData() {
        $result = '';

        if (isset($this->pollsAvaible[$this->poll_id])) {
            $cells = wf_TableCell(__('Status'));
            $cells.= wf_TableCell(__('Start date'));
            $cells.= wf_TableCell(__('End date'));
            $cells.= wf_TableCell(__('Admin'));
            $rows = wf_TableRow($cells, 'row1');

            $window = @$this->poll_id . ' - ' . @$this->pollsAvaible[$this->poll_id]['title'];
            $cells = wf_TableCell($this->renderPollStatus($this->poll_id));
            $cells.= wf_TableCell($this->pollsAvaible[$this->poll_id]['start_date']);
            $cells.= wf_TableCell($this->pollsAvaible[$this->poll_id]['end_date']);
            $cells.= wf_TableCell($this->initAdminName($this->pollsAvaible[$this->poll_id]['admin']));
            $rows.= wf_TableRow($cells, 'row4');

            $table = wf_TableBody($rows, '', 0);

            $result.= show_window($window, $table);
        }

        return ($result);
    }

    /**
     * Renders Poll config container
     * 
     * @return string
     */
    public function renderFormPoll() {
        $result = '';

        // Preset start date and time 
        if (isset($this->pollsAvaible[$this->poll_id])) {
            $poll_action = 'editpoll';
            $poll_name = $this->pollsAvaible[$this->poll_id]['title'];
            $start_date = date("Y-m-d", strtotime($this->pollsAvaible[$this->poll_id]['start_date']));
            $start_time = date("H:i", strtotime($this->pollsAvaible[$this->poll_id]['start_date']));
            $end_date = date("Y-m-d", strtotime($this->pollsAvaible[$this->poll_id]['end_date']));
            $end_time = date("H:i", strtotime($this->pollsAvaible[$this->poll_id]['end_date']));
            $poll_status = $this->pollsAvaible[$this->poll_id]['enabled'];
            $post_submit = 'Save';
        } else {
            $poll_action = 'createpoll';
            $poll_name = '';
            $start_date = date("Y-m-d");
            $start_time = date("H:i");
            $end_date = '';
            $end_time = '';
            $poll_status = true;
            $post_submit = 'Create';
        }

        $cells = wf_TableCell(__('Poll title'));
        $cells.= wf_TableCell(wf_TextInput($poll_action  . '[title]', '', $poll_name, false, '27'));
        $rows = wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Start date'));
        $cells.= wf_TableCell(wf_DatePickerPreset($poll_action  . '[startdate]', $start_date) . wf_TimePickerPreset($poll_action  . '[starttime]', $start_time, '', false));
        $rows.= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('End date'));
        $cells.= wf_TableCell(wf_DatePickerPreset($poll_action  . '[enddate]', $end_date) . wf_TimePickerPreset($poll_action  . '[endtime]', $end_time, '', false));
        $rows.= wf_TableRow($cells, 'row2');
        
        $cells = wf_TableCell(__('Enabled'));
        $cells.= wf_TableCell(wf_CheckInput($poll_action  . '[enabled]', '', false, $poll_status));
        $rows.= wf_TableRow($cells, 'row2');

        $rows.= wf_TableRow(wf_TableCell(wf_Submit($post_submit)));

        $table = wf_TableBody($rows, '', 0);
        $result = wf_Form("", "POST", $table, 'glamour');

        return ($result);
    }

    /**
     * Renders Poll options preview container
     * 
     * @return string
     */
    public function renderPreviewPollOption() {
        $result = '';
        if (isset($this->pollsAvaible[$this->poll_id])) {
            if (isset($this->pollsOptions[$this->poll_id])) {
                $poll_options = $this->pollsOptions[$this->poll_id];
                $inputs = '';
                foreach ($poll_options as $id => $option) {
                    $inputs.= wf_RadioInput('option', $option, $id, true);
                }
                $result.= wf_Form("", "POST", $inputs, 'glamour polls');
            } else {
                $result.= $this->messages->getStyledMessage(__('You have not created any answer options yet'), 'info');
            }

            $result.= $this->renderPollData();

        } else {
            $result.= $this->messages->getStyledMessage(__('This poll does not exist'), 'error');
        }

        return ($result);
    }  
    
     /**
     * Poll control function 
     * 
     * @param array $polls_data
     * @return void
     */
    public function controlPoll(array $polls_data) {
        $result = '';
        $message_warn = '';
        if ( ! empty($polls_data)) {
            // Check poll name
            if ( ! empty($polls_data['title'])) {
                $name = ($polls_data['title']) ;
            } else {
                $message_warn.= $this->messages->getStyledMessage(__('Poll title cannot be empty'), 'warning');
            }

            // Check poll start time
            if ( ! empty($polls_data['startdate']) and ! empty($polls_data['starttime'])) {
                $startDateTime = date("Y-m-d H:i:s", strtotime(mysql_real_escape_string($polls_data['startdate'] . $polls_data['starttime'])));
            } else {
                $message_warn.= $this->messages->getStyledMessage(__('Poll start time cannot be empty'), 'warning');
            }

            // Check poll end time
            if ( ! empty($polls_data['enddate']) and ! empty($polls_data['endtime'])) {
                $endDateTime = date("Y-m-d H:i:s", strtotime(mysql_real_escape_string($polls_data['enddate'] . $polls_data['endtime'])));
            } else {
                $message_warn.= $this->messages->getStyledMessage(__('Poll end time cannot be empty'), 'warning');
            }

            // Check that poll end time more that start time
            if (isset($startDateTime) AND isset($endDateTime) AND strtotime($startDateTime) >= strtotime($endDateTime)) {
                $message_warn.= $this->messages->getStyledMessage(__('Poll start time cannot be more than end time'), 'warning');
            }

            // Check poll status enabled
            $status = (isset($polls_data['enabled'])) ? 1 : 0;

            // Check that we dont have warning message and create poll
            if (empty($message_warn) and @$_POST['createpoll']) {
                $poll_id = $this->createPoll($name, $status, $startDateTime, $endDateTime, $parametr = '');
                // Check that we create poll, get his $poll_id and redirect to create variants
                if ($poll_id) {
                    rcms_redirect(self::URL_ME . '&action=polloptions&poll_id=' . $poll_id);
                }
            } elseif (empty($message_warn) and @$_POST['editpoll']) {
                $new_poll_data = array('title' => $name, 'enabled' => $status, 'start_date' => $startDateTime, 'end_date' => $endDateTime, 'params' => $parametr = '');
                $this->editPoll($this->poll_id, $new_poll_data);
                rcms_redirect(self::URL_ME . '&action=polloptions&poll_id=' . $this->poll_id);
            }
        } else {
            $result.= $this->messages->getStyledMessage(__('Poll data cannot be empty '), 'warning');
        }

        $result.= $message_warn;

        return ($result);
    }
    
     /**
     * Poll options control  
     * 
     * @param array $poll_options
     * @return void
     */
    public function controlPollOptions(array $poll_options) {
        $result = '';
        $message_warn = '';
        if ( ! empty($poll_options)) {
            // Count options
            if (count($poll_options) < 2) {
                $message_warn = $this->messages->getStyledMessage(__('The number of options cannot be less than two'), 'warning');
            }
            // Check for empty options value
            if (array_intersect($poll_options, array(''))) {
                $message_warn = $this->messages->getStyledMessage(__('Options for voting responses can not be empty'), 'warning');
            }
            // Check that we dont have warning message and create poll
            if (empty($message_warn) AND @$_POST['polloptions'] AND isset($this->pollsAvaible[$this->poll_id])) {
                $this->editPollConfigs($this->poll_id, $poll_options);
                // If dont have Message warninng  - go to Poll option preview container
                rcms_redirect(self::URL_ME . '&show_options=true&poll_id=' . $this->poll_id);
            }
        } else {
            $result.= $this->messages->getStyledMessage(__('Poll options cannot be empty'), 'warning');
        }

        $result.= $message_warn;
        return ($result);
    }

     /**
     * Renders Polls options from container
     * 
     * @param int $poll_id
     * @return string
     */
    public function renderFormPollOption() {
        $result = '';
        $form = '';
        if (isset($this->pollsAvaible[$this->poll_id])) {
            // Form parameter for future, if on next we want use this function global
            $method = "POST";
            $input_name = "polloptions";
            $input_width = 150;
            $label = 'Option';
            $value = '';

            // Loads needed function 
            $this->loadPollJavaScript($input_name, $label, $value, $input_width);
            $this->loadPollCss();
            
            $form = $this->pollJavaScript;
            $form.= $this->pollCss;
            // Create form
             $form.= '
                        <form method="' . $method . '">
                            <div id="add_field_area">';
            if (isset($this->pollsOptions[$this->poll_id])) {
                $n = 1;
                foreach ($this->pollsOptions[$this->poll_id] as $opt_id => $text) {
                    $form.= '
                            <div id="add' . $n . '" class="add">
                                <label>' . __($label) . ' №' . $n . '</label>
                                <input type="text" width="' . $input_width . '" name="' . $input_name . '[' . $opt_id . ']" id="' . $input_name . '" value="' . $text . '"/>';
                            if ($n >= 3 ) {
                                $form.= '<div class="deletebutton" onclick="if(!confirm(\'' . __('Be careful! If you delete the option, you also delete the poll results by this option.') . '\')) {return false;} deleteField(\'' . $n . '\');"></div>';
                            }
                    $form.= '
                            </div>';
                    $n++;
                }
            } else {
             $form.= '
                                <div id="add1" class="add">
                                    <label>' . __($label) . ' №1</label>
                                    <input type="text" width="' . $input_width . '" name="' . $input_name . '[]" id="' . $input_name . '" value="' . $value . '"/>
                                </div>
                                <div id="add2" class="add">
                                    <label>' . __($label) . ' №2</label>
                                    <input type="text" width="' . $input_width . '" name="' . $input_name . '[]" id="' . $input_name . '" value="' . $value . '"/>
                                </div>';
            }
             $form.= '
                            </div>';
             $form.= '
                            <div onclick="addField();" class="addbutton">' . __('Add new field') . '</div>
                            <div>
                                <input type="submit" value="' . __('Save') . '" class="createbutton">
                            </div>
                        </form>
            ';
        }

        $result.= $this->renderPollData();
        $result.= $form;

        return ($result);
    }

    /**
     * Renders polls module control panel
     * 
     * @return void
     */
    public function panel() {
        $result = '';
        // Add backlink
        if (wf_CheckGet(array('action')) OR wf_CheckGet(array('show_options'))) {
            $result.= wf_BackLink(self::URL_ME);
        }

        if (cfr('POLLSCONFIG') AND @$_GET['action'] != 'create_poll') {
            $result.= wf_Link(self::URL_ME . '&action=create_poll', wf_img('skins/add_icon.png') . ' ' . __('Create poll'), false, 'ubButton') . ' ';
        }

        if (cfr('POLLSREPORT') AND @$_GET['action'] != 'create_poll') {
            $result.= wf_Link('index.php?module=report_polls', wf_img('skins/icon_star.gif') . ' ' . __('Show voting results'), false, 'ubButton') . ' ';
        }

        if (cfr('POLLSCONFIG') AND @$_GET['action'] == 'polloptions' OR (cfr('POLLSCONFIG') AND wf_CheckGet(array('show_options'))) AND isset($this->pollsAvaible[$this->poll_id])) {
            $result.= wf_Link(self::URL_ME . '&action=edit_poll&poll_id=' . $this->poll_id, wf_img('skins/icon_extended.png') . ' ' . __('Configure Poll'), false, 'ubButton') . ' ';
        }

        if (cfr('POLLSCONFIG') AND (@$_GET['action'] == 'edit_poll' OR wf_CheckGet(array('show_options'))) AND isset($this->pollsAvaible[$this->poll_id])) {
            $result.= wf_Link(self::URL_ME . '&action=polloptions&poll_id=' . $this->poll_id, wf_img('skins/icon_edit.gif') . ' ' . __('Edit answer options'), false, 'ubButton') . ' ';
        }

        if (cfr('POLLSCONFIG') AND wf_CheckGet(array('action')) AND $_GET['action'] != 'create_poll' AND isset($this->pollsAvaible[$this->poll_id])) {
            $result.= wf_Link(self::URL_ME . '&show_options=true&poll_id=' . $this->poll_id, wf_img('skins/icon_eye.gif') . ' ' . __('Show preliminary voting form'), false, 'ubButton') . ' ';
        }

        return ($result);
    }

    /**
     * Deletes all data about poll from database by ID
     * 
     * @param int $poll_id
     * @return void
     */
    public function deletePollData() {
        if(isset($this->pollsAvaible[$this->poll_id])) {
            $this->deletePoll($this->poll_id);
        }
        rcms_redirect(self::URL_ME);
    }

    /**
     * Renders polls module control panel interface
     * 
     * @return string
     */
    public function renderAvaiblePolls() {
        $columns = array('ID', 'Poll title', 'Status', 'Start date', 'End date', 'Number of votes', 'Number of options', 'Admin');
        if (cfr('POLLSCONFIG')) {
            $columns[] = 'Actions';
        }
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxavaiblepolls=true', false, 'polls', 100, $opts);
        return ($result);
    }

    /**
     * Renders json formatted data about Polls
     * 
     * @return void
     */
    public function ajaxAvaiblePolls() {
        $json = new wf_JqDtHelper();
        if (!empty($this->pollsAvaible)) {
            foreach ($this->pollsAvaible as $poll_id => $poll) {
                $this->loadPollsVotesCached($poll_id);
                $acts = '';
                if (cfr('POLLSCONFIG')) {
                    $acts.= wf_JSAlert(self::URL_ME . '&action=delete_poll&poll_id=' . $poll_id, web_delete_icon(), 'If you delete this poll, you will delete all data including voting results') . ' ';
                    $acts.= wf_JSAlert(self::URL_ME . '&action=edit_poll&poll_id=' . $poll_id, web_icon_extended('Configure Poll'), 'Are you serious') . ' ';
                }
                if (isset($this->pollsOptionsCount[$poll_id])) {
                    $options = $this->pollsOptionsCount[$poll_id];
                    $options.= ' ' . wf_Link(self::URL_ME . '&show_options=true&poll_id=' . $poll_id, web_icon_search('Show preliminary voting form'));
                    if (cfr('POLLSCONFIG')) {
                        $options.= wf_JSAlert(self::URL_ME . '&action=polloptions&poll_id=' . $poll_id, ' ' . web_edit_icon('Edit answer options'), 'Are you serious') . ' ';
                    }
                } else {
                    $options = 0;
                    if (cfr('POLLSCONFIG')) {
                        $options.= ' ' . wf_Link(self::URL_ME . '&action=polloptions&poll_id=' . $poll_id, web_icon_create('Create answer options'));
                    }
                }
                if (isset($this->pollsVotesCount[$poll_id])) {
                    $votes = $this->pollsVotesCount[$poll_id];
                    $votes.= ' ' . wf_Link('?module=report_polls&action=show_poll_votes&poll_id=' . $poll_id, web_stats_icon('View poll results'));
                } else {
                    $votes = 0;
                }

                $data[] = $poll_id;
                $data[] = $poll['title'];
                $data[] = $this->renderPollStatus($poll_id);
                $data[] = $poll['start_date'];
                $data[] = $poll['end_date'];
                $data[] = $votes;
                $data[] = $options;
                $data[] = $this->initAdminName($poll['admin']);
                
                if (cfr('POLLSCONFIG')) {
                    $data[] = $acts;
                }

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }
}

?>

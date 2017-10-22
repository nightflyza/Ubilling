<?php

class PollsReport extends Polls {

    /**
     * returns all addres
     *
     * @var array as login => full adress
     */
    protected $alladdress = array();

    const URL_REPORT = '?module=report_polls';

    public function __construct() {
        $this->initMessages();
        $this->initCache();
        $this->setPollId();
        $this->loadAdminsName();
        $this->getFulladdress();
        $this->loadAvaiblePollsCached();
        $this->loadPollsOptionsCached();
        $this->loadPollsVotesCached();
    }

    /**
     * Loads full address list from cache
     * 
     * @return void
     */
    protected function getFulladdress() {
        $this->alladdress = zb_AddressGetFulladdresslistCached();
        $this->numberAllUsers = count($this->alladdress);
    }

    /**
     * Loads the number of all users 
     * 
     * @return string
     */
    protected function getNumberAllUsers() {
        $result = count($this->alladdress);
        return ($result);
    }

    /**
     * Draw 3DPie about poll votes
     * 
     * @return void
     */
    protected function draw3DPie($poll_id) {
        // Create parametr for Number of voters
        $params_users = array();
        $params_users[__('All users')] = $this->getNumberAllUsers();
        $params_users[__('Voted Users')] =  $this->pollsVotesCount[$poll_id];

        // Create parametr for votes
        $params_votes = array();
        foreach ($this->pollsVotes[$poll_id]['option_id'] as $login => $value) {
            $string = $this->pollsOptions[$poll_id][$value];
            $count = $this->pollsOptionVotesCount[$poll_id][$value];
            $params_votes[$string] = $count;
        }

        $chartOptsUsers = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";
        $chartUsers = wf_gcharts3DPie($params_users, __('Number of voted users'), '400px', '400px', $chartOptsUsers);

        $chartOptsVotes = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, pieSliceText: 'value-and-percentage', ";
        $chartVotes= wf_gcharts3DPie($params_votes, __('Number of votes'), '400px', '400px', $chartOptsVotes);

        $cells = wf_TableCell($chartUsers);
        $cells.= wf_TableCell($chartVotes);
        $rows = wf_TableRow($cells);
        $votes_3D = wf_TableBody($rows, '100%', 0, '');

        $result = show_window(__('Number of votes on a 3D chart'), $votes_3D);

        return ($result);
    }

    /**
     * Returns polls search form
     * 
     * @return string
     */
    protected function renderPollsSearchForm() {

        $param_selector_status = array(
                                        '',
                                        'disabled' => __('Disabled'),
                                        'nostarted' => __('Not yet started'),
                                        'finished' => __('Finished'),
                                        'progress' => __('In progress'),
                                        );
        $param_selector_polls = array('');
        foreach ($this->pollsOptions as $poll_id => $poll_opt) {
            $param_selector_polls[$poll_id] = $this->pollsAvaible[$poll_id]['title'];
        }

        $cells = wf_TableCell(__('Poll'));
        $cells.= wf_TableCell(wf_RadioInput('polls_search[search_by]', '', 'poll_id', false));
        $cells.= wf_TableCell(wf_Selector('polls_search[poll_id]', $param_selector_polls, '', $this->poll_id, false));
        $rows = wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(wf_RadioInput('polls_search[search_by]', '', 'date', false));
        $cells.= wf_TableCell(wf_DatePickerPreset('polls_search[date][start_date]', '') . ' ' . __('From') . wf_DatePickerPreset('polls_search[date][end_date]', '') . ' ' . __('To'));
        $rows.= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Status'));
        $cells.= wf_TableCell(wf_RadioInput('polls_search[search_by]', '', 'status', false, false));
        $cells.= wf_TableCell(wf_Selector('polls_search[status]', $param_selector_status, '', '',  false));
        $rows.= wf_TableRow($cells, 'row2');

        $rows.= wf_TableRow(wf_TableCell(wf_Submit('Search')));

        $form = wf_TableBody($rows, '', 0);
        $result = show_window(__('Search polls'), wf_Form("", "POST", $form, 'glamour'));

        return ($result);
    }

    /**
     * Loads the number of all users 
     * 
     * @return string
     */
    protected function searchPollsOptions($search_data) {
        $result = array();
        $where = '';
        $query = "SELECT `polls`.`id` AS `polls_id`,`polls_options`.`id`,`polls_options`.`text` FROM `polls_options` LEFT JOIN `polls` ON (`polls_options`.`poll_id` = `polls`.`id`) "; // On lust must be space
        if ($search_data['search_by'] == 'poll_id') {
            $where = " WHERE `polls`.`id` = '" . $search_data['poll_id'] . "'";
        }
        if ($search_data['search_by'] == 'date') {
            if ($search_data['date']['start_date'] AND $search_data['date']['end_date']) {
                $where = " WHERE `start_date` > '" . $search_data['date']['start_date']. "' AND `end_date` < '" . $search_data['date']['end_date']. "'";
            } elseif ($search_data['date']['start_date'] AND ! $search_data['date']['end_date']) {
                $where = " WHERE `start_date` > '" . $search_data['date']['start_date']. "'";
            } elseif (! $search_data['date']['start_date'] AND $search_data['date']['end_date']) {
                $where = " WHERE `end_date` < '" . $search_data['date']['end_date']. "'";
            }
        }
        if ($search_data['search_by'] == 'status') {
            if ($search_data['status'] == 'disabled') {
                $where = " WHERE `polls`.`enabled` = '0' AND `end_date` > '" . date("Y-m-d H:i:s"). "'";
            } elseif ($search_data['status'] == 'nostarted') {
                $where = " WHERE `polls`.`enabled` = '1' AND `start_date` > '" . date("Y-m-d H:i:s"). "'";
            } elseif ($search_data['status'] == 'finished') {
                $where = " WHERE `end_date` < '" . date("Y-m-d H:i:s"). "'";
            } elseif ($search_data['status'] == 'progress') {
                $where = " WHERE `polls`.`enabled` = '1' AND `start_date` < '" . date("Y-m-d H:i:s"). "' AND `end_date` > '" . date("Y-m-d H:i:s"). "'";
            }
        }
        if ($where) {
            $get_result = simple_queryall($query . $where . " ORDER BY `polls_id` ASC");
            if ($get_result){
                foreach ($get_result as $data) {
                    $result[$data['polls_id']][$data['id']] = $data['text'];
                }
            }
        }

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
        if (wf_CheckGet(array('action')) OR wf_CheckGet(array('show_votes')) OR wf_CheckPost(array('polls_search'))) {
            $result.= wf_BackLink(self::URL_REPORT);
        }

        if (cfr('POLLS')) {
            $result.= wf_Link(self::URL_ME, wf_img('skins/icon_star.gif') . ' ' . __('Show polls'), true, 'ubButton') . ' ';
        }

        return ($result);
    }

    /**
     * Renders polls module control panel interface
     * 
     * @return string
     */
    public function renderPollsSearchVotes(array $search_data) {
        $result = '';
        if ( ! empty($search_data)) {
            // Check for empty search value
            if (! isset($search_data['search_by'])) {
                $result.= show_window('', $this->messages->getStyledMessage(__('You did not select the search parameter'), 'warning'));
            } else {
                $search_results = $this->searchPollsOptions($search_data);
                if ( ! empty($search_results)) {
                    foreach ($search_results as $poll_id => $poll_opt) {
                        $window = __('ID') . ': ' . $poll_id;
                        $window.= ', ' . __('Title') . ': ' . $this->pollsAvaible[$poll_id]['title'];
                        $window.= ', ' . __('Status') . ': ' . $this->renderPollStatus($poll_id);
                        $window.= ', ' . __('Actions') . ': ' . wf_Link(self::URL_REPORT . '&action=show_poll_votes&poll_id=' . $poll_id, web_stats_icon('View poll results'));
                        $columns = array('ID', 'Options', 'Number of votes', 'Visual', 'Actions');
                        $opts = '"order": [[ 0, "asc" ]]';
                        $loader = wf_JqDtLoader($columns, self::URL_REPORT . '&ajaxavaiblevotes=true&poll_id=' . $poll_id, false, 'Option', 100, $opts);
                        $result.= show_window($window, $loader);
                    }
                } else {
                    $result.= show_window('', $this->messages->getStyledMessage(__('Empty reply received'), 'warning'));
                }
            }
        }

        return ($result);
    }

    /**
     * Renders polls module control panel interface
     * 
     * @return string
     */
    public function renderOptionVotes() {
        $result = '';
        $opt_arr = array();
        $opt_name = '';
        if (isset($this->pollsAvaible[$this->poll_id])) {
            $result.= $this->renderPollData($this->poll_id);

            $cells = wf_TableCell(__('ID'));
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Address'));
            $rows = wf_TableRow($cells, 'row2');

            // Check that we get option_id and array optionVotes have this option
            if (wf_CheckGet(array('option_id'))) {
                $option_id = vf($_GET['option_id']);

                if (isset($this->pollsOptions[$this->poll_id][$option_id])) {
                    if (isset($this->pollsVotes[$this->poll_id])) {
                        $opt_arr = array_keys($this->pollsVotes[$this->poll_id]['option_id'], $option_id);

                        if ( ! empty($opt_arr) ) {
                            foreach ($opt_arr as $login) {
                                $address = (isset($this->alladdress[$login])) ? $this->alladdress[$login] : '';

                                $cells = wf_TableCell($this->pollsVotes[$this->poll_id]['date'][$login]);
                                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $login, $login, false));
                                $cells.= wf_TableCell($address);
                                $rows.= wf_TableRow($cells, 'row3');
                            }

                            $opt_name = $this->pollsOptions[$this->poll_id][$option_id];
                            $table = wf_TableBody($rows, '100%', 0);
                            $result.= show_window(__('Voting results for option') . ': ' . $opt_name, $table);
                        } else {
                            $result.= show_window('', $this->messages->getStyledMessage(__('For this option, no one voted yet'), 'info'));
                        }
                    } else {
                        $result.= show_window('', $this->messages->getStyledMessage(__('No one voted on this poll yet'), 'info'));
                    }
                 } else {
                    $result.= show_window('', $this->messages->getStyledMessage(__('The answer option passed to the module does not exist'), 'error'));
                 }
             } else {
                $result.= show_window('', $this->messages->getStyledMessage(__('The module was not given the answer option'), 'error'));
             }
        } else {
            $result.= show_window('', $this->messages->getStyledMessage(__('This poll does not exist'), 'error'));
        }

        return ($result);
    }

    /**
     * Renders polls module control panel interface
     * 
     * @return string
     */
    public function renderAvaibleVotes() {
        $result = '';
        // Render search poll form
        $result.= $this->renderPollsSearchForm();
        if (wf_CheckPost(array('polls_search'))) {
            $result.= $this->renderPollsSearchVotes($_POST['polls_search']);
        } else {
            foreach (array_reverse($this->pollsOptions, TRUE) as $poll_id => $poll_opt) {

                $window = __('ID') . ': ' . $poll_id;
                $window.= ', ' . __('Title') . ': ' . $this->pollsAvaible[$poll_id]['title'];
                $window.= ', ' . __('Status') . ': ' . $this->renderPollStatus($poll_id);
                $window.= ', ' . __('Actions') . ': ' . wf_Link(self::URL_REPORT . '&action=show_poll_votes&poll_id=' . $poll_id, web_stats_icon('View poll results'));
                $columns = array('ID', 'Options', 'Number of votes', 'Visual', 'Actions');
                $opts = '"order": [[ 0, "asc" ]]';
                $loader = wf_JqDtLoader($columns, self::URL_REPORT . '&ajaxavaiblevotes=true&poll_id=' . $poll_id, false, 'Option', 100, $opts);
                $result.= show_window($window, $loader);
            }
        }

        return ($result);
    }

    /**
     * Renders json formatted data about Polls
     * 
     * @return void
     */
    public function ajaxAvaibleVotes() {
        $json = new wf_JqDtHelper();
        if(isset($this->pollsAvaible[$this->poll_id])) {

            foreach ($this->pollsOptions[$this->poll_id] as $id => $options) {
                if (isset($this->pollsOptionVotesCount[$this->poll_id][$id])) {
                    $votes = $this->pollsOptionVotesCount[$this->poll_id][$id];
                    $act = ' ' . wf_Link(self::URL_REPORT . '&action=show_option_votes&poll_id=' . $this->poll_id . '&option_id=' . $id, web_icon_search());
                } else {
                    $votes = 0;
                    $act = '';
                }
                $total_votes = (isset($this->pollsVotesCount[$this->poll_id])) ? $this->pollsVotesCount[$this->poll_id] : 0;

                $data[] = $id;
                $data[] = $options;
                $data[] = $votes;
                $data[] = web_bar($votes, $total_votes);
                $data[] = $act;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders polls votes control panel
     * 
     * @return string
     */
    public function renderPollVotes() {
        $result = '';
        if(isset($this->pollsAvaible[$this->poll_id])) {
            if (isset($this->pollsVotes[$this->poll_id])) {
                $result.= $this->renderPollData($this->poll_id);
                $result.= $this->draw3DPie($this->poll_id);
                $columns = array('ID', 'Option', 'Date', 'User', 'Address');
                $opts = '"order": [[ 0, "desc" ]]';
                $result = show_window(__('Poll results'), wf_JqDtLoader($columns, self::URL_REPORT . '&ajaxapollvotes=true&poll_id=' . $this->poll_id, false, 'votes', 100, $opts));
            } else {
                $result.= show_window('', $this->messages->getStyledMessage(__('No one voted on this poll yet'), 'info'));
            }
        } else {
            $result.= show_window('', $this->messages->getStyledMessage(__('This poll does not exist'), 'error'));
        }
        return ($result);
    }

    /**
     * Renders json formatted data about Polls votes
     * 
     * @return void
     */
    public function ajaxPollVotes() {

        $json = new wf_JqDtHelper();
        if(isset($this->pollsAvaible[$this->poll_id])) {

            foreach ($this->pollsVotes[$this->poll_id]['id'] as $login => $value) {
                $address = (isset($this->alladdress[$login])) ? $this->alladdress[$login] : '';

                $data[] = $value;
                $data[] = $this->pollsOptions[$this->poll_id][$this->pollsVotes[$this->poll_id]['option_id'][$login]];
                $data[] = $this->pollsVotes[$this->poll_id]['date'][$login];
                $data[] = wf_Link('?module=userprofile&username=' . $login, $login, false);
                $data[] = $address;

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }
}

?>

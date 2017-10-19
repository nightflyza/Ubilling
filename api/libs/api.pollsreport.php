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
    }

    /**
     * Draw 3DPie about poll votes
     * 
     * @return void
     */
    protected function draw3DPie($poll_id) {
        // Create parametr for Number of voters
        $params_users = array();
        $query = "SELECT COUNT(1) AS `count` FROM `users`";
        $all_users = simple_query($query);
        $params_users[__('All users')] = $all_users['count'];
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
     * Renders polls module control panel
     * 
     * @return void
     */
    public function panel() {
        $result = '';
        // Add backlink
        if (wf_CheckGet(array('action')) OR wf_CheckGet(array('show_votes'))) {
            $result.= wf_BackLink(self::URL_REPORT);
        }

        if (cfr('POLLS')) {
            $result.= wf_Link(self::URL_ME, wf_img('skins/icon_star.gif') . ' ' . __('Show polls'), false, 'ubButton') . ' ';
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
                            $this->getFulladdress();
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
        foreach ($this->pollsOptions as $poll_id => $poll_opt) {

            $window = __('ID') . ': ' . $poll_id;
            $window.= ', ' . __('Title') . ': ' . $this->pollsAvaible[$poll_id]['title'];
            $window.= ', ' . __('Status') . ': ' . $this->renderPollStatus($poll_id);
            $window.= ', ' . __('Actions') . ': ' . wf_Link(self::URL_REPORT . '&action=show_poll_votes&poll_id=' . $poll_id, web_stats_icon(__('Result')));
            $columns = array('ID', 'Options', 'Number of votes', 'Visual', 'Actions');
            $opts = '"order": [[ 0, "asc" ]]';
            $loader = wf_JqDtLoader($columns, self::URL_REPORT . '&ajaxavaiblevotes=true&poll_id=' . $poll_id, false, 'Option', 100, $opts);
            $result.= show_window($window, $loader);
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
        $this->getFulladdress();

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

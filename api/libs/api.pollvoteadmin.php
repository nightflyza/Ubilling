<?php

/**
 * Administrators Votes/Polls class
 */
class PollVoteAdmin {

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';
    
    public function __construct() {
        $this->setLogin();
        $this->checkBaseAvail();
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
     * Must prevent update troubles and make billing usable between 0.8.5 and 0.8.6 releases
     * 
     * @return bool
     */
    protected function checkBaseAvail() {
        if (version_compare(file_get_contents("RELEASE"), "0.8.5", ">")) {
            $query_check = "SHOW COLUMNS FROM `polls` WHERE FIELD = 'voting'";
            $result_check = simple_query($query_check);
            if (! $result_check) {
                $query = "ALTER TABLE `polls` ADD `voting` VARCHAR(255) NOT NULL DEFAULT 'Users'";
                nr_query($query);
            }
        }
    }

    /**
     * gets poll that user not voted yet
     * 
     * @return string
     */
    protected function loadPollForVoiting() {
        $result = '';
        $date = date("Y-m-d H:i:s");
        $query = "SELECT `id` FROM `polls`WHERE id NOT IN (SELECT poll_id FROM `polls_votes` "
                . "WHERE `login` = '" .$this->myLogin . "') "
                . "AND `enabled` = '1' "
                . "AND `start_date` <= '" . $date . "' "
                . "AND `end_date` >= '" . $date . "' "
                . "AND `voting` = 'Employee' "
                . "LIMIT 1";
        $result_q = simple_query($query);
        if ($result_q) {
            $result = $result_q['id'];
        }
        return ($result);
    }

    /**
     * Load poll data
     * 
     * @param type $poll_id
     * @return type
     */
    protected function getPollData($poll_id) {
        $result = array();
        $query = "SELECT * FROM `polls` WHERE `id` = '" . $poll_id . "'";
        $poll_data = simple_queryall($query);
        if ($poll_data) {
            foreach ($poll_data as $value) {
                $result['title'] = $value['title'];
                $result['start_date'] = $value['start_date'];
                $result['end_date'] = $value['end_date'];
                $result['id'] = $value['id'];
            }
        }
        return ($result);
    }

    /**
     * gets poll options
     * 
     * @return string
     */
    protected function loadPollOptoins($avaible_poll) {
        $result = array();
        $query = "SELECT `polls_options`.`id`,`poll_id`,`text` FROM `polls_options`
            LEFT JOIN `polls` ON (`polls_options`.`poll_id` = `polls`.`id`) 
            WHERE `polls`.`id` = '" . $avaible_poll . "' ORDER BY `polls_options`.`id`";
        $options = simple_queryall($query);
        if ($options) {
            foreach ($options as $value) {
                $result[$value['poll_id']][$value['id']] = $value['text'];
            }
        }
        return ($result);
    }

    /**
     * Renders Poll voiting form
     * 
     * @return string
     */
    public function renderVotingForm() {
        $result = '';
        $avaible_poll = $this->loadPollForVoiting();
        if ($avaible_poll) {
            $option_data = $this->loadPollOptoins($avaible_poll);
            if ($option_data) {
                $inputs = '';
                $poll_data = $this->getPollData($avaible_poll);
                foreach ($option_data[$avaible_poll] as $id => $option) {
                    $inputs.= wf_RadioInput('vote', $option, $id, true);
                }
                $inputs.= wf_HiddenInput('poll_id', $avaible_poll);
                $inputs.= wf_tag('br');
                $inputs.= wf_Submit('Vote');
                $form = wf_Form("", "POST", $inputs, 'glamour');

                $result = wf_modalOpened($poll_data['title'], $form, '600', '400');
            }
        }
        return ($result);
    }

    /**
     * Add user's vote to the database
     * 
     * @param type $option_id, $poll_id
     */
    public function createAdminVoteOnDB($option_id, $poll_id) {
        $check_query = "SELECT 1 FROM `polls_options` 
                        LEFT JOIN `polls` ON (`polls_options`.`poll_id` = `polls`.`id`) 
                        WHERE `poll_id` NOT IN (SELECT `poll_id` FROM `polls_votes` WHERE `login` = '" . $this->myLogin . "') 
                        AND `polls_options`.`id` = '" . $option_id . "'
                        AND `polls`.`id` = '" . $poll_id . "'";
        $check_result = simple_query($check_query);
        if ($check_result) {
            $date = date("Y-m-d H:i:s");
            $query = "INSERT INTO `polls_votes` (`id`, `date`, `option_id`, `poll_id`, `login`) 
                      VALUES (NULL, '" . $date . "', '" . $option_id . "', '" . $poll_id . "', '" . $this->myLogin . "');";
            nr_query($query);
        }
    }

}

?>

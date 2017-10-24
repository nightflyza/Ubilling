<?php

class Polls {
    protected $userLogin = '';
    
    public function __construct($login) {
        $this->setLogin($login);
    }

    /**
     * gets current user login
     * 
     * @return string
     */
    protected function setLogin($login) {
        $this->userLogin = $login;
    }

    /**
     * gets poll that user not voited yet
     * 
     * @return string
     */
    protected function loadPollForVoiting() {
        $result = '';
        $date = date("Y-m-d H:i:s");
        $query = "SELECT `id` FROM `polls`WHERE id NOT IN (SELECT poll_id FROM `polls_votes` "
                . "WHERE `login` = '" . $this->userLogin . "') "
                . "AND `enabled` = '1' "
                . "AND `start_date` <= '" . $date . "' "
                . "AND `end_date` >= '" . $date . "' "
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
     * Load users voites
     * 
     * @return string
     */
    protected function loadUserVoites() {
        $result = array();
        $query = "SELECT `title`,`start_date`,`end_date`,`text`,`date` 
                FROM `polls_votes` 
                LEFT JOIN `polls` ON (`polls_votes`.`poll_id` = `polls`.`id`)
                LEFT JOIN `polls_options` ON (`polls_votes`.`option_id` = `polls_options`.`id`) 
                WHERE `login` = '" . $this->userLogin . "'";
        $result = simple_queryall($query);
 
        return ($result);
    }

    /**
     * Renders Poll voiting form
     * 
     * @return string
     */
    public function renderVoitingForm() {
        $avaible_poll = $this->loadPollForVoiting();
        if ($avaible_poll) {
            $option_data = $this->loadPollOptoins($avaible_poll);
            if ($option_data) {
                $inputs = '';
                $poll_data = $this->getPollData($avaible_poll);
                foreach ($option_data[$avaible_poll] as $id => $option) {
                    $inputs.= la_RadioInput('voice', $option, $id, true);
                }
                $inputs.= la_HiddenInput('poll_id', $avaible_poll);
                $inputs.= la_tag('br');
                $inputs.= la_Submit('Voite');
                $form = la_Form("", "POST", $inputs, 'glamour');

                $result = la_modalOpened($poll_data['title'], $form);
            }
        }
        return ($result);
    }

    /**
     * Add user's voice to the database
     * 
     * @param type $option_id, $poll_id
     */
    public function createUserVoiceOnDB($option_id, $poll_id) {
        $check_query = "SELECT 1 FROM `polls_options` 
                        LEFT JOIN `polls` ON (`polls_options`.`poll_id` = `polls`.`id`) 
                        WHERE `poll_id` NOT IN (SELECT `poll_id` FROM `polls_votes` WHERE `login` = '" . $this->userLogin . "') 
                        AND `polls_options`.`id` = '" . $option_id . "'
                        AND `polls`.`id` = '" . $poll_id . "'";
        $check_result = simple_query($check_query);
        if ($check_result) {
            $date = date("Y-m-d H:i:s");
            $query = "INSERT INTO `polls_votes` (`id`, `date`, `option_id`, `poll_id`, `login`) 
                      VALUES (NULL, '" . $date . "', '" . $option_id . "', '" . $poll_id . "', '" . $this->userLogin . "');";
            nr_query($query);
        }
    }

    /**
     * Load user voites
     * 
     * @return void
     */
    public function renderUserVoites() {
        $result = '';
        $voites_data = $this->loadUserVoites();
        if ($voites_data) {
            $cells = la_TableCell(__('Title'));
            $cells.= la_TableCell(__('Start date'));
            $cells.= la_TableCell(__('End date'));
            $cells.= la_TableCell(__('Answer'));
            $cells.= la_TableCell(__('Voting date'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($voites_data as $value) {
                $cells = la_TableCell($value['title']);
                $cells.= la_TableCell($value['start_date']);
                $cells.= la_TableCell($value['end_date']);
                $cells.= la_TableCell($value['text']);
                $cells.= la_TableCell($value['date']);
                $rows.= la_TableRow($cells, 'row2');
            }

            $result = la_TableBody($rows, '100%', '');
        } else {
            $result = __('You have not yet responded to polls');
        }
        return ($result);
    }
}

?>

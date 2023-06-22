<?php

/**
 * User polls exporting class
 */
class PollsExport extends PollsReport {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains raw votes data
     *
     * @var array
     */
    protected $allPollsVotesRaw = array();

    /**
     * Contains preprocessed votes data for each poll as:
     * pollId=>[login=>login/date/option_id]
     *
     * @var array
     */
    protected $allPollsVotesProcessed = array();

    /**
     * Contains current instance URL to push some data
     *
     * @var string
     */
    protected $exportUrl = '';

    /**
     * Name of POST variable to export updated users data
     *
     * @var string
     */
    protected $exportVar = '';

    /**
     * Export URL HTTP abstraction placeholder
     *
     * @var object
     */
    protected $apiCrm = '';

    /**
     * Some predefined stuff here
     */
    const EXPORT_PID = 'BTRX42_POLLS';

    /**
     * Там, де річка, наче стрічка
     * В`ється через балку
     */
    public function __construct() {
        parent::__construct();
        $this->loadConfig();
        $this->initApiCrm();
        $this->loadAllPollsVotes();
        $this->preprocessAllPollsVotes();
    }

    /**
     * Preloads some required configs for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['BTRX24_POLLS_URL'])) {
            $this->exportUrl = $this->altCfg['BTRX24_POLLS_URL'];
        }

        if (isset($this->altCfg['BTRX24_POLLS_VAR'])) {
            $this->exportVar = $this->altCfg['BTRX24_POLLS_VAR'];
        }
    }

    /**
     * Preloads raw voting data
     * 
     * @return void
     */
    protected function loadAllPollsVotes() {
        if (!empty($this->pollsAvaible)) {
            $votesDb = new NyanORM('polls_votes');
            $this->allPollsVotesRaw = $votesDb->getAll('id');
        }
    }

    /**
     * Performs preprocessing of users votes data
     * 
     * @return void
     */
    protected function preprocessAllPollsVotes() {
        if (!empty($this->allPollsVotesRaw)) {
            foreach ($this->allPollsVotesRaw as $eachVoteId => $eachVoteData) {
                $pollId = $eachVoteData['poll_id'];
                if (isset($this->pollsAvaible[$pollId])) {
                    //users polls?
                    if ($this->pollsAvaible[$pollId]['voting'] == 'Users') {
                        //is poll enabled?
                        if ($this->pollsAvaible[$pollId]['enabled'] == 1) {
                            $this->allPollsVotesProcessed[$pollId][$eachVoteData['login']] = array(
                                'login' => $eachVoteData['login'],
                                'date' => $eachVoteData['date'],
                                'option_id' => $eachVoteData['option_id']
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns all existing polls votes
     * 
     * @return array
     */
    public function getAllPollsVotes() {
        $result = array();
        $curTimestamp = time();
        $curDate = curdate();
        if (!empty($this->pollsAvaible)) {
            foreach ($this->pollsAvaible as $eachPollId => $eachPollData) {
                //is poll enabled?
                if ($eachPollData['enabled'] == 1) {
                    //users polls?
                    if ($eachPollData['voting'] == 'Users') {
                        //poll is running now?
                        $pollStartTimestamp = strtotime($eachPollData['start_date']);
                        $pollEndTimestamp = strtotime($eachPollData['end_date']);
                        //and it runs now?
                        if (($curTimestamp >= $pollStartTimestamp) AND ( $curTimestamp <= $pollEndTimestamp)) {
                            $result[$eachPollId]['id'] = $eachPollId;
                            $result[$eachPollId]['title'] = $eachPollData['title'];
                            $result[$eachPollId]['enabled'] = $eachPollData['enabled'];
                            $votesTmp = array();
                            if (isset($this->allPollsVotesProcessed[$eachPollId])) {
                                $eachPollVotes = $this->allPollsVotesProcessed[$eachPollId];
                                if (!empty($eachPollVotes)) {
                                    foreach ($eachPollVotes as $eachVotedLogin => $eachVoteData) {
                                        //only current date votes filter
                                        if (ispos($eachVoteData['date'], $curDate)) {
                                            $votesTmp[] = array(
                                                'login' => $eachVoteData['login'],
                                                'vote' => @$this->pollsOptions[$eachPollId][$eachVoteData['option_id']],
                                                'option_id' => $eachVoteData['option_id'],
                                                'address' => @$this->alladdress[$eachVotedLogin],
                                                'date' => $eachVoteData['date']
                                            );
                                        }
                                    }
                                }
                            }
                            $result[$eachPollId]['votes'] = $votesTmp;
                        }
                    }
                }
            }
//                                          .-""-.
//                                         (___/\ \
//                       ,                 (|^ ^ ) )
//                      /(                _)_\=_/  (
//                ,..__/ `\          ____(_/_ ` \   )
//                 `\    _/        _/---._/(_)_  `\ (
//                   '--\ `-.__..-'    /.    (_), |  )
//                       `._        ___\_____.'_| |__/
//                          `~----"`   `-.........'
//                                  ^^^^ а це типу русалонька!            
        }
        return($result);
    }

    /**
     * Inits CRM HTTP abstraction layer
     * 
     * @return void
     */
    protected function initApiCrm() {
        if (!empty($this->exportUrl)) {
            $this->apiCrm = new OmaeUrl($this->exportUrl);
        } else {
            throw new Exception('EX_NO_EXPORT_URL');
        }
    }

    /**
     * Pushes polls user votes struct into CRM hook
     * 
     * @param array $pollsVoteData
     * 
     * @return void
     */
    protected function pushCrmData($pollsVoteData) {
        $jsonData = json_encode($pollsVoteData);
        $this->apiCrm->dataPost($this->exportVar, $jsonData);
        $this->apiCrm->response();
    }

    /**
     * Just pushes all votes and polls data onto CRM hook
     * 
     * @return void
     */
    public function runExport() {
        $pollsVotesData = $this->getAllPollsVotes();
        if (!empty($pollsVotesData)) {
            //empty voting cleanup
            foreach ($pollsVotesData as $pollId => $pollData) {
                if (empty($pollData['votes'])) {
                    unset($pollsVotesData[$pollId]);
                }
            }

            //pushing data to CRM
            if (!empty($pollsVotesData)) {
                $this->pushCrmData($pollsVotesData);
            }
        }
    }

}

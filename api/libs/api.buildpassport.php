<?php

/**
 * Extended Build information base class
 */
class BuildPassport {

    /**
     * Contains all builds passpord data as buildid=>data
     *
     * @var array
     */
    protected $allPassportData = array();

    /**
     * Predefined owner types array
     *
     * @var array
     */
    protected $ownersArr = array('' => '-');

    /**
     * Predefined floors counts
     *
     * @var array
     */
    protected $floorsArr = array('' => '-');

    /**
     * Contains predefined entrances counts
     *
     * @var array
     */
    protected $entrancesArr = array('' => '-');

    /**
     * Database abstraction layer placeholder
     *
     * @var object
     */
    protected $passportsDb = '';

    /**
     * Some static defines, routes etc here
     */
    const DATA_SOURCE = 'buildpassport';
    const EX_NO_OWNERS = 'EMPTY_OWNERS_PARAM';
    const EX_NO_OPTS = 'NOT_ENOUGHT_OPTIONS';

    public function __construct() {
        $this->initDb();
        $this->loadData();
        $this->savePassport();
        $this->loadConfig();
    }

    /**
     * Inits passports database abstraction layer
     */
    protected function initDb() {
        $this->passportsDb = new NyanORM(self::DATA_SOURCE);
    }

    /**
     * loads all existing builds passport data into protected prop
     * 
     * @return void
     */
    protected function loadData() {
        $this->allPassportData = $this->passportsDb->getAll('buildid');
    }

    /**
     * load build passport data options
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();

        //extracting owners
        if (!empty($altCfg['BUILD_OWNERS'])) {
            $rawOwners = explode(',', $altCfg['BUILD_OWNERS']);
            foreach ($rawOwners as $ia => $eachowner) {
                $this->ownersArr[$eachowner] = $eachowner;
            }
        } else {
            throw new Exception(self::EX_NO_OWNERS);
        }

        //extracting floors and entrances
        if (!empty($altCfg['BUILD_EXTOPTS'])) {
            $rawOpts = explode(',', $altCfg['BUILD_EXTOPTS']);
            if (sizeof($rawOpts) < 3) {
                $maxFloors = $rawOpts[0];
                $maxEntrances = $rawOpts[1];

                for ($floors = 1; $floors <= $maxFloors; $floors++) {
                    $this->floorsArr[$floors] = $floors;
                }

                for ($entrances = 1; $entrances <= $maxEntrances; $entrances++) {
                    $this->entrancesArr[$entrances] = $entrances;
                }
            } else {
                throw new Exception(self::EX_NO_OPTS);
            }
        } else {
            throw new Exception(self::EX_NO_OPTS);
        }
    }

    /**
     * returns some build passport edit form
     * 
     * @praram $buildid existing build id
     * 
     * @return string
     */
    public function renderEditForm($buildid) {
        $buildid = ubRouting::filters($buildid, 'int');

        if (isset($this->allPassportData[$buildid])) {
            $currentData = $this->allPassportData[$buildid];
        } else {
            $currentData = array();
        }

        $inputs = wf_HiddenInput('savebuildpassport', $buildid);
        $inputs .= wf_Selector('powner', $this->ownersArr, __('Owner'), @$currentData['owner'], true);
        $inputs .= wf_TextInput('pownername', __('Owner name'), @$currentData['ownername'], true, 30);
        $inputs .= wf_TextInput('pownerphone', __('Owner phone'), @$currentData['ownerphone'], true, 30);
        $inputs .= wf_TextInput('pownercontact', __('Owner contact person'), @$currentData['ownercontact'], true, 30);
        $keys = (@$currentData['keys'] == 1) ? true : false;
        $inputs .= wf_CheckInput('pkeys', __('Keys available'), true, $keys);
        $inputs .= wf_TextInput('paccessnotices', __('Build access notices'), @$currentData['accessnotices'], true, 40);
        $inputs .= wf_Selector('pfloors', $this->floorsArr, __('Floors'), @$currentData['floors'], false);
        $inputs .= wf_Selector('pentrances', $this->entrancesArr, __('Entrances'), @$currentData['entrances'], false);
        $inputs .= wf_TextInput('papts', __('Apartments'), @$currentData['apts'], true, 5);

        $inputs .= __('Notes') . wf_tag('br');
        $inputs .= wf_TextArea('pnotes', '', @$currentData['notes'], true, '50x6');
        $inputs .= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * saves new passport data for some build
     * 
     * @return void
     */
    protected function savePassport() {
        if (ubRouting::checkPost('savebuildpassport')) {
            $buildid = ubRouting::post('savebuildpassport', 'int');

            $owner = ubRouting::post('powner', 'mres');
            $ownername = ubRouting::post('pownername', 'mres');
            $ownerphone = ubRouting::post('pownerphone', 'mres');
            $ownercontact = ubRouting::post('pownercontact', 'mres');
            $keys = (ubRouting::checkPost('pkeys')) ? 1 : 0;
            $accessnotices = ubRouting::post('paccessnotices', 'mres');
            $floors = ubRouting::post('pfloors', 'mres');
            $entrances = ubRouting::post('pentrances', 'mres');
            $apts = ubRouting::post('papts', 'mres');
            $notes = ubRouting::post('pnotes', 'mres');


            //filling new data
            $this->passportsDb->data('owner', $owner);
            $this->passportsDb->data('ownername', $ownername);
            $this->passportsDb->data('ownerphone', $ownerphone);
            $this->passportsDb->data('ownercontact', $ownercontact);
            $this->passportsDb->data('keys', $keys);
            $this->passportsDb->data('accessnotices', $accessnotices);
            $this->passportsDb->data('floors', $floors);
            $this->passportsDb->data('apts', $apts);
            $this->passportsDb->data('entrances', $entrances);
            $this->passportsDb->data('notes', $notes);

            if (isset($this->allPassportData[$buildid])) {
                //updating existing record
                $this->passportsDb->where('buildid', '=', $buildid);
                $this->passportsDb->save();
                log_register('BUILD PASSPORT SAVE [' . $buildid . ']');
            } else {
                //new record
                $this->passportsDb->data('buildid', $buildid);
                $this->passportsDb->create();
                log_register('BUILD PASSPORT CREATE [' . $buildid . ']');
            }

            //reload actual data after saving changes
            $this->loadData();
        }
    }

    /**
     * Returns build passport data if it exists
     * 
     * @param int $buildId
     * 
     * @return array
     */
    public function getPassportData($buildId) {
        $result = array();
        if (isset($this->allPassportData[$buildId])) {
            $result = $this->allPassportData[$buildId];
        }
        return($result);
    }

}

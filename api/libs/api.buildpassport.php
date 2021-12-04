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
    const URL_PASSPORT = '?module=buildpassport';
    const ROUTE_BUILD = 'buildid';
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
        $inputs .= wf_TextInput('pownerphone', __('Owner phone'), @$currentData['ownerphone'], true, 30, 'mobile');
        $inputs .= wf_TextInput('pownercontact', __('Owner contact person'), @$currentData['ownercontact'], true, 30);
        $keys = (@$currentData['keys'] == 1) ? true : false;
        $inputs .= wf_CheckInput('pkeys', __('Keys available'), true, $keys);
        $inputs .= wf_TextInput('paccessnotices', __('Build access notices'), @$currentData['accessnotices'], true, 40);
        $inputs .= wf_Selector('pfloors', $this->floorsArr, __('Floors'), @$currentData['floors'], false);
        $inputs .= wf_Selector('pentrances', $this->entrancesArr, __('Entrances'), @$currentData['entrances'], false);
        $inputs .= wf_TextInput('papts', __('Apartments'), @$currentData['apts'], true, 5);
        $inputs .= __('Notes') . wf_tag('br');
        $inputs .= wf_TextArea('pnotes', '', @$currentData['notes'], true, '50x6');
        $inputs .= wf_CheckInput('pcontract', __('Contract signed'), false, @$currentData['contract']) . ' ';
        $inputs .= wf_CheckInput('pmediator', __('Signed through an intermediary'), true, @$currentData['mediator']) . ' ';
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns some build passport data preview
     * 
     * @param int $buildid existing build id
     * @param string $buildAddress  optional address string
     * 
     * @return string
     */
    public function renderPassportData($buildid, $buildAddress = '') {
        $result = '';
        $buildid = ubRouting::filters($buildid, 'int');
        $rows = '';
        if (!empty($buildAddress)) {
            $cells = wf_TableCell(__('Address'), '30%', 'row2');
            $cells .= wf_TableCell($buildAddress);
            $rows .= wf_TableRow($cells, 'row3');
        }

        if (isset($this->allPassportData[$buildid])) {
            $currentData = $this->allPassportData[$buildid];


            $cells = wf_TableCell(__('Owner'), '30%', 'row2');
            $cells .= wf_TableCell($currentData['owner']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Owner name'), '', 'row2');
            $cells .= wf_TableCell($currentData['ownername']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Owner phone'), '', 'row2');
            $cells .= wf_TableCell($currentData['ownerphone']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Owner contact person'), '', 'row2');
            $cells .= wf_TableCell($currentData['ownercontact']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Keys available'), '', 'row2');
            $keysLabel = ($currentData['keys']) ? wf_img_sized('skins/icon_key.gif', __('Keys available'), '12') . ' ' . __('Yes') : __('No');
            $cells .= wf_TableCell($keysLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Build access notices'), '', 'row2');
            $cells .= wf_TableCell($currentData['accessnotices']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Floors'), '', 'row2');
            $cells .= wf_TableCell($currentData['floors']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Entrances'), '', 'row2');
            $cells .= wf_TableCell($currentData['entrances']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Apartments'), '', 'row2');
            $cells .= wf_TableCell($currentData['apts']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Notes'), '', 'row2');
            $cells .= wf_TableCell($currentData['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $contractSignLabel = ($currentData['contract']) ? __('Yes') : __('No');
            $cells = wf_TableCell(__('Contract signed'), '', 'row2');
            $cells .= wf_TableCell($contractSignLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $mediatorLabel = ($currentData['mediator']) ? __('Yes') : __('No');
            $cells = wf_TableCell(__('Signed through an intermediary'), '', 'row2');
            $cells .= wf_TableCell($mediatorLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $result = wf_TableBody($rows, '100%', 0);
        }

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
            $contract = (ubRouting::checkPost('pcontract')) ? 1 : 0;
            $mediator = (ubRouting::checkPost('pmediator')) ? 1 : 0;



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
            $this->passportsDb->data('contract', $contract);
            $this->passportsDb->data('mediator', $mediator);

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

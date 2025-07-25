<?php

class KATOTTG {
    /**
     * Basic entities database abstraction layer
     *
     * @var object
     */
    protected $katottgDb='';
    /**
     * City bindings database abstraction layer
     *
     * @var object
     */
    protected $katottgCitiesDb='';
    /**
     * Street bindings database abstraction layer
     *
     * @var object
     */
    protected $katottgStreetsDb='';

    /**
     * Contains all basic entities as id=>data
     *
     * @var array
     */
    protected $allKatottg=array();
    /**
     * Contains all city bindings as cityid=>data
     *
     * @var array
     */
    protected $allCityBindings=array();
    /**
     * Contains all street bindings as streetid=>data
     *
     * @var array
     */
    protected $allStreetBingings=array();

    /**
     * Contains all cities as cityId=>name
     *
     * @var array
     */
    protected $allCities=array();

    /**
     * Contains all streets as streetId=>streetData
     *
     * @var array
     */
    protected $allStreets=array();

    /**
     * System messages helper
     *
     * @var object
     */
    protected $messages='';
    

    /**
     * Some predefined stuff
     */
    const TABLE_KATOTTG='katottg';
    const TABLE_KATOTTG_CITIES='katottg_cities';
    const TABLE_KATOTTG_STREETS='katottg_streets';
    const URL_ME='?module=katottg';
    const URL_CHECK='https://directory.org.ua/territories/';
    const URL_API_LOOKUP='http://katottg.ubilling.net.ua/';
    const AGENT_PREFIX = 'UbillingKATOTTG';
    /**
     * Some routing here
     */
    const PROUTE_NEW_OB='newkatottgob';
    const PROUTE_NEW_RA='newkatottgra';
    const PROUTE_NEW_TG='newkatottgtg';
    const PROUTE_NEW_CI='newkatottgci';
    const PROUTE_NEW_NAME='newkatottgname';
    const PROUTE_BIND_KAT='bindkatottgid';
    const PROUTE_BIND_CITY='bindcityid';
    const PROUTE_BIND_STREET='bindstreetid';
    const PROUTE_BIND_STREET_CD='bindstreetcd';
    const PROUTE_BIND_STREET_CITYID='bindstreetcityid';
    const PROUTE_BIND_STREET_KATID='bindstreetkatid';

    const ROUTE_LIST='list';
    const ROUTE_CREATE_AUTO='createkatottgauto';
    const ROUTE_CREATE_MANUAL='createkatottgmanual';
    const ROUTE_EDIT='editkatottg';
    const ROUTE_DELETE='deletekatottg';
    const ROUTE_UNBIND_CITY='unbindcityid';
    const ROUTE_STREET_BIND='streetmagic';
    

    public function __construct($loadGeo=false) {
        $this->initMessages();
        $this->initDb();
        $this->loadData();
        if ($loadGeo) {
            $this->loadCities();
            $this->loadStreets();
        }
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function initDb() {
        $this->katottgDb = new NyanORM(self::TABLE_KATOTTG);
        $this->katottgCitiesDb = new NyanORM(self::TABLE_KATOTTG_CITIES);
        $this->katottgStreetsDb = new NyanORM(self::TABLE_KATOTTG_STREETS);
    }

    protected function loadData() {
        $this->allKatottg = $this->katottgDb->getAll('id');
        $this->allCityBindings = $this->katottgCitiesDb->getAll('cityid');
        $this->allStreetBingings = $this->katottgStreetsDb->getAll('streetid');
    }

    protected function loadCities() {
        $this->allCities=zb_AddressGetFullCityNames();
    }

    protected function loadStreets() {
        $this->allStreets=zb_AddressGetStreetsDataAssoc();
    }

    protected function requestRemoteKatottg($level,$filter='') {
        $result=array();
        $requestUrl=self::URL_API_LOOKUP.'?level='.$level;
        if (!empty($filter)) {
            $requestUrl.='&filter='.$filter;
        }
        $remoteApi=new OmaeUrl($requestUrl);
        $ubVer = file_get_contents('RELEASE');
        $agent = self::AGENT_PREFIX . '/' . trim($ubVer);
        $remoteApi->setUserAgent($agent);
        $result=$remoteApi->response();
        if (!empty($result)) {
            if (json_validate($result)) {
                $result=json_decode($result,true);
            }
        }
        return($result);
    }

    protected function prepareSelectorData($data) {
        $result=array();
        if (!empty($data)) {
            $usedKeys=array();
            $result['']='-';
            foreach ($data as $item) {
                $entityCode=$item['code'];
                if (isset($usedKeys[$entityCode])) {
                    while (isset($usedKeys[$entityCode])) {
                        $entityCode.='+';
                    }
                }
                $result[$entityCode]=$item['name'];
                $usedKeys[$entityCode]=1;
            }
        }
        return($result);
    }

    protected function renderKatottgSelector($name,$label,$level=1,$filter='') {
        $result='';
        $remoteData=$this->requestRemoteKatottg($level,$filter);
        $params=$this->prepareSelectorData($remoteData);
        if (!empty($remoteData)) {
            $result.=wf_SelectorSearchableAC($name,$params,$label,'',true);
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
        return($result);
    }

    protected function renderValidationControl($code) {
        $result='';
        if (!empty($code)) {
            $result.=wf_Link(self::URL_CHECK.$code,wf_img('skins/question.png',__('Check')),false,'','target="_blank"');

        }
        return($result);
    }

    protected function validateKatottgCode($code) {
        $result=false;
        if (!empty($code)) {
            if (preg_match('/^UA\d{17}$/', $code)) {
                $result = true;
            }
        }
        return($result);
    }

    public function renderModuleControls() {
        $result = '';
        $result.=wf_Link(self::URL_ME.'&'.self::ROUTE_CREATE_AUTO.'=true', wf_img('skins/done_icon.png').' '. __('Create automatically'), false, 'ubButton');
        $result.=wf_Link(self::URL_ME.'&'.self::ROUTE_CREATE_MANUAL.'=true', wf_img('skins/categories_icon.png').' '.__('Create manual'), false, 'ubButton');
        $result.=wf_Link(self::URL_ME.'&'.self::ROUTE_LIST.'=true', wf_img('skins/icon_table.png').' '.__('Available locations'), false, 'ubButton');
        $result.=wf_Link(self::URL_ME.'&'.self::ROUTE_STREET_BIND.'=true', wf_img('skins/icon_street.gif').' '.__('Street magic'), false, 'ubButton');
        return ($result);
    }

    public function renderCreateFormAuto() {
        $result = '';
        $inputs = '';
        $sup=wf_tag('sup',false).'*'.wf_tag('sup',false);
        
        $currentOb = ubRouting::post(self::PROUTE_NEW_OB,'gigasafe');
        $currentRa = ubRouting::post(self::PROUTE_NEW_RA,'gigasafe');
        $currentTg = ubRouting::post(self::PROUTE_NEW_TG,'gigasafe');
        $currentCi = ubRouting::post(self::PROUTE_NEW_CI,'gigasafe');
        $currentName = ubRouting::post(self::PROUTE_NEW_NAME,'safe');
        
        if (empty($currentOb)) {
            $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_OB, __('Region').' / '.__('Oblast'), 1);
        } else {
            $validationControl = $this->renderValidationControl($currentOb);
            $inputs .= wf_TextInput(self::PROUTE_NEW_OB, __('Region').' / '.__('Oblast').' '.$validationControl, $currentOb, true, 22, 'gigasafe', '', self::PROUTE_NEW_OB);
            
        }
        
        if (!empty($currentOb)) {
            if (empty($currentRa)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_RA, __('District'), 2, $currentOb);
            } else {
                $validationControl = $this->renderValidationControl($currentRa);
                $inputs .= wf_TextInput(self::PROUTE_NEW_RA, __('District').' '.$validationControl, $currentRa, true, 22, 'gigasafe', '', self::PROUTE_NEW_RA);
            }
        }
        
        if (!empty($currentOb) && !empty($currentRa)) {
            if (empty($currentTg)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_TG, __('Territorial community'), 3, $currentRa);
            } else {
                $validationControl = $this->renderValidationControl($currentTg);
                $inputs .= wf_TextInput(self::PROUTE_NEW_TG, __('Territorial community').' '.$validationControl, $currentTg, true, 22, 'gigasafe', '', self::PROUTE_NEW_TG);
            }
        }
        
        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg)) {
            if (empty($currentCi)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_CI, __('Settlement'), 4, $currentTg);
            } else {
                $validationControl = $this->renderValidationControl($currentCi);
                $inputs .= wf_TextInput(self::PROUTE_NEW_CI, __('Settlement').' '.$validationControl, $currentCi, true, 22, 'gigasafe', '', self::PROUTE_NEW_CI);
            }
        }
        
        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg) && !empty($currentCi)) {
            $inputs .= wf_TextInput(self::PROUTE_NEW_NAME, __('Name').$sup, $currentName, true, 22);
        }
        
        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg) && !empty($currentCi)) {
            $inputs.=wf_delimiter();
            $inputs .= wf_Submit(__('Create'));
        }
        
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        
        return ($result);
    }

    public function renderCreateFormManual() {
        $result = '';
        $inputs = '';
        $sup = wf_tag('sup', false) . '*' . wf_tag('sup', false);
        
        $inputs .= wf_TextInput(self::PROUTE_NEW_OB, __('Region').' / '.__('Oblast') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_OB);
        $inputs .= wf_TextInput(self::PROUTE_NEW_RA, __('District') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_RA);
        $inputs .= wf_TextInput(self::PROUTE_NEW_TG, __('Territorial community') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_TG);
        $inputs .= wf_TextInput(self::PROUTE_NEW_CI, __('Settlement') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_CI);
        $inputs .= wf_TextInput(self::PROUTE_NEW_NAME, __('Name') . $sup, '', true, 22);
        
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        
        return ($result);
    }

    public function createKatottgEntity() {
        $requiredFields=array(
            self::PROUTE_NEW_OB,
            self::PROUTE_NEW_RA,
            self::PROUTE_NEW_TG,
            self::PROUTE_NEW_CI,
            self::PROUTE_NEW_NAME
        );
        
        if (ubRouting::checkPost($requiredFields)) {
            $currentOb = ubRouting::post(self::PROUTE_NEW_OB,'gigasafe');
            $currentRa = ubRouting::post(self::PROUTE_NEW_RA,'gigasafe');
            $currentTg = ubRouting::post(self::PROUTE_NEW_TG,'gigasafe');
            $currentCi = ubRouting::post(self::PROUTE_NEW_CI,'gigasafe');
            $currentName = ubRouting::post(self::PROUTE_NEW_NAME,'safe');
            
            
                $invalidCodes = array();
                
                if (!$this->validateKatottgCode($currentOb)) {
                    $invalidCodes[] = __('Region') . ': ' . $currentOb;
                }
                if (!$this->validateKatottgCode($currentRa)) {
                    $invalidCodes[] = __('District') . ': ' . $currentRa;
                }
                if (!$this->validateKatottgCode($currentTg)) {
                    $invalidCodes[] = __('Territorial community') . ': ' . $currentTg;
                }
                if (!$this->validateKatottgCode($currentCi)) {
                    $invalidCodes[] = __('Settlement') . ': ' . $currentCi;
                }
                
                if (empty($invalidCodes)) {
                    if (!empty($currentName)) {
                    $this->katottgDb->data('ob',$currentOb);
                    $this->katottgDb->data('ra',$currentRa);
                    $this->katottgDb->data('tg',$currentTg);
                    $this->katottgDb->data('ci',$currentCi);
                    $this->katottgDb->data('name',$currentName);
                    $this->katottgDb->create();
                    $newId=$this->katottgDb->getLastId();
                    log_register('KATOTTG CREATE ['.$newId.'] NAME `'.$currentName.'`');
                    ubRouting::nav(self::URL_ME.'&'.self::ROUTE_LIST.'=true');
                    } else {
                        show_error(__('All fields marked with an asterisk are mandatory'));
                    }
                } else {
                    $errorMessage = __('Wrong request') . ': ' . implode(', ', $invalidCodes);
                    show_error($errorMessage);
                }
            
        } 
    }

    public function deleteKatottgEntity($id) {
        $id=ubRouting::get(self::ROUTE_DELETE,'int');
        if (!empty($id)) {
            if (isset($this->allKatottg[$id])) {
                $currentData=$this->allKatottg[$id];
                $this->katottgDb->where('id','=', $id);
                $this->katottgDb->delete();
                log_register('KATOTTG DELETE ['.$id.'] NAME `'.$currentData['name'].'`');
                ubRouting::nav(self::URL_ME.'&'.self::ROUTE_LIST.'=true');
            } else {
             log_register('KATOTTG DELETE FAIL ['.$id.'] NOT FOUND');
            }
        }
    }

    public function renderKatottgList() {
        $result = '';
        if (!empty($this->allKatottg)) {
            $cells=wf_tablecell(__('ID'));
            $cells.=wf_tablecell(__('Name'));
            $cells.=wf_tablecell(__('Region'));
            $cells.=wf_tablecell(__('District'));
            $cells.=wf_tablecell(__('Territorial community'));
            $cells.=wf_tablecell(__('Settlement'));
            $cells.=wf_tablecell(__('Actions'));
            $rows=wf_tableRow($cells,'row1');
            foreach ($this->allKatottg as $katottg) {   
                $cells=wf_tablecell($katottg['id']);
                $cells.=wf_tablecell($katottg['name']);
                $cells.=wf_tablecell($katottg['ob'] . ' ' . $this->renderValidationControl($katottg['ob']));
                $cells.=wf_tablecell($katottg['ra'] . ' ' . $this->renderValidationControl($katottg['ra']));
                $cells.=wf_tablecell($katottg['tg'] . ' ' . $this->renderValidationControl($katottg['tg']));
                $cells.=wf_tablecell($katottg['ci'] . ' ' . $this->renderValidationControl($katottg['ci']));
                $actionControls=wf_Link(self::URL_ME.'&'.self::ROUTE_EDIT.'='.$katottg['id'], web_edit_icon());
                $deleteUrl=self::URL_ME.'&'.self::ROUTE_DELETE.'='.$katottg['id'];
                $cancelUrl=self::URL_ME.'&'.self::ROUTE_LIST.'=true';
                $deleteAlert=$this->messages->getDeleteAlert();
                $deleteDialog=wf_ConfirmDialog($deleteUrl,web_delete_icon(),$deleteAlert,'',$cancelUrl,__('Delete').'?');
                if (!$this->isKatottgProtected($katottg['id'])) {
                    $actionControls.=$deleteDialog;    
                }
                
                $cells.=wf_tablecell($actionControls);
                $rows.=wf_tableRow($cells,'row5');
            }
            $result.=wf_tableBody($rows,'100%',0);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    public function renderCityBindingForm() {
        $result = '';
        
        if (!empty($this->allCities)) {
            if (!empty($this->allKatottg)) {
                $cityParams=$this->allCities;
                $katParams=array();
                foreach ($this->allKatottg as $katottg) {
                    $katParams[$katottg['id']]=$katottg['name'];
                }

                foreach ($cityParams as $cityId=>$cityName) {
                    if (isset($this->allCityBindings[$cityId])) {
                        unset($cityParams[$cityId]);
                    }
                }

                if (!empty($cityParams)) {
                    $inputs = wf_Selector(self::PROUTE_BIND_CITY, $cityParams, __('City'), '', false);
                    $inputs .= wf_Selector(self::PROUTE_BIND_KAT, $katParams, __('KATOTTG'), '', false).' ';
                    $inputs .= wf_Submit(__('Assign'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                }
                
            }

        }
        return($result);
    }

    protected function isKatottgProtected($katottgId) {
        $katottgId=ubRouting::filters($katottgId,'int');
        $result=false;
        if (!empty($this->allCityBindings)) {
            foreach ($this->allCityBindings as $cityId=>$bindData) {
                if ($bindData['katid']==$katottgId) {
                    $result=true;
                    break;
                }
            }
        }
        return($result);
    }

    public function bindCityToKatottg($katottgId,$cityId) {
        $katottgId=ubRouting::filters($katottgId,'int');
        $cityId=ubRouting::filters($cityId,'int');

        if (!empty($katottgId) and !empty($cityId)) {
            if (isset($this->allKatottg[$katottgId]) and isset($this->allCities[$cityId])) {
                
                $this->katottgCitiesDb->data('cityid',$cityId);
                $this->katottgCitiesDb->data('katid',$katottgId);
                $this->katottgCitiesDb->create();
                log_register('KATOTTG BIND CITY ['.$cityId.'] TO KATOTTG ['.$katottgId.']');
                ubRouting::nav(self::URL_ME.'&'.self::ROUTE_LIST.'=true');
            
            } else {
                log_register('KATOTTG BIND CITY FAIL ['.$cityId.'] TO KATOTTG ['.$katottgId.'] NOT FOUND');
            }
            
        }
    }

    public function unbindCityFromKatottg($cityId) {
        $cityId=ubRouting::filters($cityId,'int');
        if (!empty($cityId)) {
            $this->katottgCitiesDb->where('cityid','=', $cityId);
            $this->katottgCitiesDb->delete();
            log_register('KATOTTG UNBIND CITY ['.$cityId.']');
            ubRouting::nav(self::URL_ME.'&'.self::ROUTE_LIST.'=true');
        }
    }

    public function renderCityBindingList() {
        $result = '';
        if (!empty($this->allCityBindings)) {
            $cells=wf_tablecell(__('City'));
            $cells.=wf_tablecell(__('Settlement').' '.__('KATOTTG'));
            $cells.=wf_tablecell(__('Code'));
            $cells.=wf_tablecell(__('Actions'));
            $rows=wf_tableRow($cells,'row1');
            foreach ($this->allCityBindings as $cityId=>$bindData) {
                $katName=(isset($this->allKatottg[$bindData['katid']]))?$this->allKatottg[$bindData['katid']]['name']:__('Deleted');
                $cityName=(isset($this->allCities[$cityId]))?$this->allCities[$cityId]:__('Deleted');
                $cityCode=(isset($this->allKatottg[$bindData['katid']])) ? $this->allKatottg[$bindData['katid']]['ci'].' ' . $this->renderValidationControl($this->allKatottg[$bindData['katid']]['ci']) : __('Unknown');
                $cells=wf_tablecell($cityName);
                $cells.=wf_tablecell($katName);
                $cells.=wf_tablecell($cityCode);
                $unbindUrl=self::URL_ME.'&'.self::ROUTE_UNBIND_CITY.'='.$bindData['cityid'];
                $cancelUrl=self::URL_ME.'&'.self::ROUTE_LIST.'=true';
                $alert=$this->messages->getDeleteAlert();
                $unbindDialog=wf_ConfirmDialog($unbindUrl,web_delete_icon(),$alert,'',$cancelUrl,__('Delete').'?');
                $actionControls=$unbindDialog;
                $cells.=wf_tablecell($actionControls);
                $rows.=wf_tableRow($cells,'row5');
            }   
            $result.=wf_tableBody($rows,'100%',0);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    protected function getCityIdByKatId($katId) {
        $result = '';
        if (!empty($this->allCityBindings)) {
            foreach ($this->allCityBindings as $cityId=>$bindData) {
                if ($bindData['katid']==$katId) {
                    $result=$cityId;
                    break;  
                }
            }
        }
        return($result);
    }

    protected function isStreetBound($streetId) {
        $result=false;
        if (!empty($this->allStreetBindings)) {
            foreach ($this->allStreetBingings as $streetId=>$bindData) {
               if ($bindData['streetid']==$streetId) {
                $result=true;
                break;
               }
            }
        }
        return($result);
    }

    protected function renderStreetSelector($katId) {
        $katId=ubRouting::filters($katId,'int');
        $cityId=$this->getCityIdByKatId($katId);
        $result = '';
        if (!empty($cityId)) {
            $cityName=$this->allCities[$cityId];
        
        if (!empty($this->allStreets)) {
            $streetParams=array();
            foreach ($this->allStreets as $streetId=>$streetData) {
                if ($cityId==$streetData['cityid']) {
                    $streetParams[$streetId]=$cityName.' - '.$streetData['streetname'];
                }
            }

            $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET, $streetParams, __('Street'), '', false);
            $result .= $inputs;
        }
    }
        return($result);
    }

    public function renderCityDistrictSelector($cityCode) {
        $result = '';
        $cityCode=ubRouting::filters($cityCode,'gigasafe');
        $remoteData=$this->requestRemoteKatottg(5,'&filter='.$cityCode);
        if (!empty($remoteData)) {
           $districtParams=array();
           foreach ($remoteData as $io=>$district) {
            $districtParams[$district['code']]=$district['name'];
           }
           $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET_CD, $districtParams, __('City district'), '', false);
           $result .= $inputs;
       
        } else {
            $result.=wf_TextInput(self::PROUTE_BIND_STREET_CD,__('City district'),'',false,22);
        }
        return($result);
    }


    public function renderStreetBindingForm() {
        $result = '';
        $inputs = '';
        if (!ubRouting::checkPost(self::PROUTE_BIND_STREET_KATID)) {
        if (!empty($this->allCityBindings)) {
            $cityParams=array();
            foreach ($this->allCityBindings as $cityId=>$bindData) {
                $cityParams[$bindData['katid']]=$this->allCities[$cityId];
            }
            $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET_KATID, $cityParams, __('City'), '', false);
            $inputs .= wf_Submit(__('Chose'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }   
        } else {
            //city already chosen
            $katId=ubRouting::post(self::PROUTE_BIND_STREET_KATID,'int');
            $katData=$this->allKatottg[$katId];
            $cityCode=$katData['ci'];
            $inputs.=wf_HiddenInput(self::PROUTE_BIND_STREET_KATID,ubRouting::post(self::PROUTE_BIND_STREET_KATID,'int'));
            $inputs.= $this->renderStreetSelector($katId);
            $inputs.= $this->renderCityDistrictSelector($cityCode);
            $inputs.=wf_Submit(__('Assign'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

}
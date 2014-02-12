<?php

/*
 * UKV cable TV accounting implementation
 */

class UkvSystem {

    protected $tariffs = array();
    protected $users=array();
    protected $cities=array(''=>'-');
    protected $streets=array(''=>'-');
    
    //static routing URL
    const URL_TARIFFS_MGMT='?module=ukv&tariffs=true'; //tariffs management
    const URL_USERS_MGMT='?module=ukv&users=true'; //users management
    const URL_USERS_LIST='?module=ukv&users=true&userslist=true'; //users list route
    const URL_USERS_PROFILE='?module=ukv&users=true&showuser='; //user profile
    const URL_USERS_REGISTER='?module=ukv&users=true&register=true'; //users registration route
    const URL_USERS_AJAX_SOURCE='?module=ukv&ajax=true'; //ajax datasource for JQuery data tables
    const URL_INET_USER_PROFILE='?module=userprofile&username='; //internet user profile
    
    //registration options
    const REG_ACT=1;
    const REG_CASH=0;
    
    //some exeptions
    const EX_TARIFF_FIELDS_EMPTY='EMPTY_TARIFF_OPTS_RECEIVED';
    const EX_USER_NOT_EXISTS='NO_EXISTING_UKV_USER';
    const EX_USER_NOT_SET='NO_VALID_USERID_RECEIVED';
    

    public function __construct() {
        $this->loadTariffs();
        $this->loadUsers();
        $this->loadCities();
        $this->loadStreets();
    }

    /*
     * loads all tariffs into private tariffs prop
     * 
     * @return void
     */

    protected function loadTariffs() {
        $query = "SELECT * from `ukv_tariffs`";
        $alltariffs = simple_queryall($query);
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $each) {
                $this->tariffs[$each['id']] = $each;
            }
        }
    }
    
    
    /*
     * loads all existing cities into private cities prop
     * 
     * @return void
     */

    protected function loadCities() {
        $query = "SELECT * from `city` ORDER BY `id` ASC;";
        $allcities = simple_queryall($query);
        if (!empty($allcities)) {
            foreach ($allcities as $io => $each) {
                $this->cities[$each['cityname']] = $each['cityname'];
            }
        }
    }
    
     /*
     * loads all existing streets into private streets prop
     * 
     * @return void
     */

    protected function loadStreets() {
        $query = "SELECT DISTINCT `streetname` from `street` ORDER BY `streetname` ASC;";
        $allstreets = simple_queryall($query);
        if (!empty($allstreets)) {
            foreach ($allstreets as $io => $each) {
                $this->streets[$each['streetname']] = $each['streetname'];
            }
        }
    }

    /*
     * creates new tariff into database
     * 
     * @param $name  tariff name
     * @param $price tariff price 
     * 
     * @return void
     */

    public function tariffCreate($name, $price) {
        $name = mysql_real_escape_string($name);
        $name= trim($name);
        $price = mysql_real_escape_string($price);
        $price= trim($price);
        if ((!empty($name)) AND (!empty($price))) {
        $query = "INSERT INTO `ukv_tariffs` (`id`, `tariffname`, `price`) VALUES (NULL, '" . $name . "', '" . $price . "');";
        nr_query($query);
        log_register("UKV TARIFF CREATE `" . $name . "` WITH PRICE `" . $price . "`");
        } else {
            throw new Exception(self::EX_TARIFF_FIELDS_EMPTY);
        }
    }
    
    /*
     * check is tariff protected/used by some users
     * 
     * @param @tariffid  existing tariff ID
     * 
     * @return bool
     */
    protected function tariffIsProtected($tariffid) {
        $tariffid=vf($tariffid,3);
        $query="SELECT `id` from `ukv_users` WHERE `tariffid`='".$tariffid."';";
        $data=  simple_query($query);
        if (empty($data)) {
            return (false);
        } else {
            return(true);
        }
    }

    /*
     * deletes some existing tariff from database
     * 
     * @param $tariffid existing tariff ID
     * 
     * @return void
     */

    public function tariffDelete($tariffid) {
        $tariffid = vf($tariffid, 3);
        //check - is tariff used by anyone?
        if (!$this->tariffIsProtected($tariffid)) {
            $tariffName = $this->tariffs[$tariffid]['tariffname'];
            $query = "DELETE from `ukv_tariffs` WHERE `id`='" . $tariffid . "'";
            nr_query($query);
            log_register("UKV TARIFF DELETE `" . $tariffName . "`  [" . $tariffid . "]");
        } else {
            log_register("UKV TARIFF DELETE PROTECTED TRY [".$tariffid."]");
        }
    }

    /*
     * saves some tariff params into database
     * 
     * @param $tariffid    existing tariff ID
     * @param $tariffname  new name of the tariff
     * @param $price       new tariff price
     */

    public function tariffSave($tariffid,$tariffname,$price) {
        $tariffid = vf($tariffid, 3);
        $tariffname=  mysql_real_escape_string($tariffname);
        $tariffname= trim($tariffname);
        $price= mysql_real_escape_string($price);
        $price= trim($price);
        
        if ((!empty($tariffname)) AND (!empty($price))) {
        $query="UPDATE `ukv_tariffs` SET `tariffname` = '".$tariffname."', `price` = '".$price."' WHERE `id` = '".$tariffid."';";
        nr_query($query);
        log_register("UKV TARIFF CHANGE `" . $tariffname . "` WITH PRICE `".$price."`  [" . $tariffid . "]");
        } else {
            throw new Exception(self::EX_TARIFF_FIELDS_EMPTY);
        }
    }
    
    /*
     * returns tariff edit form 
     * 
     * @param $tariffid existing tariff id
     * 
     * @rerturn string
     */
    protected function tariffEditForm($tariffid) {
        $tariffid=vf($tariffid,3);
        
        $inputs=  wf_HiddenInput('edittariff', $tariffid);
        $inputs.= wf_TextInput('edittariffname', __('Tariff name'), $this->tariffs[$tariffid]['tariffname'], true, '20');
        $inputs.= wf_TextInput('edittariffprice', __('Tariff Fee'), $this->tariffs[$tariffid]['price'], true, '5');
        $inputs.= wf_Submit(__('Save'));
        $result= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
     * returns tariff creation form
     * 
     * @return string
     */
    protected function tariffCreateForm() {
        $inputs=  wf_HiddenInput('createtariff', 'true');
        $inputs.= wf_TextInput('createtariffname', __('Tariff name'), '', true, '20');
        $inputs.= wf_TextInput('createtariffprice', __('Tariff Fee'), '', true,'5');
        $inputs.= wf_Submit(__('Create new tariff'));
        $result=  wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }


    /*
     * renders CaTV tariffs list with some controls
     * 
     * @return void
     */
    public function renderTariffs() {
        
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Tariff Fee'));
        $cells.= wf_TableCell(__('Actions'));
        $rows=  wf_TableRow($cells, 'row1');
        
        if (!empty($this->tariffs)) {
            foreach ($this->tariffs as $io=>$each) {
                $cells=  wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['tariffname']);
                $cells.= wf_TableCell($each['price']);
                $actlinks=  wf_JSAlert(self::URL_TARIFFS_MGMT.'&tariffdelete='.$each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actlinks.= wf_modal(web_edit_icon(), __('Edit').' '.$each['tariffname'], $this->tariffEditForm($each['id']), '', '400', '200');
                $cells.= wf_TableCell($actlinks,'','',$customkey='sorttable_customkey="0"'); //need this to keep table sortable
                $rows.=  wf_TableRow($cells, 'row3');
            }
        }
        
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= wf_modal(wf_img('skins/plus.png', __('Create new tariff')),__('Create new tariff') , $this->tariffCreateForm(), '', '400', '200');
        return ($result);
    }
    
    
    /*
     * returns module control panel
     * 
     * @return string
     */
    public function panel() {
        $result=   wf_Link(self::URL_USERS_LIST, wf_img('skins/icon_user.gif').' '.__('Users'), false, 'ubButton');
        $result.=  wf_Link(self::URL_USERS_REGISTER, wf_img('skins/icon_add.gif').' '.__('Users registration'), false, 'ubButton');
        $result.=  wf_Link(self::URL_TARIFFS_MGMT,wf_img('skins/icon_dollar.gif').' '. __('Tariffs'), false, 'ubButton');
        return ($result);
    }
    
    
    /*
     * loads all users from database to private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $query="SELECT * from `ukv_users`";
        $allusers= simple_queryall($query);
        if (!empty($allusers)) {
            foreach ($allusers as $io=>$each) {
                $this->users[$each['id']]=$each;
            }
        }
    }

    /*
     * registers new users into database and returns new user ID
     * 
     * @return int 
     */
    public function userCreate() {
        $curdate=  date("Y-m-d H:i:s");
        $query="
            INSERT INTO `ukv_users` (
                            `id` ,
                            `contract` ,
                            `tariffid` ,
                            `cash` ,
                            `active` ,
                            `realname` ,
                            `passnum` ,
                            `passwho` ,
                            `passdate` ,
                            `paddr`,
                            `ssn` ,
                            `phone` ,
                            `mobile` ,
                            `regdate` ,
                            `city` ,
                            `street` ,
                            `build` ,
                            `apt` ,
                            `inetlogin` ,
                            `notes`
                            )
                            VALUES (
                            NULL ,
                            NULL ,
                            NULL ,
                            '".self::REG_CASH."',
                            '".self::REG_ACT."',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            '".$curdate."',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL
                            );  ";
        $newUserId=  simple_get_lastid('ukv_users');
        $result=$newUserId;
        log_register("UKV REGISTER USER ((".$newUserId."))");
        return ($result);
    }
    
   
    
    /*
     * returns user edit form for some userid
     * 
     * @param $userid  existing user ID
     * 
     * @return string
     */
    protected function userEditForm($userid) {
        $userid=vf($userid,3);
         if (isset($this->users[$userid])) {
            $switchArr=array('1'=>__('Yes'),'0'=>__('No'));
            $tariffArr=array();
            if (!empty($this->tariffs)) {
                foreach ($this->tariffs as $io=>$each) {
                    $tariffArr[$each['id']]=$each['tariffname'];
                }
            } 
             
            $userData=$this->users[$userid];
            
            $inputs='';
            $inputs.= wf_HiddenInput('usereditprocessing', $userid);
            $inputs.= wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3').__('Full address').  wf_tag('h3',true);
            $inputs.= wf_Selector('ueditcity', $this->cities, __('City'), $userData['city'], true);
            $inputs.= wf_Selector('ueditstreet', $this->streets, __('Street'), $userData['street'], true);
            $inputs.= wf_TextInput('ueditbuild', __('Build'), $userData['build'], false, '5');
            $inputs.= wf_TextInput('ueditapt', __('Apartment'), $userData['apt'], true, '4');
            $inputs.= wf_tag('div', true);
            
            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3').__('Contact info').  wf_tag('h3',true);
            $inputs.= wf_TextInput('ueditrealname', __('Real Name'), $userData['realname'], true, '30');
            $inputs.= wf_TextInput('ueditphone', __('Phone'), $userData['phone'], true, '20');
            $inputs.= wf_TextInput('ueditmobile', __('Mobile'), $userData['mobile'], true, '20');
            $inputs.= wf_tag('div', true);
            
            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3').__('Services').  wf_tag('h3',true);
            $inputs.= wf_TextInput('ueditcontract', __('Contract'), $userData['contract'], true, '10');
            $inputs.= wf_Selector('uedittariff', $tariffArr, __('Tariff'), $userData['tariffid'], true);
            $inputs.= wf_Selector('ueditactive', $switchArr, __('Connected'), $userData['active'], true);
            $inputs.= wf_TextInput('ueditregdate', __('Contract date'), $userData['regdate'], true, '20');
            $inputs.= wf_TextInput('ueditinetlogin', __('Login'), $userData['inetlogin'], true, '20');
            $inputs.= wf_tag('div', true);
            
            
            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3').__('Passport data').  wf_tag('h3',true);
            $inputs.= wf_TextInput('ueditpassnum', __('Passport number'), $userData['passnum'], true, '20');
            $inputs.= wf_TextInput('ueditpasswho', __('Issuing authority'), $userData['passwho'], true, '20');
            $inputs.= wf_DatePickerPreset('ueditpassdate', $userData['passdate'],true).__('Date of issue').  wf_tag('br');
            $inputs.= wf_TextInput('ueditssn', __('SSN'), $userData['ssn'], true, '20');
            $inputs.= wf_TextInput('ueditpaddr', __('Registration address'), $userData['paddr'], true, '20');
            $inputs.= wf_tag('div', true);
            
            $inputs.=wf_tag('div', false, 'floatpanelswide');
            $inputs.= wf_TextInput('ueditnotes', __('Notes'), $userData['notes'], false, '60');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));
            
            $result= wf_Form('', 'POST', $inputs, 'ukvusereditform');
            
            return ($result);
         }
                 
    }
    
    /*
     * saves some user params into database
     * 
     * @return void
     */
    public function userSave() {
        if (wf_CheckPost(array('usereditprocessing'))) {
            $userId=vf($_POST['usereditprocessing']);
            $where="WHERE `id`='".$userId."';";
            $tablename='ukv_users';
         
            //saving city
            if ($this->users[$userId]['city']!=$_POST['ueditcity']) {
                simple_update_field($tablename, 'city', $_POST['ueditcity'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE CITY `'.$_POST['ueditcity'].'`');
            }
            
            //saving street
            if ($this->users[$userId]['street']!=$_POST['ueditstreet']) {
                simple_update_field($tablename, 'street', $_POST['ueditstreet'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE STREET `'.$_POST['ueditstreet'].'`');
            }
            
            //saving build
            if ($this->users[$userId]['build']!=$_POST['ueditbuild']) {
                simple_update_field($tablename, 'build', $_POST['ueditbuild'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE BUILD `'.$_POST['ueditbuild'].'`');
            }
            
            //saving apartment
            if ($this->users[$userId]['apt']!=$_POST['ueditapt']) {
                simple_update_field($tablename, 'apt', $_POST['ueditapt'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE APT `'.$_POST['ueditapt'].'`');
            }
            
            //saving realname
            if ($this->users[$userId]['realname']!=$_POST['ueditrealname']) {
                simple_update_field($tablename, 'realname', $_POST['ueditrealname'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE REALNAME `'.$_POST['ueditrealname'].'`');
            }
            
            //saving phone
            if ($this->users[$userId]['phone']!=$_POST['ueditphone']) {
                simple_update_field($tablename, 'phone', $_POST['ueditphone'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE PHONE `'.$_POST['ueditphone'].'`');
            }
            
            //saving mobile number
            if ($this->users[$userId]['mobile']!=$_POST['ueditmobile']) {
                simple_update_field($tablename, 'mobile', $_POST['ueditmobile'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE MOBILE `'.$_POST['ueditmobile'].'`');
            }
            
            //saving contract
            if ($this->users[$userId]['contract']!=$_POST['ueditcontract']) {
                simple_update_field($tablename, 'contract', $_POST['ueditcontract'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE CONTRACT `'.$_POST['ueditcontract'].'`');
            }
            
            //saving tariff
            if ($this->users[$userId]['tariffid']!=$_POST['uedittariff']) {
                simple_update_field($tablename, 'tariffid', $_POST['uedittariff'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE TARIFF ['.$_POST['uedittariff'].']');
            }

            //saving user activity
            if ($this->users[$userId]['active']!=$_POST['ueditactive']) {
                simple_update_field($tablename, 'active', $_POST['ueditactive'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE ACTIVE `'.$_POST['ueditactive'].'`');
            }
          
            //saving registration date
            if ($this->users[$userId]['regdate']!=$_POST['ueditregdate']) {
                simple_update_field($tablename, 'regdate', $_POST['ueditregdate'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE REGDATE `'.$_POST['ueditregdate'].'`');
            }
            
           //saving user internet backlinking
            if ($this->users[$userId]['inetlogin']!=$_POST['ueditinetlogin']) {
                simple_update_field($tablename, 'inetlogin', $_POST['ueditinetlogin'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE INETLOGIN `'.$_POST['ueditinetlogin'].'`');
            }
           
            //saving passport number
            if ($this->users[$userId]['passnum']!=$_POST['ueditpassnum']) {
                simple_update_field($tablename, 'passnum', $_POST['ueditpassnum'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE PASSPORTNUM `'.$_POST['ueditpassnum'].'`');
            }
            
            //saving passport issuing authority
            if ($this->users[$userId]['passwho']!=$_POST['ueditpasswho']) {
                simple_update_field($tablename, 'passwho', $_POST['ueditpasswho'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE PASSPORTWHO `'.$_POST['ueditpasswho'].'`');
            }
            
            //saving passport issue date
            if ($this->users[$userId]['passdate']!=$_POST['ueditpassdate']) {
                simple_update_field($tablename, 'passdate', $_POST['ueditpassdate'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE PASSPORTDATE `'.$_POST['ueditpassdate'].'`');
            }

            //saving user SSN
            if ($this->users[$userId]['ssn']!=$_POST['ueditssn']) {
                simple_update_field($tablename, 'ssn', $_POST['ueditssn'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE SSN `'.$_POST['ueditssn'].'`');
            }
            
            //saving user registration address
            if ($this->users[$userId]['paddr']!=$_POST['ueditpaddr']) {
                simple_update_field($tablename, 'paddr', $_POST['ueditpaddr'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE  PASSADDRESS`'.$_POST['ueditpaddr'].'`');
            }
            
            //saving user notes
            if ($this->users[$userId]['notes']!=$_POST['ueditnotes']) {
                simple_update_field($tablename, 'notes', $_POST['ueditnotes'], $where);
                log_register('UKV USER (('.$userId.')) CHANGE  NOTES `'.$_POST['ueditnotes'].'`');
            }

            
            
        } else {
            throw new Exception(self::EX_USER_NOT_SET);
        }
    }


    /*
     * returns some existing user profile
     * 
     * @param $userid existing user`s ID
     * 
     * @return string
     */
    public function userProfile($userid) {
        global $ubillingConfig;
        $altcfg=$ubillingConfig->getAlter();
        $userid=vf($userid,3);
        if (isset($this->users[$userid])) {
            $userData=$this->users[$userid];
            $rows='';
            
            //zero apt numbers as private builds
                if ($altcfg['ZERO_TOLERANCE']) {
                    $apt= ($userData['apt']==0) ? '' : '/'.$userData['apt'] ;
                } else {
                    $apt='/'.$userData['apt'];
                }
           
            
            $cells=  wf_TableCell(__('Full address'), '20%', 'row2');
            $cells.= wf_TableCell($userData['city'].' '.$userData['street'].' '.$userData['build'].$apt);
            $rows.= wf_TableRow($cells, 'row3');
                 
            
            $cells=  wf_TableCell(__('Real Name'), '20%', 'row2');
            $cells.= wf_TableCell($userData['realname']);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Phone'), '20%', 'row2');
            $cells.= wf_TableCell($userData['phone']);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Mobile'), '20%', 'row2');
            $cells.= wf_TableCell($userData['mobile']);
            $rows.= wf_TableRow($cells, 'row3');
                 
            $cells=  wf_TableCell(wf_tag('b').__('Contract').  wf_tag('b',true), '20%', 'row2');
            $cells.= wf_TableCell(wf_tag('b').$userData['contract'].wf_tag('b',true));
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Tariff'), '20%', 'row2');
            $cells.= wf_TableCell(@$this->tariffs[$userData['tariffid']]['tariffname']);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(wf_tag('b').__('Cash').wf_tag('b',true), '20%', 'row2');
            $cells.= wf_TableCell(wf_tag('b').$userData['cash'].wf_tag('b',true));
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Connected'), '20%', 'row2');
            $cells.= wf_TableCell(web_bool_led($userData['active']));
            $rows.= wf_TableRow($cells, 'row3');
           
            $cells=  wf_TableCell(__('User contract date'), '20%', 'row2');
            $cells.= wf_TableCell($userData['regdate']);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Internet account'), '20%', 'row2');
            $inetLink=(!empty($userData['inetlogin'])) ? wf_Link(self::URL_INET_USER_PROFILE.$userData['inetlogin'], web_profile_icon().' '.$userData['inetlogin'], false, '') : '';
            $cells.= wf_TableCell($inetLink);
            $rows.= wf_TableRow($cells, 'row3');
            
            $cells=  wf_TableCell(__('Notes'), '20%', 'row2');
            $cells.= wf_TableCell($userData['notes']);
            $rows.= wf_TableRow($cells, 'row3');
            
            $result=  wf_TableBody($rows, '100%', 0, '');
            
            $result.= wf_modal(wf_img('skins/icon_user_edit_big.gif', __('Edit user')), __('Edit user'), $this->userEditForm($userid), '', '900', '530');
            
            return ($result);
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }
    
    
    /*
     * renders full user list with some ajax data
     * 
     * @return string
     */
    public function renderUsers() {
          $jqDt='
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#ukvusershp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "'.__('Show').' _MENU_",
			"sZeroRecords": "'.__('Nothing found').'",
			"sInfo": "'.__('Showing').' _START_ '.__('to').' _END_ '.__('of').' _TOTAL_ '.__('users').'",
			"sInfoEmpty": "'.__('Showing').' 0 '.__('to').' 0 '.__('of').' 0 '.__('users').'",
			"sInfoFiltered": "('.__('Filtered').' '.__('from').' _MAX_ '.__('Total').')",
                        "sSearch":       "'.__('Search').'",
                        "sProcessing":   "'.__('Processing').'..."
		},
           
                "aoColumns": [
                null,
                null,
                null,
                null,
                null,
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": false,
        "iDisplayLength": 50,
        "sAjaxSource": \''.self::URL_USERS_AJAX_SOURCE.'\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';
      
     $result=$jqDt;

     $result.= wf_tag('table', false, '', 'width="100%" id="ukvusershp"');
     $result.= wf_tag('thead');
     $cells=  wf_TableCell(__('Full address'));
     $cells.= wf_TableCell(__('Real Name'));
     $cells.= wf_TableCell(__('Contract'));
     $cells.= wf_TableCell(__('Tariff'));
     $cells.= wf_TableCell(__('Connected'));
     $cells.= wf_TableCell(__('Cash'));
     $result.= wf_TableRow($cells, 'row1');
     $result.= wf_tag('thead', true);
     
     $result.= wf_tag('table', true);
     
      
      return ($result);
    }
    
    /*
     * extract ajax data for JQuery data tables
     */
    public function ajaxUsers() {
        global $ubillingConfig;
        $altcfg=$ubillingConfig->getAlter();
        
        $result='{ 
                  "aaData": [ ';
        if (!empty($this->users)) {
            foreach ($this->users as $io=>$each) {
                
                //zero apt numbers as private builds
                if ($altcfg['ZERO_TOLERANCE']) {
                    $apt= ($each['apt']==0)? '' : '/'.$each['apt'];
                } else {
                    $apt='/'.$each['apt'];
                }
                //city display
                if ($altcfg['CITY_DISPLAY']) {
                    $city=$each['city'].' ';
                } else {
                    $city='';
                }
                
                //activity flag
                $activity= ($each['active']) ? web_bool_led($each['active']).' '.__('Yes') : web_bool_led($each['active']).' '.__('No');
                $activity= str_replace('"', '', $activity);
                
                //profile link
                $profileLink=  wf_Link(self::URL_USERS_PROFILE.$each['id'], web_profile_icon(), false).' ';
                $profileLink=  str_replace('"', '', $profileLink);
                $profileLink= str_replace("\n", '', $profileLink);
                
                $result.='
                    [
                    "'.$profileLink.$city.$each['street'].' '.$each['build'].$apt.'",
                    "'.$each['realname'].'",
                    "'.$each['contract'].'",
                    "'.@$this->tariffs[$each['tariffid']]['tariffname'].'",
                    "'.$activity.'",
                    "'.$each['cash'].'"
                    ],';
            }
            $result=  substr($result,0,-1);
        }
         $result.='] 
        }';
        die($result);
    }
    
    
    
}



?>
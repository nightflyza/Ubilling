<?php
if (cfr('CORPS')) {
    $altcfg=$ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $greed=new Avarice();
        $beggar=$greed->runtime('CORPS');
        if (!empty($beggar)) {
            
            class Corps {
                
                const ROUTE_PREFIX='show';
                
                const URL_TAXTYPE='taxtypes';
                const URL_TAXTYPE_LIST='?module=corps&show=taxtypes';
                const URL_TAXTYPE_DEL='?module=corps&show=taxtypes&deltaxtypeid=';
                
                const URL_CORPS='corps';
                const URL_CORPS_LIST='?module=corps&show=corps';
                const URL_CORPS_EDIT='?module=corps&show=corps&editid=';
                const URL_CORPS_ADD='?module=corps&show=corps&add=true';
                const URL_CORPS_DEL='?module=corps&show=corps&deleteid=';
                
                
                protected $users=array();
                protected $corps=array();
                protected $persons=array();
                protected $taxtypes=array();
                protected $doctypes=array(
                                '1'=>'Certificate',
                                '2'=>'Regulations',
                                '3'=>'Reference'
                            );
             
                public function __construct() {
                    $this->loadUsers();
                    $this->loadCorps();
                    $this->loadPersons();
                    $this->loadTaxtypes();
                }
                
                /*
                 * loads available corps from database into private prop
                 * 
                 * @return void
                 */
                protected function loadCorps() {
                    $query="SELECT * from `corp_data`";
                    $all=  simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io=>$each) {
                            $this->corps[$each['id']]=$each;
                        }
                    }
                }
                
                /*
                 * loads taxtypes from database
                 * 
                 * @return void
                 */
                protected function loadTaxtypes() {
                    $query="SELECT * from `corp_taxtypes`";
                    $all=  simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io=>$each) {
                            $this->taxtypes[$each['id']]=$each['type'];
                        }
                    }
                }
                
                /*
                 * loads contact persons from database
                 * 
                 * @return void
                 */
                protected function loadPersons() {
                    $query="SELECT * from `corp_persons`";
                    $all=  simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io=>$each) {
                            $this->persons[$each['id']]=$each;
                        }
                    }
                }
                
                 /*
                 * loads user bindings from database and store it into private prop users
                 * 
                 * @return void
                 */
                protected function loadUsers() {
                    $query="SELECT * from `corp_users`";
                    $all=  simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io=>$each) {
                            $this->users[$each['login']]=$each['corpid'];
                        }
                    }
                }
                
                /*
                 * returns existing taxtype edit form
                 * 
                 * @param $id int existing tax type ID
                 * 
                 * @return string
                 */
                protected function taxtypeEditForm($id) {
                    $id=vf($id,3);
                    $result='';
                    if (isset($this->taxtypes[$id])) { 
                        $inputs=  wf_HiddenInput('edittaxtypeid', $id);
                        $inputs.= wf_TextInput('edittaxtype', __('Type'), $this->taxtypes[$id], true, '40');
                        $inputs.= wf_Submit(__('Save'));
                        $result=  wf_Form("", 'POST', $inputs, 'glamour');
                    } else {
                        $result=__('Not existing item');
                    }
                    return ($result);
                }
                
                 /*
                 * returns new taxtype creation form
                 * 
                 * @return string
                 */
                protected function taxtypeCreateForm() {
                        $inputs= wf_TextInput('newtaxtype', __('Type'), '', true, '40');
                        $inputs.= wf_Submit(__('Create'));
                        $result=  wf_Form("", 'POST', $inputs, 'glamour');
                    
                    return ($result);
                }
                
                /*
                 * creates new taxtype 
                 * 
                 * @param $type string new taxtype
                 * 
                 * @return void
                 */
                public function taxtypeCreate($type) {
                    $type=  mysql_real_escape_string($type);
                    $query="INSERT INTO  `corp_taxtypes` (`id`, `type`) VALUES (NULL, '".$type."'); ";
                    nr_query($query);
                    $newId=  simple_get_lastid('corp_taxtypes');
                    log_register("CORPS CREATE TAXTYPE [".$newId."]");
                    
                }
                
                /*
                 * returns standard localized deletion alert
                 * 
                 * @return string
                 */
                protected function alertDelete() {
                    return (__('Removing this may lead to irreparable results'));
                }
                
                /*
                 * return existing taxtypes list with edit controls
                 * 
                 * @return string
                 */
                
                public function taxtypesList() {
                    $cells=  wf_TableCell(__('ID'));
                    $cells.= wf_TableCell(__('Type'));
                    $cells.= wf_TableCell(__('Actions'));
                    $rows=  wf_TableRow($cells, 'row1');
                    if (!empty($this->taxtypes)) {
                        foreach ($this->taxtypes as $id=>$type) {
                            $cells=  wf_TableCell($id);
                            $cells.= wf_TableCell($type);
                            $actlinks= wf_JSAlert(self::URL_TAXTYPE_DEL.$id, web_delete_icon(), $this->alertDelete());
                            $actlinks.=  wf_modal(web_edit_icon(), __('Edit'), $this->taxtypeEditForm($id), '', '450', '150');
                            $cells.= wf_TableCell($actlinks);
                            $rows.=  wf_TableRow($cells, 'row3');
                        }
                    }
                    
                    $result= wf_TableBody($rows, '100%', '0', 'sortable');
                    $result.= wf_modal(wf_img('skins/icon_add.gif').' '.__('Create'), __('Create'), $this->taxtypeCreateForm(), 'ubButton', '450', '150');
                    return ($result);
                }
                
                /*
                 * deletes existing tax type from database
                 * 
                 * @return void
                 */
                public function taxtypeDelete($id) {
                    $id=vf($id,3);
                    if (isset($this->taxtypes[$id])) {
                        $query="DELETE from `corp_taxtypes` WHERE `id`='".$id."';";
                        nr_query($query);
                        log_register("CORPS DELETE TAXTYPE [".$id."]");
                    }   
                    
                }
                
                /*
                 * edits existing tax type
                 * 
                 * @param $id int existing taxtype ID
                 * @param $type new taxtype description
                 * 
                 * @return void
                 */
                public function taxtypeEdit($id,$type) {
                    $id=vf($id,3);
                    if (isset($this->taxtypes[$id])) {
                        simple_update_field('corp_taxtypes', 'type', $type, "WHERE `id`='".$id."';");
                        log_register("CORPS EDIT TAXTYPE [".$id."]");
                    }
                }
                
                /*
                 * list available corps with some controls
                 * 
                 * @return string
                 */
                public function corpsList() {
                    
                    $cells=wf_TableCell(__('ID'));
                    $cells.=wf_TableCell(__('Corp name'));
                    $cells.=wf_TableCell(__('Address'));
                    $cells.=wf_TableCell(__('Document type'));
                    $cells.=wf_TableCell(__('Document date'));
                    $cells.=wf_TableCell(__('Tax payer status'));
                    $cells.=wf_TableCell(__('Actions'));
                    $rows=  wf_TableRow($cells, 'row1');
                    if (!empty($this->corps)) {
                        foreach ($this->corps as $io=>$each) {
                            $cells=wf_TableCell($each['id']);
                            $cells.=wf_TableCell($each['corpname']);
                            $cells.=wf_TableCell($each['address']);
                            if (isset($this->doctypes[$each['doctype']])) {
                                $doctype=__($this->doctypes[$each['doctype']]);
                            } else {
                                $doctype=$each['doctype'];
                            }
                            $cells.=wf_TableCell($doctype);
                            $cells.=wf_TableCell($each['docdate']);
                            if (isset($this->taxtypes[$each['taxtype']])) {
                                $taxtype=  $this->taxtypes[$each['taxtype']];
                            } else {
                                $taxtype=$each['taxtype'];
                            }
                            $cells.=wf_TableCell($taxtype);
                            $actlinks=   wf_JSAlert(self::URL_CORPS_DEL.$each['id'], web_delete_icon(), $this->alertDelete()).' ';
                            $actlinks.=  wf_JSAlert(self::URL_CORPS_EDIT.$each['id'], web_edit_icon(), __('Are you serious')).' ';
                            $actlinks.= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), $each['corpname'], $this->corpPreview($each['id']), '', '800', '600');
                            $cells.=wf_TableCell($actlinks);
                            $rows.=  wf_TableRow($cells, 'row3');
                            
                        }
                    }
                    
                    $result=  wf_TableBody($rows, '100%', 0, 'sortable');
                    return ($result);
                }
                
                /*
                 * show existing corp preview
                 * 
                 * @param $id int existing corp ID
                 * 
                 * @return string
                 */
                public function corpPreview($id) {
                    $id=vf($id,3);
                    $result='';
                    if (isset($this->corps[$id])) {
                        $cells= wf_TableCell(__('Corp name'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['corpname']);
                        $rows= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Address'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['address']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Document type'), '', 'row2');
                        if (isset($this->doctypes[$this->corps[$id]['doctype']])) {
                            $doctype=__($this->doctypes[$this->corps[$id]['doctype']]);
                        } else {
                            $doctype=$this->corps[$id]['doctype'];
                        }
                        $cells.= wf_TableCell($doctype);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Document number'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['docnum']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Document date'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['docdate']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Bank account'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['bankacc']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Bank name'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['bankname']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Bank MFO'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['bankmfo']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('EDRPOU'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['edrpou']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('NDS number'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['ndstaxnum']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('INN code'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['inncode']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Tax type'), '', 'row2');
                        if (isset($this->taxtypes[$this->corps[$id]['taxtype']])) {
                            $taxtype=$this->taxtypes[$this->corps[$id]['taxtype']];
                        } else {
                            $taxtype=$this->corps[$id]['taxtype'];
                        }
                        $cells.= wf_TableCell($taxtype);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $cells= wf_TableCell(__('Notes'), '', 'row2');
                        $cells.= wf_TableCell($this->corps[$id]['notes']);
                        $rows.= wf_TableRow($cells, 'row3');
                        
                        $result=  wf_TableBody($rows, '100%', '0');
                        $result.= $this->personsList($id);
                    } else {
                        $result=__('Not existing item');
                    }
                    return ($result);
                }
                
                /*
                 * returns selector of existing doctypes
                 * 
                 * @param $name string input name
                 * 
                 * @return string
                 */
                protected function doctypeSelector($name,$selected='') {
                    $doctypes=array();
                    if (!empty($this->doctypes)) {
                        foreach ($this->doctypes as $id=>$type) {
                            $doctypes[$id]=__($type);
                        }
                    }
                    $result=  wf_Selector($name, $doctypes, __('Document type'), $selected, false);
                    return ($result);
                }
                


                /*
                 * returns corp edit form
                 * 
                 * @param $id existing corp ID
                 * 
                 * @return string
                 */
                public function corpEditForm($id) {
                    $id=vf($id,3);
                    $result='';
                    if (isset($this->corps[$id])) {
                        $data=$this->corps[$id];
                        $sup=  wf_tag('sup').'*'.wf_tag('sup',true);
                        $inputs=  wf_HiddenInput('editcorpid',$id);
                        $inputs.= wf_TextInput('editcorpname', __('Corp name').$sup, $data['corpname'], true, '40');
                        $inputs.= wf_TextInput('editcoraddress', __('Address'), $data['address'], true, '40');
                        $inputs.= $this->doctypeSelector('editdoctype', $data['doctype']);
                        $inputs.= wf_DatePickerPreset('editdocdate', $data['docdate'], true).' '.__('Document date').  wf_tag('br');
                        $inputs.= wf_TextInput('editdocnum', __('Document number'), $data['docnum'], true, '20');
                        $inputs.= wf_TextInput('editbankacc', __('Bank account'), $data['bankacc'], true, '20');
                        $inputs.= wf_TextInput('editbankname', __('Bank name'), $data['bankname'], true, '20');
                        $inputs.= wf_TextInput('editbankmfo', __('Bank MFO'), $data['bankmfo'], true, '20');
                        $inputs.= wf_TextInput('editedrpou', __('EDRPOU'), $data['edrpou'], true, '20');
                        $inputs.= wf_TextInput('editndstaxnum', __('NDS number'), $data['ndstaxnum'], true, '20');
                        $inputs.= wf_TextInput('editinncode', __('INN code'), $data['inncode'], true, '20');
                        $inputs.= wf_Selector('edittaxtype', $this->taxtypes, __('Tax type'), $data['taxtype'], true);
                        $inputs.= wf_TextInput('editnotes', __('Notes'), $data['notes'], true, '40');
                        $inputs.= wf_Submit(__('Save'));
                        
                        
                        $result=  wf_Form('', 'POST', $inputs, 'glamour');
                    } else {
                        $result=__('Not existing item');
                    }
                    return ($result);
                }
                
                      /*
                 * returns corp edit form
                 * 
                 * @param $id existing corp ID
                 * 
                 * @return string
                 */
                public function corpCreateForm() {
                        $sup=  wf_tag('sup').'*'.wf_tag('sup',true);
                        
                        $inputs=  wf_HiddenInput('createcorpid','true');
                        $inputs.= wf_TextInput('createcorpname', __('Corp name').$sup, '', true, '40');
                        $inputs.= wf_TextInput('createaddress', __('Address'), '', true, '40');
                        $inputs.= $this->doctypeSelector('createdoctype', '');
                        $inputs.= wf_DatePickerPreset('createdocdate', curdate(), true).' '.__('Document date').  wf_tag('br');
                        $inputs.= wf_TextInput('adddocnum', __('Document number'), '', true, '20');
                        $inputs.= wf_TextInput('addbankacc', __('Bank account'), '', true, '20');
                        $inputs.= wf_TextInput('addbankname', __('Bank name'), '', true, '20');
                        $inputs.= wf_TextInput('addbankmfo', __('Bank MFO'), '', true, '20');
                        $inputs.= wf_TextInput('addedrpou', __('EDRPOU'), '', true, '20');
                        $inputs.= wf_TextInput('addndstaxnum', __('NDS number'), '', true, '20');
                        $inputs.= wf_TextInput('addinncode', __('INN code'), '', true, '20');
                        $inputs.= wf_Selector('addtaxtype', $this->taxtypes, __('Tax type'), '', true);
                        $inputs.= wf_TextInput('addnotes', __('Notes'), '', true, '40');
                        $inputs.= wf_Submit(__('Create'));
                        
                        
                        $result=  wf_Form('', 'POST', $inputs, 'glamour');
                    
                    return ($result);
                }
                
                
                /*
                 * deletes existing corp by ID
                 * 
                 * @param $id int existing corp ID
                 * 
                 * @return void
                 */
                public function corpDelete($id) {
                    $id=vf($id,3);
                    if (isset($this->corps[$id])) {
                        $query="DELETE from `corp_data` WHERE `id`='".$id."'; ";
                        nr_query($query);
                        log_register("CORPS DELETE CORP [".$id."]");
                    }
                    
                }
                
                /*
                 * edits corp in database
                 * 
                 * @param $id int existing corp ID
                 * 
                 * @return void
                 */
                public function corpSave($id) {
                    $id=vf($id,3);
                    if (isset($this->corps[$id])) {
                        simple_update_field('corp_data', 'corpname', $_POST['editcorpname'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'address', $_POST['editcoraddress'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'doctype', $_POST['editdoctype'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'docdate', $_POST['editdocdate'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'docnum', $_POST['editdocnum'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'bankacc', $_POST['editbankacc'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'bankname', $_POST['editbankname'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'bankmfo', $_POST['editbankmfo'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'edrpou', $_POST['editedrpou'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'ndstaxnum', $_POST['editndstaxnum'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'inncode', $_POST['editinncode'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'taxtype', $_POST['edittaxtype'], "WHERE `id`='".$id."'");
                        simple_update_field('corp_data', 'notes', $_POST['editnotes'], "WHERE `id`='".$id."'");
                        log_register("CORPS EDIT CORP [".$id."]");
                    }
                    
                }
                
                /*
                 * creates new corp in database
                 * 
                 * @return int
                 */
                
                public function corpCreate() {
                    $corpname=  mysql_real_escape_string($_POST['createcorpname']);
                    $address=mysql_real_escape_string($_POST['createaddress']);
                    $doctype=vf($_POST['createdoctype'],3);
                    $docdate=mysql_real_escape_string($_POST['createdocdate']);
                    $docnum=mysql_real_escape_string($_POST['adddocnum']);
                    $bankacc=mysql_real_escape_string($_POST['addbankacc']);
                    $bankname=mysql_real_escape_string($_POST['addbankname']);
                    $bankmfo=mysql_real_escape_string($_POST['addbankmfo']);
                    $edrpou=mysql_real_escape_string($_POST['addedrpou']);
                    $taxnum=mysql_real_escape_string($_POST['addndstaxnum']);
                    $inncode=mysql_real_escape_string($_POST['addinncode']);
                    $taxtype=vf($_POST['addtaxtype'],3);
                    $notes=mysql_real_escape_string($_POST['addnotes']);
                    $query="INSERT INTO `corp_data` (`id`, `corpname`, `address`, `doctype`, `docnum`, `docdate`, `bankacc`, `bankname`, `bankmfo`, `edrpou`, `ndstaxnum`, `inncode`, `taxtype`, `notes`) "
                         . "VALUES (NULL, '".$corpname."', '".$address."', '".$doctype."', '".$docnum."', '".$docdate."', '".$bankacc."', '".$bankname."', '".$bankmfo."', '".$edrpou."', '".$taxnum."', '".$inncode."', '".$taxtype."', '".$notes."');";
                    nr_query($query);
                    $newID=  simple_get_lastid('corp_data');
                    log_register("CORPS CREATE CORP [".$newID."]");
                    return ($newID);
                }
                
                /*
                 * returns corps link panel
                 * 
                 * @return string
                 */
                public function corpsPanel() {
                    $result=   wf_Link(self::URL_CORPS_ADD, wf_img('skins/icon_add.gif').' '.__('Create'), false, 'ubButton');
                    $result.=  wf_Link(self::URL_CORPS_LIST, wf_img('skins/icon_search_small.gif').' '.__('Available corps'), false, 'ubButton');
                    $result.=  wf_Link(self::URL_TAXTYPE_LIST, wf_img('skins/icon_dollar.gif').' '.__('Available tax types'), false, 'ubButton');
                    return ($result);
                }
                
                
                /*
                 * returns contact persons list for some corp
                 * 
                 * @param $corpid int Existing corp ID
                 */
                protected function personsList($corpid) {
                    $corpid=vf($corpid,3);
                    $result='';
                    if (!empty($this->persons)) {
                         $cells=  wf_TableCell(__('ID'));
                         $cells.= wf_TableCell(__('Real Name'));
                         $cells.= wf_TableCell(__('Phone'));
                         $cells.= wf_TableCell(__('IM'));
                         $cells.= wf_TableCell(__('Email'));
                         $cells.= wf_TableCell(__('Appointment'));
                         $rows=  wf_TableRow($cells, 'row1');
                        
                        foreach ($this->persons as $io=>$each) {
                         if ($each['corpid']==$corpid) {
                                 $cells=  wf_TableCell($each['id']);
                                 $cells.= wf_TableCell($each['realname']);
                                 $cells.= wf_TableCell($each['phone']);
                                 $cells.= wf_TableCell($each['im']);
                                 $cells.= wf_TableCell($each['email']);
                                 $cells.= wf_TableCell($each['appointment']);
                                 $rows.=  wf_TableRow($cells, 'row3');
                         }
                            
                        }
                        
                        $result.= wf_tag('b').__('Contact persons').  wf_tag('b',true);
                        $result.=  wf_TableBody($rows, '100%', '0', 'sortable');
                    }
                    return ($result);
                }
                
            
            }
            
            
            
            
            
            /*
             * controller section
             */
            
            $corps=new Corps();
            
            
            if (wf_CheckGet(array(Corps::ROUTE_PREFIX))) {
                $route=$_GET[Corps::ROUTE_PREFIX];
                
                //taxtypes controller
                if ($route==Corps::URL_TAXTYPE) {
                   //del 
                   if (wf_CheckGet(array('deltaxtypeid'))) {
                       if (method_exists($corps, $beggar['METH']['TTFLUSH']))  $corps->$beggar['METH']['TTFLUSH']($_GET['deltaxtypeid']);
                       rcms_redirect(Corps::URL_TAXTYPE_LIST);
                   }
                   //edit
                   if (wf_CheckPost(array('edittaxtypeid','edittaxtype'))) {
                       $corps->taxtypeEdit($_POST['edittaxtypeid'], $_POST['edittaxtype']);
                       rcms_redirect(Corps::URL_TAXTYPE_LIST);
                   }
                   //add
                   if (wf_CheckPost(array('newtaxtype'))) {
                       $corps->taxtypeCreate($_POST['newtaxtype']);
                       rcms_redirect(Corps::URL_TAXTYPE_LIST);
                   }
                    
                    show_window('',wf_Link(Corps::URL_CORPS_LIST, __('Back'), true, 'ubButton'));
                     if (method_exists($corps, $beggar['METH']['TTRENDER']))    show_window(__('Available tax types'),$corps->$beggar['METH']['TTRENDER']());
                 
                }
                
                
                //corps controller
                if ($route==Corps::URL_CORPS) {
                    show_window('',$corps->corpsPanel());
                    
                    //del
                    if (wf_CheckGet(array('deleteid'))) {
                        if (method_exists($corps, $beggar['METH']['FLUSH'])) $corps->$beggar['METH']['FLUSH']($_GET['deleteid']);
                        rcms_redirect(Corps::URL_CORPS_LIST);
                    }
                    
                    //add
                    if (wf_CheckGet(array('add'))) {
                        //creation 
                        if (wf_CheckPost(array('createcorpid'))) {
                            if (method_exists($corps, $beggar['METH']['ADD'])) $corps->$beggar['METH']['ADD']();
                            rcms_redirect(Corps::URL_CORPS_LIST);
                        }
                        show_window('', wf_Link(Corps::URL_CORPS_LIST, __('Back'), true, 'ubButton'));
                        if (method_exists($corps, $beggar['VP']['FADF']))  show_window(__('Create'),$corps->$beggar['VP']['FADF']());
                    }
                    
                    //editing
                    if (wf_CheckGet(array('editid'))) {
                        //editing push
                        if (wf_CheckPost(array('editcorpid','editcorpname'))) {
                            if (method_exists($corps, $beggar['METH']['PUSH']))  $corps->$beggar['METH']['PUSH']($_POST['editcorpid']);
                            rcms_redirect(Corps::URL_CORPS_LIST);
                        }
                        
                        show_window('', wf_Link(Corps::URL_CORPS_LIST, __('Back'), true, 'ubButton'));
                        if (method_exists($corps, $beggar['VP']['MODF']))  show_window(__('Edit'), $corps->$beggar['VP']['MODF']($_GET['editid']));
                    } else {
                        
                           if (!wf_CheckGet(array('add'))) {
                               if (method_exists($corps, $beggar['METH']['RENDER']))   show_window(__('Available corps'),$corps->$beggar['METH']['RENDER']());
                           } 
                    }
                    
                    
                    
                    
                }
                
                
                
                
            }
            
            
            
            
            
            
        } else {
            show_window(__('Error'), __('No license key available'));
        }
        
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
    
} else {
    show_window(__('Error'), __('Access denied'));
}

?>
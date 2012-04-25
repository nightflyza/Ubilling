<?php

 function cf_TypeGetAll() {
      $query="SELECT * from `cftypes`";
      $result=simple_queryall($query);
      return($result);
  }
  
  function cf_TypeGetData($typeid) {
      $typeid=vf($typeid,3);
      $query="SELECT * from `cftypes` WHERE `id`='".$typeid."'";
      $result=simple_query($query);
      return($result);
  }
   
  
  function cf_TypeDelete($cftypeid) {
      $cftypeid=vf($cftypeid);
      $query="DELETE from `cftypes` WHERE `id`='".$cftypeid."'";
      nr_query($query);
      log_register("CFTYPE DELETE ".$cftypeid);
  }
  
  
  function cf_TypeAdd($newtype,$newname) {
      $newtype=vf($newtype);
      $newname=mysql_real_escape_string($newname);
      if ((!empty ($newname)) AND (!empty ($newtype))) {
      $query="
          INSERT INTO `cftypes` (
            `id` ,
            `type` ,
            `name`
             )
             VALUES (    
             NULL , '".$newtype."', '".$newname."'
             );";
      nr_query($query);
      log_register("CFTYPE ADD ".$newtype." ".$newname);
      }
   }
   
   
   function cf_TypeAddForm() {
       $form='
           <form action="" method="POST">
           <select name="newtype">
           <option value="VARCHAR">VARCHAR</option>
           <option value="TRIGGER">TRIGGER</option>
           <option value="TEXT">TEXT</option>
           </select> '.__('Field type').' <br>
           <input type="text" size="15" name="newname"> '.__('Field name').' <br>
           <input type="submit" value="'.__('Create').'">
           </form>
           ';
       return($form);
   }
   
   function cf_TypeEditForm($typeid) {
       $typeid=vf($typeid,3);
       $typedata=cf_TypeGetData($typeid);
       $current_type=$typedata['type'];
       $current_name=$typedata['name'];
       $availtypes=array('VARCHAR'=>'VARCHAR','TRIGGER'=>'TRIGGER','TEXT'=>'TEXT');
       
       $editinputs=wf_HiddenInput('editid', $typeid);
       $editinputs.=wf_Selector('edittype', $availtypes, 'Field type', $current_type, true);
       $editinputs.=wf_TextInput('editname', 'Field name', $current_name, true);
       $editinputs.=wf_Submit('Edit');
       $editform=wf_Form('', 'POST', $editinputs, 'glamour');
       show_window(__('Edit custom field type'),$editform);
       show_window('',wf_Link('?module=cftypes', 'Back', true, 'ubButton'));
       
   }
   
  
  function cf_TypesShow() {
      //construct editor
      $titles=array(
          'ID',
          'Field type',
          'Field name'
              );
      $keys=array(
          'id',
          'type',
          'name'
      );
      $alldata=cf_TypeGetAll();
      $module='cftypes';
      //show it
      $result=web_GridEditor($titles, $keys, $alldata, $module, true, true);
      return($result);
  }
  
  function cf_TypeGetController($login,$type,$typeid) {
      $type=vf($type);
      $typeid=vf($typeid);
      $login=mysql_real_escape_string($login);
      $result='';
      if ($type=='VARCHAR') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="modtype" value="'.$typeid.'">
            <input type="hidden" name="login" value="'.$login.'"> 
            <input type="text"  name="content" size="20" value=""> 
            <input type="submit" value="'.__('Save').'">
            </form>
            ';    
      }
      
      if ($type=='TRIGGER') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="modtype" value="'.$typeid.'">
            <input type="hidden" name="login" value="'.$login.'"> 
            <select name="content">
            <option value="1">'.__('Yes').'</option>
            <option value="0">'.__('No').'</option>
            </select> 
            <input type="submit" value="'.__('Save').'">
            </form>
            ';    
      }
      
        if ($type=='TEXT') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="modtype" value="'.$typeid.'">
            <input type="hidden" name="login" value="'.$login.'"> 
            <textarea name="content" cols="25" rows="5"></textarea> 
            <input type="submit" value="'.__('Save').'">
            </form>
            ';    
      }
      return ($result);
  }
  
  
  function cf_TypeGetSearchControl($type,$typeid) {
      $type=vf($type);
      $typeid=vf($typeid);
      $result='';
      if ($type=='VARCHAR') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="cftypeid" value="'.$typeid.'">
            <input type="text"  name="cfquery" size="20" value=""> 
            <input type="submit" value="'.__('Search').'">
            </form>
            ';    
      }
      
      if ($type=='TRIGGER') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="cftypeid" value="'.$typeid.'">
            <select name="cfquery">
            <option value="1">'.__('Yes').'</option>
            <option value="0">'.__('No').'</option>
            </select> 
            <input type="submit" value="'.__('Search').'">
            </form>
            ';    
      }
      
        if ($type=='TEXT') {
        $result='
            <form action="" method="POST">
            <input type="hidden" name="cftypeid" value="'.$typeid.'">
            <input type="text"  name="cfquery" size="20" value=""> 
            <input type="submit" value="'.__('Search').'">
            </form>
            ';    
      }
      return ($result);
  }
  
  
   function cf_FieldSet($typeid,$login,$content) {
      $typeid=vf($typeid);
      $login=mysql_real_escape_string($login);
      $content=mysql_real_escape_string($content);
      cf_FieldDelete($login,$typeid);
      $query="
          INSERT INTO `cfitems` (
            `id` ,
            `typeid` ,
            `login` ,
            `content`
                )
            VALUES (
            NULL , '".$typeid."', '".$login."', '".$content."'
            );
            ";
      nr_query($query);
      log_register("CF SET ".$login." ".$typeid);
  }
  
  
  function cf_FieldDelete($login,$typeid) {
      $typeid=vf($typeid);
      $login=mysql_real_escape_string($login);
      $query="DELETE from `cfitems` WHERE `typeid`='".$typeid."' AND `login`='".$login."'";
      nr_query($query);
  }
  
  
  function cf_FieldGet($login,$typeid) {
      $typeid=vf($typeid);
      $login=mysql_real_escape_string($login);
      $result='';
      $query="SELECT `content` from `cfitems` WHERE `login`='".$login."' AND `typeid`='".$typeid."'";
      $content=simple_query($query);
      if (!empty ($content)) {
        $result=$content['content'];
      }
      return ($result);
   }
  
   
   function cf_FieldDisplay($type,$data) {
       if ($type=='TRIGGER') {
           $data=  web_bool_led($data);
       }
       if ($type=='TEXT') {
           $data= str_replace("\n", '<br>', $data);
       }
       return ($data);
   }
  

      function cf_FieldEditor($login) {
          global $billing;
          $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
          
       //edit routine 
       if (isset ($_POST['modtype'])) {
        cf_FieldSet($_POST['modtype'], $_POST['login'], $_POST['content']);
        
        //need to reset user after change?
        if ($alter_conf['RESETONCFCHANGE']) {
            $billing->resetuser($login);
            log_register('RESET User '.$login);
        }
        
        }
       $alltypes=cf_TypeGetAll();
       $login=mysql_real_escape_string($login);
       $form='';
       if (!empty ($alltypes)) {
           $form.='<table width="100%" border="0">';
           $form.='
                <tr class="row1">
                <td>'.__('Field name').'</td>
                <td>'.__('Current value').'</td>
                <td>'.__('Actions').'</td>
                </tr>
                ';
           foreach ($alltypes as $io=>$eachtype) {
               
            $form.='
                <tr class="row3">
                <td>'.$eachtype['name'].'</td>
                <td>'. cf_FieldDisplay($eachtype['type'],cf_FieldGet($login, $eachtype['id'])).'</td>
                <td>'.cf_TypeGetController($login, $eachtype['type'],$eachtype['id']).'</td>
                </tr>
                ';
            
            
           }
           
       $form.='</table>';
       show_window(__('Additional profile fields'),$form);
       }
   }
  
   
   function cf_FieldShower($login) {
       $alltypes=cf_TypeGetAll();
       $login=mysql_real_escape_string($login);
       $form='';
       if (!empty ($alltypes)) {
           $form.='<table width="100%" border="0">';
           
           foreach ($alltypes as $io=>$eachtype) {
               
            $form.='
                <tr>
                <td  class="row2" width="30%">'.$eachtype['name'].'</td>
                <td class="row3">'.cf_FieldDisplay($eachtype['type'],cf_FieldGet($login, $eachtype['id'])).'</td>
                </tr>
                ';
           }
           
       $form.='</table>';
      
       }
       return($form);
   }
   
?>

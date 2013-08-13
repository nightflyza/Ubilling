<?php
/*
 * backport of user tags API from kvtstg
 */

function web_priority_selector($max=6) {
    $selector='<select name="newpriority">';
    for ($i=$max;$i>0;$i--) {
        $selector.='<option value="'.$i.'">'.$i.'</option>';
    }
    $selector.='</select>';
    return($selector);
}

 function stg_show_tagtypes() {
     $query="SELECT * from `tagtypes` ORDER BY `id` ASC";
     $alltypes=simple_queryall($query);

     $cells=   wf_TableCell(__('ID'));
     $cells.=  wf_TableCell(__('Color'));
     $cells.=  wf_TableCell(__('Priority'));
     $cells.=  wf_TableCell(__('Text'));
     $cells.=  wf_TableCell(__('Actions'));
     $rows=  wf_TableRow($cells, 'row1');
     
     if (!empty ($alltypes)) {
         foreach ($alltypes as $io =>$eachtype) {
             $eachtagcolor=$eachtype['tagcolor'];
             $actions=  wf_JSAlert('?module=usertags&delete='.$eachtype['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
             $actions.= wf_Link('?module=usertags&edit='.$eachtype['id'], web_edit_icon(), false);
             
             $cells=   wf_TableCell($eachtype['id']);
             $cells.=  wf_TableCell(wf_tag('font', false, '', 'color="'.$eachtagcolor.'"').$eachtagcolor.  wf_tag('font', true));
             $cells.=  wf_TableCell($eachtype['tagsize']);
             $cells.=  wf_TableCell($eachtype['tagname']);
             $cells.=  wf_TableCell($actions);
             $rows.=  wf_TableRow($cells, 'row3');
     
         }
     }
      
     $result=  wf_TableBody($rows, '100%', 0, 'sortable');
     
     //construct adding form
     $inputs=  wf_TextInput('newcolor', __('Color'), '#'.rand(11,99).rand(11,99).rand(11,99), false, '10');
     $inputs.=  wf_TextInput('newtext', __('Text'), '', false, '15');
     $inputs.= web_priority_selector().' '.__('Priority').' ';
     $inputs.= wf_HiddenInput('addnewtag', 'true');
     $inputs.= wf_Submit(__('Create'));
     $form= wf_Form("", 'POST', $inputs, 'glamour');
     $result.= $form;
     
     
show_window(__('Tag types'), $result);
 }
 
  function stg_add_tagtype() {
     $color=mysql_real_escape_string($_POST['newcolor']);
     $size=vf($_POST['newpriority']);
     $text=mysql_real_escape_string($_POST['newtext']);
     $query="
         INSERT INTO `tagtypes` (
`id` ,
`tagcolor` ,
`tagsize` ,
`tagname`
)
VALUES (
NULL , '".$color."', '".$size."', '".$text."'
);
 ";
 nr_query($query);
 stg_putlogevent('TAGTYPE ADD `'.$text.'`');
 }
 
   function stg_delete_tagtype($tagid) {
     $tagid=vf($tagid,3);
     $query="DELETE from `tagtypes` WHERE `id`='".$tagid."'";
     nr_query($query);
     log_register('TAGTYPE DELETE ['.$tagid.']');
 }
 
 function stg_get_alltagnames() {
     $query="SELECT * from `tagtypes`";
     $alltagtypes=  simple_queryall($query);
     $result=array();
     if(!empty ($alltagtypes)) {
     foreach ($alltagtypes as $io=>$eachtype) {
         $result[$eachtype['id']]=$eachtype['tagname'];
     }    
     }
     return($result);
 }
 
  function stg_get_tagtype_data($tagtypeid) {
     $tagtypeid=vf($tagtypeid,3);
     $query="SELECT * from `tagtypes` WHERE `id`='".$tagtypeid."'";
     $result=simple_query($query);
     return($result);
 }
 
 function stg_show_user_tags($login) {
     $query="SELECT * from `tags` WHERE `login`='".$login."';";
     $alltags=simple_queryall($query);
     $result='';
     $oo=0;
     if (!empty ($alltags)) {
        foreach ($alltags as $io=>$eachtag){
            $result.=stg_get_tag_body($eachtag['tagid']);
        }
          return($result);
     }
     
 }
 
  function stg_tagadd_selector() {
    $query="SELECT * from `tagtypes` ORDER by `id` ASC";
    $alltypes=simple_queryall($query);
    $selector='
        <form action="" method="POST">
        <select name="tagselector">';
    if (!empty ($alltypes)) {
        foreach ($alltypes as $io=>$eachtype) {
            $selector.='<option value="'.$eachtype['id'].'">'.$eachtype['tagname'].'</option>';
        }
    }
    $selector.='</select>
        <input type="submit" value="'.__('Add').'">
        </form>';
    show_window(__('Add tag'),$selector);
 }
 
  function stg_tagid_selector() {
    $query="SELECT * from `tagtypes`";
    $alltypes=simple_queryall($query);
    $selector='
        <select name="newtagid">';
    if (!empty ($alltypes)) {
        foreach ($alltypes as $io=>$eachtype) {
            $selector.='<option value="'.$eachtype['id'].'">'.$eachtype['tagname'].'</option>';
        }
    }
    $selector.='</select>';
    return($selector);
 }

 function stg_tagdel_selector($login) {
     $login=vf($login);
     $query="SELECT * from `tags` where `login`='".$login."'";
     $usertags=simple_queryall($query);
     $result='';
     if (!empty ($usertags)) {
         foreach ($usertags as $io=>$eachtag) {
             $result.=stg_get_tag_body_deleter($eachtag['tagid'],$login,$eachtag['id']);
         }
     }
     show_window(__('Delete tag'), $result);
 }
 
  function stg_add_user_tag($login, $tagid) {
     $login=vf($login);
     $query="
         INSERT INTO `tags` (
`id` ,
`login` ,
`tagid`
)
VALUES (
NULL , '".$login."', '".$tagid."'
); ";
     nr_query($query);
     stg_putlogevent('TAGADD ('.$login.') TAGID ['.$tagid.']');
 }
 
  function stg_del_user_tag($tagid) {
     $query="DELETE from `tags` WHERE `id`='".$tagid."'";
     nr_query($query);
     stg_putlogevent('TAGDEL TAGID ['.$tagid.']');
  }

 function stg_get_tag_body($id) {
     $query="SELECT * from `tagtypes` where `id`='".$id."'";
     $tagbody=simple_query($query);
     
     $result=  wf_tag('font', false, '', 'color="'.$tagbody['tagcolor'].'" size="'.$tagbody['tagsize'].'"');
     $result.= '<a href="?module=tagcloud&tagid='.$id.'" style="color: '.$tagbody['tagcolor'].';">'.$tagbody['tagname'].'</a>';
     $result.= wf_tag('font',true);
     $result.='&nbsp;';
     return($result);
 }
 
   function stg_get_tag_body_deleter($id,$login,$tagid) {
     $query="SELECT * from `tagtypes` where `id`='".$id."'";
     $tagbody=simple_query($query);
     $result='<font color="'.$tagbody['tagcolor'].'" size="'.$tagbody['tagsize'].'">'.$tagbody['tagname'].'
         <sup>
         <a href="?module=usertags&username='.$login.'&deletetag='.$tagid.'">'.  web_delete_icon().'</a>
         </sup>
         </font> &nbsp; ';
     return($result);
 }
 
 function zb_FlushAllUserTags($login) {
       $login=  mysql_real_escape_string($login);
       $query="DELETE from `tags` WHERE `login`='".$login."'";
       nr_query($query);
       log_register("TAG FLUSH (".$login.")");
     
 }
 
 function zb_VserviceCreate($tagid,$price,$cashtype,$priority) {
        $tagid=vf($tagid);
        $price=vf($price);
        $cashtype=vf($cashtype);
        $priority=vf($priority);
        $query="
            INSERT INTO `vservices` (
                `id` ,
                `tagid` ,
                `price` ,
                `cashtype` ,
                `priority`
                )
                VALUES (
                NULL , '".$tagid."', '".$price."', '".$cashtype."', '".$priority."'
                );";
        nr_query($query);
        log_register("CREATE VSERVICE ".$tagid.' '.$price.' '.$cashtype.' '.$priority);
    }
    
    function zb_VsericeDelete($vservid) {
        $vservid=vf($vservid);
        $query="DELETE from `vservices` where `id`='".$vservid."'";
        nr_query($query);
        log_register("DELETE VSERVICE ".$vservid);
    }
    
    function zb_VserviceGetAllData() {
        $query="SELECT * from `vservices`";
        $result=array();
        $result=simple_queryall($query);
        return ($result);
    }
    
    function zb_VservicesGetAllNames() {
        $result=array();
        $allservices=zb_VserviceGetAllData();
        $alltagnames=stg_get_alltagnames();
        if (!empty ($allservices)) {
            foreach ($allservices as $io=>$eachservice) {
                @$result[$eachservice['id']]=$alltagnames[$eachservice['tagid']];
            }
        }
        return ($result);
    }
    
    
    function zb_VservicesGetAllNamesLabeled() {
        $result=array();
        $allservices=zb_VserviceGetAllData();
        $alltagnames=stg_get_alltagnames();
        if (!empty ($allservices)) {
            foreach ($allservices as $io=>$eachservice) {
                @$result['Service:'.$eachservice['id']]=$alltagnames[$eachservice['tagid']];
            }
        }
        return ($result);
    }
    
    function web_VserviceAddForm() {
            $form='
                <form action="" method="POST" class="glamour">
                <br>    '. stg_tagid_selector() .' '.__('Tag').'
                <br>    <select name="newcashtype"> 
                            <option value="stargazer">'.__('stargazer user cash').'</option>
                            <option value="virtual">'.__('virtual services cash').'</option>
                        </select>'.__('Cash type').'
                <br>    '.  web_priority_selector().' '.__('Priority').'
                <br>    <input type="text" name="newfee" size="5"> '.__('Fee').'
                <br>    <input type="submit" value="'.__('Create').'">
                </form>
                
                ';
            return($form);
        }
    
    function web_VservicesShow() {
        $allvservices=zb_VserviceGetAllData();
        $tagtypesquery="SELECT * from `tagtypes`";
        $alltagtypes=simple_queryall($tagtypesquery);
        
        //construct editor
        $titles=array(
        'ID',
        'Tag',
        'Fee',
        'Cash type',
        'Priority'
        );
    $keys=array('id',
        'tagid',
        'price',
        'cashtype',
        'priority'
        );
    show_window(__('Virtual services'),web_GridEditorVservices($titles, $keys, $allvservices,'vservices',true,false));
    if (!empty($alltagtypes)) {
     show_window(__('Add virtual service'),  web_VserviceAddForm());   
     }
    }
    
    function zb_VserviceCashClear($login) {
        $login=vf($login);
        $query="DELETE from `vcash` where `login`='".$login."'";
        nr_query($query);
    }
    
    function zb_VserviceCashCreate($login,$cash) {
        $login=vf($login);
        $cash=mysql_real_escape_string($cash);
        $query_set="INSERT INTO `vcash` (
                `id` ,
                `login` ,
                `cash`
                )
                VALUES (
                NULL , '".$login."', '".$cash."'
                );  ";
        nr_query($query_set);
        log_register("ADD VCASH ".$login." ".$cash);
    }
    
     function zb_VserviceCashSet($login,$cash) {
        $login=vf($login);
        $cash=mysql_real_escape_string($cash);
        $query_set="UPDATE `vcash` SET `cash` = '".$cash."' WHERE `login` ='".$login."' LIMIT 1 ;";
        nr_query($query_set);
        log_register("CHANGE VCASH ".$login." ".$cash);
    }
    
    function zb_VserviceCashGet($login) {
        $login=vf($login);
        $query="SELECT `cash` from `vcash` WHERE `login`='".$login."'";
        $result=simple_query($query);
         if (empty ($result)) {
            $result=0;
            zb_VserviceCashCreate($login, 0);
            } else {
            $result=$result['cash'];
            }
            return($result);
    }
    
    function zb_VserviceCashLog($login,$balance,$cash,$cashtype,$note='') {
    $login=vf($login);
    $cash=mysql_real_escape_string($cash);
    $cashtype=vf($cashtype);
    $note=mysql_real_escape_string($note);
    $date=curdatetime();
    $balance=zb_VserviceCashGet($login);
        $query="INSERT INTO `vcashlog` (
                `id` ,
                `login` ,
                `date` ,
                `balance` ,
                `summ` ,
                `cashtypeid` ,
                `note`
                )
                VALUES (
                NULL , '".$login."', '".$date."', '".$balance."', '".$cash."', '".$cashtype."', '".$note."'
                );";
        nr_query($query);
    }
    
    
    function zb_VserviceCashFee($login,$fee,$vserviceid) {
        $login=vf($login);
        $fee=vf($fee);
        $balance=zb_VserviceCashGet($login);
        $newcash=$balance-$fee;
        zb_VserviceCashSet($login, $newcash);
        zb_VserviceCashLog($login,$balance, $newcash, $vserviceid);
    }
 
    function zb_VserviceCashAdd($login,$cash,$vserviceid) {
        $login=vf($login);
        $cash=mysql_real_escape_string($cash);
        $balance=zb_VserviceCashGet($login);
        $newcash=$balance+$cash;
        zb_VserviceCashSet($login, $newcash);
        zb_VserviceCashLog($login,$balance, $newcash, $vserviceid);
    }
    
    
    
    function web_VservicesSelector() {
        $allservices=zb_VserviceGetAllData();
        $alltags= stg_get_alltagnames();
        $select='<select name="vserviceid">';
        if (!empty ($allservices)) {
            foreach ($allservices as $io=>$eachservice) {
                $select.='<option value="'.$eachservice['id'].'">'.@$alltags[$eachservice['tagid']].'</option>';
            }
        }
        $select.='<select>';
        return($select);
    }
    
    function zb_VservicesProcessAll($debug=0,$log_payment=true) {
    $query_services="SELECT * from `vservices` ORDER by `priority` DESC";
                if ($debug) {
                print (">>".curdatetime()."\n");
                print (">>Searching virtual services\n");
                print ($query_services."\n");
                }
    $allservices=simple_queryall($query_services);
    if (!empty ($allservices)) {
         if ($debug) {
                print (">>Virtual services found!\n");
                print_r($allservices);
                }
        foreach ($allservices as $io=>$eachservice) {
            $users_query="SELECT `login` from `tags` WHERE `tagid`='".$eachservice['tagid']."'";
                    if ($debug) {
                        print (">>Searching users with this services\n");
                        print($users_query."\n");
                        }
            $allusers=simple_queryall($users_query);
            if (!empty ($allusers)) {
                if ($debug) {
                print (">>Users found!\n");
                print_r($allusers);
                }
                foreach ($allusers as $io2=>$eachuser) {
                    if ($debug) {
                    print (">>Processing user:".$eachuser['login']."\n");
                    }
                    if ($debug) {
                    print (">>service:".$eachservice['id']."\n");
                    print (">>price:".$eachservice['price']."\n");
                    print (">>processing cashtype:".$eachservice['cashtype']."\n");
                    }
                    if ($eachservice['cashtype']=='virtual') {
                        if ($debug) {
                            $current_cash=zb_VserviceCashGet($eachuser['login']);
                            print(">>current cash:".$current_cash."\n");
                        }
                        if ($debug!=2) {
                        zb_VserviceCashFee($eachuser['login'], $eachservice['price'], $eachservice['id']);
                        }
                    }
                    if ($eachservice['cashtype']=='stargazer') {
                          if ($debug) {
                             $current_cash=zb_UserGetStargazerData($eachuser['login']);
                             $current_cash=$current_cash['Cash'];
                             print(">>current cash:".$current_cash."\n");
                        }
                        if ($debug!=2) {
                        $fee="-".$eachservice['price'];
                        if ($log_payment) {
                            $method='add';
                        } else {
                            $method='correct';
                        }
                        zb_CashAdd($eachuser['login'], $fee, $method, '1', 'Service:'.$eachservice['id']);
                        }
                    }
                }
              }
            
        }
    }
    
}
    
?>
<?php

function zb_CardCreate($serial,$cash) {
    $admin=whoami();
    $date=curdatetime();
    $query="
        INSERT INTO `cardbank` (
                    `id` ,
                    `serial` ,
                    `cash` ,
                    `admin` ,
                    `date` ,
                    `active` ,
                    `used` ,
                    `usedate` ,
                    `usedlogin` ,
                    `usedip`
                    )
                    VALUES (
                    NULL , '".$serial."', '".$cash."', '".$admin."', '".$date."', '1', '0', NULL , '', NULL
                    );
                    ";
    nr_query($query);
}

function zb_CardGenerate($count,$price) {
    $count=vf($count);
    $price=vf($price);
    if ((empty ($count)) OR (empty ($price))) {
        die("No count or price");
    }
    $reported='';
    for ($cardcount=0;$cardcount<$count;$cardcount++) {
        $serial=mt_rand(1111, 9999).mt_rand(1111, 9999).mt_rand(1111, 9999).mt_rand(1111, 9999);
        $reported.=$serial."\n";
        zb_CardCreate($serial, $price);
    }
    log_register("CARDS CREATED ".$cardcount." PRICE ".$price);
    return($reported);
}

function zb_CardsGetCount() {
        $query="SELECT COUNT(`id`) from `cardbank`";
        $result=  simple_query($query);
        $result=$result['COUNT(`id`)'];
        return ($result);
}

function web_CardsShow() {
    
    $totalcount=zb_CardsGetCount();
    $perpage=100;
    
     //pagination 
         if (!isset ($_GET['page'])) {
          $current_page=1;
          } else {
          $current_page=vf($_GET['page'],3);
          }
          
          if ($totalcount>$perpage) {
          $paginator=wf_pagination($totalcount, $perpage, $current_page, "?module=cards",'ubButton');
          $from=$perpage*($current_page-1);
          $to=$perpage;
          $query="SELECT * from `cardbank` ORDER by `id` DESC LIMIT ".$from.",".$to.";";
          $alluhw=  simple_queryall($query);
         
          } else {
          $paginator='';
          $query="SELECT * from `cardbank` ORDER by `id` DESC;";
          $alluhw=  simple_queryall($query);
        }
    
    
    $allcards=  simple_queryall($query);
    $result='<table width="100%" boder="0">
        <form action="" method="POST">
        ';
     $result.='
                 <tr class="row1">
                 <td>'.__('ID').'</td>
                 <td>'.__('Serial number').'</td>
                 <td>'.__('Price').'</td>
                 <td>'.__('Admin').'</td>
                 <td>'.__('Date').'</td>
                 <td>'.__('Active').'</td>
                 <td>'.__('Used').'</td>
                 <td>'.__('Usage date').'</td>
                 <td>'.__('Used login').'</td>
                 <td>'.__('Used IP').'</td>
                 <td></td>
                 </tr>
                 ';
             
    if (!empty ($allcards)) {
        foreach ($allcards as $io => $eachcard) {
            
             $result.='
                 <tr class="row3">
                 <td>'.$eachcard['id'].'</td>
                 <td>'.$eachcard['serial'].'</td>
                 <td>'.$eachcard['cash'].'</td>
                 <td>'.$eachcard['admin'].'</td>
                 <td>'.$eachcard['date'].'</td>
                 <td>'.web_bool_led($eachcard['active']).'</td>
                 <td>'.web_bool_led($eachcard['used']).'</td>
                 <td>'.$eachcard['usedate'].'</td>
                 <td>'.$eachcard['usedlogin'].'</td>
                 <td>'.$eachcard['usedip'].'</td>
                 <td><input type="checkbox" name="_cards['.$eachcard['id'].']"></td>
                 </tr>
                 ';
             
        }
    }
    $result.='</table>';
    $result.=$paginator.  wf_delimiter();
    $result.='
        <select name="cardactions">
        <option value="caexport">'.__('Export serials').'</option>
        <option value="caactive">'.__('Mark as active').'</option>
        <option value="cainactive">'.__('Mark as inactive').'</option>
        <option value="cadelete">'.__('Delete').'</option>
        </select>
        <input type="submit" value="'.__('With selected').'">
        </form>
        ';
    
    
    
    return($result);
}


function web_CardsGenerateForm() {
    $form='
        <form action="" method="POST">
       '.__('Count').': <input type="text" name="gencount" size="3">
       '.__('Price').': <input type="text" name="genprice" size="3">
        <input type="submit" value="'.__('Create').'">
        </form>
        ';
    
    $inputs=  wf_TextInput('gencount', 'Count', '', false,'5');
    $inputs.=wf_TextInput('genprice', 'Price', '', false,'5');
    $inputs.=wf_Submit('Create');
    $form=  wf_Form("", 'POST', $inputs, 'glamour');
    
    return($form);
}

function zb_CardsGetData($id) {
    $id=vf($id);
    $query="SELECT * from `cardbank` WHERE `id`='".$id."'";
    $result=simple_query($query);
    return($result);
}

function zb_CardsMarkInactive($id) {
    $id=vf($id);
    $query="UPDATE `cardbank` SET `active` = '0' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register("CARDS INACTIVE ".$id);
}

function zb_CardsMarkActive($id) {
    $id=vf($id);
    $query="UPDATE `cardbank` SET `active` = '1' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register("CARDS ACTIVE ".$id);
}


function zb_CardsDelete($id) {
    $id=vf($id);
    $query="DELETE FROM `cardbank` WHERE `id`='".$id."'";
    nr_query($query);
    log_register("CARDS DELETE ".$id);
}

function zb_CardsExport($id) {
    $carddata=zb_CardsGetData($id);
    $cardnum=$carddata['serial'];
    // i want to templatize it later
    $result=$cardnum;
    return($result);
}

function zb_CardsMassactions() {
    if (isset($_POST['_cards'])) {
    $cards_arr=$_POST['_cards'];
       if (!empty ($cards_arr)) {
        //cards export
        if ($_POST['cardactions']=='caexport') {
            $exportdata='<textarea rows="20" cols="80">';
         foreach ($cards_arr as $cardid=>$on) {
             $exportdata.=zb_CardsExport($cardid)."\n";
         }
         $exportdata.='</textarea>';
         show_window(__('Export'),$exportdata);
        }
       // cards activate
       if ($_POST['cardactions']=='caactive') {
         foreach ($cards_arr as $cardid=>$on) {
             zb_CardsMarkActive($cardid);
         }  
       }
       
       // cards deactivate
       if ($_POST['cardactions']=='cainactive') {
         foreach ($cards_arr as $cardid=>$on) {
             zb_CardsMarkInactive($cardid);
         }  
       }
       
       // cards delete
       if ($_POST['cardactions']=='cadelete') {
         foreach ($cards_arr as $cardid=>$on) {
             zb_CardsDelete($cardid);
         }  
        }
       } else {
           show_error(__('No cards selected'));
       }
 
         
    } else {
        show_error('No cards selected');
    }
}


function web_CardShowBrutes() {
    $query="SELECT * from `cardbrute`";
    $allbrutes=simple_queryall($query);
    $result='<table width="100%" border="0" class="sortable">';
     $result.='
                <tr class="row1">
                <td>'.__('ID').'</td>
                <td>'.__('Serial number').'</td>
                <td>'.__('Date').'</td>
                <td>'.__('Login').'</td>
                <td>'.__('IP').'</td>
                <td>'.__('Full address').'</td>
                <td>'.__('Real Name').'</td>
                </tr>
                ';
    if (!empty ($allbrutes)) {
        $allrealnames=zb_UserGetAllRealnames();
        $alladdress=zb_AddressGetFulladdresslist();
        foreach ($allbrutes as $io=>$eachbrute) {
            $result.='
                <tr class="row3">
                <td>'.$eachbrute['id'].'</td>
                <td>'.$eachbrute['serial'].'</td>
                <td>'.$eachbrute['date'].'</td>
                <td>'.$eachbrute['login'].' <a  href="?module=userprofile&username='.$eachbrute['login'].'">'.  web_profile_icon().'</a> </td>
                <td>'.$eachbrute['ip'].' <a href="?module=cards&cleanip='.$eachbrute['ip'].'">'.web_delete_icon('Clean this IP').'</a> </td>
                <td>'.@$alladdress[$eachbrute['login']].'</td>
                <td>'.@$allrealnames[$eachbrute['login']].'</td>
                </tr>
                ';
        }
    }
    $result.='</table>';
    return ($result);
}

function zb_CardBruteCleanIP($ip) {
    $query="DELETE from `cardbrute` where `ip`='".$ip."'";
    nr_query($query);
    log_register("CARDBRUTE DELETE ".$ip);
}

?>

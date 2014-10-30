<?php

/**
 * Creates card in database with some serial and price
 * 
 * @param int   $serial
 * @param float $cash
 * 
 * @return void
 */
function zb_CardCreate($serial,$cash) {
    $admin=whoami();
    $date=curdatetime();
    $query="INSERT INTO `cardbank` (`id` ,`serial` , `cash` , `admin` , `date` , `active` , `used` , `usedate` , `usedlogin` , `usedip`) "
         . "VALUES (NULL , '".$serial."', '".$cash."', '".$admin."', '".$date."', '1', '0', NULL , '', NULL);";
    nr_query($query);
}

/**
 * Generates cards in database with some price, and returns it serials
 * 
 * @param int   $count
 * @param float $price
 * @return string
 */
function zb_CardGenerate($count,$price) {
    $count=vf($count,3);
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
    log_register("CARDS CREATED `".$cardcount."` PRICE `".$price."`");
    return($reported);
}

/**
 * Returns count of available payment cards
 * 
 * @return int
 */
function zb_CardsGetCount() {
        $query="SELECT COUNT(`id`) from `cardbank`";
        $result=  simple_query($query);
        $result=$result['COUNT(`id`)'];
        return ($result);
}

/**
 * Returns available list with some controls
 * 
 * @return string
 */
function web_CardsShow() {
    $result='';
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
        
    $cells=  wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Serial number'));
    $cells.= wf_TableCell(__('Price'));
    $cells.= wf_TableCell(__('Admin'));
    $cells.= wf_TableCell(__('Date'));
    $cells.= wf_TableCell(__('Active'));
    $cells.= wf_TableCell(__('Used'));
    $cells.= wf_TableCell(__('Usage date'));
    $cells.= wf_TableCell(__('Used login'));
    $cells.= wf_TableCell(__('Used IP'));
    $cells.= wf_TableCell('');
    $rows= wf_TableRow($cells, 'row1') ;
             
    if (!empty ($allcards)) {
        foreach ($allcards as $io => $eachcard) {
 
            $cells=  wf_TableCell($eachcard['id']);
            $cells.= wf_TableCell($eachcard['serial']);
            $cells.= wf_TableCell($eachcard['cash']);
            $cells.= wf_TableCell($eachcard['admin']);
            $cells.= wf_TableCell($eachcard['date']);
            $cells.= wf_TableCell(web_bool_led($eachcard['active']));
            $cells.= wf_TableCell(web_bool_led($eachcard['used']));
            $cells.= wf_TableCell($eachcard['usedate']);
            $cells.= wf_TableCell($eachcard['usedlogin']);
            $cells.= wf_TableCell($eachcard['usedip']);
            $cells.= wf_TableCell(wf_CheckInput('_cards['.$eachcard['id'].']', '', false, false));
            $rows.= wf_TableRow($cells, 'row3') ;
             
        }
    }
   
    $result=  wf_TableBody($rows, '100%', 0, '');
    $result.=$paginator.  wf_delimiter();
    
   
    $cardActions=array(
        'caexport'=>__('Export serials'),
        'caactive'=>__('Mark as active'),
        'cainactive'=>__('Mark as inactive'),
        'cadelete'=>__('Delete')
    );
    
    $actionSelect=  wf_Selector('cardactions', $cardActions, '', '', false);
    $actionSelect.= wf_Submit(__('With selected'));
    $result.=$actionSelect;
    $result= wf_Form('', 'POST', $result, '');
    
    return($result);
}

/**
 * Returns new cards generation form
 * 
 * @return string
 */
function web_CardsGenerateForm() {
    $inputs=  wf_TextInput('gencount', 'Count', '', false,'5');
    $inputs.=wf_TextInput('genprice', 'Price', '', false,'5');
    $inputs.=wf_Submit('Create');
    $form=  wf_Form("", 'POST', $inputs, 'glamour');
    
    return($form);
}

/**
 * Returns cards search form
 * 
 * @return string
 */
function web_CardsSearchForm() {
    $inputs=  wf_TextInput('cardsearch', __('Serial number'), '', false, '17');
    $inputs.= wf_Submit('Search');
    $result=  wf_Form("", "POST", $inputs, 'glamour');
    return ($result);
}

/**
 * Returns card by serial search results
 * 
 * @param string $serial
 * @return string
 */
function web_CardsSearchBySerial($serial) {
    $serial=  mysql_real_escape_string($serial);
    $query="SELECT * from `cardbank` WHERE `serial` LIKE '%".$serial."%'";
    $allcards=  simple_queryall($query);
    $result=__('Nothing found');
    
    if (!empty($allcards)) {
 
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Serial number'));
        $cells.= wf_TableCell(__('Price'));
        $cells.= wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Active'));
        $cells.= wf_TableCell(__('Used'));
        $cells.= wf_TableCell(__('Usage date'));
        $cells.= wf_TableCell(__('Used login'));
        $cells.= wf_TableCell(__('Used IP'));
        $rows  = wf_TableRow($cells, 'row1');
         
        foreach ($allcards as $io=>$eachcard) {
                $cells=  wf_TableCell($eachcard['id']);
                $cells.= wf_TableCell($eachcard['serial']);
                $cells.= wf_TableCell($eachcard['cash']);
                $cells.= wf_TableCell($eachcard['admin']);
                $cells.= wf_TableCell($eachcard['date']);
                $cells.= wf_TableCell(web_bool_led($eachcard['active']));
                $cells.= wf_TableCell(web_bool_led($eachcard['used']));
                $cells.= wf_TableCell($eachcard['usedate']);
                $cells.= wf_TableCell($eachcard['usedlogin']);
                $cells.= wf_TableCell($eachcard['usedip']);
                $rows.=  wf_TableRow($cells, 'row3');
        }
        
        $result=  wf_TableBody($rows, '100%', 0, 'sortable');
    }
    return ($result);
}

/**
 * Gets payment card data by its ID
 * 
 * @param int $id
 * @return array
 */
function zb_CardsGetData($id) {
    $id=vf($id,3);
    $query="SELECT * from `cardbank` WHERE `id`='".$id."'";
    $result=simple_query($query);
    return($result);
}

/**
 * Marks payment card as inactive
 * 
 * @param int $id
 */
function zb_CardsMarkInactive($id) {
    $id=vf($id,3);
    $query="UPDATE `cardbank` SET `active` = '0' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register("CARDS INACTIVE [".$id."]");
}

/**
 * Marks payment card as active
 * 
 * @param int $id
 */
function zb_CardsMarkActive($id) {
    $id=vf($id,3);
    $query="UPDATE `cardbank` SET `active` = '1' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register("CARDS ACTIVE [".$id."]");
}

/**
 * Delete card from database by its ID
 * 
 * @param int $id
 */
function zb_CardsDelete($id) {
    $id=vf($id,3);
    $query="DELETE FROM `cardbank` WHERE `id`='".$id."'";
    nr_query($query);
    log_register("CARDS DELETE [".$id."]");
}

/**
 * Exports payment card number 
 * 
 * @param int $id
 * @return string
 */
function zb_CardsExport($id) {
    $id=vf($id,3);
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
         $exportdata='';
         foreach ($cards_arr as $cardid=>$on) {
             $exportdata.=zb_CardsExport($cardid)."\n";
         }
         
         $exportresult=  wf_TextArea($exportdata, '', $exportdata, true, '80x20');
         show_window(__('Export'),$exportresult);
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
        show_error(__('No cards selected'));
    }
}

/**
 * Returns payment card brutes attempts list
 * 
 * @return string
 */
function web_CardShowBrutes() {
    $query="SELECT * from `cardbrute`";
    $allbrutes=simple_queryall($query);

    $cells=  wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Serial number'));
    $cells.= wf_TableCell(__('Date'));
    $cells.= wf_TableCell(__('Login'));
    $cells.= wf_TableCell(__('IP'));
    $cells.= wf_TableCell(__('Full address'));
    $cells.= wf_TableCell(__('Real Name'));
    $rows=  wf_TableRow($cells, 'row1');
     
    if (!empty ($allbrutes)) {
        $allrealnames=zb_UserGetAllRealnames();
        $alladdress=zb_AddressGetFulladdresslist();
        foreach ($allbrutes as $io=>$eachbrute) {
            $cleaniplink=  wf_JSAlert('?module=cards&cleanip='.$eachbrute['ip'], web_delete_icon(__('Clean this IP')), __('Removing this may lead to irreparable results'));
           
            $cells=  wf_TableCell($eachbrute['id']);
            $cells.= wf_TableCell($eachbrute['serial']);
            $cells.= wf_TableCell($eachbrute['date']);
            $cells.= wf_TableCell(wf_Link('?module=userprofile&username='.$eachbrute['login'], web_profile_icon().' '.$eachbrute['login']));
            $cells.= wf_TableCell($eachbrute['ip'].' '.$cleaniplink);
            $cells.= wf_TableCell(@$alladdress[$eachbrute['login']]);
            $cells.= wf_TableCell(@$allrealnames[$eachbrute['login']]);
            $rows.=  wf_TableRow($cells, 'row3');
        }
    }
    
    $result=  wf_TableBody($rows, '100%', 0, 'sortable');
    $cleanAllLink=  wf_JSAlert('?module=cards&cleanallbrutes=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), 'Are you serious');
    show_window(__('Bruteforce attempts').' '.$cleanAllLink,$result);
    return ($result);
}

/**
 * Deletes some brute attempt by target IP
 * 
 * @param string $ip
 * @return void
 */
function zb_CardBruteCleanIP($ip) {
    $query="DELETE from `cardbrute` where `ip`='".$ip."'";
    nr_query($query);
    log_register("CARDBRUTE DELETE `".$ip."`");
}

/**
 * Deletes all brute attempts
 * 
 * @return void
 */
function zb_CardBruteCleanupAll() {
    $query="TRUNCATE TABLE `cardbrute`;";
    nr_query($query);
    log_register("CARDBRUTE CLEANUP");
}

?>

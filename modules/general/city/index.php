<?php
// check for right of current admin on this module
if (cfr('CITY')) {

    function web_CityCreateForm() {
        $form='
            <form action="" method="POST">
            <input type="text" name="newcityname"> '.__('New City name').'<sup>*</sup> <br>
            <input type="text" name="newcityalias"> '.__('New City alias').' <br>
            <input type="submit" value="'.__('Create').'">
            </form>
            ';
        return($form);
    }

    function web_CityLister() {
    $allcity=zb_AddressGetCityAllData();
    $form='<table width="100%" border="0" class="sortable">';
    $form.='
        <tr class="row1">
            <td>'.__('ID').'</td>
            <td>'.__('City name').'</td>
            <td>'.__('City alias').'</td>
            <td>'.__('Actions').'</td>
        </tr>
        ';
    if (!empty ($allcity)) {
        foreach ($allcity as $io=>$eachcity) {
        $form.='
        <tr class="row3">
            <td>'.$eachcity['id'].'</td>
            <td>'.$eachcity['cityname'].'</td>
            <td>'.$eachcity['cityalias'].'</td>
            <td>
            '.  wf_JSAlert('?module=city&action=delete&cityid='.$eachcity['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
            '.  wf_JSAlert('?module=city&action=edit&cityid='.$eachcity['id'], web_edit_icon(), 'Are you serious').'
            <a href="?module=streets">'.  web_street_icon().'</a>
            </td>
        </tr>
        ';
        }
    }
    $form.='</table>';
    return($form);
    }


    function web_CityEditForm($cityid) {
        $citydata=zb_AddressGetCityData($cityid);
        $cityname=$citydata['cityname'];
        $cityalias=$citydata['cityalias'];
        $form='
            <form action="" method="POST">
            <input type="text" name="editcityname" value="'.$cityname.'"> '.__('City name').'<sup>*</sup> <br>
            <input type="text" name="editcityalias" value="'.$cityalias.'"> '.__('City alias').' <br>
            <input type="submit" value="'.__('Save').'">
            </form>
            ';
        $form.=wf_Link('?module=city', 'Back', true, 'ubButton');
        return($form);
    }
    ///// routines
    if (isset($_POST['newcityname'])) {
        $newcityname=$_POST['newcityname'];
        if (isset($_POST['newcityalias'])) {
        $newcityalias=$_POST['newcityalias'];
        } else {
        $newcityalias='';
        }
        
        if (!empty($newcityname)) {
         zb_AddressCreateCity($newcityname, $newcityalias);
         rcms_redirect('?module=city');
        } else {
            show_error(__('Empty city name'));
        }
    }
    
    if (isset($_GET['action'])) {
        if (isset($_GET['cityid'])) {
        $cityid=$_GET['cityid'];

        if ($_GET['action']=='delete') {
            if (!zb_AddressCityProtected($cityid)) {
            zb_AddressDeleteCity($cityid);
            rcms_redirect('?module=city');
            } else {
                show_window(__('Error'),__('You can not just remove a city where there are streets and possibly survivors'));
            }
        }
        if ($_GET['action']=='edit') {
            if (isset ($_POST['editcityname'])) {
                if (!empty($_POST['editcityname'])) {
                    zb_AddressChangeCityName($cityid, $_POST['editcityname']);
                }
                
                zb_AddressChangeCityAlias($cityid, $_POST['editcityalias']);
                rcms_redirect('?module=city');
          }
            show_window(__('Edit City'),web_CityEditForm($cityid));
        }
        }
    }
    ///// forms
    show_window(__('Create new city'),web_CityCreateForm());
    show_window(__('Available cities'),web_CityLister());
    
} else {
      show_error(__('You cant control this module'));
}

?>

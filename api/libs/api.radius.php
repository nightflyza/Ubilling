<?php

function web_NasTemplatesShow() {
    $query="SELECT * from `nastemplates`";
    $alltemplates=simple_queryall($query);
    
    $tablecells=wf_TableCell(__('ID'));
    $tablecells.=wf_TableCell(__('NAS'));
    $tablecells.=wf_TableCell(__('Template'));
    $tablecells.=wf_TableCell(__('Actions'));
    $tablerows=wf_TableRow($tablecells, 'row1');
    
    if (!empty($alltemplates)) {
        
        foreach ($alltemplates as $io=>$eachtemplate) {
            $nasdata=zb_NasGetData($eachtemplate['nasid']);
            
            $tablecells=wf_TableCell($eachtemplate['id']);
            $tablecells.=wf_TableCell($eachtemplate['nasid'].':'.$nasdata['nasname']);
            $tablecells.=wf_TableCell('<pre>'.$eachtemplate['template'].'</pre>');
            $actions=wf_JSAlert("?module=radiust&delete=".$eachtemplate['id'], web_delete_icon(), 'Are you serious');
            $actions.=wf_Link("?module=radiust&edit=".$eachtemplate['id'], web_edit_icon(), false, '');
            $tablecells.=wf_TableCell($actions);
            $tablerows.=wf_TableRow($tablecells, 'row3');
        }
    }
    $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
    show_window(__('Available NAS Radius attribute templates'),$result);
}

function web_NasTemplateAddForm() {
    $allradiusnas_q="SELECT * from `nas` WHERE `nastype`='radius'";
    $allradiusnas=simple_queryall($allradiusnas_q);
    $nasselector=array();
    if (!empty($allradiusnas)) {
        foreach ($allradiusnas as $io=>$eachnas) {
            $nasselector[$eachnas['id']]=$eachnas['id'].':'.$eachnas['nasname'];
        }
        
        $addinputs=wf_Selector('newnasid', $nasselector, 'Network Access Servers','',true);
        $addinputs.=wf_TextArea('newnastemplate', '', '', true, '60x10');
        $addinputs.=wf_Submit('Create');
        
        $addform=wf_Form('', 'POST', $addinputs, 'glamour');
        show_window(__('Add new template'),$addform);
    }
    
}

function web_NasTemplateEditForm($id) {
    $id=vf($id,3);
    $allradiusnas_q="SELECT * from `nas` WHERE `nastype`='radius'";
    $allradiusnas=simple_queryall($allradiusnas_q);
    $template_q="SELECT * from `nastemplates` WHERE `id`='".$id."'";
    $template_data=simple_query($template_q);
    $nasselector=array();
    if (!empty($allradiusnas)) {
        foreach ($allradiusnas as $io=>$eachnas) {
            $nasselector[$eachnas['id']]=$eachnas['id'].':'.$eachnas['nasname'];
        }
        
        $addinputs=wf_Selector('editnasid', $nasselector, 'Network Access Servers',$template_data['nasid'],true);
        $addinputs.=wf_HiddenInput('edittemplateid',$template_data['id']);
        $addinputs.=wf_TextArea('editnastemplate', '', $template_data['template'], true, '60x10');
        $addinputs.=wf_Submit('Change');
        
        $addform=wf_Form('', 'POST', $addinputs, 'glamour');
        show_window(__('Edit template'),$addform);
    }
    
}




function ra_NasGetTemplate($nasid) {
    $nasid=vf($nasid,3);
    $query="SELECT `template` from `nastemplates` WHERE `nasid`='".$nasid."'";
    $result=simple_query($query);
    if (!empty ($result)) {
        $result=$result['template'];
    } else {
        $result='';
    }
    return ($result);
}

function ra_NasAddTemplate($nasid,$template) {
    $nasid=vf($nasid,3);
    $template=DB_real_escape_string($template);
    $query="INSERT INTO `nastemplates` (
            `id` ,
            `nasid` ,
            `template`
            )
            VALUES (
            NULL , '".$nasid."', '".$template."'
            );";
    nr_query($query);
    log_register('RADIUSTEMPLATE ADD '.$nasid);
}

function ra_NasDeteleTemplate($id) {
    $id=vf($id,3);
    $query="DELETE from `nastemplates` WHERE `id`='".$id."'";
    nr_query($query);
    log_register('RADIUSTEMPLATE DELETE '.$id);
    
}
// already parsed template as param needed
function ra_UserRebuildAttributes($login,$attrtemplate,$verbose=false) {
    $login=DB_real_escape_string($login);
    $clean_q="DELETE from `radattr` WHERE `login`='".$login."'";
    nr_query($clean_q);

    if (!empty ($attrtemplate)) {
        if ($verbose) {
            show_window(__('User attributes'),'<pre>'.$attrtemplate.'</pre>');
        }
        
        $splitted=explodeRows($attrtemplate);
        if (!empty ($splitted)) {
            foreach ($splitted as $io=>$eachattr){
                if (ispos($eachattr,'=')) {
                $attr_raw=explode('=', $eachattr);
                $attr=$attr_raw[0];
                $value=$attr_raw[1];
                $query="INSERT INTO `radattr` (`id` ,`login` ,`attr` ,`value`) VALUES (NULL , '".$login."', '".$attr."', '".$value."');";
                nr_query($query);
                }
            }
        }
    }
}

function ra_UserRebuild($login,$verbose=false) {
    $login=DB_real_escape_string($login);
    $userip=zb_UserGetIP($login);
    $netid=zb_NetworkGetByIp($userip);
    $nasid=zb_NasGetByNet($netid);
    $nastemplate=ra_NasGetTemplate($nasid);
    $alluserdata=zb_TemplateGetAllUserData();
    $parsed_template=zb_TemplateReplace($login, $nastemplate, $alluserdata);
    ra_UserRebuildAttributes($login, $parsed_template,$verbose);
    log_register("RADIUST REBUILD (".$login.")");
}


// rebuild attributes for all users on all nas of type radius
function ra_NasRebuildAll() {
    $nas_q="SELECT * from `nas` WHERE `nastype`='radius'";
    $radiusnas=simple_queryall($nas_q);
    if (!empty ($radiusnas)) {
        $allips=zb_UserGetAllIPs();
        $transips=array_flip($allips);
        $allnetids=zb_UserGetNetidsAll();
        $alluserdata=zb_TemplateGetAllUserData();
        foreach ($radiusnas as $io=>$eachnas) {
            $netid=$eachnas['netid'];
            $nasid=$eachnas['id'];
            $nastemplate=ra_NasGetTemplate($nasid);
            if (!empty ($nastemplate)) {
            foreach ($allnetids as $ip=>$eachnetid) {
                if ($eachnetid==$netid) {
                    $userlogin=$transips[$ip];
                    if (!empty ($userlogin)) {
                        $parsed_template=zb_TemplateReplace($userlogin, $nastemplate, $alluserdata);
                        ra_UserRebuildAttributes($userlogin, $parsed_template, false);
                    }
                }
            }
            }
            
        }
    }
}




?>

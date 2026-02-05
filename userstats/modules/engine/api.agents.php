<?php

/*
 * Backport of existing contragent mechanics
 */

function zbs_AgentAssignedGetDataFast($login, $address) {
    $allassigns = zbs_AgentAssignGetAllData();
    $allassignsStrict = zbs_AgentAssignStrictGetAllData();
    $assigned_agent = zbs_AgentAssignCheckLoginFast($login, $allassigns, $address, $allassignsStrict);
    $result = zbs_ContrAhentGetData($assigned_agent);
    return($result);
}

function zbs_AgentAssignGetAllData() {
    $query = "SELECT * from `ahenassign`";
    $allassigns = simple_queryall($query);
    return($allassigns);
}

function zbs_AgentAssignStrictGetAllData() {
    $result = array();
    $query = "SELECT * from `ahenassignstrict`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['agentid'];
        }
    }
    return ($result);
}

function zbs_AgentAssignCheckLoginFast($login, $allassigns, $address, $allassignsstrict) {
    global $us_config;
    $alter_cfg = $us_config;
    $result = array();
    $assignMode= (isset($alter_cfg['AGENTS_ASSIGN'])) ? $alter_cfg['AGENTS_ASSIGN'] : 1;
    $defaultAgent = (isset($alter_cfg['DEFAULT_ASSIGN_AGENT'])) ? $alter_cfg['DEFAULT_ASSIGN_AGENT'] : 1;

    if (isset($allassignsstrict[$login])) {
        $result = $allassignsstrict[$login];
        return ($result);
    }

    if (!empty($address)) {
        if (empty($allassigns)) {
            $result = $defaultAgent;
        } else {
            $useraddress = $address;
            foreach ($allassigns as $io => $eachassign) {
                if (strpos($useraddress, $eachassign['streetname']) !== false) {
                    $result = $eachassign['ahenid'];
                    break;
                } else {
                        $result = $defaultAgent;
                }
            }
        }
    } else {
        $result = $defaultAgent;
    }
    if ($assignMode == 0) {
        $result = $defaultAgent;
    }

    return($result);
}


 function zbs_ContrAhentGetData($id) {
        $id=vf($id);
        $query="SELECT * from `contrahens` WHERE `id`='".$id."'";
        $result=simple_query($query);
        return($result);
}


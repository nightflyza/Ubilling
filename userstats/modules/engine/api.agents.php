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
    //быстренько проверяем нету ли принудительной привязки по логину
    if (isset($allassignsstrict[$login])) {
        $result = $allassignsstrict[$login];
        return ($result);
    }


    // если пользователь куда-то заселен
    if (!empty($address)) {
        // возвращаем дефолтного агента если присваиваний нет вообще
        if (empty($allassigns)) {
            $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
        } else {
            //если какие-то присваивалки есть
            $useraddress = $address;

            // проверяем для каждой присваивалки попадает ли она под нашего абонента
            foreach ($allassigns as $io => $eachassign) {
                if (strpos($useraddress, $eachassign['streetname']) !== false) {
                    $result = $eachassign['ahenid'];
                    break;
                } else {
                    // и если не нашли - возвращаем  умолчательного
                    $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
                }
            }
        }
    } else {
        //если пользователь бомжует - возвращаем тоже умолчательного
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }
    // если присваивание выключено возвращаем умолчального
    if (!$alter_cfg['AGENTS_ASSIGN']) {
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }

    return($result);
}


 function zbs_ContrAhentGetData($id) {
        $id=vf($id);
        $query="SELECT * from `contrahens` WHERE `id`='".$id."'";
        $result=simple_query($query);
        return($result);
     }

?>
<?php


    if (empty($upPorts[1])) {
        switch ($upPorts[0]) {
            case '25':
                $plist_add_tagged = "0000008000000000";
                break;
            case '26':
                $plist_add_tagged = "0000004000000000";
                break;
            case '27':
                $plist_add_tagged = "0000002000000000";
                break;
            case '28':
                $plist_add_tagged = "0000001000000000";
                break;
        }
    } elseif (empty($upPorts[1])) {
        if($upPorts[0] == '25' and $upPorts[1] == '26') {
            $plist_add_tagged = "000000C000000000";
        }
        if($upPorts[0] == '25' and $upPorts[1] == '27') {
            $plist_add_tagged = "000000A000000000";
        }
        if($upPorts[0] == '25' and $upPorts[1] == '28') {
            $plist_add_tagged = "0000009000000000";
        }
    } elseif (empty($upPorts[2])) {
        if($upPorts[0] == '25' and $upPorts[1] == '26' and $upPorts[2] == '27') {
            $plist_add_tagged = "000000E000000000";
        }
        if($upPorts[0] == '25' and $upPorts[1] == '26' and $upPorts[2] == '27') {
            $plist_add_tagged = "000000D000000000";
        }
    } else {
        $plist_add_tagged = "000000F000000000";
    }
    
?>
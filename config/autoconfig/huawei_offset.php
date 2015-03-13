<?php
    
    if($swport >=1 and $swport <= 3) { $group=0; $port_group=$swport; }
    if($swport >= 4 and $swport <= 7) { $group=1; $port_group=$swport - 3; }
    if($swport >= 8 and $swport <= 11) { $group=2; $port_group=$swport - 7; }
    if($swport >= 12 and $swport <= 15) { $group=3; $port_group=$swport - 11; }
    if($swport >= 16 and $swport <= 19) { $group=4; $port_group=$swport - 15; }
    if($swport >= 20 and $swport <= 23) { $group=5; $port_group=$swport - 19; }
    if($swport >= 24 and $swport <= 27) { $group=6; $port_group=$swport - 23; }
    if($swport >= 28 and $swport <= 31) { $group=7; $port_group=$swport - 27; }
    if($swport >= 32 and $swport <= 35) { $group=8; $port_group=$swport - 31; }
    if($swport >= 36 and $swport <= 39) { $group=9; $port_group=$swport - 35; }
    if($swport >= 40 and $swport <= 43) { $group=10; $port_group=$swport - 39; }
    if($swport >= 44 and $swport <= 47) { $group=11; $port_group=$swport - 43; }
    if($swport >= 48 and $swport <= 51) { $group=12; $port_group=$swport - 47; }
    if($swport >= 52 and $swport <= 55) { $group=13; $port_group=$swport - 51; }
    if($group == "0") {
        if($port_group == "1") { $offset = "4"; }
        if($port_group == "2") { $offset = "2"; }
        if($port_group == "3") { $offset = "1"; }
    } if($group == "6") {
        if($port_group == "1") { $offset = "E"; }
    } else {
        if($port_group == "1") { $offset = "8"; }
        if($port_group == "2") { $offset = "4"; }
        if($port_group == "3") { $offset = "2"; }
        if($port_group == "4") { $offset = "1"; }
    }
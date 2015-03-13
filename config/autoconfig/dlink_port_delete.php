<?php

	if($port_number >=1 and $port_number <= 4) { $group=0; $port_group=$port_number; }
	if($port_number >= 5 and $port_number <= 8) { $group=1; $port_group=$port_number - 4; }
	if($port_number >= 9 and $port_number <= 12) { $group=2; $port_group=$port_number - 8; }
	if($port_number >= 13 and $port_number <= 16) { $group=3; $port_group=$port_number - 12; }
	if($port_number >= 17 and $port_number <= 20) { $group=4; $port_group=$port_number - 16; }
	if($port_number >= 21 and $port_number <= 24) { $group=5; $port_group=$port_number - 20; }
	if($port_number >= 25 and $port_number <= 28) { $group=6; $port_group=$port_number - 24; }
	if($port_number >= 29 and $port_number <= 32) { $group=7; $port_group=$port_number - 28; }
	if($port_number >= 33 and $port_number <= 36) { $group=8; $port_group=$port_number - 32; }
	if($port_number >= 37 and $port_number <= 40) { $group=9; $port_group=$port_number - 36; }
	if($port_number >= 41 and $port_number <= 44) { $group=10; $port_group=$port_number - 40; }
	if($port_number >= 45 and $port_number <= 48) { $group=11; $port_group=$port_number - 44; }
	if($port_number >= 49 and $port_number <= 52) { $group=12; $port_group=$port_number - 48; }

	if ($plist[$group] == "1") {
	        if($port_group == "4") { $offset = "0"; }
	}
	if ($plist[$group] == "2") {
		if($port_group == 3) { $offset = "0"; }
	}
	if ($plist[$group] == "3") {
		if($port_group == "3") { $offset = "1"; }
		if($port_group == "4") { $offset = "2"; }
	}
	if ($plist[$group] == "4") {
		if($port_group == "2") { $offset = "0"; }
	}
	if ($plist[$group] == "5") {
		if($port_group == "2") { $offset = "1"; }
		if($port_group == "4") { $offset = "4"; }
	}
	if ($plist[$group] == "6") {
		if($port_group == "2") { $offset = "2"; }
		if($port_group == "3") { $offset = "4"; }
	}
	if ($plist[$group] == "7") {
	        if($port_group == "2") { $offset = "3"; }
	        if($port_group == "3") { $offset = "5"; }
		if($port_group == "4") { $offset = "6"; }
	}
	if ($plist[$group] == "8") {
	        if($port_group == "1") { $offset = "0"; }
	}
	if ($plist[$group] == "9") {
        	if($port_group == "1") { $offset = "1"; }
        	if($port_group == "4") { $offset = "8"; }
	}
	if ($plist[$group] == "A") {
        	if($port_group == "1") { $offset = "2"; }
        	if($port_group == "3") { $offset = "8"; }
	}
	if ($plist[$group] == "B") {
	        if($port_group == "1") { $offset = "3"; }
	        if($port_group == "3") { $offset = "9"; }
		if($port_group == "4") { $offset = "A"; }
	}
	if ($plist[$group] == "C") {
	        if($port_group == "1") { $offset = "4"; }
	        if($port_group == "2") { $offset = "8"; }
	}
	if ($plist[$group] == "D") {
	        if($port_group == "1") { $offset = "5"; }
	        if($port_group == "2") { $offset = "9"; }
		if($port_group == "4") { $offset = "C"; }
	}
	if ($plist[$group] == "E") {
	        if($port_group == "1") { $offset = "6"; }
	        if($port_group == "2") { $offset = "A"; }
		if($port_group == "3") { $offset = "C"; }
	}
	if ($plist[$group] == "F") {
	        if($port_group == "1") { $offset = "7"; }
	        if($port_group == "2") { $offset = "B"; }
		if($port_group == "3") { $offset = "D"; }
	        if($port_group == "4") { $offset = "E"; }
	}

        $port_list=snmp2_get( $ip, $community, "1.3.6.1.4.1.2011.5.25.42.3.1.1.1.1.3.$vlan");
        $plist=str_replace(array(' ','Hex-STRING:'),'',$plist);
        $plist=trim($port_list);

?>

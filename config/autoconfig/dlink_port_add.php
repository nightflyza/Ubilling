<?php

if ($port_number >= 1 and $port_number <= 4) {
    $group_add = 0;
    $port = $port_number;
}
if ($port_number >= 5 and $port_number <= 8) {
    $group_add = 1;
    $port = $port_number - 4;
}
if ($port_number >= 9 and $port_number <= 12) {
    $group_add = 2;
    $port = $port_number - 8;
}
if ($port_number >= 13 and $port_number <= 16) {
    $group_add = 3;
    $port = $port_number - 12;
}
if ($port_number >= 17 and $port_number <= 20) {
    $group_add = 4;
    $port = $port_number - 16;
}
if ($port_number >= 21 and $port_number <= 24) {
    $group_add = 5;
    $port = $port_number - 20;
}
if ($port_number >= 25 and $port_number <= 28) {
    $group_add = 6;
    $port = $port_number - 24;
}
if ($port_number >= 29 and $port_number <= 32) {
    $group_add = 7;
    $port = $port_number - 28;
}
if ($port_number >= 33 and $port_number <= 36) {
    $group_add = 8;
    $port = $port_number - 32;
}
if ($port_number >= 37 and $port_number <= 40) {
    $group_add = 9;
    $port = $port_number - 36;
}
if ($port_number >= 41 and $port_number <= 44) {
    $group_add = 10;
    $port = $port_number - 40;
}
if ($port_number >= 45 and $port_number <= 48) {
    $group_add = 11;
    $port = $port_number - 44;
}
if ($port_number >= 49 and $port_number <= 52) {
    $group_add = 12;
    $port = $port_number - 48;
}


if ($port == "1") {
    $plist_add = "8";
}
if ($port == "2") {
    $plist_add = "4";
}
if ($port == "3") {
    $plist_add = "2";
}
if ($port == "4") {
    $plist_add = "1";
}
	
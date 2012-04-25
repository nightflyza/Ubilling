#!/bin/bash

IFUP=eth0
IFDOWN=eth1
IPT="/sbin/iptables"
tc="/sbin/tc"


$IPT -t mangle --flush

$tc qdisc del dev $IFUP root handle 1: htb
$tc qdisc del dev $IFDOWN root handle 1: htb


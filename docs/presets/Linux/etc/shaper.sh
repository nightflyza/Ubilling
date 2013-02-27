#!/bin/bash

IFUP=eth0
IFDOWN=eth1
IPT="/sbin/iptables"
tc="/sbin/tc"
SPEEDUP=100mbit
SPEEDDOWN=100mbit

$IPT -t mangle --flush

$tc qdisc add dev $IFDOWN root handle 1: htb
$tc class add dev $IFDOWN parent 1: classid 1:1 htb rate $SPEEDDOWN ceil $SPEEDDOWN

$tc qdisc add dev $IFUP root handle 1: htb
$tc class add dev $IFUP parent 1: classid 1:1 htb rate $SPEEDUP ceil $SPEEDUP



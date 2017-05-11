#!/bin/sh

username=$1
password=$2
int=$3
ip=$4
vlan=$5

echo "BSD.remote" $username $password $int $ip $vlan >> /etc/exp/test

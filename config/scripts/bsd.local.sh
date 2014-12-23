#!/bin/sh

int=$1
ip=$2
vlan=$3
vlanif="vlan$vlan"

ngctl mkpeer vlan: eiface $vlanif ether
ngctl name vlan:$vlanif $vlanif
ngctl msg vlan: addfilter { vlan=$vlan hook="$vlanif" }

ifconfig `ifconfig | grep ngeth | awk '{print $1}' | cut -f 1 -d :` name $vlanif

ifconfig $vlanif ether `ifconfig $int | grep ether | awk '{print $2}'` up

ifconfig bridge0 addm $vlanif
ifconfig bridge0 private $vlanif
ifconfig bridge0 -discover $vlanif
ifconfig bridge0 sticky $vlanif

echo "ngctl mkpeer vlan: eiface $vlanif ether" >> /etc/rc.d/start.sh
echo "ngctl name vlan:$vlanif $vlanif " >> /etc/rc.d/start.sh
echo "ngctl msg vlan: addfilter { vlan=$vlan hook=\"$vlanif\" } " >> /etc/rc.d/start.sh
echo "ifconfig `ifconfig | grep ngeth | awk '{print $1}' | cut -f 1 -d :` name $vlanif " >> /etc/rc.d/start.sh
echo "ifconfig $vlanif ether `ifconfig $int | grep ether | awk '{print $2}'` up " >> /etc/rc.d/start.sh
echo "ifconfig bridge0 addm $vlanif " >> /etc/rc.d/start.sh
echo "ifconfig bridge0 private $vlanif " >> /etc/rc.d/start.sh
echo "ifconfig bridge0 -discover $vlanif " >> /etc/rc.d/start.sh
echo "ifconfig bridge0 sticky $vlanif " >> /etc/rc.d/start.sh

#!/usr/local/bin/expect -f
set timeout 4
set username [lindex $argv 0]
set password [lindex $argv 1]
set vlan [lindex $argv 2]
set int [lindex $argv 3]
set addr [lindex $argv 4]
set host [lindex $argv 5]

spawn ssh $username@$host
expect {
	"(yes/no)?*" {
		send "yes\r"
		}
}
expect "Password:*"
send "$password\r"
expect {
	"*>" {
		send "enable\r"
		expect "Password: *"
		send "$password\r"
		expect "*#"
		send "configure terminal\r"
	}
	"*#" {
		send "configure terminal\r"
	}
}
expect "*(config)#"
send "vlan $vlan\r"
expect "*(config-vlan)"
send "exit\r"
expect "*(config)#"
send "interface vlan $vlan\r"
expect "*(config-if)#"
send "ip unnumbered $int\r"
expect "*(config-if)#"
send "no ip proxy-arp\r"
expect "*(config-if)#"
send "no ip redirects\r"
expect "*(config-if)#"
send "no ip unreachable\r"
expect "*(config-if)#"
send "exit\r"
expect "*(config)#"
send "ip route $addr 255.255.255.255 vlan$vlan\r"
expect "*(config)#"
send "end\r"
expect "*#"
send "write\r"
expect "*#"
send "exit\r"
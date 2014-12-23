#!/usr/local/bin/expect -f
set timeout 2
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
expect "Password:"
sleep .1
send "$password\r"
expect "*>"
sleep .1
send "enable\r"
expect "Password:"
sleep .1
send "$password\r"
expect "*#"
sleep .1
send "configure terminal\r"
expect "*(config)#"
sleep .1
send "interface vlan $vlan\r"
expect "*(config-if)#"
sleep .1
send "ip unnumbered $int\r"
expect "*(config-if)#"
sleep .1
send "ip helper-address $addr\r"
expect "*(config-if)#"
sleep .1
send "end\r"
expect "*#"
sleep .1
send "write\r"
expect "*#"
sleep .1
send "exit\r"
expect eof

<?php
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////
if(!empty($system->config['enable_ids'])){
	$urlref = ($_SERVER['REQUEST_URI']); 

	if(isset($_COOKIE['UID'])) {
    	print('Error connecting to MySQL database. Please try later');
    	die();
	}
	
	function logattack(){
		global $system;
		rcms_log_put('Hack attempt', $system->user['username'], 'Remote address: ' . ($_SERVER['REMOTE_ADDR']) . "\n" .
			'Suspected URI: ' . ($_SERVER['REQUEST_URI']) . "\n" . 'Suspected referer: ' . ($_SERVER['HTTP_REFERER']) . "\n" .
			'User agent: ' . ($_SERVER['HTTP_USER_AGENT']) . "\n");
	}

// search of SQL Injections like a index.php?module=articles&c=news&b=1&a=1+[SQL injection here]
if ((stristr($urlref, 'articles')) AND (stristr($urlref, 'news')) AND (stristr($urlref, 'union')))
	{
	logattack();
	print ('You have an error in your SQL syntax near \'WHERE newsid =');
	die();
	}

// search of SQL Injections like a index.php?module=articles&c=news&b=1+[SQL injection here]&a=1
if ((stristr($urlref, 'module=articles')) AND (stristr($urlref, 'b=+')) AND (stristr($urlref, 'union')))
	{
	logattack();
	print ('You have an error in your SQL syntax near \'WHERE bid =');
	die();
	}

// search of trivial fopen bug like index.php?module=user.list&user=../../../../etc/passwd
if ((stristr($urlref, 'user.list')) AND (stristr($urlref, 'user')) AND (stristr($urlref, 'etc/passwd')))
	{
	logattack();
	print ('failed to open stream: No such file or directory /etc/passwd');
	die();
	}

// search of trivial fopen bug like index.php?module=user.list&user=../../../../etc/shadow
if ((stristr($urlref, 'user.list')) AND (stristr($urlref, 'user')) AND (stristr($urlref, 'etc/shadow')))
	{
	logattack();
	print ('failed to open stream: No such file or directory /etc/shadow');
	die();
	}

// search of SQL Injections in gallery
if ((stristr($urlref, '=gallery')) AND (stristr($urlref, 'id=')) AND (stristr($urlref, 'union')))
	{
	logattack();
	print ('You have an error in your SQL syntax near \'WHERE imageid =');
	die();
	}

// Test for DoS via SQL injection like index.php?[someparam]=BENCHMARK(10000000,BENCHMARK(10000000,md5(current_date)))
if (stristr($urlref, 'benchmark'))
	{
	logattack();
	setcookie('UID', rand(2,50), time()+7200);
	die();
	}
	
//Showing some usefulpasswd file ;)
if (stristr($urlref, 'module=../../../../etc/passwd'))
	{
	$passwdfile='
root:x:0:0::/root:/bin/bash
bin:x:1:1:bin:/bin:
daemon:x:2:2:daemon:/sbin:
adm:x:3:4:adm:/var/log:
lp:x:4:7:lp:/var/spool/lpd:
sync:x:5:0:sync:/sbin:/bin/sync
shutdown:x:6:0:shutdown:/sbin:/sbin/shutdown
halt:x:7:0:halt:/sbin:/sbin/halt
mail:x:8:12:mail:/:
news:x:9:13:news:/usr/lib/news:
uucp:x:10:14:uucp:/var/spool/uucppublic:
operator:x:11:0:operator:/root:/bin/bash
games:x:12:100:games:/usr/games:
ftp:x:14:50::/home/ftp:
smmsp:x:25:25:smmsp:/var/spool/clientmqueue:
mysql:x:27:27:MySQL:/var/lib/mysql:/bin/bash
rpc:x:32:32:RPC portmap user:/:/bin/false
sshd:x:33:33:sshd:/:
gdm:x:42:42:GDM:/var/state/gdm:/bin/bash
pop:x:90:90:POP:/:
nobody:x:99:99:nobody:/:
firebird:x:1006:102:Firebird Database Administrator:/opt/firebird:/bin/bash
sql:x:1007:100:,,,:/home/sql:/bin/bash
nagios:x:1008:100::/home/nagios:
iconci:x:1009:100:Iconci,,,:/home/iconci:/bin/bash
httpd:x:1010:104:Apache HTTPD User,,,:/home/httpd:/bin/bash
	';
	logattack();
	print($passwdfile);
	die();
	}

//And showing more useful shadow file withe real passwords ;)
if(stristr($urlref, 'module=../../../../etc/shadow')) {
	$shadowfile='
root:$1$SWU0pAUD$Ht3oFKJy/Qt/Cp.yTvygZ1:12835:0:99999:7:::
bin:*:12796:0:99999:7:::
daemon:*:12796:0:99999:7:::
adm:*:12796:0:99999:7:::
lp:*:12796:0:99999:7:::
sync:*:12796:0:99999:7:::
shutdown:*:12796:0:99999:7:::
halt:*:12796:0:99999:7:::
mail:*:12796:0:99999:7:::
news:*:12796:0:99999:7:::
uucp:*:12796:0:99999:7:::
operator:*:12796:0:99999:7:::
games:*:12796:0:99999:7:::
gopher:*:12796:0:99999:7:::
nobody:*:12796:0:99999:7:::
vcsa:!!:12796:0:99999:7:::
rpm:!!:12796:0:99999:7:::
xfs:!!:12796:0:99999:7:::
rpc:!!:12796:0:99999:7:::
dbus:!!:12796:0:99999:7:::
mailnull:!!:12796:0:99999:7:::
smmsp:!!:12796:0:99999:7:::
rpcuser:!!:12796:0:99999:7:::
nfsnobody:!!:12796:0:99999:7:::
nscd:!!:12796:0:99999:7:::
ntp:!!:12796:0:99999:7:::
sshd:!!:12796:0:99999:7:::
pcap:!!:12796:0:99999:7:::
amanda:!!:12796:0:99999:7:::
named:!!:12796:0:99999:7:::
apache:!!:12796:0:99999:7:::
desktop:!!:12796:0:99999:7:::
mailman:!!:12796:0:99999:7:::
fax:!!:12796:0:99999:7:::
mysql:!!:12796:0:99999:7:::
nut:!!:12796:0:99999:7:::
postgres:!!:12796:0:99999:7:::
pvm:!!:12796:0:99999:7:::
squid:!!:12796:0:99999:7:::
webalizer:!!:12796:0:99999:7:::
wnn:!!:12796:0:99999:7:::
nagios:!!:12796:0:99999:7:::
netdump:!!:12796:0:99999:7:::
popa3d:!!:12796:0:99999:7:::
snort:!!:12796:0:99999:7:::
admin:$1$A/TbUhKj$UOoGXnP3gWgaCFFDukJhQ/:12848:0:99999:7:::';
	logattack();
	print($shadowfile);
	die();
}
}
?>
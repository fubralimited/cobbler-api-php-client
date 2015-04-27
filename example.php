<?php
include ('settings.php');
include ('CobblerApiClient.php');

$client = new CobblerApiClient($cobbler_server['host'], $cobbler_server['port'], $cobbler_server['path'], $cobbler_server['user'], $cobbler_server['password'], false);

$name = 'testing';
$hostname = $name.'public.mycompany';
$macaddress = '32:00:17:70:bd:a0';
$profile = 'centos-6.6-x86_64';

$client->deleteSystem($name);

$params = array();
$params['name'] = $name;
$params['host'] = $hostname;
$params['mac'] = $macaddress;
$params['profile'] = $profile;
$system_id = $client->createSystem($params);


$key = 'mysshkey';
$client->setSSHKey($name, $key);
$client->setPassword($name,'plainrootpassword');




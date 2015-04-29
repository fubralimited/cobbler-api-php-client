<?php
include ('settings.php');
include ('CobblerApiClient.php');

//1. Create client
$client = new CobblerApiClient($cobbler_server['host'], $cobbler_server['port'], $cobbler_server['path'], $cobbler_server['user'], $cobbler_server['password'], false);

//2. List Systems
echo '<h1>SYSTEMS</h1>';
$systems = $client->listSystems();
foreach ($systems as $system){
	//var_dump($system);
}
//3. List Distros
echo '<h1>DISTROS</h1>';
$distros = $client->listDistros();
foreach ($distros as $distro){
	//var_dump($distro);
}

//4. List Profiles
echo '<h1>PROFILES</h1>';
$profiles = $client->listProfiles();
foreach ($profiles as $profile){
	//var_dump($profile);
}

//5. List Images
echo '<h1>IMAGES</h1>';
$images = $client->listImages();
foreach ($images as $image){
	//var_dump($image);
}

$name = 'testing';
$hostname = $name.'public.mycompany';
$macaddress = '32:00:17:70:bd:ab';
$profile = 'centos-6.6-x86_64';
$key = 'mysshkey';
$password = 'plainrootpassword';

$params = array();
$params['name'] = $name;
$params['host'] = $hostname;
$params['mac'] = $macaddress;
$params['profile'] = $profile;




try{
	//6. Create System
	$system_id = $client->createSystem($params);
}finally{
	//7. Delete System
	//$client->deleteSystem($name);
	$client->setSSHKey($name, $key);
	$client->setPassword($name,'plainrootpassword');
}





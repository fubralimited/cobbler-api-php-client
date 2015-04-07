<?php
include ('settings.php');
include ('CobblerAPIClient.class.php');

$client = new CobblerAPIClient($cobbler_server['host'], $cobbler_server['port'], $cobbler_server['path'], $cobbler_server['user'], $cobbler_server['password'], false);

$token = $client->auth();
$name = 'delete';
$client->deleteSystem($name);
$system_id = $client->createSystem($name,'delete.host.delete','32:00:17:70:bd:a0', 'centos-6.6-x86_64');
$client->disableNetboot($name);
$key = 'AAAAB3NzaC1yc2EAAAADAQABAAABAQC8fWKArTOopWdgfEBp/9jbW6+o4i+5ETfgcNtYqoFH/x2BqiMJnkgiMdNCSmFK6zImSIBq92ajXOq8nlpq2RPYM0vc00NqOBYEPqE+VIBUkBRBXlrLO0gqAQeKxHHXAP1h6dlVq0kIWDOftUV9YrP4dQa61gC1p2W9cgiyYbU7YJb07kfs77bd0EfzLhiTAT67E+5aG7HyEmeV0Oz9YYGFXz6g9EQWYFF/sC784OyDrUvCXisrp3hXP/hY9R9JWO0o8lxtZTHaNLLXY87JG4FwvIpQQE/eh54HvI4/ChtWSqSWRegOom1J3JbgquxWSX2R1xJKd4YYHh47pmGgEAfv';
$client->setSSHKey($name, $key);
$client->setSSHKey($name, 'aa');
$client->setPassword($name,'pepito');
$client->setPassword($name,'flores');

echo '<b>SYSTEMS</b>'.PHP_EOL;
var_dump($client->listSystems());
echo '<b>PROFILES</b>'.PHP_EOL;
var_dump($client->listProfiles());
echo '<b>IMAGES</b>'.PHP_EOL;
var_dump($client->listImages());
echo '<b>DISTROS</b>'.PHP_EOL;
var_dump($client->listDistros());

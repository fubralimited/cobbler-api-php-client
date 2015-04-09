<?php

include ('IXRLibrary.php');

class Cobbler {
	
	/**
	 * Client to the xmlrpc Cobbler API, would be the object handling all the requests to the API
	 */
	protected $_ixrClient;

	/**
	 * Cobbler user, needed for authenticated calls to the API
	 */
	protected $_user;

	/**
	 * Cobbler password, needed for authenticated calls to the API
	 */
	protected $_pass;

	/**
	 * Constructor. Gets the cobbler api parameters, builds the ixr client and stores user and 
	 * password for future auth operations.
	 *
	 * @access public
	 * @param string $host Cobbler hostname or ip address
	 * @param string $port Cobbler secure/SSL port
	 * @param string $path Cobbler API path
	 * @param string $user Cobbler user
	 * @param string $pass Cobbler password
	 * @param string $debug (optional) Wether we want to print out stuff from the ixr client or not
	 */

	function __construct($host, $port, $path, $user, $pass, $debug=false){
		
		$this->_ixrClient = new IXR_ClientSSL($host, $path, $port);
		$this->_ixrClient->debug = $debug;
		$this->_user = $user;
		$this->_pass = $pass;
	} 

	function auth(){
		$this->ixr_client->query('login',$this->user,$this->pass);
		return $this->ixr_client->getResponse();
	}

	function getSystemHandle($token, $system_name){	
		$this->ixr_client->query('get_system_handle', $system_name, $token);
		return $this->ixr_client->getResponse();
	}

	function updateMetadata($token, $system_name, $key, $value){

		$this->ixr_client->query('get_system', $system_name);
		$system = $this->ixr_client->getResponse();
		$metadata = $system['ks_meta'];
		$metadata[$key] = $value;
		$metadata_string = implode(' ', array_map(function ($v, $k) { return $k . '=' . $v; }, $metadata, array_keys($metadata)));
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'ks_meta', $metadata_string, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return 1;

	}

	function findSystem($key, $value){
		$this->ixr_client->query('find_system', array($key => $value));
		return $this->ixr_client->getResponse();
	}

	function existsSystem($key, $value){
		$systems = $this->findSystem($key, $value);
		return sizeof($systems) > 0;
	}

	function listSystems(){
		$token = $this->auth();	
		$this->ixr_client->query('get_systems');
		return $this->ixr_client->getResponse();
	}

	function listDistros(){
		$token = $this->auth();	
		$this->ixr_client->query('get_distros');
		return $this->ixr_client->getResponse();
	}

	function listProfiles(){
		$token = $this->auth();	
		$this->ixr_client->query('get_profiles');
		return $this->ixr_client->getResponse();
	}

	function listImages(){
		$token = $this->auth();	
		$this->ixr_client->query('get_images');
		return $this->ixr_client->getResponse();
	}

	//TODO: Validate name, host and mac to avoid duplicated systems
	function createSystem($name, $host, $mac, $profile, $interface_name = 'eth0'){
		
		$token = $this->auth();

		if ($this->existsSystem('name',$name)){
			throw new Exception('There is already a system using that name');
		}		

		if ($this->existsSystem('hostname',$host)){
			throw new Exception('There is already a system using that hostname');
		}	

		if ($this->existsSystem('mac_address',$mac)){
			throw new Exception('There is already a system using that mac address');
		}	

		$this->ixr_client->query('new_system',$token);
		$system_id = $this->ixr_client->getResponse();
		$this->ixr_client->query('modify_system', $system_id, 'name', $name, $token);
		$this->ixr_client->query('modify_system', $system_id, 'hostname', $host, $token);
		$this->ixr_client->query('modify_system', $system_id, 'profile', $profile, $token);

		$interface = array();
		$interface['macaddress-'.$interface_name] = $mac;
		//$interface['ipaddress-'.$interface_name] = $ip;
		//$interface['gateway-'.$interface_name] = $mac;
		//$interface['virtbridge-'.$interface_name] = 'xenbr0';
		//$interface['dnsname-'.$interface_name] = $mac;
		//$interface['static-'.$interface_name] = True;
		//$interface['dhcptag-'.$interface_name] = $mac;
		//$interface['staticroutes-'.$interface_name] = $mac;
		$this->ixr_client->query('modify_system', $system_id, 'modify_interface', $interface, $token);


		$this->ixr_client->query('save_system', $system_id, $token);

		return $system_id;

	}   

	function deleteSystem($system_name){

		$token = $this->auth();	
		$this->ixr_client->query('remove_system', $system_name, $token);
		return 1;

	}

	function enableNetboot($system_name){

		$token = $this->auth();	
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'netboot_enabled', True, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return 1;

	}

	function disableNetboot($system_name){

		$token = $this->auth();	
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'netboot_enabled', False, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return 1;

	}

	function setSSHKey($system_name, $key) {

		$token = $this->auth();	
		$this->updateMetadata($token, $system_name, 'ssh_key', $key);
		return 1;
		
	}

	function setPassword($system_name, $password) {

		$token = $this->auth();	
		$password_crypted = crypt($password);
		$this->updateMetadata($token, $system_name, 'custom_password', $password_crypted);
		return 1;
		
	}
}

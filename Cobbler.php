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
	public function __construct($host, $port, $path, $user, $pass, $debug=false){
		
		$this->_ixrClient = new IXR_ClientSSL($host, $path, $port);
		$this->_ixrClient->debug = $debug;
		$this->_user = $user;
		$this->_pass = $pass;
	} 

	/**
	 * Performs an authentication request to the Cobbler API and retrieves a token from it.
	 *
	 * @access protected
	 * @return string Auth token that can be used in other requests to the API
	 */
	protected function auth(){
		$this->ixr_client->query('login',$this->user,$this->pass);
		return $this->ixr_client->getResponse();
	}

	/**
	 * Request the API a system handler, efectively, an identifier to perform updates on existing systems.
	 *
	 * @access protected
	 * @param string $token       Auth token, required to validate our request to the API
	 * @param string $system_name Name of the system we want to manage
	 * @return string             System handle
	 */
	protected function getSystemHandle($token, $system_name){	
		$this->ixr_client->query('get_system_handle', $system_name, $token);
		return $this->ixr_client->getResponse();
	}

	/**
	 * Generic method to upgrade kickstart metadata on the system
	 *
	 * @access protected
	 * @param string $token       Auth token, required to validate our request to the API
	 * @param string $system_name Name of the system we want to edit
	 * @param string $key Name of the metadata item to edit
	 * @param string $value Value of the metadata item to edit
	 * @return boolean It returns true if everything went fine
	 */
	protected function updateMetadata($token, $system_name, $key, $value){

		$this->ixr_client->query('get_system', $system_name);
		$system = $this->ixr_client->getResponse();
		$metadata = $system['ks_meta'];
		$metadata[$key] = $value;
		$metadata_string = implode(' ', array_map(function ($v, $k) { return $k . '=' . $v; }, $metadata, array_keys($metadata)));
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'ks_meta', $metadata_string, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return true;

	}

	/**
	 * Generic method for finding systems in Cobbler using one of his attributes
	 *
	 * @access protected
	 * @param string $key Name of the attribute
	 * @param string $value Value of the attribute
	 * @return array List of all the systems in Cobbler matching the params
	 */
	protected function findSystem($key, $value){
		$this->ixr_client->query('find_system', array($key => $value));
		return $this->ixr_client->getResponse();
	}

	/**
	 * Determine if there is a system in Cobbler with some specific attribute value
	 *
	 * @access protected
	 * @param string $key Name of the attribute
	 * @param string $value Value of the attribute
	 * @return boolean True if it can find a system matching those params, false otherwise
	 */
	protected function existsSystem($key, $value){
		$systems = $this->findSystem($key, $value);
		return sizeof($systems) > 0;
	}

	/**
	 * Request Cobbler API for list of systems
	 *
	 * @access public
	 * @return array List all the systems in Cobbler
	 */
	public function listSystems(){
		$token = $this->auth();	
		$this->ixr_client->query('get_systems');
		return $this->ixr_client->getResponse();
	}

	/**
	 * Request Cobbler API for list of systems
	 *
	 * @access public
	 * @return array List all the systems in Cobbler
	 */
	public function listDistros(){
		$token = $this->auth();	
		$this->ixr_client->query('get_distros');
		return $this->ixr_client->getResponse();
	}

	/**
	 * Request Cobbler API for list of profiles
	 *
	 * @access public
	 * @return array List all the profiles in Cobbler
	 */
	public function listProfiles(){
		$token = $this->auth();	
		$this->ixr_client->query('get_profiles');
		return $this->ixr_client->getResponse();
	}

	/**
	 * Request Cobbler API for list of images
	 *
	 * @access public
	 * @return array List all the images in Cobbler
	 */
	public function listImages(){
		$token = $this->auth();	
		$this->ixr_client->query('get_images');
		return $this->ixr_client->getResponse();
	}

	/**
	 * Create a new system in Cobbler
	 *
	 * @access public
	 * @param $name Name of the new system, must be unique as it will act as identifier
	 * @param $host Hostname of the new system, must be unique
	 * @param $mac Mac address of the new system, must be unique
	 * @param $profile Profile (OS + kickstart template) of the new system
	 * @param $interfaceName Name of the main network interface of the new system, may vary from an SO to another
	 * @return string The id of the new system
	 */
	public function createSystem($name, $host, $mac, $profile, $interfaceName = 'eth0'){
		
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
		$interface['macaddress-'.$interfaceName] = $mac;
		//$interface['ipaddress-'.$interfaceName] = $ip;
		//$interface['gateway-'.$interfaceName] = $mac;
		//$interface['virtbridge-'.$interfaceName] = 'xenbr0';
		//$interface['dnsname-'.$interfaceName] = $mac;
		//$interface['static-'.$interfaceName] = True;
		//$interface['dhcptag-'.$interfaceName] = $mac;
		//$interface['staticroutes-'.$interfaceName] = $mac;
		$this->ixr_client->query('modify_system', $system_id, 'modify_interface', $interface, $token);


		$this->ixr_client->query('save_system', $system_id, $token);

		return $system_id;

	}   

	/**
	 * Request Cobbler API to delete an existing system
	 *
	 * @access public
	 * @param string $system_name Name of the system to delete
	 * @return boolean True if everything goes fine, false otherwise
	 */
	public function deleteSystem($system_name){

		$token = $this->auth();	
		$this->ixr_client->query('remove_system', $system_name, $token);
		return true;

	}

	/**
	 * Request Cobbler API to enable netbooting on an existing system
	 *
	 * @access public
	 * @param string $system_name Name of the system
	 * @return boolean True if everything goes fine, false otherwise
	 */
	public function enableNetboot($system_name){

		$token = $this->auth();	
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'netboot_enabled', True, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return true;

	}

	/**
	 * Request Cobbler API to disable netbooting on an existing system
	 *
	 * @access public
	 * @param string $system_name Name of the system
	 * @return boolean True if everything goes fine, false otherwise
	 */
	public function disableNetboot($system_name){

		$token = $this->auth();	
		$handle = $this->getSystemHandle($token, $system_name);
		$this->ixr_client->query('modify_system', $handle,'netboot_enabled', False, $token);
		$this->ixr_client->query('save_system', $handle, $token);
		return true;

	}

	/**
	 * Request Cobbler API to set up a SSH key for an exiting system. If the system has already a SSH key, 
	 * this will update it and change it in the next reprovision operation. 
	 *
	 * @access public
	 * @param string $system_name Name of the system
	 * @param string $key SSH key to add to the system. 
	 * @return boolean True if everything goes fine, false otherwise
	 */
	public function setSSHKey($system_name, $key) {

		$token = $this->auth();	
		$this->updateMetadata($token, $system_name, 'ssh_key', $key);
		return true;
		
	}

	/**
	 * Request Cobbler API to set up a password for the root user on an exiting system.
	 *
	 * @access public
	 * @param string $system_name Name of the system
	 * @param string $password Plain text password to add to the system
	 * @return boolean True if everything goes fine, false otherwise
	 */
	public function setPassword($system_name, $password) {

		$token = $this->auth();	
		$password_crypted = crypt($password);
		$this->updateMetadata($token, $system_name, 'custom_password', $password_crypted);
		return true;
		
	}
}

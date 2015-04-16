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
		$this->_ixrClient->query('login',$this->_user,$this->_pass);
		return $this->_ixrClient->getResponse();
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
		$this->_ixrClient->query('get_system_handle', $system_name, $token);
		return $this->_ixrClient->getResponse();
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

		$this->_ixrClient->query('get_system', $system_name);
		$system = $this->_ixrClient->getResponse();
		$metadata = $system['ks_meta'];
		$metadata[$key] = $value;
		$metadata_string = implode(' ', array_map(function ($v, $k) { return $k . '=' . $v; }, $metadata, array_keys($metadata)));
		$handle = $this->getSystemHandle($token, $system_name);
		$this->_ixrClient->query('modify_system', $handle,'ks_meta', $metadata_string, $token);
		$this->_ixrClient->query('save_system', $handle, $token);
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
		$this->_ixrClient->query('find_system', array($key => $value));
		return $this->_ixrClient->getResponse();
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
		$this->_ixrClient->query('get_systems');
		return $this->_ixrClient->getResponse();
	}

	/**
	 * Request Cobbler API for list of systems
	 *
	 * @access public
	 * @return array List all the systems in Cobbler
	 */
	public function listDistros(){
		$token = $this->auth();
		$this->_ixrClient->query('get_distros');
		return $this->_ixrClient->getResponse();
	}

	/**
	 * Request Cobbler API for list of profiles
	 *
	 * @access public
	 * @return array List all the profiles in Cobbler
	 */
	public function listProfiles(){
		$token = $this->auth();
		$this->_ixrClient->query('get_profiles');
		return $this->_ixrClient->getResponse();
	}

	/**
	 * Request Cobbler API for list of images
	 *
	 * @access public
	 * @return array List all the images in Cobbler
	 */
	public function listImages(){
		$token = $this->auth();
		$this->_ixrClient->query('get_images');
		return $this->_ixrClient->getResponse();
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
	public function createSystem($params = array()){

		$token = $this->auth();

		if (empty($params['name'])) {
			throw new \Exception('Missing argument: `name` is required.');
		}

		if (empty($params['host'])) {
			throw new \Exception('Missing argument: `host` is required.');
		}

		if (empty($params['mac'])) {
			throw new \Exception('Missing argument: `mac` is required.');
		}

		if (empty($params['profile'])) {
			throw new \Exception('Missing argument: `profile` is required.');
		}

		$name = $params['name'];
		$item = $params['item'];
		$mac = $params['mac'];
		$profile = $params['profile'];

		if (empty($params['interface'])) {
			$interfaceName = 'eth0';
		}else{
			$interfaceName = $params['interface'];
		}

		//$ip = $params['ip'];
		//$gateway = $params['gateway'];
		//$dnsname1 = $params['dnsname1'];
		//$dnsname2 = $params['dnsname2'];


		if ($this->existsSystem('name',$name)){
			throw new Exception('There is already a system using that name');
		}

		if ($this->existsSystem('hostname',$host)){
			throw new Exception('There is already a system using that hostname');
		}

		if ($this->existsSystem('mac_address',$mac)){
			throw new Exception('There is already a system using that mac address');
		}

		$this->_ixrClient->query('new_system',$token);
		$system_id = $this->_ixrClient->getResponse();
		$this->_ixrClient->query('modify_system', $system_id, 'name', $name, $token);
		$this->_ixrClient->query('modify_system', $system_id, 'hostname', $host, $token);
		$this->_ixrClient->query('modify_system', $system_id, 'profile', $profile, $token);

		$interface = array();
		$interface['macaddress-'.$interfaceName] = $mac;
		//$interface['ipaddress-'.$interfaceName] = $ip;
		//$interface['gateway-'.$interfaceName] = $mac;
		//$interface['virtbridge-'.$interfaceName] = 'xenbr0';
		//$interface['dnsname-'.$interfaceName] = $mac;
		//$interface['static-'.$interfaceName] = True;
		//$interface['dhcptag-'.$interfaceName] = $mac;
		//$interface['staticroutes-'.$interfaceName] = $mac;
		$this->_ixrClient->query('modify_system', $system_id, 'modify_interface', $interface, $token);


		$this->_ixrClient->query('save_system', $system_id, $token);

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
		$this->_ixrClient->query('remove_system', $system_name, $token);
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
		$this->_ixrClient->query('modify_system', $handle,'netboot_enabled', True, $token);
		$this->_ixrClient->query('save_system', $handle, $token);
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
		$this->_ixrClient->query('modify_system', $handle,'netboot_enabled', False, $token);
		$this->_ixrClient->query('save_system', $handle, $token);
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

	/**
	 * Request Cobbler API for the current status of the server, to check if is active or being installed
	 *
	 * @access public
	 * @param string $ip IP assigned to the server
	 * @return string State of the server
	 */
	public function getStatus($ip) {

		$token = $this->auth();
		$this->_ixrClient->query('get_status' ,'normal', $token);
		$status = $this->_ixrClient->getResponse();
		if (sizeof($status[$ip])){
			return end ($status[$ip]);
		}
		return 'unknown';
	}
}

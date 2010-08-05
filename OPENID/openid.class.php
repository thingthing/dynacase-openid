<?php
/*
 FREE TO USE
 Under License: GPLv3
 Simple OpenID PHP Class
 */

/*
 * The Yadis library is necessary for OpenID 2.0 specifications.
 * The default path assumes the library is located in the same directory
 * as the Dope OpenID class file. Feel free to change the path to this
 * library if necessary.
 */
require_once 'Services/Yadis/Yadis.php';

Class SimpleOpenID
{
	private $openid1;
	private $openid2;
	private $authtype;
	private $authproviderlist;
	private $class;
	private $version;
	private $openid_url_identity;
	private $openid_ns;
	private $openid_version;
	public $arr_userinfo = array();
	private $URLs = array();
	private $error = array();
	public $fields = array(
		      'required'	 => array(),
		      'optional'	 => array(),
	);
	private $types = array(
			 	'nickname',
				'email',
				'fullname',
				'dob',
				'gender',
				'postcode',
				'country',
				'language',
				'timezone',
				'prefix',
				'firstname',
				'lastname',
				'suffix',
	);
	// An associative array of AX schema definitions
	private $arr_ax_types = array(
				'nickname'  => 'http://axschema.org/namePerson/friendly',
				'email'     => 'http://axschema.org/contact/email',
				'fullname'  => 'http://axschema.org/namePerson',
				'dob'       => 'http://axschema.org/birthDate',
				'gender'    => 'http://axschema.org/person/gender',
				'postcode'  => 'http://axschema.org/contact/postalCode/home',
				'country'   => 'http://axschema.org/contact/country/home',
				'language'  => 'http://axschema.org/pref/language',
				'timezone'  => 'http://axschema.org/pref/timezone',
				'prefix'    => 'http://axschema.org/namePerson/prefix',
				'firstname' => 'http://axschema.org/namePerson/first',
				'lastname'  => 'http://axschema.org/namePerson/last',
				'suffix'    => 'http://axschema.org/namePerson/suffix',
	);

	/**
	 *
	 * Set new openid object with identity
	 * @param $identity
	 */
	public function __construct($identity)
	{
		if ( ! $identity) {
			$this->errorStore('OPENID_NOIDENTITY','No identity passed to OpenID constructor.');
			return FALSE;
		}

		// cURL is required for OpenID to work.
		if ( ! function_exists('curl_exec')) {
			die('Error: Freedom OpenID requires the PHP cURL extension.');
		}

		// Set user's identity.
		$this->setIdentity($identity);

		include_once("WHAT/Class.openidAuthenticator.php");

		//Set env variable
		$this->openid1 = 1;
		$this->openid2 = 2;
		$this->authtype = getAuthType();
		$this->authproviderlist = getAuthProvider();
		$this->class = new openidAuthenticator($this->authtype, $this->authproviderlist);
		$this->findProviderInParms();
	}

	public function findProviderInParms() {
		foreach ($this->class->parms{'providers'} as $k => $v) {
			if (strstr($this->openid_url_identity, $k) !== False) {
				$this->version = $this->findVersionInParms($v);
				break;
			}
		}
		if (!$this->version) {
			error_log("No version found");
			$this->version = $this->openid1;
		}
		elseif ($this->version != $this->openid2 && $this->version != $this->openid1) {
			error_log($this->version);
			$redir_url = $this->class->parms{'authurl'} . '&openid_mode=noversion';
			header('Location: ' . $redir_url);
			exit();
		}
	}

	function findVersionInParms($str) {
		$tab = array();
		$tab = explode(',', $str);
		foreach ($tab as $k => $v) {
			if (strstr($v, "version") !== FALSE) {
				$subtab = array();
				$subtab = explode(":", $v);
				if ($subtab[1]) {
					$res = trim($subtab[1]);
				}
				else {
					$res = "No correct value for Openid version, please check you're conf file";
				}
				return $res;
			}
		}
		return "No version found, check you're conf file";
	}

	/**
	 *
	 * Set user username
	 * @param $identity
	 */
	public function setUsername($identity)
	{
		$res = array();
		if (stripos($identity, 'http://') !== false)
		{
			$res = explode("http://", $identity);
			if ($res[1]) {
				$identity = $res[1];
			}
		}
		elseif (stripos($identity, 'https://') !== false)
		{
			$res = explode("https://", $identity);
			if ($res[1]) {
				$identity = $res[1];
			}
		}
		if (strchr($identity, "/") !== FALSE && strchr($identity, "/") === "/") {
			$identity = substr($identity, 0, strlen($identity) - 1);
		}
		return $identity;
	}

	/**
	 * Set return url
	 *
	 * @param $a
	 */
	public function setReturnURL($a)
	{
		if (strchr($a, "index.php") !== FALSE) {
			$a = substr($a, 0, strpos($a, "index.php"));
		}
		if (strstr($a, "?")) {
			if (strchr($a, "&") !== FALSE && strchr($a, "&") === "&") {
				$a .= 'getOpenID=' . $_POST["id"];
			}
			else {
				$a .= '&getOpenID=' . $_POST["id"];
			}
		}
		else {
			$a .= '?getOpenID=' . $_POST["id"];
		}
		if ($this->version == $this->openid1) {
			$this->URLs['approved'] = $a;
		}
		elseif ($this->version == $this->openid2) {
			$this->URLs['return'] = $a;
		}
	}

	/**
	 *
	 * Set default url
	 * @param $a
	 */
	public function setTrustRoot($a)
	{
		if (strchr($a, "index.php") !== FALSE) {
			$a = substr($a, 0, strpos($a, "index.php"));
		}
		$this->URLs['trust_root'] = $a;
	}

	/**
	 *
	 * Set cancel url
	 * @param $a
	 */
	public function setCancelURL($a)
	{
		$this->URLs['cancel'] = $a;
	}

	/**
	 *
	 * Set required fields you want to get from openid(if these fields or not given, authentication will fail)
	 * @param $a
	 */
	public function setRequiredInfo($a)
	{
		if (is_array($a)){
			$this->fields['required'] = $a;
		}
		else
		{
			$this->fields['required'][] = $a;
		}
	}

	/**
	 *
	 * Set optinal fields you want to get from openid(even if these fields are not given, authentification will work)
	 * @param $a
	 */
	public function setOptionalInfo($a)
	{
		if (is_array($a))
		{
			$this->fields['optional'] = $a;
		}
		else
		{
			$this->fields['optional'][] = $a;
		}
	}


	public function setPapePolicies($policies)
	{
		if (is_array($policies)) {
			$this->fields['pape_policies'] = $policies;
		}
		else {
			$this->fields['pape_policies'][] = $policies;
		}
	}

	public function setPapeMaxAuthAge($seconds){
		// Numeric value greater than or equal to zero in seconds
		// How much time should the user be given to authenticate?
		if(preg_match("/^[1-9]+[0-9]*$/",$seconds)){
			$this->fields['pape_max_auth_age'] = $seconds;
		}
		else {
			$this->errorStore('OPENID_MAXAUTHAGE','Max Auth Age must be a numeric value greater than or equal to zero in seconds.');
			return FALSE;
		}
	}

	/**
	 *
	 * Check if error is set
	 */
	public function isError()
	{
		if ( ! empty($this->error)) {
			return TRUE;
		}
		else{
			return FALSE;
		}
	}

	/**
	 *
	 * Get Error
	 */
	public function getError()
	{
		$e = $this->error;
		return array('code'=>$e[0],'description'=>$e[1]);
	}


	/**
	 *
	 * Get openid server
	 */
	public function getOpenIDServer()
	{
		if ($this->version == $this->openid2) {
			//Try Yadis Protocol discovery first
			$http_response = array();
			$fetcher = Services_Yadis_Yadis::getHTTPFetcher();
			$yadis_object = Services_Yadis_Yadis::discover($this->openid_url_identity, $http_response, $fetcher);

			// Yadis object is returned if discovery is successful
			if($yadis_object != NULL) {
					
				$service_list  = $yadis_object->services();
				$service_types = $service_list[0]->getTypes();
					
				$servers   = $service_list[0]->getURIs();
				$delegates = $service_list[0]->getElements('openid:Delegate');

			}
		}
		elseif ($this->version == $this->openid1) {
			$response = $this->CURL_Request($this->openid_url_identity);
			list($servers, $delegates) = $this->HTML2OpenIDServer($response);
		}

		// If no servers were discovered by parsing HTML, error out
		if (empty($servers))
		{
			error_log(__CLASS__."::".__FUNCTION__." OPENID_NOSERVERFOUND");
			$this->errorStore('OPENID_NOSERVERSFOUND');
			return false;
		}
		// If $service_type has at least one non-null character
		if (isset($service_types[0]) && ($service_types[0] != "")) {
			$this->setServiceType($service_types[0]);
		}
		// If $delegates has at least one non-null character
		if (isset($delegates[0]) && ($delegates[0] != "")) {
			$this->setIdentity($delegates[0]);
		}
		$this->setOpenIDServer($servers[0]);
		return $servers[0];
	}

	/**
	 * Redirect to openid provider
	 */
	public function redirect()
	{
		$redirect_to = $this->getRedirectURL();
		header('Location: ' . $redirect_to);
	}

	/**
	 * Check with openid information with openid server
	 */
	public function validateWithServer()
	{
		if ($this->version == $this->openid1) {
			$params = array(
		    'openid.assoc_handle' => urlencode($_GET['openid_assoc_handle']),
		    'openid.signed' => urlencode($_GET['openid_signed']),
		    'openid.sig' => urlencode($_GET['openid_sig'])
			);
			// Send only required parameters to confirm validity
			$arr_signed = explode(",",str_replace('sreg.','sreg_',$_GET['openid_signed']));
			for ($i=0; $i<count($arr_signed); $i++)
			{
				$s = str_replace('sreg_','sreg.', $arr_signed[$i]);
				$c = $_GET['openid_' . $arr_signed[$i]];
				$params['openid.' . $s] = urlencode($c);
			}
			$params['openid.mode'] = "check_authentication";
			$openid_server = $this->getOpenIDServer();
			if ($openid_server == false)
			{
				error_log(__CLASS__."::".__FUNCTION__." Server return false abort");
				return false;
			}
		}
		elseif ($this->version == $this->openid2) {
			$params = array();

			// Find keys that include dots and store them in an array
			preg_match_all("/([\w]+[\.])/",$_GET['openid_signed'],$arr_periods);
			$arr_periods = array_unique(array_shift($arr_periods));

			// Duplicate the dot keys array, but replace the dot with an underscore
			$arr_underscores = preg_replace("/\./","_",$arr_periods);

			// The OpenID Provider returns a list of signed keys we need to validate
			$arr_get_signed_keys = explode(",",str_replace($arr_periods, $arr_underscores, $_GET['openid_signed']));

			// Send back only the signed keys to confirm validity
			foreach($arr_get_signed_keys as $key) {
				$paramKey = str_replace($arr_underscores, $arr_periods, $key);
				$params["openid.$paramKey"] = urlencode($_GET["openid_$key"]);
			}
			$params['openid.assoc_handle'] = urlencode($_GET['openid_assoc_handle']);
			$params['openid.signed']       = urlencode($_GET['openid_signed']);
			$params['openid.sig']  = urlencode($_GET['openid_sig']);
			$params['openid.mode'] = "check_authentication";

			$openid_server = $this->getOpenIDServer();

			if ($openid_server == FALSE) {
				return FALSE;
			}
		}
		$response = $this->CURL_Request($openid_server,'POST',$params);
		$data = $this->splitResponse($response);
		if ($data['is_valid'] == "true") {
			return true;
		}
		else {
			error_log(__CLASS__."::".__FUNCTION__." wrong data return false::".implode($params, "--"));
			return false;
		}
	}

	/**
	 *
	 * Filter user info in openid url
	 * @param $get
	 */
	public function filterUserInfo($get)
	{
		if ($this->version == $this->openid1) {
			$i = 0;
			$ret = array();
			while (isset($this->types[$i]))
			{
				if (isset($get["openid_sreg_" . $this->types[$i]])) {
					$ret[$this->types[$i]] = $get["openid_sreg_" . $this->types[$i]];
				}
				$i++;
			}
			return ($ret);
		}
		elseif ($this->version == $this->openid2) {
			foreach($get as $key => $value){
				$trimmed_key = substr($key,strrpos($key,"_")+1);
				if(stristr($key, 'openid_ext1_value') && isset($value[1])) {
					$this->arr_userinfo[$trimmed_key] = $value;
					error_log("value = ". $value);
				}
				if(stristr($key, 'sreg_') && array_key_exists($trimmed_key, $this->arr_ax_types)) {
					$this->arr_userinfo[$trimmed_key] = $value;
					error_log("value2 = ". $value);
				}
			}
			return $this->arr_userinfo;
		}
	}

	/**
	 *
	 * Set Identity URL
	 * @param $identity
	 */
	public function SetIdentity($identity)
	{
		if ((stripos($identity, 'http://') === false)
		&& (stripos($identity, 'https://') === false))
		{
			$identity = 'http://'.$identity;
		}
		if (stripos($identity, 'gmail') || stripos($identity, 'google')) {
			$identity = "https://www.google.com/accounts/o8/id";
			$this->version = $this->openid2;
		}
		if (stripos($identity, 'yahoo')) {
			$identity = "https://me.yahoo.com/";
			$this->version = $this->openid2;
		}
		$this->openid_url_identity = $identity;
	}

	/**
	 *
	 * form and executer curl request
	 * @param unknown_type $url
	 * @param unknown_type $method
	 * @param unknown_type $params
	 */
	public function CURL_Request($url, $method="GET", $params = "")
	{
		if (is_array($params)) {
			$params = $this->array2url($params);
		}
		$curl = curl_init($url . ($method == "GET" && $params != "" ? "?" . $params : ""));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
		curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		if ($method == "POST") {
			$err = curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		}
		$response = curl_exec($curl);
		if (curl_errno($curl) == 0) {
			$response;
		}
		else {
			$this->errorStore('OPENID_CURL', curl_error($curl));
			error_log(__CLASS__."::".__FUNCTION__."Error:". curl_error($curl));
			$redir_url = $this->class->parms{'authurl'} . '&openid_mode=notvalid';
			error_log('redir url === ' . $redir_url);
			header('Location: ' . $redir_url);
			exit();
		}
		curl_close($curl);
		return $response;
	}

	/**
	 * transform html request in openid information
	 * @param $content
	 */
	public function HTML2OpenIDServer($content)
	{
		$get = array();

		// Get details of their OpenID server and (optional) delegate
		preg_match_all('/<link[^>]*rel=[\'"]openid.server[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);
		preg_match_all('/<link[^>]*href=\'"([^\'"]+)[\'"][^>]*rel=[\'"]openid.server[\'"][^>]*\/?>/i', $content, $matches2);
		$servers = array_merge($matches1[1], $matches2[1]);
		preg_match_all('/<link[^>]*rel=[\'"]openid.delegate[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);
		preg_match_all('/<link[^>]*href=[\'"]([^\'"]+)[\'"][^>]*rel=[\'"]openid.delegate[\'"][^>]*\/?>/i', $content, $matches2);
		$delegates = array_merge($matches1[1], $matches2[1]);
		$ret = array($servers, $delegates);
		return $ret;
	}

	/**
	 * Split reponse got from openid server to put in a array
	 * @param $response
	 */
	public function splitResponse($response)
	{
		$r = array();
		$response = explode("\n", $response);
		foreach($response as $line)
		{
			$line = trim($line);
			if ($line != "")
			{
				list($key, $value) = explode(":", $line, 2);
				$r[trim($key)] = trim($value);
			}
		}
		return $r;
	}

	/**
	 *
	 * converts associated array to URL Query String
	 * @param $arr
	 */
	public function array2url($arr)
	{
		if (!is_array($arr)) {
			return false;
		}
		$query = '';
		foreach($arr as $key => $value)
		{
			$query .= $key . "=" . $value . "&";
		}
		return $query;
	}

	/**
	 *
	 * Set openid server url
	 * @param $identity
	 */
	public function setOpenIDServer($url)
	{
		$this->URLs['openid_server'] = $url;
	}

	/**
	 *
	 * Check wich version of openid user is using and fill openid_service variable.
	 * @param unknown_type $url
	 */
	private function setServiceType($url)
	{

		//check the protocol version in order to handle backwards compatibility.

		if (stristr($url, "2.0")) {
			$ns = "http://specs.openid.net/auth/2.0";
			$version = "2.0";
		}
		else if (stristr($url, "1.1")) {
			$ns = "http://openid.net/signon/1.1";
			$version = "1.1";
		}
		else {
			$ns = "http://openid.net/signon/1.0";
			$version = "1.0";
		}
		$this->openid_ns      = $ns;
		$this->openid_version = $version;
	}

	/**
	 * Get url for openid redirection
	 */
	public function getRedirectURL()
	{
		$params = array();
		$params['openid.identity'] = urlencode($this->openid_url_identity);
		$params['openid.mode'] = 'checkid_setup';
		if ($this->version == $this->openid1) {
			$params['openid.trust_root'] = urlencode($this->URLs['trust_root']);
			$params['openid.return_to'] = urlencode($this->URLs['approved']);

		}
		elseif ($this->version == $this->openid2) {
			$params['openid.return_to'] = urlencode($this->URLs['return']);
			$params['openid.ns']         = urlencode($this->openid_ns);
			$params['openid.claimed_id'] = urlencode("http://specs.openid.net/auth/2.0/identifier_select");
			$params['openid.identity']   = urlencode("http://specs.openid.net/auth/2.0/identifier_select");
			$params['openid.realm']      = urlencode($this->URLs['trust_root']);
		}
		/**
		 * Handle user attribute requests.
		 */
		$info_request = FALSE;

		// User Info Request: Setup
		if (isset($this->fields['required']) || isset($this->fields['optional'])) {
			$params['openid.ns.ax']   = "http://openid.net/srv/ax/1.0";
			$params['openid.ax.mode'] = "fetch_request";
			$params['openid.ns.sreg'] = "http://openid.net/extensions/sreg/1.1";

			$info_request = TRUE;
		}

		// If we're requesting user info from Google opr yahoo, it MUST be specified as "required"
		// Will not work otherwise.
		if ((stristr($this->URLs['openid_server'], 'google.com') || stristr($this->URLs['openid_server'], 'yahoo.com')) && $info_request == TRUE) {
			$this->fields['required'] = array_unique(array_merge($this->fields['optional'], $this->fields['required']));
			$this->fields['optional'] = array();
		}

		//User info required data
		if (isset($this->fields['required']) && (!empty($this->fields['required'])))
		{
			// Set required params for Attribute Exchange (AX) protocol
			$params['openid.ax.required']   = implode(',',$this->fields['required']);
			foreach($this->fields['required'] as $field) {
				if ($this->version == $this->openid1) {
					if(array_key_exists($field,$this->types)) {
						$params["openid.ax.type.$field"] = urlencode($this->types[$field]);
					}
				}
				elseif ($this->version == $this->openid2) {
					if(array_key_exists($field,$this->arr_ax_types)) {
						$params["openid.ax.type.$field"] = urlencode($this->arr_ax_types[$field]);
					}
				}
			}
			// Set required params for Simple Registration (SREG) protocol
			$params['openid.sreg.required'] = implode(',',$this->fields['required']);
		}

		//User info optional data
		if (isset($this->fields['optional']) && (!empty($this->fields['optional'])))
		{
			// Set optional params for Attribute Exchange (AX) protocol
			$params['openid.ax.if_available'] = implode(',',$this->fields['optional']);
			foreach($this->fields['optional'] as $field) {
				if ($this->version == $this->openid1) {
					if(array_key_exists($field,$this->types)) {
						$params["openid.ax.type.$field"] = urlencode($this->types[$field]);
					}
				}
				elseif ($this->version == $this->openid2) {
					if(array_key_exists($field,$this->arr_ax_types)) {
						$params["openid.ax.type.$field"] = urlencode($this->arr_ax_types[$field]);
					}
				}
			}
			// Set optional params for Simple Registration (SREG) protocol
			$params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
		}

		// Add PAPE params if exists
		if (isset($this->fields['pape_policies']) && (!empty($this->fields['pape_policies']))) {
			$params['openid.ns.pape'] = "http://specs.openid.net/extensions/pape/1.0";
			$params['openid.pape.preferred_auth_policies'] = urlencode(implode(' ',$this->fields['pape_policies']));

			if($this->fields['pape_max_auth_age']) {
				$params['openid.pape.max_auth_age'] = $this->fields['pape_max_auth_age'];
			}
		}
		$urlJoiner = (strstr($this->URLs['openid_server'], "?")) ? "&" : "?";
		return $this->URLs['openid_server'] . $urlJoiner . $this->array2url($params);
	}

	/**
	 *
	 * Store Error
	 * @param $code
	 * @param $desc
	 */
	public function errorStore($code, $desc = null)
	{
		$errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
		if ($desc == null) {
			$desc = $errs[$code];
		}
		$this->error = array($code,$desc);
	}

	public function getTrustRoot() {
		return $this->URLs['trust_root'];
	}

	public function getOpenidVersion() {
		return $this->version;
	}

	/**
	 *
	 *  Get Identity
	 */

	public function getIdentity()
	{
		return $this->openid_url_identity;
	}

	/**
	 * 
	 * Get a good username for freedom
	 * @param unknown_type $username
	 */
	public function getUsername($username) {
		//If openid provider is google or yahoo, useridentity would be the return url so we put the email instead for more readability
		if (stripos($username, 'gmail') || stripos($username, 'google') || stripos($username, 'yahoo')) {
			$userinfo = $this->filterUserInfo($_GET);
			if ($userinfo['email']) {
				$username = $userinfo['email'];
			}
		}
		if (stripos($username, '/')) {
			$username = str_replace("/", ".", $username);
		}
		return $username;
	}
}
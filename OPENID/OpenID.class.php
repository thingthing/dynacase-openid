<?php
/*
 FREE TO USE
 Under License: GPLv3
 Simple OpenID PHP Class
 */

Class SimpleOpenID
{

	var $openid_url_identity;
	private $openid_ns;
	private $openid_version;
	private $URLs = array();
	private $error = array();
	public $fields = array(
		      'required'	 => array(),
		      'optional'	 => array(),
	);
	private $types = array(
			 "nickname",
			 "email",
			 "fullname",
			 "dob",
			 "gender",
			 "postcode",
			 "country",
			 "language",
			 "timezone",
			 "prefix",
			 "firstname",
			 "lastname",
			 "suffix"
			 );
			 /**
			  *
			  * Set new openid object with identity
			  * @param $identity
			  */
			 public function __construct($identity)
			 {
			 	if ( ! $identity) {
			 		$this->errorStore('OPENID_NOIDENTITY','No identity passed to Dope OpenID constructor.');
			 		return FALSE;
			 	}

			 	// cURL is required for Dope OpenID to work.
			 	if ( ! function_exists('curl_exec')) {
			 		die('Error: Dope OpenID requires the PHP cURL extension.');
			 	}

			 	// Set user's identity.
			 	$this->SetIdentity($identity);
			 }

			 /**
			  *
			  * Filter user info in openid url
			  * @param $get
			  */
			 public function filterUserInfo($get)
			 {
			 	$i = 0;
			 	$ret = array();
			 	while (isset($this->types[$i]))
			 	{
			 		if (isset($get["openid_sreg_" . $this->types[$i]]))
			 		$ret[$this->types[$i]] = $get["openid_sreg_" . $this->types[$i]];
			 		$i++;
			 	}
			 	return ($ret);
			 }

			 /**
			  *
			  * Set openid server url
			  * @param $a
			  */
			 public function SetOpenIDServer($a)
			 {
			 	$this->URLs['openid_server'] = $a;
			 }

			 /**
			  *
			  * Set default url
			  * @param $a
			  */
			 public function SetTrustRoot($a)
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
			 public function SetCancelURL($a)
			 {
			 	$this->URLs['cancel'] = $a;
			 }

			 /**
			  * Set return url
			  *
			  * @param $a
			  */
			 public function SetApprovedURL($a)
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
			 	$this->URLs['approved'] = $a;
			 }

			 /**
			  *
			  * Set required fields you want to get from openid(if these fields or not given, authentication will fail)
			  * @param $a
			  */
			 public function SetRequiredFields($a)
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
			 public function SetOptionalFields($a)
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

			 /**
			  *
			  * Set user username
			  * @param $identity
			  */
			 public function SetUsername($identity)
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
			 	return $identity;
			 }

			 /**
			  *
			  * Set Identity URL
			  * @param $a
			  */
			 public function SetIdentity($a)
			 {
			 	if ((stripos($a, 'http://') === false)
			 	&& (stripos($a, 'https://') === false))
			 	{
			 		$a = 'http://'.$a;
			 	}
			 	// Google is not publishing its XRDS document yet, so the OpenID
			 	// endpoint must be set manually for now.
			 	//Does not work
			 	if (stripos($a, 'gmail') OR stripos($a, 'google')) {
			 		$a = "https://www.google.com/accounts/o8/id";
			 	}
			 	$this->openid_url_identity = $a;
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
			  *
			  *  Get Identity
			  */
			 	
			 public function GetIdentity()
			 {
			 	return $this->openid_url_identity;
			 }

			 /**
			  *
			  * Get Error
			  */
			 public function GetError()
			 {
			 	$e = $this->error;
			 	return array('code'=>$e[0],'description'=>$e[1]);
			 }

			 /**
			  *
			  * Store Error
			  * @param $code
			  * @param $desc
			  */
			 public function ErrorStore($code, $desc = null)
			 {
			 	$errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
			 	if ($desc == null)
			 	$desc = $errs[$code];
			 	$this->error = array($code,$desc);
			 }

			 /**
			  *
			  * Check if error is set
			  */
			 public function IsError()
			 {
			 	if (count($this->error) > 0)
			 	return true;
			 	else
			 	return false;
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
			 	$err = curl_setopt($curl, CURLOPT_HEADER, false);
			 	$err = curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			 	$err = curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
			 	$err = curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
			 	$err = curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			 	$err = curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			 	if ($err === FALSE){
			 		error_log(__CLASS__."::".__FUNCTION__."Curl error got");
			 	}
			 	if ($method == "POST")
			 	{
			 		$err = curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			 	}
			 	$err = curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			 	$response = curl_exec($curl);
			 	if (curl_errno($curl) == 0) {
			 		$response;
			 	}
			 	else {
			 		$this->ErrorStore('OPENID_CURL', curl_error($curl));
			 		error_log(__CLASS__."::".__FUNCTION__."Error:". curl_error($curl));
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
			  *
			  * Get openid server
			  */
			 public function GetOpenIDServer()
			 {
			 		$response = $this->CURL_Request($this->openid_url_identity);
			 		list($servers, $delegates) = $this->HTML2OpenIDServer($response);
			 	// If no servers were discovered by parsing HTML, error out
			 	if (empty($servers))
			 	{
			 		error_log(__CLASS__."::".__FUNCTION__." OPENID_NOSERVERFOUND");
			 		$this->ErrorStore('OPENID_NOSERVERSFOUND');
			 		return false;
			 	}
			 	// If $service_type has at least one non-null character
			 	if (isset($service_types[0]) && ($service_types[0] != "")) {
			 		$this->setServiceType($service_types[0]);
			 	}
			 	// If $delegates has at least one non-null character
			 	if (isset($delegates[0]) && ($delegates[0] != "")) {
			 		$this->SetIdentity($delegates[0]);
			 	}
			 	$this->SetOpenIDServer($servers[0]);
			 	return $servers[0];
			 }

			 /**
			  * Get url for openid redirection
			  */
			 public function GetRedirectURL()
			 {
			 	$params = array();
			 	$params['openid.return_to'] = urlencode($this->URLs['approved']);
			 	$params['openid.mode'] = 'checkid_setup';
			 	$params['openid.identity'] = urlencode($this->openid_url_identity);
			 	$params['openid.trust_root'] = urlencode($this->URLs['trust_root']);
			 	if (isset($this->fields['required'])
			 	&& (count($this->fields['required']) > 0))
			 	{
			 		$params['openid.sreg.required'] = implode(',',$this->fields['required']);
			 	}
			 	if (isset($this->fields['optional']) && (count($this->fields['optional']) > 0))
			 	{
			 		$params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
			 	}
			 	// If we're requesting user info from Google, it MUST be specified as "required"
			 	// Will not work otherwise.
			 	if (stristr($this->URLs['openid_server'], 'google.com') && $info_request == TRUE) {
			 		$this->fields['required'] = array_unique(array_merge($this->fields['optional'], $this->fields['required']));
			 		$this->fields['optional'] = array();
			 	}
			 	// User Info Request: Required data
			 	if (isset($this->fields['required']) && ( ! empty($this->fields['required']))) {
			 		// Set required params for Attribute Exchange (AX) protocol
			 		$params['openid.ax.required']   = implode(',',$this->fields['required']);
			 		foreach($this->fields['required'] as $field) {
			 			if(array_key_exists($field,$this->types)) {
			 				$params["openid.ax.type.$field"] = urlencode($this->arr_ax_types[$field]);
			 			}
			 		}
			 		// Set required params for Simple Registration (SREG) protocol
			 		$params['openid.sreg.required'] = implode(',',$this->fields['required']);
			 	}
			 	// User Info Request: Optional data
			 	if (isset($this->fields['optional']) && ( ! empty($this->fields['optional']))) {
			 		// Set optional params for Attribute Exchange (AX) protocol
			 		$params['openid.ax.if_available'] = implode(',',$this->fields['optional']);
			 		foreach($this->fields['optional'] as $field) {
			 			if(array_key_exists($field,$this->types)) {
			 				$params["openid.ax.type.$field"] = urlencode($this->arr_ax_types[$field]);
			 			}
			 		}
			 		// Set optional params for Simple Registration (SREG) protocol
			 		$params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
			 	}
			 	$urlJoiner = (strstr($this->URLs['openid_server'], "?")) ? "&" : "?";
			 	return $this->URLs['openid_server'] . $urlJoiner . $this->array2url($params);
			 }

			 /**
			  * Redirect to openid provider
			  */
			 public function Redirect()
			 {
			 	$redirect_to = $this->GetRedirectURL();
			 	header('Location: ' . $redirect_to);
			 }

			 /**
			  * Check with openid information with openid server
			  */
			 public function ValidateWithServer()
			 {
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
			 	$openid_server = $this->GetOpenIDServer();
			 	if ($openid_server == false)
			 	{
			 		error_log(__CLASS__."::".__FUNCTION__." Server return false abort");
			 		return false;
			 	}
			 	$response = $this->CURL_Request($openid_server,'POST',$params);
			 	$data = $this->splitResponse($response);
			 	if ($data['is_valid'] == "true") {
			 		return true;
			 	}
			 	else {
			 		error_log(__CLASS__."::".__FUNCTION__." wrong data return false");
			 		return false;
			 	}
			 }
}

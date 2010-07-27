<?php
/*
 FREE TO USE
 Under License: GPLv3
 Simple OpenID PHP Class

 Some modifications by Eddie Roosenmaallen, eddie@roosenmaallen.com

 -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

 This Class was written to make easy for you to integrate OpenID on your website.
 This is just a client, which checks for user's identity. This Class Requires CURL Module.
 It should be easy to use some other HTTP Request Method, but remember, often OpenID servers
 are using SSL.
 We need to be able to perform SSL Verification on the background to check for valid signature.

 HOW TO USE THIS CLASS:
 STEP 1)
 $openid = new SimpleOpenID;
 :: SET IDENTITY ::
 $openid->SetIdentity($_POST['openid_url']);
 :: SET RETURN URL ::
 $openid->SetApprovedURL('http://www.yoursite.com/return.php'); // Script which handles a response from OpenID Server
 :: SET TRUST ROOT ::
 $openid->SetTrustRoot('http://www.yoursite.com/');
 :: FETCH SERVER URL FROM IDENTITY PAGE ::  [Note: It is recomended to cache this (Session, Cookie, Database)]
 $openid->GetOpenIDServer(); // Returns false if server is not found
 :: REDIRECT USER TO OPEN ID SERVER FOR APPROVAL ::

 :: (OPTIONAL) SET OPENID SERVER ::
 $openid->SetOpenIDServer($server_url); // If you have cached previously this, you don't have to call GetOpenIDServer and set value this directly

 STEP 2)
 Once user gets returned we must validate signature
 :: VALIDATE REQUEST ::
 true|false = $openid->ValidateWithServer();

 ERRORS:
 array = $openid->GetError(); 	// Get latest Error code

 FIELDS:
 OpenID allowes you to retreive a profile. To set what fields you'd like to get use (accepts either string or array):
 $openid->SetRequiredFields(array('email','fullname','dob','gender','postcode','country','language','timezone'));
 or
 $openid->SetOptionalFields('postcode');

 IMPORTANT TIPS:
 OPENID as is now, is not trust system. It is a great single-sign on method. If you want to
 store information about OpenID in your database for later use, make sure you handle url identities
 properly.
 For example:
 https://steve.myopenid.com/
 https://steve.myopenid.com
 http://steve.myopenid.com/
 http://steve.myopenid.com
 ... are representing one single user. Some OpenIDs can be in format openidserver.com/users/user/ - keep this in mind when storing identities

 To help you store an OpenID in your DB, you can use function:
 $openid_db_safe = $openid->OpenID_Standarize($upenid);
 This may not be comatible with current specs, but it works in current enviroment. Use this function to get openid
 in one format like steve.myopenid.com (without trailing slashes and http/https).
 Use output to insert Identity to database. Don't use this for validation - it may fail.

 */

/*
 * The Yadis library is necessary for OpenID 2.0 specifications.
 */

include_once 'Services/Yadis/Yadis.php';

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

			 function filterUserInfo($get)
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

			 function SetOpenIDServer($a)
			 {
			 	$this->URLs['openid_server'] = $a;
			 }

			 function SetTrustRoot($a)
			 {
			 if (strchr($a, "index.php") !== FALSE) {
			 		$a = substr($a, 0, strpos($a, "index.php"));
			 	}
			 	$this->URLs['trust_root'] = $a;
			 }

			 function SetCancelURL($a)
			 {
			 	$this->URLs['cancel'] = $a;
			 }

			 function SetApprovedURL($a)
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
			 	error_log("approved url = " .$a);
			 	$this->URLs['approved'] = $a;
			 }

			 function SetRequiredFields($a)
			 {
			 	if (is_array($a)){
			 		$this->fields['required'] = $a;
			 	}
			 	else
			 	{
			 		$this->fields['required'][] = $a;
			 	}
			 }

			 function SetOptionalFields($a)
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

			 function SetUsername($identity)
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

			 // Set Identity URL
			 function SetIdentity($a)
			 {
			 	if ((stripos($a, 'http://') === false)
			 	&& (stripos($a, 'https://') === false))
			 	{
			 		$a = 'http://'.$a;
			 	}
			 	// Google is not publishing its XRDS document yet, so the OpenID
			 	// endpoint must be set manually for now.
			 	if (stripos($a, 'gmail') OR stripos($a, 'google')) {
			 		$a = "https://www.google.com/accounts/o8/id";
			 	}
			 	error_log("identity= ".$a);
			 	$this->openid_url_identity = $a;
			 }

			 private function setServiceType($url)
			 {
			 	/*
			 	 * Hopefully the provider is using OpenID 2.0 but let's check
			 	 * the protocol version in order to handle backwards compatibility.
			 	 * Probably not the most efficient method, but it works for now.
			 	 */
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

			 // Get Identity
			 function GetIdentity()
			 {
			 	return $this->openid_url_identity;
			 }

			 function GetError()
			 {
			 	$e = $this->error;
			 	return array('code'=>$e[0],'description'=>$e[1]);
			 }

			 function ErrorStore($code, $desc = null)
			 {
			 	$errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
			 	if ($desc == null)
			 	$desc = $errs[$code];
			 	$this->error = array($code,$desc);
			 }

			 function IsError()
			 {
			 	if (count($this->error) > 0)
			 	return true;
			 	else
			 	return false;
			 }

			 function splitResponse($response)
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

			 //To store openid info in database
			 function OpenID_Standarize($openid_identity = null)
			 {
			 	if ($openid_identity === null){
			 		$openid_identity = $this->openid_url_identity;
			 	}
			 	$u = parse_url(strtolower(trim($openid_identity)));
			 	if (!isset($u['path']) || ($u['path'] == '/')) {
			 		$u['path'] = '';
			 	}
			 	if(substr($u['path'],-1,1) == '/') {
			 		$u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
			 	}
			 	// If there is a query string, then use identity as is
			 	if (isset($u['query'])) {
			 		return $u['host'] . $u['path'] . '?' . $u['query'];
			 	}
			 	else {
			 		return $u['host'] . $u['path'];
			 	}
			 }

			 // converts associated array to URL Query String
			 function array2url($arr)
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

			 function FSOCK_Request($url, $method="GET", $params = "")
			 {
			 	$fp = fsockopen("ssl://www.myopenid.com", 443, $errno, $errstr, 3); // Connection timeout is 3 seconds
			 	if (!$fp)
			 	{
			 		$this->ErrorStore('OPENID_SOCKETERROR', $errstr);
			 		return false;
			 	}
			 	else
			 	{
			 		$request = $method . " /server HTTP/1.0\r\n";
			 		$request .= "User-Agent: Simple OpenID PHP Class (http://www.phpclasses.org/simple_openid)\r\n";
			 		$request .= "Connection: close\r\n\r\n";
			 		fwrite($fp, $request);
			 		stream_set_timeout($fp, 4); // Connection response timeout is 4 seconds
			 		$res = fread($fp, 2000);
			 		$info = stream_get_meta_data($fp);
			 		fclose($fp);
			 		if ($info['timed_out']) {
			 			$this->ErrorStore('OPENID_SOCKETTIMEOUT');
			 		}
			 		else {
			 			return $res;
			 		}
			 	}
			 }

			 // Remember, SSL MUST BE SUPPORTED
			 function CURL_Request($url, $method="GET", $params = "")
			 {
			 	if (is_array($params)) {
			 		$params = $this->array2url($params);
			 	}
			 	$curl = curl_init($url . ($method == "GET" && $params != "" ? "?" . $params : ""));
			 	//$err = curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			 	$err = curl_setopt($curl, CURLOPT_HEADER, false);
			 	$err = curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			 	$err = curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
			 	$err = curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
			 	$err = curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			 	$err = curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			 	if ($err === FALSE){
			 		error_log(__CLASS__."::".__FUNCTION__."Error got");
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

			 function HTML2OpenIDServer($content)
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

			 function GetOpenIDServer()
			 {
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
			 	// Else try HTML discovery
			 	else {
			 		$response = $this->CURL_Request($this->openid_url_identity);
			 		list($servers, $delegates) = $this->HTML2OpenIDServer($response);
			 	}
			 	// If no servers were discovered by Yadis or by parsing HTML, error out
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

			 function GetRedirectURL()
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

			 function Redirect()
			 {
			 	$redirect_to = $this->GetRedirectURL();
			 	if (headers_sent())
			 	{
			 		// Use JavaScript to redirect if content has been previously sent (not recommended, but safe)
			 		echo '<script language="JavaScript" type="text/javascript">window.location=\'';
			 		echo $redirect_to;
			 		echo '\';</script>';
			 	}
			 	else
			 	{
			 		// Default Header Redirect
			 		header('Location: ' . $redirect_to);
			 	}
			 }

			 function ValidateWithServer()
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
			 		error_log(__CLASS__."::".__FUNCTION__." data ok return true");
			 		return true;
			 	}
			 	else {
			 		error_log(__CLASS__."::".__FUNCTION__." wrong data return false");
			 		return false;
			 	}
			 }
}

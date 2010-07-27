<?php
include_once("WHAT/Class.Authenticator.php");


Class                   openidAuthenticator extends Authenticator {

	public              $auth_session = null;
/**
 * Function to check if user is authenticate. 
 * If not add if url openid add, get param from the openid url.
 * @see Class/Authenticator/Authenticator::checkAuthentication()
 */
	public function     checkAuthentication() {
		include_once("OPENID/OpenID.class.php");

		$session = $this->getAuthSession();
		if ($session->read('username') != "") {
			//Freedom user already connect
			return TRUE;
		}
		if ($session->read('OPENID_AUTH', false) == true) {
			//Openid user already open
			return true;
		}
		if (!isset($_GET["getOpenID"])) {
			//No information from openid
			$session->close();
			return FALSE;
		}
		$openid = new SimpleOpenID($_GET['openid_identity']);
		$username = $openid->SetUsername($_GET['openid_identity']);
		$openid_validation_result = $openid->ValidateWithServer();
		if ($openid_validation_result == false) {
			error_log(__CLASS__."::".__FUNCTION__." "."Validation with openid failed");
			$session->close();
			return FALSE;
		}
		$session->register('username', $username);
		$session->register("OPENID_AUTH", true);
		//check if user exist on openid database
		if (!$this->freedomUserExists($username)) {
			//user doesn't exist in freedom database try to create it
			if (!$this->tryInitializeUser($username)) {
				return false;
			}
		}
		return true;
	}
/**
 * Get the current session
 * 
 */
	public function     getAuthSession() {
		if (!$this->auth_session) {
			include_once('WHAT/Class.Session.php');
			$this->auth_session = new Session($this->parms{'cookie'});
			if (array_key_exists($this->parms{'cookie'}, $_COOKIE)) {
				$this->auth_session->Set($_COOKIE[$this->parms{'cookie'}]);
			}
			else {
				$this->auth_session->Set();
			}
		}
		return $this->auth_session;
	}

	/**
	 * check if user has authorization
	 * @see Class/Authenticator/Authenticator::checkAuthorization()
	 */
	public function     checkAuthorization($opt) {
		if ($opt['username'] != "") {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * If information got from form, verify information and redirect to openid
	 * @see Class/Authenticator/Authenticator::askAuthentication()
	 */
	public function	askAuthentication() {
		
		if (!isset($_POST["openidSubmit"]))
		{
			include_once("OPENID/openID.php");
			exit(0);
		}
		
		if (!preg_match("#^http://#", $_POST["openid_identifier"]))
		{
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Malformed Openid"));
			$this->logout("");
			exit(0);
		}

		$openid = new SimpleOpenID($_POST['openid_identifier']);
		$openid->SetTrustRoot('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$openid->SetOptionalFields(array('nickname',
				     'email',
				     'fullname',
				     'dob',
				     'gender',
				     'postcode',
				     'country',
				     'language',
				     'timezone'));
		if (!$openid->GetOpenIDServer())
		{
			$error = $openid->GetError();
			error_log("ERROR CODE: " . $error['code']);
			error_log("ERROR DESCRIPTION: " . $error['description']);
			echo "ERROR CODE: ". $error['code'] . "\n";
			echo "ERROR DESCRIPTION: " . $error['description'];
			die;
		}
		$openid->SetApprovedURL('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$openid->Redirect();
		return ;
	}

	/**
	 * Get user's password
	 * @see Class/Authenticator/Authenticator::getAuthPw()
	 */
	public function	getAuthPw() {
		$session_auth=$this->getAuthSession();
		$password = $session_auth->read('password');
		return $password;
	}

	/**
	 * Get user's username
	 * @see Class/Authenticator/Authenticator::getAuthUser()
	 */
	public function	getAuthUser() {
		$session_auth=$this->getAuthSession();
		$username = $session_auth->read('username');
		return $username;
	}

	/**
	 * Logout
	 * @see Class/Authenticator/Authenticator::logout()
	 */
	public function     logout($redir_uri) {
		$session = $this->getAuthSession();
		$session->close();
		if( $redir_uri == "" && array_key_exists('indexurl', $this->parms) ) {
			header('Location: '.$this->parms{'indexurl'});
		} else {
			header('Location: '.$redir_uri);
		}
	}

	/**
	 * set session's variable
	 * @see Class/Authenticator/Authenticator::setSessionVar()
	 */
	public function	setSessionVar($name, $value) {
		$session_auth=$this->getAuthSession();
		$session_auth->register($name, $value);
		return $session_auth->read($name);
	}

	/**
	 * get session's variable
	 * @see Class/Authenticator/Authenticator::getSessionVar()
	 */
	public function	getSessionVar($name) {
		$session_auth=$this->getAuthSession();
		return $session_auth->read($name);
	}

}

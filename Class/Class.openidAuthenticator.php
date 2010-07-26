<?php
include_once("WHAT/Class.Authenticator.php");


Class                   openidAuthenticator extends Authenticator {

	public              $auth_session = null;

	public function     checkAuthentication() {
		include_once("OPENID/OpenID.class.php");

		$session = $this->getAuthSession();
		if ($session->read('username') != "") {
			error_log(__CLASS__."::".__FUNCTION__.":"."Session already opened (freedom)");
			return TRUE;
		}
		if ($session->read('OPENID_AUTH', false) == true) {
			error_log(__CLASS__."::".__FUNCTION__.":"."Session already opened (OPENID)");
			return true;
		}
		if (!isset($_GET["getOpenID"])) {
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("No authorisation token"));
			$session->close();
			return FALSE;
		}
		$openid = new SimpleOpenID;
		$username = $openid->SetUsername($_GET['openid_identity']);
		$openid->SetIdentity($username);
		$openid_validation_result = $openid->ValidateWithServer();
		if ($openid_validation_result == false) {
			error_log(__CLASS__."::".__FUNCTION__." "."Validation with openid failed");
			$session->close();
			return FALSE;
		}
		$session->register('username', $username);
		$session->register("OPENID_AUTH", true);
		error_log(__CLASS__."::".__FUNCTION__."::"."Validation with openid success creating freedom user if not existant");
		if (!$this->freedomUserExists($username)) {
			if (!$this->tryInitializeUser($username)) {
				return false;
			}
		}
		return true;
	}

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

	public function     checkAuthorization($opt) {
		if ($opt['username'] != "") {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	public function	askAuthentication() {
		if (!isset($_POST["openidSubmit"]))
		{
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("No submit"));
			include_once("OPENID/openID.php");
			exit(0);
		}
		if (!preg_match("#^http://#", $_POST["id"]))
		{
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Malformed Openid"));
			$this->logout("");
			exit(0);
		}

		$openid = new SimpleOpenID;
		$openid->SetIdentity($_POST['id']);
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
			exit(0);
		}
		if (!$openid->GetOpenIDServer())
		{
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Can't get OpenidServer"));
			$this->logout("");
			return FALSE;
		}
		$openid->SetApprovedURL('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '?getOpenID=' . $_POST["id"]);
		$openid->Redirect();
		error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Redirection"));
		return ;
	}

	public function	getAuthPw() {
		$session_auth=$this->getAuthSession();
		$password = $session_auth->read('password');
		return $password;
	}

	public function	getAuthUser() {
		$session_auth=$this->getAuthSession();
		$username = $session_auth->read('username');
		return $username;
	}

	public function     logout($redir_uri) {
		$session = $this->getAuthSession();
		$session->close();
		if( $redir_uri == "" && array_key_exists('indexurl', $this->parms) ) {
			header('Location: '.$this->parms{'indexurl'});
		} else {
			header('Location: '.$redir_uri);
		}
	}

	public function	setSessionVar($name, $value) {
		$session_auth=$this->getAuthSession();
		$session_auth->register($name, $value);
		return $session_auth->read($name);
	}

	public function	getSessionVar($name) {
		$session_auth=$this->getAuthSession();
		return $session_auth->read($name);
	}

}

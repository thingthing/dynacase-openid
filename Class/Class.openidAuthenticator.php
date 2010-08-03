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
		include_once("OPENID/openid.class.php");

		if (isset($_GET['openid_mode'])) {
			if ($_GET['openid_mode'] == "cancel") {
				//User cancel the openid validation
				$redir_url = $this->parms{'authurl'} . '&openid_mode=cancel';
				error_log('redir url cancel === ' . $redir_url);
				header('Location: ' . $redir_url);
				exit();
			}
		}
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
			$redir_url = $this->parms{'authurl'} . '&openid_mode=notvalid';
			error_log('redir url === ' . $redir_url);
			header('Location: ' . $redir_url);
			$session->close();
			return FALSE;
		}
		//If openid provider is gmail, useridentity would be the return url so i put the email instead for more readability
		if (stripos($username, 'gmail') || stripos($username, 'google')) {
			$userinfo = $openid->filterUserInfo($_GET);
			if ($userinfo['email']) {
				$username = $userinfo['email'];
			}
		}
		$session->register('username', $username);
		$session->register("OPENID_AUTH", true);
		error_log('try to create user::'.$username);
		//check if user exist on openid database
		if (!$this->freedomUserExists($username)) {
			//user doesn't exist in freedom database try to create it
			if (!$this->tryInitializeUser($username)) {
				return false;
			}
		}
		$this->redirecturl();
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

		if (!isset($_POST["submit"]))
		{
			header('Location: ' . $this->parms{'authurl'});
			//include_once($this->parms{'authurl'});
			exit(0);
		}

		$openid = new SimpleOpenID($_POST['openid_identifier']);
		$openid->setTrustRoot('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$openid->setOptionalInfo(array('nickname',
				     'email',
				     'fullname',
				     'dob',
				     'gender',
				     'postcode',
				     'country',
				     'language',
				     'timezone'));
		if (!$openid->getOpenIDServer())
		{
			$error = $openid->GetError();
			error_log("ERROR CODE: " . $error['code']);
			error_log("ERROR DESCRIPTION: " . $error['description']);;
			$redir_url = $this->parms{'authurl'} . '&openid_mode=noserver';
			header('Location: ' . $redir_url);
			return FALSE;
		}
		$openid->setReturnURL('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$openid->redirect();
		return ;
	}

	/**
	 * Redirect to index
	 */
	public function redirecturl() {
		$url = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		error_log('redirect to ::'.$url);
		if (strpos($url, "getOpenID")!== FALSE) {
			$url = substr($url, 0, strpos($url, "getOpenID") - 1);
		}
		error_log('after change redirect to ::'.$url);
		header('Location: '. $url);
		exit(0);
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
		if( $redir_uri == "" && array_key_exists('authurl', $this->parms) ) {
			header('Location: ' . $this->parms{'authurl'});
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

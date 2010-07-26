<?php

include_once("WHAT/Class.Provider.php");

Class openidProvider extends Provider {

	public function validateCredential($username, $password) {
		return true;
	}


	public function validateAuthorization($opt) {
		return TRUE;
	}

	public function initializeUser($username) {
		@include_once('WHAT/Class.User.php');
		@include_once('FDL/Class.Doc.php');
		@include_once('WHAT/Class.Session.php');
		@include_once("OPENID/OpenID.class.php");

		global $action;
		$err = "";
		
		$CoreNull="";
		$core = new Application();
		$core->Set("CORE",$CoreNull);
		$core->session=new Session();
		$core->session->set();
		$action=new Action();
		$action->Set("",$core);
		$action->user=new User("",1); //create user as admin

		$openid = new SimpleOpenID;
		$openid->SetIdentity($username);
		$userinfo = $openid->filterUserInfo($_GET);
		$wu = new User();
		if ($userinfo['firstname']) {
			$wu->firstname= $userinfo['firstname'];
		}
		else {
			$wu->firstname='--';
		}
		if ($userinfo['fullname']) {
			$wu->lastname=$userinfo['fullname'];
		}
		else {
			$wu->lastname='--';
		}
		$wu->mail=$userinfo['email'];
		$wu->login=$username;
		$wu->password_new=uniqid("ldap");
		$wu->iddomain="0";
		$wu->famid="IUSER";

		$err=$wu->Add();
		if ($err != "") {
			$core->session->close();
			return sprintf(_("cannot create user %s: %s"),$username,$err);
		}

		include_once("FDL/Class.DocFam.php");
		$dbaccess=getParam("FREEDOM_DB");
		$du= new_doc($dbaccess,$wu->fid);
		if (!$du->isAlive()) {
			$err=$wu->delete();
			$core->session->close();
			return sprintf(_("cannot create user %s: %s"),$login,$err." (freedom)");
		}
		$du->setValue("us_whatid",$wu->id);
		$err = $du->modify();
		if( $err != "" ) {
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Error modifying user '%s' err=[%s]", $username, $err));
			$core->session->close();
			return $err;
		}
		error_log("Freedom user $username added (id=".$du->id.")");
		if ( $this->parms{'dGroup'}!='') {
			$gu = new_Doc($dbaccess, $this->parms{'dGroup'});
			if ($gu->isAlive()) {
				$errg=$gu->addFile($du->id);
				if ($errg=="") {
					error_log("User $username added to group ".$this->parms{'dGroup'});
				}
				else {
					error_log("Can't add user to group");
				}
			}
		}
		$err = $du->refresh();
		if( $err != "" ) {
			error_log(__CLASS__."::".__FUNCTION__." ".sprintf("Error refreshing user '%s' err=[%s]", $username, $err));
			$core->session->close();
			return $err;
		}
		$core->session->close();
		return $err;
	}
}
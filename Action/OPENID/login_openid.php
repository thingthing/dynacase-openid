<?php
include_once("WHAT/Class.openidAuthenticator.php");

function login_openid(Action &$action) {
	$action->parent->AddCssRef("OPENID:login_openid.css",true);
	//$action->parent->AddCssRef($action->GetLayoutFile("login_openid.css"),true); ==> layout statique
	$action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/resizeimg.js");
	$action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/geometry.js");

	if ($_GET['openid_mode'] && $_GET['openid_mode'] == 'noserver') {
		$action->lay->set('NOSERVER', true);
	}
	else {
		$action->lay->set('NOSERVER', false);
	}
	if (($_GET['openid_mode'] && $_GET['openid_mode'] == 'notvalid')) {
		$action->lay->set('NOTVALID', true);
	}
	else {
		$action->lay->set('NOTVALID', false);
	}
	if (($_GET['openid_mode'] && $_GET['openid_mode'] == 'cancel')) {
		$action->lay->set('CANCEL', true);
	}
	else {
		$action->lay->set('CANCEL', false);
	}
	$action->lay->set('ERR_MESSAGE_SERVER', 'No openid server found for this identity');
	$action->lay->set('ERR_MESSAGE_VALID', 'Not a valid openid');
	$action->lay->set('ERR_MESSAGE_CANCEL', 'Request canceled');
	$openid_providers = array();
	$authtype = getAuthType();
	$authproviderlist = getAuthProvider();
	$class = new openidAuthenticator($authtype, $authproviderlist);
	foreach ($class->parms{'providers'} as $k => $v) {
		$openid_providers[$k]['DATA'] = '{' . $v . '}' . ',';
	}
	$action->lay->setBlockData("PROVIDERS", $openid_providers);
}
?>

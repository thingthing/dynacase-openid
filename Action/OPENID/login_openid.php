<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OPENID
*/

include_once ("WHAT/Class.openidAuthenticator.php");

function login_openid(Action & $action)
{
    $action->parent->AddCssRef("AUTHENT:loginform.css", true);
    //$action->parent->AddCssRef($action->GetLayoutFile("login_openid.css"),true); ==> layout statique
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/resizeimg.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/geometry.js");
    
    if ($_GET['openid_mode'] && $_GET['openid_mode'] == 'noserver') {
        $action->lay->set('NOSERVER', true);
    } else {
        $action->lay->set('NOSERVER', false);
    }
    if (($_GET['openid_mode'] && $_GET['openid_mode'] == 'notvalid')) {
        $action->lay->set('NOTVALID', true);
    } else {
        $action->lay->set('NOTVALID', false);
    }
    if (($_GET['openid_mode'] && $_GET['openid_mode'] == 'cancel')) {
        $action->lay->set('CANCEL', true);
    } else {
        $action->lay->set('CANCEL', false);
    }
    if (($_GET['openid_mode'] && $_GET['openid_mode'] == 'noversion')) {
        $action->lay->set('NOVERSION', true);
    } else {
        $action->lay->set('NOVERSION', false);
    }
    $openid_providers = array();
    $authtype = getAuthType();
    $authproviderlist = getAuthProvider();
    $class = new openidAuthenticator($authtype, $authproviderlist);
    if (!$class->parms{'htmlauthurl'} || !$class->parms{'username'} || !$class->parms{'password'}) {
        $action->lay->set('HTMLINFO', false);
    } else {
        $action->lay->set('HTMLINFO', true);
        $action->lay->set('FREEDOMPROVIDER', $class->parms{'htmlauthurl'});
    }
    if (!$class->parms{'providers'}) {
        $action->lay->set('PROVIDER', false);
    } else {
        $action->lay->set('PROVIDER', true);
        foreach ($class->parms{'providers'} as $k => $v) {
            $openid_providers[$k]['DATA'] = '{' . $v . ',openid2: true}' . ',';
        }
        $action->lay->setBlockData("PROVIDERS", $openid_providers);
    }
}
?>

<!-- HEAD HTML -->

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<LINK REL="icon" HREF="CORE/Images/logo16.ico">
<LINK REL="SHORTCUT ICON" HREF="CORE/Images/logo16.ico">
<LINK rel="stylesheet" type="text/css" href="OPENID/Layout/openID.css">
<title>Openid freedom Authentification</title>
<LINK REL="stylesheet" type="text/css"
	HREF="?sole=A&freedom_param=762062d3924bbca18834a0c5cf8d6c3e&app=CORE&action=CORE_CSS">
<link rel="stylesheet" type="text/css"
	href="STYLE/DEFAULT/Layout/gen.css?wv=5782">
<link rel="stylesheet" type="text/css"
	href="WHAT/Layout/size-normal.css?wv=5782">
<link rel="stylesheet" type="text/css"
	href="?sole=Y&&app=CORE&action=CORE_CSS&session=762062d3924bbca18834a0c5cf8d6c3e&layout=AUTHENT:loginform.css">
<style type="text/css">
</style>
<script type="text/javascript" language="JavaScript"
	src="WHAT/Layout/logmsg.js?wv=5782"></script>
<script type="text/javascript" language="JavaScript"
	src="WHAT/Layout/resizeimg.js?wv=5782"></script>
<script type="text/javascript" language="JavaScript"
	src="WHAT/Layout/geometry.js?wv=5782"></script>

<script language="JavaScript">
  <!--
    var logmsg=new Array();
if ('displayLogMsg' in window) displayLogMsg(logmsg);

  //-->
   </script>
</head>

<body class="core">

<!-- END HEAD HTML -->

<div><script language="JavaScript">
<!--

var isNN = (navigator.appName.indexOf("Netscape") != -1);
if (isNN) {
  document.captureEvents(Event.KEYPRESS)
}
document.onkeypress = trackEnter

function trackEnter(evt)
{
  var intKeyCode;

  if (isNN)
    intKeyCode = evt.which;
  else
    intKeyCode = window.event.keyCode;

  if (intKeyCode == 13) { // enter key
    return false;
  } else
    return true;
}

function aumilieu(eid) {
   var winH=getFrameHeight();
   var winW=getFrameWidth();
   if (document.getElementById) { // DOM3 = IE5, NS6
   var divlogo = document.getElementById(eid);
   divlogo.style.position = 'absolute';
   if ((winH>0) && (winW>0)) {
     divlogo.style.top = (winH/2 - (divlogo.offsetHeight/2)+ document.body.scrollTop)+'px';
     divlogo.style.left = (winW/2 - (divlogo.offsetWidth/2))+'px';
   }
 }
 return true;
}

function display_help() {
  aumilieu('zonehelp');
  document.getElementById('zonehelp').style.visibility = 'visible';
  document.getElementById('zonehelp').style.zIndex = 100;
}
function close_help() {
  document.getElementById('zonehelp').style.visibility = 'hidden';
}
function centerZone() {
aumilieu('authform');
aumilieu('zonehelp');
}

-->

</script>

<div id="zonehelp" class="zhelp">
<div class="title">Aide &agrave; la connexion</div>
<div class="content">Cette fen&ecirc;tre permet de vous identifier,
c'est &agrave; dire indiquer aux applications qui vous &ecirc;tes. <br />
<br />
Ceci est n&eacute;cessaire pour garantir que les informations vous
concernant sont accessibles seulement par vous-m&ecirc;me. Pour vous
identifier, il vous faut saisir un identifiant openid. (si vous n'en
poss&eacute;dez pas vous pouvez en cr&eacute;er un sur
https://www.myopenid.com/signup). Vous serez ensuite redirig&eacute;
vers votre provider openid.<br />
<br />
Il est important de respecter la casse (utilisation des
caract&egrave;res majuscule et minuscule) lors de la saisie de votre
identifiant<br />
</div>
<div class="close"><a href="javascript:close_help()">fermer l'aide</a></div>
</div>

<form method="post" onsubmit="this.login.disabled=true;"><input
	type="hidden" name="openid_action" value="login" />
<div id="authform" class="authmain">

<div class="form">

<div class="banner">
<table cellspacing="0">
	<tr>
		<td><img width="48px" src="CORE/Images/logo-mini.png" needresize="1" /></td>
		<td><span class="societe">Zoo</span><br>
		Openid Connection</td>
	</tr>
</table>
</div>

<div class="zoneinput">
<table cellspacing="0">
	<tr>
		<td class="label">Username</td>
		<td class="input"><input type="text" name="openid_identifier"
			id="openid_identifier" /></td>
		<td class="input"><input type="submit" name="openidSubmit"
			value="login &gt;&gt;" /></td>
	</tr>
</table>
</div>
<?php if ($_GET['openid_mode'] && $_GET['openid_mode'] == 'cancel') {?>
<div id="msgerr" class="message">Validation Cancel</div>
	<?php }
	elseif (($_GET['openid_mode'] && $_GET['openid_mode'] == 'notvalid')) {?>
<div id="msgerr" class="message">Not a valid openid</div>
		<?php }?>
<div class="buttons"><a href="javascript:display_help()">help</a></div>

</div>

</div>

</form>
<!-- BEGIN ID SELECTOR Doesn't work--> <script type="text/javascript"
	id="__openidselector" src="OPENID/script.php" charset="utf-8"></script>
<!-- END ID SELECTOR --> <script>
addEvent(window,'load',centerZone);
addEvent(window,'resize',centerZone);
document.getElementById('zonehelp').style.visibility = 'hidden';

</script> <!--  FOOT TABLE HTML --></div>

<!-- END FOOT TABLE HTML -->


<!--  FOOT HTML -->

</body>
</html>

<!-- END FOOT HTML -->

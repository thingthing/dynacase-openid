
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>OpenID Example</title>
</head>
<body>
<div>
<fieldset id="openid"><legend>OpenID Login</legend>
<form method="post" onsubmit="this.login.disabled=true;">
<input type="hidden" name="openid_action" value="login"/>
<div>
<input type="text" name="openid_identifier" id="openid_identifier"/>
<input type="submit" name="openidSubmit" value="login &gt;&gt;"/> 
</div>
</form>
<!-- BEGIN ID SELECTOR Doesn't work-->
<script type="text/javascript" id="__openidselector" src="OPENID/script.php" charset="utf-8"></script>
<!-- END ID SELECTOR -->
</fieldset>
</div>
</body>
</html>



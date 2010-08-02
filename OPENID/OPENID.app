<?php

global $app_desc,$action_desc;

$app_desc = array (
"name"			=>"OPENID",		//Name
"short_name"	=>"Openid",		//Short name
"description"	=>"Openid Authentification Application",	//long description
"access_free"	=>"Y",			//Access free ? (Y,N)
"displayable"	=>"N",			//Should be displayed on an app list (Y,N)
);

$action_desc = array (
  array( 
   "name"		=>"LOGIN_OPENID",
   "short_name"	=>"login",
   "root"		=>"Y"
  ) ,
);
?>
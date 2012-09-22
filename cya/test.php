<?php
/* $Id: index.php,v 1.2 2007/01/24 16:47:29 dmenconi Exp $ */
include_once("config.php");
include_once("../library/debug.php");
include_once("../library/loc_login.php");

$PARAMS = array_merge($_POST,$_GET);	
$link = make_mysql_connect($dbhost,$dbuser,$dbpass,$dbname);
$login = loc_GetAuthenticated($PARAMS['username'],$PARAMS['password'],$link,$appword,$cookiename="login",$admin=false,$expiry=0,$title="Design Application",$color='#F0F0c0',$mode="login","index.php",false);
if(!login) exit();

debug_array("PARAMS",$PARAMS);
$username = loc_get_username();
debug_string("username",$username);
$u= loc_get_username();
debug_string("u",$u);


exit();

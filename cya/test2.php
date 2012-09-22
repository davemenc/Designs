<?php
include_once("config.php");
include_once("../library/miscfunc.php");
include_once("../library/debug.php");
include_once("../library/loc_login.php");
include_once("../library/mysql.php");
include_once("../library/htmlfuncs.php");
//debug_on();
      // This string is updated by the source control system and
     // used to track changes.
$data = "cookiedata";
$cookexp = 0;
$cookiename = "test";
debug_array("COOKIE",$_COOKIE);
$cookstr = $_COOKIE[$cookiename];
debug_string("cookstr1",$cookstr);
if ($cookstr=""){
	debug_string("no cookie; add it");
	setcookie($cookiename,$data,$cookexp);
debug_string("I added it");
debug_string("cookiename",$cookiename);
debug_string("data",$data);
debug_String("cookexp",$cookexp);
}
$cookstr = $_COOKIE[$cookiename];
debug_string("cookstr2",$cookstr);

?>


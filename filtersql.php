<?php
/******************************************************************
 * FilterSQL
   ******************************************************************/
function FilterSQL($s){
//	$s = strip_tags($s);
	$s = addslashes($s);
	return $s;
}
?>
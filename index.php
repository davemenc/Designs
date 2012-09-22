<?php
/* $Id: index.php,v 1.29 2009/05/22 04:53:42 dmenconi Exp $ */
/****************************************
 * index.php -- Design Application
 *****************************************/
include_once("config.php");
include_once("../library/miscfunc.php");
include_once("../library/debug.php");
include_once("../library/loc_login.php");
include_once("../library/mysql.php");
include_once("../library/htmlfuncs.php");
include_once("../library/date.php");
include_once("../library/list.php");
include_once("../library/filtersql.php");
include_once("view_formal_design.php");

debug_string("******************************* Design App Index.php ******");

log_on();
      // This string is updated by the source control system and
     // used to track changes.
$rcsversion = "$Id: index.php,v 1.29 2009/05/22 04:53:42 dmenconi Exp $";
$rcsversion = str_replace("$","",$rcsversion);
$rcsversion = substr($rcsversion,strpos($rcsversion,",v")+2);
$localversion = $version . "<br>RCSversion: " . $rcsversion;

// combine post and get
//$PARAMS = array_merge($_POST,$_GET);	
$PARAMS = FilterSQL(array_merge($_POST,$_GET));
// connect to database
$link = make_mysql_connect($dbhost,$dbuser,$dbpass,$dbname);

// login (or ensure that we are already logged in)
$login = loc_GetAuthenticated($PARAMS,$link,$appword,$cookiename="login",$admin=false,$expiry=0,$title="Design Application",$color='#F0F0c0',"index.php",false);
if(!login) {
	debug_string("can't log in; exiting");
	 break_mysql_connect($link);
	exit();
}
if(loc_get_userno($link)==-1) JumpTo();
$username = $PARAMS['username'];
//turn on debugging AFTER login to avoid headers
//debug_on();
debug_string("did loc_GetAuth, login",$login);
debug_string("localversion",$localversion);
debug_string("lastmodified", $lastmodified);
debug_array("PARAMS",$PARAMS);

// figure out sortorder
$sortorder = "Date";
if (isset($PARAMS['sortby'])) $sortorder = $PARAMS['sortby'];
debug_string("sortorder",$sortorder);

// figure out mode and design number
if (!isset($PARAMS['mode'])){
	$mode = "display_directory";
	//Display_Directory();
} else{
	$mode=$PARAMS['mode'];
	$designno = $PARAMS['designno'];
}
debug_string("MODE",$mode);
debug_string("Design #", $designno);

	switch($mode){
		case "admin":
			Display_Admin_Form($localversion,$lastmodified);
			break;
		case "edit_design"://display the form for editing a design
			//debug_string("Mode=edit_design");
			Display_Design_Form($designno);
			break;
        case "parseAddPrivs":
			//debug_string("parseAddPrivs");
            parseAddPrivs();
			Display_Admin_Form($localversion,$lastmodified);
            break;
        case "parseDelPrivs":
            //debug_string("parseDelPrivs" );
            parseDelPrivs();
			Display_Admin_Form($localversion,$lastmodified);
            break;
		case "parse_design":
			//debug_string("mode=parse_design");
			Parse_Design($designno);
		    Display_Directory();
			//Display_Design_Form($designno);
			break;
		case "add_subsection":
			//debug_string("mode=add_subsection");
			//debug_string("design no",$designno);
			Parse_Add_SubSection($designno);
			Display_Formal_Form($designno);
			break;				 
		case "add_section":
			//debug_string("mode=add_section");
			//debug_string("design no",$designno);
			Parse_Add_Section($designno);
			Display_Formal_Form($designno);
			break;				 
		case "informal":
			//debug_string("mode=informal");
			//debug_string("design no",$designno);
			if ($PARAMS['order']=="forward") $order=False;
			else $order=True;
			Display_Informal_Form($designno,$order);
			break;
		case "informal_parse":
			if ($PARAMS['order']=="forward") $order=False;
			else $order=True;
			Parse_Informal_Form();
			Display_Informal_Form($designno,$order);
			break;
		case "informal_edit":
			$noteid=$PARAMS['editradio'];
			Display_Informal_Edit_Form($designno,$noteid);
			break;
		case "informal_edit_parse":
			if ($PARAMS['order']=="forward") $order=False;
			else $order=True;
			Parse_Informal_Edit_Form($designno);
			Display_Informal_Form($designno,$order);
			break;
		case "formaledit":
			Display_Formal_Form($designno);
			break;
		case "formal_parse":
			Parse_Formal_Form();
			Display_Formal_Form($designno);
			break;
		case "displayformal":
			CanISeeThis($designno);
			View_Formal_Design($designno);
			break;
		case "newdesign":
			Display_NewDesign_Form();
			break;
		case "newdesign_parse":
			$designno=Parse_NewDesign_Form();
			Display_Directory();
			break;
		case "renumber":
			RenumberSections($designno);
			Display_Formal_Form($designno);
			break;
		case "parse_renumber":
			debug_string("parse_renumber");
			$section = substr($PARAMS['section'],1);
			debug_string("designno",$designno);
			debug_string("section",$section);
            RenumberSubSections($designno,$section);
            Display_Formal_Form($designno);
            break;
		case "test":
			RenumberSubSections(66,665);
			Display_Formal_Form(66);
			break;
		case "display_directory":
		default: 
		    Display_Directory();
	}//switch
break_mysql_connect($link);
exit();
/***************
 * FUNCTIONS
 ****************/
function CanISeeThis($designno){
	global $link,$appnum,$adminusers;
	//debug_string("CanISeeThis");
	$username = loc_get_username();
	if (in_array ( $username, $adminusers)){//this guy's an admin!
		return;// so we skip all this nonsense -- admins can do everything
	}

	$data = MYSQLComplexSelect($link,array("*"),array("des_priv"),array("designno='".$designno."'","username='".$username."'"),array(),0);
	//debug_array("priv list",$data);
	$count = count($data);
	//debug_string("count",$count);
	if ($count<1){//
		Display_NotAuthorized($username);
		exit();
	}
	// if we get here, we're good
}
function Display_NotAuthorized($username){
	global $link,$lastmodified,$localversion,$appnum;
	//debug_string("NotAuthorized");
	Display_Generic_Header("Design Directory","#ff2020","","http://menconi.com/designs/light-bulb.ico");
	print "<h1>NOT AUTHORIZED!</h1>\n";
	print "<p><b>The user $username is not authorized to perform this function. </b></p>\n";
	print "<p>Click here to <a href=\"index.php\"> return to the WikiDesigner Directory</a>.";
	//Display_Generic_Footer($localversion,$lastmodified);
    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
	break_mysql_connect($link);
	exit();
}
function Display_Directory($sortby="date"){
	global $link,$lastmodified,$localversion,$appnum,$adminusers,$username,$sortorder;
	if (!isset($username)){
		$username = loc_get_username();
	}
	debug_string("Display_Directory()");
	debug_string("username ",$username);
	debug_string("loc_get_username",loc_get_username());
	debug_string("Display_Directory: username", $username);
	switch ($sortorder){
		case "Catno":
			$order = "des_category.Name,DesignName";
			break;
		case "Date":
			$order = "lastchange desc, DesignName";
			break;
		case "Designer":
			$order = "Designer,DesignName";
			break;
		case "DesignName":
			$order = "DesignName";
			break;
		default: 
			$order = "des_category.Name,DesignName";
	}
	if (in_array ( $username, $adminusers)){
		$sql = "select des_design.* ,greatest(max(NotesTS),max(sectionTS)) as lastchange,des_category.name as catname from des_design,des_notes,des_category,des_sections where designid=des_sections.designno and categoryid=catno and designid=des_notes.designno and des_design.Hidden='0' group by DesignID order by $order";
	}else{
		$sql = "select des_design.* ,greatest(max(NotesTS),max(sectionTS)) as lastchange,des_category.name as catname  from des_design,des_notes,des_category,des_sections,des_priv where DesignID=des_priv.designno and designid=des_sections.designno and categoryid=catno and designid=des_notes.designno and des_design.Hidden='0' and username='$username' group by DesignID order by $order";
	}
	debug_string("Sql", $sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	while ($designs[] = mysql_fetch_assoc($result)){;}
	array_pop($designs);

	Display_Generic_Header("Design Directory","#ffe0ff","","http://menconi.com/designs/light-bulb.ico");
	print "<h1>Project Design Directory</h1>\n";
debug_string("Sortorder",$sortorder);
	$select[$sortorder] = "Selected";
debug_array("select",$select);
	print "<form action=\"index.php\" method=get>";
	print "Sort By: <select name=\"sortby\" >";
	print "<option ".$select['Catno']. " value=\"Catno\" label=\"Catno\">Category</option>";
	print "<option ".$select['Date']. "  value=\"Date\" label=\"Date\">Date</option>";
	print "<option ".$select['Designer']. "  value=\"Designer\" label=\"Designer\">Designer</option>";
	print "<option ".$select['DesignName']. "  value=\"DesignName\" label=\"DesignName\">Title</option>";
	print "</select>";
	print "<input type=\"submit\" value=\"Sort\">";
	print "</form>";

	print "<br>username: $username<br>\n";
	print "<small><i>Click on name to edit design information.</i></small><br><br>\n";
	print "<table >\n";
	print "<td><b>DesignName </td><td><b>Links</b></td><td><b>Category&nbsp;&nbsp;</td><td><b>Designer </td><td><b>Date </td><td><b>Description</td>\n";

	foreach($designs as $design){
		print "<tr>\n";
		print '<td valign=top ><a href="index.php?mode=edit_design&designno=' . $design['DesignID'] . '"><font size=+1>' . $design['DesignName'] .'</font></a></td>'."\n";
		print '<td valign=top>  <a href="index.php?mode=informal&designno=' . $design['DesignID'] . '"><font size=-2>Note</font></a>&nbsp;'.' <a href="index.php?mode=formaledit&designno=' . $design['DesignID'] . '"><font size=-2>Edit</font></a>&nbsp; '.' <a href="index.php?mode=displayformal&designno=' . $design['DesignID'] . '"><font size=-2>Formal</font></a>'.'&nbsp;&nbsp;&nbsp;&nbsp;</td>'."\n";
		print '<td valign=top >' . $design['catname'] . '</td> '."\n";
		print '<td valign=top>' . $design['Designer'] . '</td>'."\n";
		print '<td valign=top>' . std_date($design['lastchange']) . '</td> '."\n";
		print '<td valign=top>&nbsp;&nbsp;&nbsp;' . $design['Description'] . '</td> '."\n";
		print "</tr>\n";
	}
	print "</table>\n";
echo <<<EOF
<hr>
<h2>Add A New Design</h2>
<form action="index.php" method=post>
<input type=hidden name="mode" value="newdesign_parse">
  
<table>
	<tr >
		<td valign=top>
				<b>DesignName:  </b>
		</td>
		<td valign=top>
				<b>Designer</b>
		</td>
		<td valign=top>
				<b>Description</b>
		</td>
		<td>
			&nbsp;
		</td>
	</tr>
	<tr >
		<td valign=top>
			<input type=text name="DesignName" size=20>
		</td>
		<td valign=top>
			<input type=text name="Designer" size=25>
		</td>
		<td valign=top>
			<input type=text name="Description" size=55>
		</td>
		<td valign=top>
			<input type="submit" value="Add New Design"> 
		</td>
	</tr>
</table>
</form>
EOF;
    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function Parse_Informal_Form(){
	global $link,$PARAMS;
	CanISeeThis($PARAMS['designno']);
	//debug_string("Parse_Informal_Form()");
	//debug_string("designno ", $PARAMS['designno']);
	//debug_string("Author", $PARAMS['Author']);
	//debug_string("Note",$PARAMS['Note'] );
	$date=date("Y-m-d");
	$sql="insert into des_notes  (DesignNo,Author,Note,Date) values ('".$PARAMS['designno'] . "','".$PARAMS['Author'] . "','" . $PARAMS['Note'] . "','".$date."')";
//	debug_string("SQL insert",$sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
}
 
function Display_Informal_Edit_Form($designno,$noteid){
	global $link,$lastmodified,$localversion,$appnum,$adminusers;
	//debug_string("Display_Informal_Edit_Form($designno,$noteid)");
	CanISeeThis($designno);
//	$sql="select * from des_design where DesignID=$designno and NoteId=$noteid and Hidden=0" ;
	$sql = "select * from des_design,des_notes where DesignID=DesignNo and NoteID=$noteid";

	//debug_string("display informal design Sql", $sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	while ($notes[] = mysql_fetch_assoc($result)){;}
	array_pop($notes);
	
	//debug_array("notes Data",$notes);
	$note = $notes[0];
	$author = $note['Author'];
	$text = $note['Note'];
	$date = $note['Date'];


echo <<<EOF
<form action="index.php" method=post>
<input type=hidden name="mode" value="informal_edit_parse">
<input type=hidden name="designno" value="$designno">
<input type=hidden name="noteid" value="$noteid">
<table border=0><tr >
<td valign="top">
<b>Author:  </b>

</td>
<td valign="top">
<b>Note:</b>
</td>
</tr>
<tr >
<td valign="top">
<input type=text name="Author" size=15 value="$author">
</td>
<td rowspan="17" valign="top">
<textarea wrap name="Note" rows="16" cols="80" >
$text
</textarea>
</td>
<td valign="top">
<input type="submit" value="Save Note">
</td>

</tr>
<tr> <td valign="top"> &nbsp;</td> </tr>
<tr> <td valign="top"> &nbsp;</td> </tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
<tr><td>&nbsp;</td><tr>
</table>
</form>

EOF;
}
function Parse_Informal_Edit_Form($designno){
	global $link,$PARAMS;
	//debug_array("Params",$PARAMS);
	//debug_string("Parse_Informal_Edit_Form($designno)");
	CanISeeThis($designno);
	$author = $PARAMS['Author'];
	$note = $PARAMS['Note'];
	$date = $PARAMS['Date'];
	$noteid = $PARAMS['noteid'];
	//debug_string("noteid",$noteid);
	//debug_string("note",$note);
	//debug_string("author",$author);
	$sql="update des_notes set Author='$author',Note='$note' where NoteID=$noteid";
	//debug_string("SQL insert",$sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
}
function Display_Informal_Form($designno,$backwardorder=True){
	global $link,$localversion,$lastmodified;
	CanISeeThis($designno);
	//debug_string ("Display_Informal_Form()");
	//debug_string ("designno",$designno);
	$sql="select * from des_design where DesignID=$designno and Hidden=0" ;
	//debug_string("display informal design Sql", $sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	$design = mysql_fetch_assoc($result);
	//debug_array("Design Data",$design);
	
	if($backwardorder)$order="desc";
	else $order="asc";
	$sql = "select * from des_notes where DesignNo=$designno order by Date $order, NoteID $order";
	//debug_string("display informal notes Sql", $sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	while($notes[] = mysql_fetch_assoc($result)){;}
	//debug_array("Notes Data",$notes);
	array_pop($notes);
	$notescount = count($notes);	
	Display_Generic_Header("Informal Design Form","#deffff","","http://menconi.com/designs/light-bulb.ico");
	$designname=$design['DesignName'];
	$designer = $design['Designer'];
	//$designdate=$design['Date'];
	$designdate = std_date($design['Date']);
	//debug_string("designdate",$designdate);
	$designdesc=$design['Description'];
echo <<<EOF
<center><font size=+1><B>$designname</b></font><br> $designdesc</center>
<center><i>$designdate</i>  </center>
<center><i>By $designer</i></center><br>

<center><a href=index.php>| Back to Design Directory</a> | <a href="index.php?mode=informal&designno=$designno&order=forward">Display Cronologically</a> | <a href="index.php?mode=informal&designno=$designno">Display Reverse Cronologically</a> |</center><br><br><br><br>
<form action="index.php" method=post>
<input type=hidden name="mode" value="informal_parse">
<input type=hidden name="designno" value="$designno">
<table><tr >
<td valign=top>
<b>Author:  </b>
</td>
<td valign=top>
<b>Note:</b>
</td>
</tr>
<tr >
<td valign=top>
<input type=text name="Author" size=15 value="$designer">
</td>
<td valign=top>
<textarea wrap name="Note" rows="6" cols="80" ></textarea>
</td>
<td valign=top>
<input type="submit" value="Add Note"> 
</td>
</tr>
</table>
</form>


EOF;
	if ($notescount>0){
		print "<form action=\"index.php\" method=post>\n";
		print "<input type=hidden name=\"mode\" value=\"informal_edit\">\n";
		print "<input type=hidden name=\"designno\" value=\"$designno\">\n";
		print "<input type=\"submit\" value=\"Edit Note\"> \n";
		Display_Existing_Notes($notes);
		print "<input type=\"submit\" value=\"Edit Note\"> \n";
		print "</form>\n";
	}

    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function Display_Existing_Notes($notes){
	//debug_string("Display_Existing_Notes()");
	//debug_array("notes",$notes);
	foreach($notes as $note){
		$id=$note['NoteID'];
		//print "<p><b>".std_date($note['NotesTS']). "  ". $note['Author']."</b>";
		print "<p><b>".std_date($note['Date']). "  ". $note['Author']."</b>";
		print " &nbsp;(Edit:  <input type =\"radio\" name=\"editradio\" value=\"$id\"/>)<br> ";
		//print "(".$note['NoteID'].", ".$note['DesignNo'].")<br>";
		$text= wordwrap(Transform_Text_To_HTML($note['Note']),80,"\n");

		print "<pre>\n".$text."\n</pre>\n";
	}
}		
function Transform_Text_To_HTML($str){
	//debug_string("Transform_Text_To_HTML");
	//debug_string("str",$str);
	//$return = htmlentities($str);
	//$return = nl2br($str);
	//$return = "<br>&nbsp;&nbsp;&nbsp;&nbsp;".$str;
	//$return = htmlentities($return);
//	$return = nl2br($return);
	$return = $str;
	//$return = str_replace("\n","<br/>&nbsp;&nbsp;&nbsp;&nbsp;",$return);
	return $return;
}
//Array ( [mode] => add_section [designno] => 13 [title] => 2nd test [section] => S129 )

function Parse_Add_SubSection($designno){
	global $link,$PARAMS;
	CanISeeThis($designno);
	//debug_string("Parse_Add_SubSection($designno)");
	// just a few facts about this SubSection
	$title = $PARAMS['title'];
	$sectioncode = $PARAMS['section'];
	//debug_string("sectioncode",$sectioncode);
	$sectionno = substr($sectioncode,1,strlen($sectioncode)-1);
	//debug_string("section",$section);

	// get the maximum existing seq number for subsections in this section
	$sql=" select Seq from des_sections where DesignNo='$designno' and SubSection=$sectionno order by Seq desc limit 1";
	//debug_string("sql",$sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	$rec = mysql_fetch_row($result);
	//debug_array("rec",$rec);
	//debug_string("rec[0]",$rec[0]);
	$newseq = $rec[0]+10;// new one is always 10 higher than the highest
	//debug_string("newseq",$newseq);

	$sqlinsert="insert into des_sections (DesignNo,SectionTitle,SubSection,Seq,SectionTS) values ('$designno','$title','$sectionno','$newseq',NULL)";
	//debug_string("sqlinsert",$sqlinsert);
	$result = mysql_query($sqlinsert,$link) or die(mysql_error());
} 
function Parse_Add_Section($designno){
	global $link,$PARAMS;
	CanISeeThis($designno);
	//debug_string("Parse_Add_Section($designno)");
	
	// get the maximum existing seq number for this design
	$sql="select Seq from des_sections where DesignNo=$designno order by Seq desc limit 1";
	//debug_string("sql",$sql);
	$result = mysql_query($sql,$link) or die(mysql_error());
	$rec = mysql_fetch_row($result);
	//debug_array("rec",$rec);
	//debug_string("rec[0]",$rec[0]);
	$newseq = $rec[0]+10;// new one is always 10 higher than the highest

	// insert the record
	$title = $PARAMS['title'];
	$sqlinsert="insert into des_sections (DesignNo,SectionTitle,SubSection,Seq,SectionTS) values ('$designno','$title',0,'$newseq',NULL)";
	$result = mysql_query($sqlinsert,$link) or die(mysql_error());
} 
function Display_Formal_Form($designno){
	global $link,$PARAMS,$localversion,$lastmodified;
	CanISeeThis($designno);
	$oldflag=debug_on();
	//debug_string ("Display_Formal_Form ");
	
	$designs = MYSQLComplexSelect($link,array("*"),array("des_design"),array("DesignID='".$designno."'","Hidden='0'"),array());	
	$design = $designs[0];

	$sections = MYSQLComplexSelect($link,array("*"),array("des_sections"),array("DesignNo='".$designno."'","SubSection=0"),array("Seq"),0);	


	//debug_array("sections",$sections);
	//debug_array("designs",$designs);
	Display_Generic_Header("Edit Formal Design",$color="#FFccFF","","http://menconi.com/designs/light-bulb.ico");

//debug_string("name",$design['DesignName']);
	print "<a name=\"TOP\"><h2>".$design['DesignName']."</h2></a>\n";	
	print "<a href=\"index.php?mode=displayformal&designno=$designno\"><i>View Formal Design</i></a> | \n";
	print "<a href=\"index.php\"><i>Return to Directory</i></a> | \n";
	print "<a href=\"index.php?mode=renumber&designno=$designno\"><i>Renumber Sections</i></a> | \n";
	print "<a href=\"index.php?mode=informal&designno=$designno\"><i>Go To Notes Page</a> | \n";
	print "<a href=\"#ADDSECTION\"><i>Jump To Add Section</i></a><br>\n";

	print "<form action=\"index.php\" method=post>\n";
	print "<br><input type=\"submit\" value=\"Submit Formal Design\"> \n";
	foreach ($sections as $section){
		$sectionno=$section['SectionID'];
		$subsections = MYSQLComplexSelect($link,array("*"),array("des_sections"),array("DesignNo='".$designno."'","SubSection='".$sectionno."'"),array("Seq"),0);	
	//debug_array("<br>section",$section);
		//print "<h3>".$section['SectionTitle']."</h3>\n";
		print "<br><input type=text name=\"T".$section['SectionID']."\" size=50 value=\"".$section['SectionTitle']."\">\n";
		if (1==$section['Hidden']) $checked=" checked "; else $checked="";
		print "&nbsp;&nbsp;&nbsp;Hide This Section?  <input type=checkbox $checked name=\"H".$section['SectionID']."\" value=\"H".$section['SectionID']."\" \>\n";

		$menu = CreateSectionMenu($sections,"M".$section['SectionID'],"<option value=\"M-1\" label=\"nomove\">Don't Move</option>	<option value=\"M0\" label=\"totop\">To Top</option>");
		print " &nbsp;&nbsp;Move To: $menu";
		print "&nbsp;&nbsp;&nbsp;Sequence: <input type=text name=\"S".$section['SectionID']."\" value=\"".$section['Seq']."\" size=5\>\n";
		print "&nbsp;&nbsp;&nbsp;Delete This Section?  <input type=checkbox name=\"D".$section['SectionID']."\" value=\"D".$section['SectionID']."\" \>\n";
		print "<br><textarea wrap name=\"N".$section['SectionID']."\" rows=\"14\" cols=\"140\" >".$section['SectionText']."</textarea>\n";
		print "<br>\n";
		foreach($subsections as $subsection){
			print "<br>";
			print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text name=\"T".$subsection['SectionID']."\" size=50 value=\"".$subsection['SectionTitle']."\">\n";

			if (1==$subsection['Hidden']) $checked=" checked "; else $checked="";
			print "&nbsp;&nbsp;&nbsp;Hide This SubSection?  <input type=checkbox $checked name=\"H".$subsection['SectionID']."\" value=\"H".$subsection['SectionID']."\" \>\n";
            $menu = CreateSectionMenu($sections,"M".$subsection['SectionID'],"<option value=\"M-1\" label=\"nomove\">Don't Move</option>    <option value=\"M0\" label=\"totop\">To Top</option>");
            print " &nbsp;&nbsp;Move To: $menu";

			print "&nbsp;&nbsp;&nbsp;Sequence: <input type=text name=\"S".$subsection['SectionID']."\" value=\"".$subsection['Seq']."\" size=5\>\n";
			print "&nbsp;&nbsp;&nbsp;Delete This Subsection?  <input type=checkbox name=\"D".$subsection['SectionID']."\" value=\"D".$subsection['SectionID']."\" \>\n";
			print "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea wrap name=\"N".$subsection['SectionID']."\" rows=\"14\" cols=\"134\" >".$subsection['SectionText']."</textarea>\n";
			print "<br>\n";
		}
	}
	print "<input type=hidden name=\"mode\" value=\"formal_parse\">\n";
	print "<br><input type=\"submit\" value=\"Submit Formal Design\"> \n";
	print "<input type=hidden name=\"designno\" value=\"$designno\">\n";
	print "</form>\n";
	//add form for adding sections
	print "<hr/>\n";
	print "<a href=\"#TOP\"><i>Jump To Top</i></a><br>\n";
	print "<h2><a name=\"ADDSECTION\">Add Section</a></h2>\n";
	print "<form action=\"index.php\" method=get>\n";
	print "<input type=hidden name=\"mode\" value=\"add_section\">\n";
	print "<input type=hidden name=\"designno\" value=\"$designno\">\n";
	print "Title: <input type=text name=\"title\" size=50>\n";
	print "<br><input type=\"submit\" value=\"Add New Section\"> \n";
	print "</form>\n";
	//add form for subsections
	print "<hr/>\n";
	print "<h2>Add Subsection</h2>\n";
	print "<form action=\"index.php\" method=get>\n";
	print "<input type=hidden name=\"mode\" value=\"add_subsection\">\n";
	print "<input type=hidden name=\"designno\" value=\"$designno\">\n";
	print "Title: <input type=text name=\"title\" size=50>&nbsp;&nbsp;&nbsp;&nbsp;\n";
	$menu = CreateSectionMenu($sections,"section");
	print "To Section: $menu";
	/*
	print "To Section: <select name=\"section\" >\n";
	foreach($sections as $section){
		print "<option value=\"S".$section['SectionID']."\" label=\"".$section['SectionTitle']."\">";
		print $section['SectionTitle']."</option>\n";
	}
	print "</select>";
	*/
	print "<br><input type=\"submit\" value=\"Add New Subsection\"> \n";
	print "</form>\n";

    //add form for renumbering sub sections
    print "<hr/>\n";
    print "<h2>Renumber Subsections</h2>\n";
    print "<form action=\"index.php\" method=get>\n";
    print "<input type=hidden name=\"mode\" value=\"parse_renumber\">\n";
    print "<input type=hidden name=\"designno\" value=\"$designno\">\n";

	$menu = CreateSectionMenu($sections,"section");
	print "Section: $menu";
    /*
    print "Section: <select name=\"section\" >\n";
    foreach($sections as $section){
        print "<option value=\"S".$section['SectionID']."\" label=\"".$section['SectionTitle']."\">";
        print $section['SectionTitle']."</option>\n";
    }
    print "</select>";
    */
    print "<input type=\"submit\" value=\"Renumber Section\"> \n";
    print "</form>\n";

	Print "<hr>\n";

    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function Parse_Formal_Form(){
	global $link,$PARAMS;
	debug_string ("Parse_Formal_Form ");
	debug_array("params",$PARAMS);
	$sectiontitle = $PARAMS['title'];

	$designno=$PARAMS['designno'];
	CanISeeThis($designno);
	//debug_string("designno",$designno);
	$deletesections=array();
	foreach ($PARAMS as $key=>$value){
		debug_string("key",$key);
		debug_string("value",$value);
		if(substr($key,0,1)=="N"){//value is the body of the section
			$sectionno = substr($key,1,strlen($key)-1);
			debug_string("update text of",$sectionno);
			$sqlupdate="update des_sections set SectionText='".$value."' where SectionID='".$sectionno."' and DesignNo='".$designno."'";
			debug_string("sqlupdate",$sqlupdate);
			$result = mysql_query($sqlupdate,$link) or die(mysql_error());
		}else if (substr($key,0,1)=="H"){//we should hide this section(!)
			$hidesections[] = substr($key,1);
		}else if (substr($key,0,1)=="D"){//we should delete this section(!)
			$deletesections[] = substr($key,1);
		}else if (substr($key,0,1)=="S"){//value is the seq of the section
			$sectionno = substr($key,1,strlen($key)-1);
			//debug_string("update seq of",$sectionno);
			$sqlupdate="update des_sections set Seq='".$value."' where SectionID='".$sectionno."' and DesignNo='".$designno."'";
			//debug_string("sqlupdate",$sqlupdate);
			$result = mysql_query($sqlupdate,$link) or die(mysql_error());
		}else if (substr($key,0,1)=="M"){//value is the seq of the section
			$sectionno = substr($key,1);
			$descsecno = substr($value,1);
			if ($descsecno>=0){
				debug_string("update seq of",$sectionno);
				debug_string("move to section",$descsecno);
				$sqlmove="update des_sections set SubSection='".$descsecno."' where SectionID='".$sectionno."' and DesignNo='".$designno."'";
				debug_string("sqlmove",$sqlmove);
				$result = mysql_query($sqlmove,$link) or die(mysql_error());
			}
		}else if (substr($key,0,1)=="T"){//value is the title of the section
			$sectionno = substr($key,1,strlen($key)-1);
			//debug_string("update title of",$sectionno);
			$sqlupdate="update des_sections set SectionTitle='".$value."' where SectionID='".$sectionno."' and DesignNo='".$designno."'";
			//debug_string("sqlupdate",$sqlupdate);
			$result = mysql_query($sqlupdate,$link) or die(mysql_error());
		}	
	}
	// now $hidesections is a list of section numbers that we need to hide
	$sqlunhide= "update des_sections set hidden=0 where DesignNo='".$designno."'";
	debug_string("Sqlunhide",$sqlunhide);
	$result = mysql_query($sqlunhide,$link) or die(mysql_error());
	debug_array("hidesections",$hidesections);
	if(count($hidesections)>0){
		foreach($hidesections as $section){
			$sqlhide = "update des_sections set hidden=1 where SectionID='".$section."' and DesignNo='".$designno."'";
			debug_string("sqlhide",$sqlhide);
			$result = mysql_query($sqlhide,$link) or die(mysql_error());
		}
	}
	// now $deletesections is a list of section numbers that we need to delete
	debug_array("deletesections",$deletesections);
	if(count($deletesections)>0){
		foreach($deletesections as $section){
			$sqldelete = "delete from des_sections where SectionID='".$section."' and DesignNo='".$designno."'";
			debug_string("sqldelete",$sqldelete);
			$result = mysql_query($sqldelete,$link) or die(mysql_error());
		}
	}
}
/*
function View_Formal_Design($designno){
	global $link,$PARAMS,$localversion,$lastmodified;
	//debug_string ("View_Formal_Design ");
	
	$designs = MYSQLComplexSelect($link,array("*"),array("des_design"),array("DesignID='".$designno."'","des_design.Hidden='0'"),array());	
	$design = $designs[0];

	$sections = MYSQLComplexSelect($link,array("*"),array("des_sections"),array("DesignNo='".$designno."'","Subsection=0","Hidden='0'"),array("Seq"));	
	//debug_array("designs",$designs);
	//debug_array("sections",$sections);
	$rawdate=$design['Date'];
	$year=substr($rawdate,0,4);
	$month=substr($rawdate,4,2);
	$day=substr($rawdate,6,2);
	$date=$month."/".$day."/".$year;

//start displaying it 
	Display_Generic_Header("View Formal Design",$color="#ddf0dd","","http://menconi.com/designs/light-bulb.ico");
	print "<table border=0>\n <tr>\n <td width=5%>&nbsp;</td>\n <td width=85%>\n"; 
	print "<center><h1>".$design['DesignName']."  </h1>\n";	
	print "<b>".$date."</b><br>\n";
	print "<b>Version ".$design['Version']."</b><br>\n";
	print "<b>".$design['Designer']."</b><br>\n";
	print "</center>\n";
	print "<h2>CONTENTS</h2>\n";
	print "<ul>\n";
	foreach ($sections as $section){
		print "<li><a href=\"#".$section['SectionTitle']."\">".$section['SectionTitle']."</a></li>\n";
		$sectionno = $section['SectionID'];
		$subsections = MYSQLComplexSelect($link,array("*"),array("des_sections"),array("DesignNo='".$designno."'","Subsection='".$sectionno."'","Hidden='0'"),array("Seq"));	
		if(count($subsections)>0){
			print "<ul>\n";
			foreach ($subsections as $subsection){
			//	print "<li><a href=\"#".$subsection['SectionTitle']."\">".$subsection['SectionTitle']."</a></li>\n";
				print "<li><a href=\"#".$subsection['SectionTitle']."\">".$subsection['SectionTitle']."</a></li>\n";
			}
			print "</ul>\n";
		}
			
	}
	print "</ul>\n";
	foreach ($sections as $section){
		$sectionno = $section['SectionID'];
		$subsections = MYSQLComplexSelect($link,array("*"),array("des_sections"),array("DesignNo='".$designno."'","Subsection='".$sectionno."'","Hidden='0'"),array("Seq"));	
		print "<br><br><a name=\"".$section['SectionTitle']."\">";
		print "<h2>".strtoupper($section['SectionTitle'])."</h2></a>\n";
		print $section['SectionText']."\n";
		print "<table border=0><tr>\n <td width=8%>&nbsp;</td>\n<td width=92%>\n";
		foreach ($subsections as $subsection){
			print "<br><br><a name=\"".$subsection['SectionTitle']."\">";
			print "<h3>".strtoupper($subsection['SectionTitle'])."</h3/a>\n";
			print $subsection['SectionText']."\n";
		}
		print "</td>\n</table>\n";//<td width=1%>&nbsp;</td>\n </table>\n";
	}
	print "</td>\n<td width=8%>&nbsp;</td>\n </table>\n";
	print"<hr><a href=\"index.php?mode=formaledit&designno=$designno\">Edit This Design</a><br>\n";
	print "<a href=\"index.php\"><i>Return to Directory</i></a><br><br>\n";
	print "<a href=\"index.php?mode=informal&designno=$designno\"><i>Go To Notes Page</a><br>\n";
    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
*/
function Parse_Design($designno){
	global $link,$PARAMS;
	CanISeeThis($designno);
	//debug_string("Parse_Design($designno)");
	//Array ( [mode] => parse_design [designno] => 13 [Date] => 20050614090749 [DesignName] => Test [Designer] => Harvard Farquar [Description] => This is just a test design [Version] => [Delete] => Delete ) 
	$Date = $PARAMS['Date'];
	$DesignName = $PARAMS['DesignName'];
	$Designer = $PARAMS['Designer'];
	$Description = $PARAMS['Description'];
	$Version = $PARAMS['Version'];
	if(isset($PARAMS['Delete'])){
		$Delete = 1;
	} else {
		$Delete = 0;
	}
	if(isset($PARAMS['Public'])){
		$Public = 1;
	} else {
		$Public = 0;
	}
	$sqlupdate = "update des_design set Date='$Date',DesignName='$DesignName',Designer='$Designer',Description='$Description',Version='$Version',Hidden='$Delete',Public='$Public' where DesignID=$designno";
	//debug_string("sqlupdate",$sqlupdate);
	$result = mysql_query($sqlupdate,$link) or die(mysql_error());
}

function Display_Design_Form($designno){
	global $link,$PARAMS,$localversion,$lastmodified;
	CanISeeThis($designno);
	//debug_string("Display_Design_Form($designno)");
	$designs = MYSQLComplexSelect($link,array("*"),array("des_design"),array("DesignID='".$designno."'","Hidden='0'"),array(),0);	 if(count($designs)<1){
		Display_Directory();
		return;
	}
	$design = $designs[0];
	if ($design['Public']==1)$checked=" checked ";
	//debug_array("Design",$design);
	$DesignName=$design['DesignName'];
	$Designer = $design['Designer'];
	$Description = $design['Description'];
	$Version = $design['Version'];
	$catno = $design['Catno'];
	//debug_array("designs",$designs);
	Display_Generic_Header("Edit Design Form",$color="#eae4ef","","http://menconi.com/designs/light-bulb.ico");
	$date=date("YmdHis");
	//debug_string("date",$date);

	$categorymenu = createmenu($link,'des_category','name','categoryid',$catno);

echo <<<EOF
<font size=+1><B>Edit $DesignName Design </b></font> <br>

<a href="index.php">Back to Design Directory</a> | 
<a href="index.php?designno=$designno&mode=informal">Notes</a> | 
<a href="index.php?designno=$designno&mode=displayformal">Formal Design</a> |
<a href="index.php?designno=$designno&mode=formaledit">Edit Formal Design</a><br><br>
<form action="index.php" method=post>
<input type=hidden name="mode" value="parse_design">
<input type=hidden name="designno" value="$designno">
<input type=hidden name="Date" value="$date">
				<b>DesignName:  </b><br>
			<input type=text name="DesignName" size=20 value="$DesignName"><br>
				<b>Designer:  </b><br>
			<input type=text name="Designer" size=25 value="$Designer"><br>
				<b>Description:  </b><br>
			<input type=text name="Description" size=60 value="$Description" ><br>
				<b>Version:  </b><br>
			<input type=text name="Version" size=10 value="$Version"><br>
				<b>Category: </b><br>
			$categorymenu			<br>
				<b>Delete Design: </b>
			<input type=checkbox name="Delete" value="Delete"><br>
				<b>Public Design: &nbsp;</b>
			<input type=checkbox name="Public" value="Public" $checked><br>
			<input type="submit" value="Update Design"> 
</form>
EOF;

    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function Parse_NewDesign_Form(){
	global $link,$PARAMS,$appnum;
	//debug_string ("Parse_NewDesign_Form()");
	$name = $PARAMS['DesignName'];
	$author = $PARAMS['Designer'];
	$desc = $PARAMS['Description'];
	$catno = 1;
	$sqlinsert="insert into des_design (DesignName,Designer,Description,AppNo,AppWord,Hidden,Version,DesignTS,Catno) values ('$name','$author','$desc',0,'GeneralApplication',0,'0.1',NULL,'$catno')";
	//debug_string("sqlinsert 1",$sqlinsert);
	$result = mysql_query($sqlinsert,$link) or die(mysql_error());

	$sqlselect = "select last_insert_id() as id from des_design";
	$result = mysql_query($sqlselect,$link) or die(mysql_error());
	$rec = mysql_fetch_assoc($result);
	$designno = $rec['id'];
	//debug_string("designid",$designno);

	$sqlinsert="insert into des_sections(DesignNo,SectionTitle,Seq) values ($designno,'Introduction',10),($designno,'Definitions',20),($designno,'Database Design',30),($designno,'Technical Design Notes',40),($designno,'Issues',50),($designno,'Future Enhancements',60)";
	//debug_string("sqlinsert 2",$sqlinsert);
	$result = mysql_query($sqlinsert,$link) or die(mysql_error());

$temp ="<table border=\"1\">\n<tr><th>Revision</th><th>Date</th><th>Author</th><th>Description</th></tr>\n<tr><td>Initial Revision (0.1)</td><td>$date</td><td>$author</td></tr>\n</table>\n";
	$findintrosql = "select SectionID from des_sections where designno=$designo and SectionTitle='Introduction'";



	$username = loc_get_username();
	//debug_string("username",$username);
	//debug_string("designno",$designno);
	$privinsert = "insert into des_priv(designno,username) values($designno,'".$username."')";
	debug_string("privinsert",$privinsert);
	$result = mysql_query($privinsert,$link);

	$date = date("Ymd",time());
	$sqlinsert="insert into des_notes(DesignNo,Date,Author,Note) values ($designno,'$date','$username',' ')";
	//debug_string("sqlinsert 3",$sqlinsert);
	$result = mysql_query($sqlinsert,$link) or die(mysql_error());

	return $designno;
}
function Display_NewDesign_Form(){
	global $localversion,$lastmodified;
	//debug_string("Display_NewDesign_Form()");
	Display_Generic_Header("New Design Form",$color="#FFccFF","","http://menconi.com/designs/light-bulb.ico");
echo <<<EOF
<font size=+1><B>Create New Design </b></font> <br>

<a href=index.php>| Back to Design Directory</a> <br><br>
<form action="index.php" method=post>
<input type=hidden name="mode" value="newdesign_parse">
<table>
	<tr >
		<td valign=top>
				<b>DesignName  </b>
		</td>
		<td valign=top>
				<b>Designer</b>
		</td>
		<td valign=top>
				<b>Description</b>
		</td>
		<td>
			&nbsp;
		</td>
	</tr>
	<tr >
		<td valign=top>
			<input type=text name="DesignName" size=20>
		</td>
		<td valign=top>
			<input type=text name="Designer" size=25>
		</td>
		<td valign=top>
			<input type=text name="Description" size=55>
		</td>
		<td valign=top>
			<input type="submit" value="Add New Design"> 
		</td>
	</tr>
</table>
</form>
EOF;

    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function parseAddPrivs(){
    global $link,$PARAMS,$adminusers,$appnum;
    //debug_string("parseAddPrivs()");
	$username = loc_get_username();
	if (!in_array ( $username, $adminusers)){//not authorized
		Display_NotAuthorized($username);
	break_mysql_connect($link);
		exit();
	}
		
    $username = substr($PARAMS['user'],1);
    $designno = substr($PARAMS['design'],1);
    //debug_string("username",$username);
    //debug_string("designno",$designno);
    $sql = "insert des_priv (username,designno) values ('$username','$designno')";
    //debug_string("sql",$sql);
    $result = mysql_query($sql,$link) or die(mysql_error());
}
function parseDelPrivs(){
    global $link,$PARAMS,$appnum,$adminusers;
    //debug_string("parseDelPrivs()");
	$username = loc_get_username();
	if (!in_array( $username, $adminusers)){//not authorized
		Display_NotAuthorized($username);
	break_mysql_connect($link);
		exit();
	}
    foreach ($PARAMS as $key=>$value){
        //debug_string("$key",$value);
        //debug_string("substr0",substr($key,0,1));
        //debug_string("strpos1", strpos($key,"U"));
        if (substr($key,0,1)=="D" && strpos($key,"U")>0){
            $pos = strpos($key,"U");
            $designno = substr($key,1,$pos-1);
            //debug_string("designno",$designno);
            $username = substr($key,$pos+1);
            //debug_string("username",$username);
            $sql = "delete from des_priv where username='$username' and designno='$designno' limit 1";
            //debug_string("sql",$sql);
            $result = mysql_query($sql,$link) or die(mysql_error());
        }
    }
}
function Display_Admin_Form($version,$lastmodified){
    global $PARAMS,$link,$appnum,$appword,$usertable ;
    debug_string("Display_Login_Admin_Form");
	debug_string("appnum",$appnum);
	debug_string("appword",$appword);
// 	$users = loc_get_userlist($appnum,$appword);
//	sort($users);
//	debug_array("users",$users);
    $users = MYSQLComplexSelect($link,array("*"),array($usertable ),array(),array(),0);
	debug_array("users",$users);
    $designs = MYSQLComplexSelect($link,array("DesignID","DesignName",),array(des_design),array(),array(),0);
	debug_array("designs",$designs);
    $privs = MYSQLComplexSelect($link,array("username","designno","time","DesignID","DesignName"),array("des_priv,des_design"),array("designno=DesignID"),array("username","DesignName"),0);
	debug_string("post select");
	debug_array("priv",$privs);
    $thisapp = $_SERVER['SCRIPT_NAME'];
	debug_string("thisapp",$thisapp);
    $pos = strpos($thisapp,"/",1)+1;
    debug_string("pos",$pos);
    $thisapp = substr($thisapp,$pos);
	debug_string("thisapp",$thisapp);

    Display_Generic_Header("Login Admin Form","#cca5FF","","http://menconi.com/designs/light-bulb.ico");
	print "<h1>Login Administration Page</h1>\n";
    print "<h2>Delete Existing Privs</h2>\n";
    print "<p>This is a list of designs and who can look at them. Check Application / User combinations to be deleted.\n";
    print "<table border=0>\n";
    print "<tr>\n";
    print "<td><b>User</b></td>\n";
    print "<td><b>Design</b></td>\n";
    print "<td><b>Delete</b></td>\n";
    print "</tr>\n";
    print '<form action="'.$thisapp.'" method="post">'."\n";
    print '<input type="hidden" name="mode" value="parseDelPrivs">'."\n";
    foreach ($privs as $priv){
		//debug_array("priv",$priv);
    	print "<tr>\n";
        print "<td>".$priv['username']."&nbsp;&nbsp;</td>\n";
        print "<td>".$priv['DesignName']."</td>\n";
        $val = 'D'.$priv['DesignID'].'U'.$priv['username'];
        print '<td><input type="checkbox" name="'.$val.'" value="'.$val.'"/>'."</td>\n";
    	print "</tr>\n";
    }
    print "</table>\n";
    print "<input type=submit value=\"Delete Privs \">\n";
    print "</form>\n";

    print "<hr>\n";

    print "<h2>Add New Privs</h2>\n";
    print "<table border=1>\n";
    print "<tr>\n";
    print "<td><b>User</b></td>\n";
    print "<td><b>Design</b></td>\n";
    print "<td>&nbsp;</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print '<form action="'.$thisapp.'" method="post">'."\n";
    print '<input type="hidden" name="mode" value="parseAddPrivs">'."\n";
    print "<td>\n";
    print '<select name="user" >'."\n";
    foreach ($users as $user){
    	$username=$user['username'];
        print '<option value="'.'a'.$username.'" label="'.$username.'">'.$username.'</option>'."\n";
    }
    print "</select></td>\n";
    print "<td>\n";
    print '<select name="design"  >'."\n";
    foreach ($designs as $design){
        print '<option value="'.'a'.$design['DesignID'].'" label="'.$design['DesignName'].'">'.$design['DesignName'].'</option>'."\n";
    }
    print "</select></td>\n";
    print "<td><input type=submit value=Create Priv></td>\n";
    print "</form>\n";
    print "</tr>\n";
    print "</table>\n";
    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
function RenumberSubSections($design,$sectionno,$increment=10){
    global $link;
    debug_string("RenumberSections($design,$sectionno,$increment)");
    $sql = "select SectionID from des_sections where subsection=$sectionno and DesignNo=$design order by seq";
	$sectionlist = MYSQLGetData($link,$sql);
	if (count($sectionlist)==0)return;//if we didn't get any records we're done; exit
	debug_array("sectionlist",$sectionlist);
	$newseq = $increment;
	foreach($sectionlist as $section){
		debug_array("section",$section);
		$sectionid=$section['SectionID'];
		debug_string("sectionid", $sectionid);
		debug_string("sectionno",$sectionno);
		$sql = "update des_sections set seq=$newseq where DesignNo=$design and SectionID=$sectionid and subsection=$sectionno" ;
		debug_string("sql",$sql);
		do_mysql($link,$sql);
		$newseq += $increment;
		debug_string("newseq",$newseq);
	}
}
/*******************************************
 * RenumberSections()
 * Renumber the sections of a specific design
 * INPUT: $design - which design to renumber
 *        $increment - where to start and what increment to use (default 10)
 * OUTPUT: None
 * RETURN: Nothing 
 * SIDEEFFECT: change seq in the sections of the design
 *******************************************/
function RenumberSections($design,$increment=10){
    global $link;
    debug_string("RenumberSections($design,$increment)");
    $sql = "select SectionID from des_sections where subsection=0 and DesignNo=$design order by seq";
	$sectionlist = MYSQLGetData($link,$sql);
	if (count($sectionlist)==0)return;//if we didn't get any records we're done; exit
	debug_array("sectionlist",$sectionlist);
	$newseq = $increment;
	foreach($sectionlist as $section){
		debug_array("section",$section);
		$sectionid=$section['SectionID'];
		debug_string("sectionid", $sectionid);
		$sql = "update des_sections set seq=$newseq where DesignNo=$design and SectionID=$sectionid" ;
		do_mysql($link,$sql);
		$newseq += $increment;
		debug_string("sql",$sql);
		debug_string("newseq",$newseq);
	}
}
function CreateSectionMenu($sections,$selectname,$extraoptions=""){
    $menu= "<select name=\"$selectname\">\n";
    $menu.=$extraoptions;
    foreach($sections as $section){
        $menu.= "<option value=\"S".$section['SectionID']."\" label=\"".$section['SectionTitle']."\">";
        $menu.= $section['SectionTitle']."</option>\n";
    }
    $menu.= "</select>";
	return $menu;
}
function MoveSection($section,$newsection){
    global $link;
	debug_string("MoveSection($section,$newsection)");
	$sql = "update des_section subsection=$newsection where SectionID=$section ";
	debug_string("MoveSection sql",$sql);
	//do_mysql($link,$sql,true);
}
?>

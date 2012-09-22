<?php
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
	Display_Generic_Header("View Formal Design",$color="#ddf0dd");
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
	print "<a href=\"index.php?mode=informal&designno=$designno\">Go To Notes Page</a><br>\n";
	print "<a href=\"index.php\"><i>Return to Directory</i></a><br><br>\n";
    Display_Generic_Footer($version,date("F d Y H:i:s", getlastmod()));
}
?>

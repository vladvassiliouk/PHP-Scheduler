<?
if ($_SERVER['PHP_AUTH_USER']>"") {
	$loginstr="logged in as ".$_SERVER['PHP_AUTH_USER']." (<a href='tstall_Cuseradmin.php'>Control Panel</a>) (<a href='tstall_Cuseradmin.php?log=off'>Log Out</a>)";
} else {
	$loginstr="not logged in. read only mode. (<a href='tstall_Cuseradmin.php?log=on'>Log in</a>)";
}
$adminuserarr=array("","vvassiliouk","mdiaz", "aroberts");
if (array_search($_SERVER['PHP_AUTH_USER'], $adminuserarr)) {
	$adminuser=1;
}
echo "<html><title>GMS Scheduler</title>
<head>
<link href=\"Asgard.css\" type=\"text/css\" rel=\"stylesheet\"> 
<script>
function toggle_visibility(id) {
if( document.getElementById(id).style.display=='none' ){
document.getElementById(id).style.display = '';
}else{
document.getElementById(id).style.display = 'none';
}
}
</script>
</head>


<body>
<table width=100%>
<TR height=\"65\">                                                                                            <!-- Starts First Header row--> 
<TD valign=\"top\" colspan=2> 
      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"> 
           <tr> 
              <td background=\"$imgpath/headerBKG.gif\" width=\"100%\"> 
                        <img src=\"$imgpath/header2_b.gif\" height=\"59\" width=\"144\" align='left'><table height='59' width=83% border=0><tr><td valign='bottom' align='right'><font color='white' size='4'>GMS Scheduler TST</font></td></tr></table></td> 
            </tr> 
        </table> 
</TD> 
</TR>       
<tr>
<td><a href=\"tstall_Cscriptsadmin.php\">Script Admin</a> | <a href=\"tstall_Cscriptsimport.php\">Scripts Import</a> | <a href='tstall_Cscriptsexport.php'>Scripts Export</a> | <a href='doc/GMS Scheduler - User Guide FINAL.htm'>User Guide</a> |  <a href='doc/GMS Scheduler - Design.htm'>Design Document</a><br>
</td><td align='right'>$loginstr</td></tr></table>";

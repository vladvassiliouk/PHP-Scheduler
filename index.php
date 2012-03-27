<?
#print_r($_POST);
$ts=$_POST['ts'];
$script=$_POST['script'];
$newtimeslot=$_POST['newtimeslot'];
$id=$_POST['id'];
$interval=$_GET['interval'];
$del=$_POST['del'];
$disable=$_GET['disable'];
$enable=$_GET['enable'];
if (empty($interval)) $interval="1";
include("include/config.inc.php");
include("include/top.inc.php");

if (!empty($del)) {
	if ($_POST['wat']=="Delete Sel.") {
		foreach($del as $key=>$val) {
			$result=$db->query("delete from scripts_tbl where id='$val'", $err);
			echo $err;
		}
	} elseif ($_POST['wat']=="Enable Sel.") {
		foreach($del as $key=>$val) {
                        $result=$db->query("update scripts_tbl set disabled='0' where id='$val'", $err);
                        echo $err;
                }
	} elseif ($_POST['wat']=="Disable Sel.") {
		foreach($del as $key=>$val) {
                        $result=$db->query("update scripts_tbl set disabled='1' where id='$val'", $err);
                        echo $err;
                }
	}
}

$result2=$db->query("select interval, sleep from interval_tbl where interval='$interval'");
$row2=$result2->fetch(SQLITE_NUM);
$interval2=$row2['0']*60;
$sleep=$row2['1'];
$sleepjs=$row2[1];
$intervaljs=$row2[0];
$maxtimeslot=$interval2 / $sleep;
$maxtimeslot=floor($maxtimeslot-1); #timeslots start at 0
#echo "maxtimeslot= $maxtimeslot, sleep=$sleep, inteval=$interval2";
if (!empty($_POST['sleep']) && !empty($_POST['interval']) && is_numeric($_POST['sleep']) && is_numeric($_POST['sleep']) && $_POST['sleep'] <= $interval2 && $_POST['sleep'] !=$sleep) {
        $result=$db->query("update interval_tbl set sleep='".$_POST['sleep']."' where interval='".$_POST['interval']."'");
        balancescripts($_POST['interval']);
	$result2=$db->query("select interval, sleep from interval_tbl where interval='$interval'");
	$row2=$result2->fetch(SQLITE_NUM);
	$interval2=$row2['0']*60;
	$sleep=$row2['1'];
	$sleepjs=$row2[1];
	$intervaljs=$row2[0];
	$maxtimeslot=$interval2 / $sleep;
	$maxtimeslot=$maxtimeslot-1;
}

if (($newtimeslot <= $maxtimeslot) && is_numeric($newtimeslot) && !empty($id)) {
	#print_r($_POST);
	$qry="update scripts_tbl set timeslot='$newtimeslot' where id='$id'";
	#echo $qry;
	$result=$db->query("update scripts_tbl set timeslot='$newtimeslot' where id='$id'", $err);
	echo "Script #$id moved to new timeslot: $newtimeslot";
	#echo $err;
} elseif (($newtimeslot > $maxtimeslot) && is_numeric($newtimeslot) && !empty($id)) {
	echo "ERROR: Timeslot $newtimeslot does not exist! Script not moved<br>";
}
if ($_POST['balance'] != "") {
	balancescripts($interval);
}
if ($ts !="" && !empty($script)) {
	$result2=$db->query("select command from scripts_tbl where command='$script'");
	$dupe=$result2->fetchSingle();
	if (empty($dupe)) {	
		$qry="insert into scripts_tbl (id, command, interval, timeslot) values(NULL, '$script', '$interval', '$ts');";
		$result=$db->query($qry);
		echo "New Script added to timeslot $ts";
	} else {
		echo "ERROR: \"$script\" already exists in the database!<br>";
	}
}

if (is_numeric($id) && !empty($script)) {
        #print_r($_POST);
	$script=sqlitestr($script);
        $qry="update scripts_tbl set command='$script' where id='$id'";
        #echo $qry;
        $result=$db->query($qry, $err);
        echo "Script #$id Updated";
        #echo $err;
} 
if (is_numeric($id) && !empty($_POST['wat'])) {
        #print_r($_POST);
	if ($_POST['wat'] == "enable") {
	        $qry="update scripts_tbl set disabled='0' where id='$id'";
	} elseif($_POST['wat'] == "disable") {
		$qry="update scripts_tbl set disabled='1' where id='$id'";
	}
       	$result=$db->query($qry, $err);
        echo "Script #$id ".$_POST['wat']."d";
        #echo $err;
}

$result=$db->query("SELECT DISTINCT interval, sleep FROM interval_tbl order by interval");
while($row=$result->fetch(SQLITE_ASSOC)){
	$interval2=$row['interval'];
	$sleepval=$row['sleep'];
	$selected="";
	if ($_GET['interval'] == $interval2) $selected="selected";
	$options=$options."<option value='$interval2' $selected>$interval2 ({$sleepval}s sleep)</option>\n";
}

$result=$db->query("SELECT * FROM scripts_tbl where interval='$interval'");
$total = $result->numRows();
$result=$db->query("SELECT * FROM scripts_tbl where interval='$interval' and disabled='0'");
$totalenabled= $result->numRows();
$result=$db->query("SELECT * FROM scripts_tbl where interval='$interval' and disabled='1'");
$totaldisabled= $result->numRows();

echo "
<script>
function go()
{
	box = document.interval.interval;
	destination = box.options[box.selectedIndex].value;
	if (destination) location.href = 'http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?interval=' + destination;
}
</script>
<script type=\"text/javascript\">
<!--
function prompter(id,script) {
        var answer = prompt (\" '\"+script+\"'\\n\\nType in the number of the timeslot you want to move this script to: (0-$maxtimeslot) \",\"\");
        document.newtimeslot.newtimeslot.value=answer;
        document.newtimeslot.id.value=id;
        document.newtimeslot.submit();
}
function prompter2(id,script,timeslot) {
        var answer = prompt (\"Editing script #\"+id+\" in timeslot #\"+timeslot,script);
        document.editscript.script.value=answer;
        document.editscript.id.value=id;
        document.editscript.submit();
}
function prompter5(id,wat,command) {
        var answer = confirm(\"Are you sure you want to \" + wat + \" this script?\\n\"+command);
	if (answer) {
        	document.enablescript.wat.value=wat;
        	document.enablescript.id.value=id;
        	document.enablescript.submit();
	}
}


function prompter3(timeslot) {
        var answer = prompt (\"Enter the command you want to add to timeslot #\"+timeslot);
        document.addscript.script.value=answer;
        document.addscript.ts.value=timeslot;
        document.addscript.submit();
}
function prompter4() {
        var answer = prompt (\"Editing $intervaljs minute interval sleep time (in seconds). Timeslots are calculated based on this value. Scripts will be auto-balanced to new timeslots.\",\"$sleepjs\");
        document.intervaledit.sleep.value=answer;
        document.intervaledit.interval.value=\"$intervaljs\";
        document.intervaledit.submit();
}

// -->
function checkuncheck()
{
if (document.check.all.checked==true) 
{
	var theForm = document.check;
	for (i=0; i<theForm.elements.length; i++) {
        if (theForm.elements[i].name=='del[]')
            theForm.elements[i].checked = 1;
    	}
}
else 
{
        var theForm = document.check;
        for (i=0; i<theForm.elements.length; i++) {
        if (theForm.elements[i].name=='del[]')
            theForm.elements[i].checked = 0;
        }

}
}
function timeframe() {
	document.check.action='tstall_Cscriptstimeframe.php';	
	document.check.submit();
}

</script>



<form method='post' action='$_PHPSELF' name='newtimeslot' onSubmit='return false;'>
<input type='hidden' name='newtimeslot' value=''>
<input type='hidden' name='id' value=''>
</form>

<form method='post' action='$_PHPSELF' name='editscript' onSubmit='return false;'>
<input type='hidden' name='script' value=''>
<input type='hidden' name='id' value=''>
</form>

<form method='post' action='$_PHPSELF' name='addscript' onSubmit='return false;'>
<input type='hidden' name='script' value=''>
<input type='hidden' name='ts' value=''>
</form>
<form method='post' action='$_PHPSELF' name='intervaledit' onSubmit='return false;'>
<input type='hidden' name='sleep' value=''>
<input type='hidden' name='interval' value='$intervaljs'>
</form>
<form method='post' action='$_PHPSELF' name='enablescript' onSubmit='return false;'>
<input type='hidden' name='wat' value=''>
<input type='hidden' name='id' value=''>
</form>


<table width=100% border=1>
<tr><form action='$_PHPSELF' method='get' onChange='go()' name='interval'><td width=15%>
Interval: <select name='interval' onChange='go()'>
$options
</select>
</form>
</td>
<td>
<table width=100% cellpadding='0'><tr><td>
<form action='$PHPSELF' method='post' name='check'><td><input type='checkbox' name='all' OnClick='checkuncheck(document.check.del);'>
<input type='submit' name='wat' value='Delete Sel.' onclick=\"javascript:return confirm('Are you sure you want to delete the selected scripts?')\">
<input type='submit' name='wat' value='Disable Sel.' onclick=\"javascript:return confirm('Are you sure you want to DISABLE the selected scripts?')\">
<input type='submit' name='wat' value='Enable Sel.' onclick=\"javascript:return confirm('Are you sure you want to ENABLE the selected scripts?')\">
<input type='submit' name='wat' value='Modify Timeframe' onclick='timeframe()'>
<input type='hidden' name='interval' value='$intervaljs'>
</td>
<td align='right'>
<input type='submit' value='Balance Scripts' onclick=\"javascript:return confirm('Are you sure you want to balance all scripts?')\" name='balance'></td></tr></table>
</td>
</td><td align='center'><a href='#' title='Edit Interval' OnClick='prompter4()'><img src='$imgpath/edit-16x16.png' border=0></a></td></tr><tr><td>Timeslot [start time]</td><td width=60%>Script(s) (Total scripts: $total, Enabled: $totalenabled, Disabled: $totaldisabled)</td>
<td width=15%>Interval (mins)</td></tr>";
for ($i=0;$i<=$maxtimeslot;$i++) {
        $timeslot=$i;
        $seconds=$timeslot * $sleep;
        if ($seconds > 0) {
                $mins = floor ($seconds / 60);
                $secs = $seconds % 60;
                $timestr="$mins:$secs";
        } else {
		$timestr="0:00";
	}	
        echo "<tr><td width=10%><table width=100%><tr><td>$timeslot [$timestr]</td><td align=right><a href='#' title='Add a script to timeslot' OnClick='prompter3(\"$timeslot\")'><img src='$imgpath/add-16x16.png' border=0></a></td></tr></table></td><td width=80%>";
        $result2=$db->query("SELECT * FROM scripts_tbl where interval='$interval' and timeslot='$timeslot'");
	
        while($row2=$result2->fetch(SQLITE_ASSOC)){
		$timestr1="";
		$fcolor="black";#script font color
                $command=trim($row2['command']);
                $id=$row2['id'];
		$result1=$db->query("SELECT e.days, e.time1,e.time2 FROM exectime_tbl as e where e.scriptid='$id'");
        	$row1=$result1->fetch(SQLITE_ASSOC);
        	$days=$row1['e.days'];
        	$time1=$row1['e.time1'];
        	$time2=$row1['e.time2'];
        	$daysarr=str_split($days);
        	$days=array();
        	foreach($daysarr as $key1=>$val1) {
                switch($val1) {
                	case "1":
                	$days[$key1]="Mon";
                	$m="CHECKED";
                	break;
                	case "2":
                	$days[$key1]="Tue";
                	$t="CHECKED";
                	break;
               	 	case "3":
                	$days[$key1]="Wed";
                	$w="CHECKED";
                	break;
                	case "4":
                	$days[$key1]="Thu";
                	$tr="CHECKED";
                	break;
                	case "5":
                	$days[$key1]="Fri";
                	$f="CHECKED";
                	break;
                	case "6":
                	$days[$key1]="Sat";
                	$s="CHECKED";
                	break;
                	case "7":
                	$days[$key1]="Sun";
                	$su="CHECKED";
                	break;
                	}
        	}
        	$days=implode(",", $days);
       	 	$timestr1="daily";
		if (count($daysarr)==7) $days=$timestr1;
       	 	if ($days>"") $timestr1="$days";
	        if (!empty($time1) && !empty($time2)) $timestr1.=" $time1-$time2 GMT";
		$disabled=$row2['disabled'];
		if ($disabled ==1) {
			$disbtn="<a href='#' title='Enable' onclick=\"prompter5($id, 'enable', '$command')\" ><img src='$imgpath/play-16x16.png' border=0></a>";
			$fcolor="#C0C0C0";
		} else {
			$disbtn="<a href='#' title='Disable' onclick=\"prompter5($id, 'disable', '$command')\" ><img src='$imgpath/pause-16x16.png' border=0></a>";
		}
                echo "<input type='checkbox' name='del[]' value='$id'>
<a href='#'
OnClick='prompter($id,\"$command\")' title='Move to
different timeslot'><img src='$imgpath/move-file-16x16.png' border=0></a><a href='#' title='Edit'
OnClick='prompter2($id,\"$command\",$timeslot)'><img src='$imgpath/edit-16x16.png' border=0></a>$disbtn <font color='$fcolor'>[$timestr1] $command</font><br>";
        }
	if ($result2->numRows() == 0) echo "<center><font color='#C0C0C0'><i>----EMPTY----</i></font></center>";       
	echo "</td><td width=10%>$interval</td></tr>";
}
echo "<tr><td></td><td>
<input type='submit' name='wat' value='Delete Sel.' onclick=\"javascript:return confirm('Are you sure you want to delete the selected scripts?')\">
<input type='submit' name='wat' value='Disable Sel.' onclick=\"javascript:return confirm('Are you sure you want to DISABLE the selected scripts?')\">
<input type='submit' name='wat' value='Enable Sel.' onclick=\"javascript:return confirm('Are you sure you want to ENABLE the selected scripts?')\">
<input type='submit' name='wat' value='Modify Timeframe' onclick='timeframe()'>
</td></td><td></td></tr></table>";
?>

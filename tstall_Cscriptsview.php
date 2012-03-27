<?
$interval=$_GET['interval'];
if (empty($interval)) $interval="1";
include("include/config.inc.php");
include("include/top.inc.php");


$result2=$db->query("select interval, sleep from interval_tbl where interval='$interval'");
$row2=$result2->fetch(SQLITE_NUM);
$interval2=$row2['0']*60;
$sleep=$row2['1'];
$sleepjs=$row2[1];
$intervaljs=$row2[0];
$maxtimeslot=$interval2 / $sleep;
$maxtimeslot=floor($maxtimeslot-1); #timeslots start at 0
#echo "maxtimeslot= $maxtimeslot, sleep=$sleep, inteval=$interval2";
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
echo"
<script>
function go()
{
	box = document.interval.interval;
	destination = box.options[box.selectedIndex].value;
	if (destination) location.href = 'http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?interval=' + destination;
}
</script>


<table width=100% border=1>
<tr><form action='$_PHPSELF' method='get' onChange='go()' name='interval'><td width=15%>
Interval: <select name='interval' onChange='go()'>
$options
</select>
</form>
</td>
<td>Total scripts: $total, Total Enabled: $totalenabled, Total Disabled: $totaldisabled
</td><td align='center'></td></tr><tr><td>Timeslot [start time]</td><td width=60%>script(s)</td><td width=15%>Interval (mins)</td></tr>";
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
        echo "<tr><td width=10%><table width=100%><tr><td>$timeslot [$timestr]</td><td align=right></td></tr></table></td><td width=80%>";
        $result2=$db->query("SELECT * FROM scripts_tbl where interval='$interval' and timeslot='$timeslot'");
        while($row2=$result2->fetch(SQLITE_ASSOC)){
		$fcolor="black";#script font color
                $command=trim($row2['command']);
                $id=$row2['id'];
		$disabled=$row2['disabled'];
		if ($disabled ==1) {
			$disbtn="<img src='$imgpath/pause-16x16.png' border=0 title='Script is Disabled'>";

			$fcolor="#C0C0C0";
		} else {
			$disbtn="<img src='$imgpath/play-16x16.png' border=0 title='Script is Enabled'>";

		}
                echo "
$disbtn<font color='$fcolor'>$command</font><br>";
        }
	if ($result2->numRows() == 0) echo "<center><font color='#C0C0C0'><i>----EMPTY----</i></font></center>";       
	echo "</td><td width=10%>$interval</td></tr>";
}
echo "<tr><td></td><td>
</td></td><td></td></tr></table>";
?>

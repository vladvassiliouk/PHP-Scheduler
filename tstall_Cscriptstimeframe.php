<?
#error_reporting(E_ALL);
#iini_set('display_errors', '1');
include("include/config.inc.php");
include("include/top.inc.php");
#echo "del is $del";
#print_r($_POST);
#create table exectime_tbl(scriptid INTEGER PRIMARY KEY, days TEXT, time1 TEXT, time2 TEXT);

$scriptsarr=$_POST['del'];
$interval=$_POST['interval'];
if (!empty($_POST['scriptsupdate'])) {
	$time1=$_POST['hour1'];
	$time2=$_POST['hour2'];
	$days=implode("",$_POST['days']);
	$scriptsarr=explode(",", $_POST['scriptsupdate']);
	foreach($scriptsarr as $key=>$val) {
		$result1=$db->query("SELECT * from exectime_tbl where scriptid='$val'");
		#$numrows=$result1->numRows();
		if ($result1->numRows()>0) {
			$qry="update exectime_tbl set days='$days', time1='$time1', time2='$time2' where scriptid='$val'";
		} else {
			$qry="insert into exectime_tbl (scriptid, days, time1, time2) values ('$val', '$days', '$time1', '$time2');";
		}
		$result=$db->query($qry);
	}	
}
foreach ($scriptsarr as $key=>$val) {
	if ($key==0) {
		$where.="s.id=$val";
	} else {
		$where.=" or s.id=$val";
	}
}
$outputstr="<table border=1><tr><td>Script</td><td>Runtime</td><td>Interval</td></tr>";
$result=$db->query("SELECT s.id, s.command, s.interval FROM scripts_tbl as s where ($where)");
while($row=$result->fetch(SQLITE_ASSOC)){
        $interval=$row['s.interval'];
	$cmd=$row['s.command'];
	$scriptsarr[]=$row['s.id'];
	$result1=$db->query("SELECT e.days, e.time1,e.time2  FROM exectime_tbl as e where e.scriptid='".$row['s.id']."'");
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
	$timestr="daily";
	if (count($daysarr)==7) $days=$timestr;
	if ($days>"") $timestr="$days";
	if (!empty($time1) && !empty($time2)) $timestr.=" $time1-$time2 GMT";
	$outputstr.="<tr><td>$cmd</td><td>$timestr</td><td>$interval</td></tr>";
}
$outputstr.="</table>";
/*
        $result=$db->query("SELECT * FROM scripts_tbl where interval='$newint'");
        	$result=$db->query("insert into interval_tbl values ('$newint', '$newsleep')");
                foreach($scriptsarr as $key => $line) {
                        if ($i==$maxslots) $i="0";
			$scriptsarr=insertcmd($scriptsarr, $i, "", $newint);                        	
			$i++;
                }
		while($row=$result->fetch(SQLITE_NUM)){
			$timeslot=$row[0];
			$scriptsarr=insertcmd($scriptsarr, $timeslot, $curts, $curint);
		}


*/
foreach($daysarr as $key=>$val) {
	switch ($val) {
		case "1":
		$m="CHECKED";
		break;
		case "2":
                $t="CHECKED";
                break;
		case "3":
                $w="CHECKED";
                break;
                case "4":
                $tr="CHECKED";
                break;
                case "5":
                $f="CHECKED";
                break;
                case "6":
                $s="CHECKED";
                break;
                case "7":
                $su="CHECKED";
                break;
	}
}
$scripts=implode(",",$scriptsarr);
echo "<form action='$_PHPSELF' method='post'>
<table border=1><tr><td>Set Run time: (Leave blank for daily default)<br>
Between Hours: <input type='text' name='hour1' size=3 value='$time1'> => <input type='text' name='hour2' size=3 value='$time2'> GMT<br>
On days: <input type='checkbox' name='days[]' value='7' $su>S <input type='checkbox' name='days[]' value='1' $m>M <input type='checkbox' name='days[]' value='2' $t>T <input type='checkbox' name='days[]' value='3' $w>W <input type='checkbox' name='days[]' value='4' $tr>T <input type='checkbox' name='days[]' value='5' $f>F <input type='checkbox' name='days[]' value='6' $s>S
</td></tr></table>
<input type='hidden' name='scriptsupdate' value='$scripts'><input type='submit'></form>

<h1>Modifying the following scripts:</h1><pre>
$outputstr
";

?>

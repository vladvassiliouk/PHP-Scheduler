<?
#error_reporting(E_ALL);
#iini_set('display_errors', '1');

include("include/config.inc.php");
include("include/top.inc.php");
#echo "del is $del";

$result=$db->query("SELECT DISTINCT interval FROM interval_tbl order by interval");
while($row=$result->fetch(SQLITE_ASSOC)){
        $interval=$row['interval'];
        $selected="";
        if ($_GET['interval'] == $interval) $selected="selected";
        $options=$options."<option value='$interval' $selected>$interval</option>\n";
}

$curts=$_POST['curts'];
$curint=$_POST['curint'];
$scripts=$_POST['scripts'];
$newint=$_POST['newint'];
$newsleep=$_POST['newsleep'];
$type=$_POST['type'];
$crontab=$_POST['crontab'];
$commentlines=$_POST['commentlines'];

if ($type=="new" && (!empty($newint) && is_numeric($newint)) && (!empty($newsleep) && is_numeric($newsleep)) && !empty($scripts)) {
	$scriptsarr=explode("\n", $scripts);
	$scriptsarr=array_reverse($scriptsarr);
        $i=0;
	$maxslots=$newint / $newsleep;
        $result=$db->query("SELECT * FROM scripts_tbl where interval='$newint'");
        if ($result->numRows() == 0) {
        	$result=$db->query("insert into interval_tbl values ('$newint', '$newsleep')");
		if ($crontab=="on") {
			foreach($scriptsarr as $key => $line) {
				$pound="";
                	 	if ($commentlines=="on") {
                        		if (substr($line,0,1)=="#") $pound="#";
                 	 	}
               			$tmparr=explode(" ", $line);
               			$tmparr=array_slice($tmparr, 5);
              			$newstr=implode(" ", $tmparr);
               			$scriptsarr[$key]=$pound.$newstr;
			}
               	}
		
                foreach($scriptsarr as $key => $line) {
                        if ($i==$maxslots) $i="0";
			$scriptsarr=insertcmd($scriptsarr, $i, "", $newint,$commentlines);                        	
			$i++;
                }
                echo "Finished creating new interval and importing scripts.<br>";
	} else {
		echo "This interval ($newint) already exists!<br>";
	}
}
if ($type=="current" && !empty($curint) && !empty($scripts)) {
	$result2=$db->query("select interval, sleep from interval_tbl where interval='$curint'");
        $row2=$result2->fetch(SQLITE_NUM);
        $interval=$row2['0']*60;
        $sleep=$row2['1'];
        $maxslots=$interval / $sleep;
        $result=$db->query("select timeslot from scripts_tbl where interval='$curint' group by timeslot");
        $totalts=$result->numRows();
        $scriptsarr=explode("\n", $scripts);
	$scriptsarr=array_reverse($scriptsarr);
        if ($crontab=="on") {
            foreach($scriptsarr as $key => $line) {
		$pound="";
		 if ($commentlines=="on") {
			if (substr($line,0,1)=="#") $pound="#";
		 }
                 $tmparr=explode(" ", $line);
                 $tmparr=array_slice($tmparr, 5);
                 $newstr=implode(" ", $tmparr);
                 $scriptsarr[$key]=$pound.$newstr;
            }
        }
        if ($maxslots > $totalts) { # check if there are less timeslots filled than the max allowed
        	for ($i=$totalts;$i<$maxslots;$i++) { # fill the remaining timeslots
			$scriptsarr=insertcmd($scriptsarr, $i, $curts, $curint, $commentlines);
        	}		        
        } 
	while (!empty($scriptsarr)) { #if there are still scripts left to insert then insert into the timeslots with least amount of scripts
		//select all timeslots sorted by the least amount of scripts assigned to them:
		$result=$db->query("select timeslot, count(command) as maxcmd from scripts_tbl where interval='$curint' group by timeslot order by maxcmd ASC, timeslot ASC");
		while($row=$result->fetch(SQLITE_NUM)){
			$timeslot=$row[0];
			$scriptsarr=insertcmd($scriptsarr, $timeslot, $curts, $curint);
		}
	}
       echo "finished importing to currently available slots\n";
}




echo "<form action='$_PHPSELF' method='post'>

<table  border=1 ><tr><td colspan=2>Insert scripts into</td></tr><tr><td><input type='radio' name='type' value='new'> New&nbsp;&nbsp;&nbsp;Interval (mins): <input 
type='text' 
name='newint'size=2>&nbsp;
Sleep time (secs): <input
type='text' name='newsleep'size=2></td><td>
<input type='radio' name='type' value='current'  CHECKED> Current&nbsp;&nbsp;&nbsp;Interval: <select 
name='curint'>$options</select>&nbsp; Single Timeslot: <input
type='text' name='curts'size=2> (optional)</td></tr><tr><td colspan=2>
<textarea cols=120 rows=20 name='scripts'></textarea><br>
<input type='checkbox' name='crontab' val='1'> Parse as crontab entries <font color='#C0C0C0'>(If your entries are in crontab format Ex: '*/5 * * * * script.sh')</font><br><input type='checkbox' name='commentlines' val='1'> Import commented lines as disabled <font color='#C0C0C0'>(Any line prefixed with a '#' will be imported as disabled)</font><br>
<input type='submit'></form></td></tr></table>";

?>

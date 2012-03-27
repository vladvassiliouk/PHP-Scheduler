<?
#error_reporting(E_ALL);
#iini_set('display_errors', '1');

include("include/config.inc.php");
include("include/top.inc.php");
#echo "del is $del";
$result=$db->query("SELECT interval, count(*) FROM scripts_tbl group by interval order by interval");
while($row=$result->fetch(SQLITE_ASSOC)){
        $interval=$row['interval'];
	$count=$row['count(*)'];
        $selected="";
        if ($_POST['interval'] == $interval) $selected="selected";
        $options=$options."<option value='$interval' $selected>$interval ($count scripts)</option>\n";
}
$interval=$_POST['interval'];
$disabled=$_POST['disabled'];
$type=$_POST['type'];
$optimize=$_POST['optimize'];
$optimizecheck="CHECKED";
if (!empty($_POST) && empty($optimize)) $optimizecheck=""; 
if ($type=="cron")  { 
	$optimizestyle="";
} else {
	$optimizestyle="none";
}
        switch($disabled) {
                case "omit":
                        $disabledstr=" and disabled='0'";
                        $omitradio="CHECKED";
                        break;
                case "comment":
                        $commentradio="CHECKED";
                        break;
                default:
                        $includeradio="CHECKED";
        }
        switch($type) {
                case "cron":
                        $cronradio="CHECKED";
                        break;
                case "text":
                        $textradio="CHECKED";
                        break;
		 default:
                        $textradio="CHECKED";

        }

if (!empty($_POST)) {
	$result=$db->query("SELECT command, disabled FROM scripts_tbl where interval='$interval' $disabledstr");
	$outputstr="<pre>";
	if ($type=="cron" && ($optimize=="" || $interval==1)) {
		while($row=$result->fetch(SQLITE_NUM)){
			$command=$row[0];
			if (!strstr($command, "/dev/null")) $command=$command." > /dev/null 2>&1";
			$isdisabled=$row[1];      
			if ($disabled=="comment" && $isdisabled==1) $comment="#"; 
			$outputstr.="$comment*/$interval * * * * $command\n";
			$comment=""; 
		}
	} elseif ($type=="text") {
		while($row=$result->fetch(SQLITE_NUM)){
                        $command=$row[0];
                        $isdisabled=$row[1];
                        if ($disabled=="comment" && $isdisabled==1) $comment="#";
                        $outputstr.="$comment$command\n";
                        $comment="";
                }
		
	} else {
       		$intervalhour=60/$interval;
        	$variations=$interval-1;
        	$b=0;
        	#echo "$interval|$intervalhour|$variations";
        	for($i=0;$i<=$variations;$i++) {
                	$b=0;
                	$min=$i;
	                while($b<=60 && $min<=60) {
        	                $mins[]=$min;
                	        $b=$b+$interval;
                        	$min=$i+$b;
	                }
        	        $minstr=implode(",", $mins);
                	$minarr[]=$minstr;
	                $mins=array();
        	}
	        #print_r($minarr);
        	$i=0;
	        $variations=count($minarr)-1;
		while($row=$result->fetch(SQLITE_NUM)){
			$script=$row[0];
			if (!strstr($script, "/dev/null")) $script=$script." > /dev/null 2>&1";
			if ($disabled=="comment" && $row[1]==1) $comment="#";
        	        if ($i>$variations) $i=0;
	                $cronstr.=$comment.$minarr[$i]." * * * * $script\n";
        	        $i++;
        	}
        	$outputstr=$cronstr;
	
	}


}
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
echo "<form action='$_PHPSELF' method='post'>

<table  border=1 ><tr valign='top'><td colspan=2>Export scripts from
 Interval: <select 
name='interval'>$options</select>
</td><td><input type='radio' name='type' value='cron' $cronradio onclick=\"toggle_visibility('cron');\">As Crontab entries <input type='radio' name='type' value='text' $textradio  onclick=\"toggle_visibility('cron');\">As text&nbsp;&nbsp;<div id='cron' style=\"display:$optimizestyle\"><input type='checkbox' name='optimize' value='true' $optimizecheck> Optimize cron minutes</div></td><td>Disabled scripts: <input type='radio' name='disabled' value='omit' $omitradio>Omit <input type='radio' name='disabled' value='comment' $commentradio>Comment out <input type='radio' name='disabled' value='include' $includeradio>Include</td><td>


<input type='submit'></form></td></tr></table>
<h1>Output:</h1><pre>
$outputstr
";

?>

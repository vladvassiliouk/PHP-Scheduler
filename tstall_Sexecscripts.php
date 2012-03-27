#!/usr/bin/php5
<?
$interval=$argv[1]; //interval in minutes at which cron runs me
$sqlitebin="/ama/gmstst/bin/sqlite/sqlite";
$dbfile="/ama/gmstst/data/ALL/database/tstall_runningpids-$interval.db";
if (!file_exists($dbfile)) {
	echo "CREATE TABLE pids (pid INTEGER PRIMARY KEY, scriptid INTEGER UNIQUE,exectime DATE, count INTEGER); | $sqlitebin $dbfile";
	exec("echo \"CREATE TABLE pids (pid INTEGER PRIMARY KEY, scriptid INTEGER UNIQUE,exectime DATE, count INTEGER);\" | $sqlitebin $dbfile");
}
$hournow=date('H');
$daynow=date('N');
ini_set("register_long_arrays", "On");
function logarchive($logfile) {
        $thresh="20000000";
        $size=filesize($logfile);
        $tstamp=date("ymdHi");
        $filearr=explode("/", $logfile);
        $filename=array_pop($filearr);
        $filepath=implode("/",$filearr);
        $files=scandir($filepath);
        if ($size >$thresh) {
                rename("$filepath/$filename", "$filepath/{$filename}_$tstamp");
                touch("$filepath/$filename");
                $files=scandir($filepath);
                $i=0;
                foreach($files as $key=>$val) {
                        if (!strstr($val, $filename)) {
                                unset($files[$key]);
                        }
                }
                #clean out old files
                if (count($files)>4) {
                        $diff=count($files)-3;
                        for($i=0;$i<=$diff;$i++) {
                                $file=array_pop($files);
                                echo "deleting $file";
                                unlink($filepath."/".$file);
                        }

                }
        }
}


function logpid($pid, $scriptid) {
	global $dbfile;
	$db2=new SQLiteDatabase($dbfile, 0777);
	$db2->query("insert into pids values('$pid','$scriptid',DATETIME('NOW'),'0')");
}

function checkpid($row) {
	#CREATE TABLE pids (pid INTEGER PRIMARY KEY, scriptid INTEGER UNIQUE,exectime DATE, count INTEGER);
        global $fh; #INFO
        global $fp; #ERROR
	global $time;
	global $interval;
	global $dbfile;
	$thresh=20;
	$scriptid=$row['id'];
	$db2=new SQLiteDatabase($dbfile, 0777);
	$result2=$db2->query("select count, pid from pids where scriptid='$scriptid'");
	if ($result2->numRows()>0) {
	        $row2=$result2->fetch(SQLITE_NUM);
		$pid=$row2[1];
		$count=$row2[0];
		$minsrunning=($interval*$count)+$interval;
		echo "mins running is $minsrunning";
		$psout=exec("ps -p $pid | grep -v PID");
		if ($psout != "" && $minsrunning>=$thresh) {
			exec("kill -9 $pid");
			$db2->query("delete from pids where scriptid='$scriptid'");
			$log=$time . " WARN: Killed: (".$row['id'].") [PID:$pid] ".trim($row['command']) ." Script still running after $thresh minutes!\n";
			fwrite($fh, $log);
			fwrite($fp, $log);
			$result="killed";
		} elseif ($psout !="") {
			$count=$count+1;
			$db2->query("update pids set count=$count where scriptid='$scriptid'");
			$result="running";
		} else {
			$db2->query("delete from pids where scriptid='$scriptid'");
		}
	}
	return $result;
}

function sleep2($seconds) {
   usleep(floor($seconds*1000000));
}

if (empty($interval)) {
	echo "ERROR: interval not defined!";
	false;
	exit;
}
$startrun=microtime(true);
$db=new SQLiteDatabase("/ama/gmstst/data/ALL/database/tstall_scheduler.db");
$result=$db->query("select max(timeslot) from scripts_tbl where interval='$interval'");
$row=$result->fetchSingle();
$maxtimeslot=$row;
if ($maxtimeslot=="") exit; #If theres no timeslots theres no scripts to run, exiting.
$result=$db->query("select sleep from interval_tbl where interval='$interval'");
$row=$result->fetchSingle();
$timeout=$row;
$log="";
$infolog="/ama/gmstst/log/PRD/all/tstall_Sexecscriptsinfo{$interval}.log";
$errlog="/ama/gmstst/log/PRD/all/tstall_Sexecscriptserr{$interval}.log";
$fh=fopen($infolog, "a");
$fp=fopen($errlog, "a");
$log=date('Y-m-d H:i:s')." INFO: Starting ".$argv[0] ."\n";
fwrite($fh, $log);
for($i=0;$i<=$maxtimeslot;$i++) {
	$result=$db->query("select command, id from scripts_tbl where interval='$interval' and timeslot='$i' and disabled=0");
	#echo "slot # $i\n";
	if ($result->numRows() > 0) {
		$diff="";
		$log=date('Y-m-d H:i:s')." INFO: Starting timeslot #".$i ."\n";
		fwrite($fh, $log);
		while($row=$result->fetch(SQLITE_ASSOC)){
			$result1=$db->query("SELECT e.days, e.time1,e.time2 FROM exectime_tbl as e where e.scriptid='".$row['id']."'");
        		$row1=$result1->fetch(SQLITE_ASSOC);
        		$days=$row1['e.days'];
        		$time1=$row1['e.time1'];
        		$time2=$row1['e.time2'];
			if (!empty($days) && !strstr($days, $daynow)) {
				$time=date('Y-m-d H:i:s');
				$log=$time . " INFO: Skipped: (".$row['id'].") ".trim($row['command']) ." -- timeframe: ($time1-$time2 $days)\n";
                                fwrite($fh, $log);
				continue;
			}
			if ((!empty($time1) && !empty($time2)) && ($time1>$hournow || $time2<$hournow)) {
				$time=date('Y-m-d H:i:s');
				$log=$time . " INFO: Skipped: (".$row['id'].") ".trim($row['command']) ." -- timeframe: ($time1-$time2 $days)\n";
                                fwrite($fh, $log);
				continue;
			}

			if (!empty($row['command'])) {
				$startms=microtime(true);
				$time=date('Y-m-d H:i:s');
				unset($stdout);
				$wat=checkpid($row);
				if ($wat=="" || $wat=="killed") {
					exec("{ ".trim($row['command'])."; }>/dev/null & echo $!",$stdout,$retval);
					$pid=$stdout[0];
					logpid($pid, $row['id']);
				} elseif ($wat=="running") {
					$log=$time . " WARN: Skipped: (".$row['id'].") [PID:$pid] ".trim($row['command']) ." Script still running!!\n";
					fwrite($fh, $log);
					fwrite($fp, $log);
					continue;
				}
				if ($retval ==0) {
					$log=$time . " INFO: exec(".$row['id'].") [PID:$pid] ".trim($row['command']) ."\n";
					fwrite($fh, $log);
				} else {
					$log=$time . " ERROR: exec(".$row['id'].") ".trim($row['command']) ."\n";
					fwrite($fh, $log);
					if (!empty($stdout)) {
						$stdoutstr=implode("\n",$stdout);
						$log.=" OUTPUT:\n$stdoutstr\n";
					}
					#fwrite($fp, $log);
				}
				$stopms=microtime(true);
				$diff=$diff+($stopms-$startms);
			}

		}
        	#echo $diff;
		$log=date('Y-m-d H:i:s')." INFO: Ended timeslot #".$i. " elapsed time: ". $diff ."\n";
		fwrite($fh, $log);
        	$sleep=$timeout-$diff;
		#echo "sleeping for". $sleep ."\n";
		if ($maxtimeslot >1 && $sleep > 0 && $i != $maxtimeslot ) {
			$log=date('Y-m-d H:i:s')." INFO: Sleeping for $sleep\n";
			fwrite($fh, $log);
			sleep2($sleep);
		}
	} else {
		$log=date('Y-m-d H:i:s')." INFO: No scripts for timeslot #".$i."\n";
	        fwrite($fh, $log);
	}
}
$endrun=microtime(true);
$elapsedrun=round($endrun - $startrun,2);
$startruntime=date('H:i:s', $startrun);
$endruntime=date('H:i:s', $endrun);
#echo "star=$startruntime, $endruntime, elapsed=$elapsedrun secs";
$log=date('Y-m-d H:i:s')." INFO: End: ".$argv[0] ." $startruntime-$endruntime took {$elapsedrun}s\n";
fwrite($fh, $log);
$tmplog="/tmp/tstall_tmpexecscriptserr".$argv[1].".log";
$tmperr=fopen($tmplog, "r");
$errors=fread($tmperr,"102400");
if (!empty($errors)) {
	$log=date('Y-m-d H:i:s') . " ERROR: Errors captured while executing scripts for interval ".$argv[1]." between $startruntime-$endruntime\n";
	$log.="--START--\n";
	$log.=$errors;
	$log.="--END--\n";
	#fwrite($fp, $log);
}
fclose($tmperr);
fclose($fp);
fclose($fh);
?>

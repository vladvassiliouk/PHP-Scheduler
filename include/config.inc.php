<?
#error_reporting(E_ALL);
#ini_set('display_errors', '1');
$imgpath="images";
$db=new SQLiteDatabase("/ama/gmstst/data/ALL/database/tstall_scheduler.db", 0777);
function balancescripts($interval) {
	echo "Balancing scripts for $interval minutes interval<br>";
	global $db;
	$result2=$db->query("select interval, sleep from interval_tbl where interval='$interval'");
	$row2=$result2->fetch(SQLITE_NUM);
	$interval2=$row2['0']*60;
	$sleep=$row2['1'];
	$maxslots=floor($interval2 / $sleep);

	$result=$db->query("select command,disabled from scripts_tbl where interval='$interval'");
	while($row=$result->fetch(SQLITE_NUM)){
		$pound="";
		if ($row[1]==1) $pound="#";
		$scriptsarr[]=$pound.$row[0]; #collect all scripts into an array
	}
	if ($result->numRows() > 0) {
		$db->query("delete from scripts_tbl where interval='$interval'");
		$scriptsarr=array_reverse($scriptsarr);
		$i=0;
		global $commentlines;
		$commentlines="on";
		foreach($scriptsarr as $key => $line) {
			if ($i==$maxslots) $i="0";
			$scriptsarr=insertcmd($scriptsarr, $i, "", $interval);
			$i++;
		}
		echo "Finished rebalancing scripts.<br>";
	}
}
function sqlitestr($str) {
        $str=str_replace("'", "''", $str); #must escape quote in sqlite with another quote
        return $str;
}



function insertcmd($scriptsarr, $timeslot, $curts, $curint) {
        global $db;
	global $commentlines;
	$disabledstr="0";
        if ($curts!="") $timeslot=$curts;
        $cmd=sqlitestr(trim(array_pop($scriptsarr)));
	if ($commentlines=="on") {
		if (substr($cmd,0,1)=="#") {
			$cmd=str_replace("#", "", $cmd);
			$disabledstr="1";
		}
	}
        $result2=$db->query("select command from scripts_tbl where command='$cmd'");
        $dupe=$result2->fetchSingle();
        if (empty($dupe) && !empty($cmd)) {
                echo "Inserting $cmd into #$timeslot timeslot for $curint interval<br>";
                $db->query("BEGIN;
                insert into scripts_tbl (id, command, interval, timeslot, disabled) values(NULL, '$cmd', '$curint', '$timeslot', '$disabledstr');
                COMMIT;");
                #echo "insert into scripts_tbl (id, command, interval, timeslot) values(NULL, '$cmd', '$curint', '$timeslot');";

        } elseif (!empty($dupe) && !empty($cmd)) {
                echo "Dupe found! $dupe<br>";
                while(!empty($dupe)) {
                        $cmd=trim(array_pop($scriptsarr));
                        $result2=$db->query("select command from scripts_tbl where command='$cmd'");
                        $dupe=$result2->fetchSingle();
                        if (empty($dupe)) {
                                echo "Inserting $cmd into #$timeslot timeslot for $curint interval<br>";
                                $db->query("BEGIN;
                                insert into scripts_tbl (id, command, interval, timeslot, disabled) values(NULL, '$cmd', '$curint', '$timeslot', '$disabledstr');
                                COMMIT;");
                        } else {
                                echo "Dupe found! $dupe<br>";
                        }
                }
        } elseif (empty($cmd) && !empty($scriptsarr)) {
                 while(empty($cmd) && !empty($scriptsarr)) {
                        $cmd=trim(array_pop($scriptsarr));
                        $result2=$db->query("select command from scripts_tbl where command='$cmd'");
                        $dupe=$result2->fetchSingle();
                        if (empty($dupe) && !empty($cmd)) {
                                echo "Inserting $cmd into #$timeslot timeslot for $curint interval<br>";
                                $db->query("BEGIN;
                                insert into scripts_tbl (id, command, interval, timeslot, disabled) values(NULL, '$cmd', '$curint', '$timeslot', '$disabledstr');
                                COMMIT;");
                        } elseif (!empty($dupe)) {
                                echo "Dupe found! $dupe<br>";
                                while(!empty($dupe)) {
                                        $cmd=trim(array_pop($scriptsarr));
                                        $result2=$db->query("select command from scripts_tbl where command='$cmd'");
                                        $dupe=$result2->fetchSingle();
                                        if (empty($dupe)) {
                                                echo "Inserting $cmd into #$timeslot timeslot for $curint interval<br>";
                                                $db->query("BEGIN;
                                                insert into scripts_tbl (id, command, interval, timeslot, disabled) values(NULL, '$cmd', '$curint', '$timeslot', '$disabledstr');
                                                COMMIT;");
                                        } else {
                                                echo "Dupe found! $dupe<br>";
                                        }

                                }
                        }
                }

        }
        return $scriptsarr;
}

?>

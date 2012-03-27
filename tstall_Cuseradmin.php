<?
if ($_GET['log']=='on' && !isset($_SERVER['PHP_AUTH_USER'])) {
        Header( 'WWW-authenticate:  basic  realm="POSTing Restricted"');
        Header( "HTTP/1.0  401  Unauthorized");
} elseif ($_GET['log']=='off') {
        Header( 'WWW-authenticate:  basic  realm="POSTing Restricted"');
        Header( "HTTP/1.0  401  Unauthorized");
        unset($_SERVER['PHP_AUTH_USER']);
}
include("include/config.inc.php");
include("include/top.inc.php");

if ($_SERVER['PHP_AUTH_USER'] && $_GET['log']=='on') {
        echo "<h3>You are now logged in as ".$_SERVER['PHP_AUTH_USER']."</h3>";
} elseif ($_SERVER['PHP_AUTH_USER']=="" && $_GET['log']=='off')  {
        echo "<h3>You have been logged off. Goodbye!</h3>";
	exit;
}
if (!empty($_SERVER['PHP_AUTH_USER'])) {

if (!empty($_POST) && !empty($_POST['newuser']) && !empty($_POST['pw']) && $adminuser==1) { #Create new user
	$user=$_POST['newuser'];
	$pass=$_POST['pw'];
	$cmd="/usr/sbin/htpasswd2 -b .passwd $user $pass";
	$outputarr=array();
        $str=exec($cmd, $outputarr, $retval);
        echo "$user created.<br>";

}

if (!empty($_POST) && !empty($_POST['deluser']) && $adminuser==1) { #delete a user 
        $user=$_POST['deluser'];
        $cmd="/usr/sbin/htpasswd2 -D .passwd $user";
        $outputarr=array();
        $str=exec($cmd, $outputarr, $retval);
        echo "$user Deleted.<br>";

}

if (!empty($_POST) && !empty($_POST['user']) &&  !empty($_POST['pw']) && $adminuser==1) { #change a users PW
        $user=$_POST['user'];
	$pass=$_POST['pw'];
        $cmd="/usr/sbin/htpasswd2 -b .passwd $user $pass";
        $outputarr=array();
        $str=exec($cmd, $outputarr, $retval);
        echo "Password changed for $user<br>";

}
 
if (!empty($_POST) && !empty($_SERVER['PHP_AUTH_USER']) && !empty($_POST['newpw1']) && !empty($_POST['newpw2'])) { # change ur pw
        $user=$_SERVER['PHP_AUTH_USER'];
        $pass1=$_POST['newpw1'];
        $pass2=$_POST['newpw2'];
        if ($pass1==$pass2) {
        $cmd="/usr/sbin/htpasswd2 -b .passwd $user $pass1";
        $outputarr=array();
        $str=exec($cmd, $outputarr, $retval);
        echo "Your pw has been changed.<br>";
        } else {
                echo "PW did not match, no changes made!";
        }
}


echo "<h3>Change your PW</h3><form method='post' action='tstall_Cuseradmin.php'>New password:<input type='password' name='newpw1'><br>
Confirm password:<input type='password' name='newpw2'><br>
<input type='submit'>
</form>";

if ($adminuser==1) {
$userarr=file(".passwd");
$userhtm="<table border=1>";
foreach($userarr as $key=>$val) {
	$pieces=explode(":", $val);
	$user=$pieces[0];
	$userhtm.="<tr><td>$user</td><td><a href='#' onclick='del(\"$user\")'>Del</a> <a href='#' onclick='chpass(\"$user\")'>Change pw</a></td></tr>";
}
$userhtm.="</table>";
echo "<script>

function del(user) {
	var answer = confirm(\"Are you sure you want to DELETE \"+user);
        if (answer) {
                document.deluser.deluser.value=user;
                document.deluser.submit();
        }	
}
</script>
<script>
function chpass(user) {
	var answer1 = prompt (\"Enter new Password for \"+user);
	var answer2 = prompt (\"Re-Enter new Password for \"+user);
	if (answer1 == answer2) {
		document.chpw.user.value=user;
		document.chpw.pw.value=answer1;
        	document.chpw.submit();
	} else {
		alert('Passwords did not match!');
	}
}
</script>
<form method='post' action='tstall_Cuseradmin.php' name='chpw' onSubmit='return false;'>
<input type='hidden' name='user' value=''>
<input type='hidden' name='pw' value=''>
</form>

<form method='post' action='tstall_Cuseradmin.php' name='deluser' onSubmit='return false;'>
<input type='hidden' name='deluser' value=''>
</form>

";
echo "<h3>User Admin</h3>";

echo "<form method='post' action='tstall_Cuseradmin.php'>Add User:<br>
Username:<input type='text' name='newuser'><br>
Pass:<input type='password' name='pw'><br>
<input type='submit'>
</form>
<br><br>
Current Users:<br>
$userhtm
";

}


} else {
	echo "<h3>You must be logged in</h3>";	
}
?>

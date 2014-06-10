<?PHP

/*
	Function library file for various Javascript calls to the database
*/

$mode = $_REQUEST['mode'];
$dbcnx = 0;

function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error());
	//mysqli_select_db($mysql_database, $dbcnx);
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

function clone_entry() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
		{
			$id = $_REQUEST['id'];
			$query = "SELECT * from `os` WHERE `id` = '$id'"; // Get the selected flavor config template
			$result = mysqli_query($dbcnx,$query);
			
			if (!$result) {
				die('MySQL query Error: ' . mysqli_error($dbcnx));
			}		
			$row = mysqli_fetch_array($result);
			$name = "Cloned ".$row['name'];
			$template = $row['template'];
			$version = $row['version'];
			$location = $row['location'];
			$can_plesk = $row['can_plesk'];
			$can_cpanel = $row['can_cpanel'];
			$flavor = $row['flavor'];
			$comment = $row['comment'];
			
			$query = "INSERT INTO `os` (name,version,location,wim,can_plesk,can_cpanel,flavor,comment,clone,parent) VALUES('$name','$version','$location','','$can_plesk','$can_cpanel','$flavor','$comment','1','$id')";
			$result = mysqli_query($dbcnx,$query);
			
			if (!$result) {
				die('MySQL query Error: ' . mysqli_error($dbcnx));
			}
		}
	exit();
}

function get_script() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
		{
			$id = $_REQUEST['id'];
			$query = "SELECT * from `panels` WHERE `id` = '$id'"; // Get the selected flavor config template
			$result = mysqli_query($dbcnx,$query);
			
			if (!$result) {
				die('MySQL query Error: ' . mysqli_error($dbcnx));
			}		
			
			$row = mysqli_fetch_array($result);
			$return = $row['name'];
			$return .= "|".$row['script'];
			echo "$return";
		}
	exit();
}

function get_comment() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
		{
			$id = $_REQUEST['id'];
			$query = "SELECT * from `os` WHERE `id` = '$id'"; // Get the selected flavor config template
			$result = mysqli_query($dbcnx,$query);
			$row = mysqli_fetch_array($result);
			$return = $row['comment'];
			echo "$return";	
		}
	exit();
}

function get_info() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
		{
			$id = $_REQUEST['id'];
			$query = "SELECT * from `os` WHERE `id` = '$id'"; // Get the selected flavor config template
			$result = mysqli_query($dbcnx,$query);
			
			if (!$result) {
				die('MySQL query Error: ' . mysqli_error($dbcnx));
			}		
			
			$row = mysqli_fetch_array($result);
			
			$name = $row['flavor'];
			
			$query = "SELECT * from `flavor` WHERE `name` = '$name'";
			$result = mysqli_query($dbcnx,$query);
			
			$row1 = mysqli_fetch_array($result);
			
			$return = $row['name'];
			$return .= "|".$row['version'];
			$return .= "|".$row['can_plesk'];
			$return .= "|".$row['can_cpanel'];
			$return .= "|".$row1['id'];
			$return .= "|".$row['comment'];
			$return .= "|".$row['wim'];
			//$return .= "|".$row1['template'];
			//$return .= "|".$row1['config'];
			//$return .= "|".$row1['uefi'];
			echo "$return";
		}
	exit();
}

function get_data() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
	{
		$id = $_REQUEST['id'];
		$query = "SELECT * from `os` WHERE `id` = '$id'";
		$result = mysqli_query($dbcnx,$query);
		
		if (!$result) {
			die('MySQL query Error: ' .mysqli_errpr($dbcnx));
		}
		$row = mysqli_fetch_array($result);
		$flavor = $row['flavor'];
		
		$query = "SELECT * from `flavor` WHERE `name` = '$flavor'";
		$result = mysqli_query($dbcnx,$query);
		
		if (!$result) {
			die('MySQL query Error: ' .mysqli_errpr($dbcnx));
		}
		$row = mysqli_fetch_array($result);
		$return = $row['use_ks'];
		$return .= "|".$row['use_preseed'];
		$return .= "|".$row['use_bsd'];
		$return .= "|".$row['use_xen'];
		$return .= "|".$row['use_unattended'];
		echo "$return";
	}
	exit();
}

function get_template() {
	global $dbcnx;
	db_connect();
	if ($_REQUEST['id'] != "")
		{
			$id = $_REQUEST['id'];
			$query = "SELECT * from `flavor` WHERE `id` = '$id'"; // Get the selected flavor config template
			$result = mysqli_query($dbcnx,$query);
			
			if (!$result) {
				die('MySQL query Error: ' . mysqli_error($dbcnx));
			}		
			
			$row = mysqli_fetch_array($result);
			$return = $row['template'];
			$return .= "|".$row['config'];
			$return .= "|".$row['uefi'];
			$return .= "|".$row['use_ks'];
			$return .= "|".$row['use_preseed'];
			$return .= "|".$row['use_bsd'];
			$return .= "|".$row['use_xen'];
			$return .= "|".$row['use_unattended'];
			echo "$return";
		}
	exit();
}

function main() { exit(); }

switch($mode) {

	case "data":
	get_data();
	break;
	
	case "clone":
	clone_entry();
	break;

	case "script":
	get_script();
	break;
	
	case "comment":
	get_comment();
	break;	

	case "info":
	get_info();
	break;	
	
	case "template":
	get_template();
	break;
	
	default:
	main();
	break;
}

?>
<?PHP

/*
	Upload processor
*/

$osurl = $_REQUEST['osurl'];
$wimurl = $_REQUEST['wimurl'];

if ($osurl == "" && $_FILES['osfile']['name'] == "" && $wimurl == "") { echo "No file URL specified or uploaded file failed to upload."; return; }

$dbcnx = 0;

function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error());
	//mysqli_select_db($mysql_database, $dbcnx);
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

$directory = "/tftpboot/boot/"; // base directory of uploaded files (need to make this a setting in config.php)

db_connect();

/* Debug Code */
//var_dump($_FILES);
//var_dump($_REQUEST);

$osname = str_replace(" ","_",$_REQUEST['osname']);
$osver = str_replace(" ","_",$_REQUEST['osver']);
$oscomment = $_REQUEST['oscomment'];
$can_plesk = $_REQUEST['can_plesk'];
$can_cpanel = $_REQUEST['can_cpanel'];
$flavor = $_REQUEST['flavor'];

if ($flavor > 0)
{
	$query = "SELECT * from `flavor` WHERE `id` = '$flavor'";
	$result = mysqli_query($dbcnx,$query);

	$row = mysqli_fetch_array($result);

	$flavorname = $row['name'];
}
else { $flavorname = $_REQUEST['flavorname']; }

if ($can_plesk == "on") { $can_plesk = 1; }
if ($can_cpanel == "on") { $can_cpanel = 1; }

$directory .= strtolower($osname) ."/";
mkdir($directory, 0777);
$directory .= $osver ."/";
mkdir($directory, 0777);

$iso_directory = $directory."iso/";
mkdir($iso_directory,0777);

$template = $_REQUEST['template'];
$config = $_REQUEST['config'];
$use_ks = $_REQUEST['use_ks'];
$use_preseed = $_REQUEST['use_preseed'];
$use_xen = $_REQUEST['use_xen'];
$use_bsd = $_REQUEST['use_bsd'];

if ($wimurl != "") { $directory = "n/a"; }

$query = "INSERT INTO `os` (name,version,comment,location,wim,can_plesk,can_cpanel,flavor) VALUES('$osname','$osver','$oscomment','$directory','$wimurl','$can_plesk','$can_cpanel','$flavorname')";
$result = mysqli_query($dbcnx,$query);

if (!$result) {
	die('MySQL query1 Error: ' . mysqli_error($dbcnx));
}

// Only insert new template IF "NEW" is selected
if ($flavor == 0)
{
	$config = mysqli_real_escape_string($dbcnx,$config);
	$template = mysqli_real_escape_string($dbcnx,$template);
	//$uefi = mysqli_real_escape_string($dbcnx,$uefi);
	$query = "INSERT INTO `flavor` (name,template,use_ks,use_preseed,use_xen,config) VALUES('$flavorname','$template','$use_ks','$use_preseed','$use_xen','$config')";
	$result = mysqli_query($dbcnx,$query);
}

if (!$result) {
	die('MySQL query2 Error: ' . mysqli_error($dbcnx));
}

/*
	URL File download
*/
if ($osurl != "")
{
	// Get filename from URL
	$filename = explode("/",$osurl);
	$filename = $filename[sizeof($filename)-1];
	
	$temp_path = "/tmp/".$filename;
	// Open file handler
	$fh = fopen($temp_path,"w");
	
	// Download file via cURL
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, str_replace(" ","%20",$osurl));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_FILE, $fh);
	curl_exec($ch); // fire off curl
	curl_close($ch); // close the handler
	fclose($fh); // close the file we were writing
	
	exec("ssh root@localhost  mv ".$temp_path." ".$directory);
	$full_path = $directory;
	$full_path .= $filename;
	
	exec("ssh root@localhost  mount -o loop ".$full_path." ".$iso_directory);
	
	if (strtolower($osname) == "ubuntu") { exec("ssh root@localhost  /root/ubuntu_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "centos") { exec("ssh root@localhost  /root/centos_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "oel") { exec("ssh root@localhost  /root/oel_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "fedora") { exec("ssh root@localhost  /root/fredora_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "debian") { exec("ssh root@localhost /root/debian_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)
	
	exec("ssh root@localhost  /root/fstab_update.sh ".$full_path." ".$iso_directory); // write iso mount to /etc/fstab to load the ISO on boot up
	echo "File download completed, ISO mounted to ".$iso_directory;
}

/*
	WIM file
*/

else if ($wimurl != "") { echo "WIM install file added to database"; }

/*
	Form file upload
*/

else if (isset($_FILES["osfile"]))
{
	if ($_FILES["osfile"]["error"] > 0)
	{
		$error = $_FILES["osfile"]["error"];
		if ($error == 1) { echo "The uploaded file exceeds the upload_max_filesize directive in php.ini."; }
		else if ($error == 2) { echo "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."; }
		else if ($error == 3) { echo "The uploaded file was only partially uploaded."; }
		else if ($error == 4) { echo "No file was uploaded."; }
		else if ($error == 6) { echo "Missing a temporary folder."; }
		else if ($error == 7) { echo "Failed to write file to disk."; }
		else { echo "PHP File Upload error code: $error"; }
	}
	else
	{
		$name = $_FILES["osfile"]["name"];
		$directory .= $name;
		if (move_uploaded_file($_FILES["osfile"]["tmp_name"],$directory))
		{
			// mount the ISO
			exec("ssh root@localhost  mount -o loop ".$directory." ".$iso_directory);
			if (strtolower($osname) == "ubuntu") { exec("ssh root@localhost  ubuntu_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "centos") { exec("ssh root@localhost  centos_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "oel") { exec("ssh root@localhost  /root/oel_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "fedora") { exec("ssh root@localhost  /root/fredora_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "debian") { exec("ssh root@localhost /root/debian_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)			
			else if (strtolower($osname) == "esxi") { exec("ssh root@localhost /root/esxi_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)			
			
			exec("ssh root@localhost  fstab_update.sh ".$directory." ".$iso_directory); // write iso mount to /etc/fstab to load the ISO on boot up
			
			echo "ISO mounted to ".$iso_directory;
		}
		else { echo "Error moving file " . $_FILES["osfile"]["tmp_name"] ." to ". $directory; }
	}
}
?>

<?PHP

/*
	Upload processor
*/

$osurl = $_REQUEST['osurl'];
$wimurl = $_REQUEST['wimurl'];

/*
	No file specified in either field abort with error message
*/
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

//$directory = "/tftpboot/boot/"; // base directory of uploaded files
// moved to setting in config.php

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

$tftproot .= strtolower($osname) ."/";
mkdir($tftproot, 0777);
$tftproot .= $osver ."/";
mkdir($tftproot, 0777);

$iso_directory = $tftproot."iso/";
mkdir($iso_directory,0777);

$template = $_REQUEST['template'];
$config = $_REQUEST['config'];
$use_ks = $_REQUEST['use_ks'];
$use_preseed = $_REQUEST['use_preseed'];
$use_xen = $_REQUEST['use_xen'];
$use_bsd = $_REQUEST['use_bsd'];

if ($wimurl != "") { $tftproot = "n/a"; }

$query = "INSERT INTO `os` (name,version,comment,location,wim,can_plesk,can_cpanel,flavor) VALUES('$osname','$osver','$oscomment','$tftproot','$wimurl','$can_plesk','$can_cpanel','$flavorname')";
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
	
	exec("sudo mv ".$temp_path." ".$tftproot);
	$full_path = $tftproot;
	$full_path .= $filename;
	
	exec("sudo mount -o loop ".$full_path." ".$iso_directory);
	
	if (strtolower($osname) == "ubuntu") { exec("sudo /root/scripts/ubuntu_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "centos") { exec("sudo /root/scripts/centos_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "oel") { exec("sudo /root/scripts/oel_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "fedora") { exec("sudo /root/scripts/fredora_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
	else if (strtolower($osname) == "debian") { exec("sudo /root/scripts/debian_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)
	
	exec("sudo /root/scripts/fstab_update.sh ".$full_path." ".$iso_directory); // write iso mount to /etc/fstab to load the ISO on boot up
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
		$tftproot .= $name;
		if (move_uploaded_file($_FILES["osfile"]["tmp_name"],$tftproot))
		{
			// mount the ISO
			exec("sudo mount -o loop ".$tftproot." ".$iso_directory);
			if (strtolower($osname) == "ubuntu") { exec("sudo ubuntu_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "centos") { exec("sudo centos_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "oel") { exec("sudo /root/scripts/oel_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "fedora") { exec("sudo /root/scripts/fredora_pxe_copy.sh ".$osver); } // copy installer kernel from ISO
			else if (strtolower($osname) == "debian") { exec("sudo /root/scripts/debian_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)			
			else if (strtolower($osname) == "esxi") { exec("sudo /root/scripts/esxi_pxe_copy.sh ".$osver); } // copy PXE Boot from Debian FTP (only place to get them)			
			
			exec("sudo fstab_update.sh ".$tftproot." ".$iso_directory); // write iso mount to /etc/fstab to load the ISO on boot up
			
			echo "ISO mounted to ".$iso_directory;
		}
		else { echo "Error moving file " . $_FILES["osfile"]["tmp_name"] ." to ". $tftproot; }
	}
}
?>

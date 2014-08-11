<?PHP
/*
	PXE Installer Admin page
	for adding new/additional ISO images

*/

$mode = $_REQUEST['mode'];
$dbcnx = 0;
$g_admin = true;
date_default_timezone_set('America/Phoenix');

/*
	SSO Functions here
*/

function auth_check() {
    global $g_admin;
    $g_admin = true;
}

/* 
	Display suite names for distro release codenames
*/

function suite_names()
{
	global $dbcnx;
	echo '
	<div class="panel panel-primary">
		<div class="panel-heading"><h5 class="panel-title">Suite Mapping (red = not available)</h5></div>
	<table class="table table-bordered table-striped">
<tr><th>Distro</th><th>Release Name</th><th>Version</th></tr>
';
	$query = "SELECT * FROM `suites` ORDER BY `distro`,`version`";
	$result = mysqli_query($dbcnx,$query);
	
	if (!$result) { die('Error retrieving suite table!'); }
	while ($row = mysqli_fetch_array($result))
	{
		$class = "";
		$distro = $row['distro'];
		$version = $row['version'];
		$suite = $row['name'];
		$supported = $row['supported'];
		if ($supported == 0) { $class = "class=\"danger\""; }
		echo "<tr $class><td>$distro</td><td>$suite</td><td>$version</td></tr>";
	}
	echo "</table></div>";
}

/*
	Display timestamp from last Mirror updates
*/

function mirror_updates()
{
	$command = "cat /var/www/html/mirrors/ubuntu/project/trace/$(hostname -f)";
	$ubuntu = exec($command);
	
	$command = "head -1 /var/www/html/mirrors/debian/project/trace/$(hostname -f)";
	$debian = exec($command);
	echo '	
	<div class="panel panel-primary">
		<div class="panel-heading"><h5 class="panel-title">Local Mirror Status</h5></div>
	<table class="table table-bordered table-striped">
	<tr><th>Mirror</th><th>Updated</th></tr>
';
	echo "<tr><td>Debian</td><td>$debian</td></tr>";
	echo "<tr><td>Ubuntu</td><td>$ubuntu</td></tr>";
	echo '</table></div>';
}

/* 
	Display CPU load
*/

function cpu_load()
{
	$command = "cat /proc/loadavg";
	$loads = explode(" ",exec($command));
	$load5 = $loads[0];
	$load10 = $loads[1];
	$load15 = $loads[2];
	
	$class = "-primary";
	
	echo "
	<div class=\"panel panel$class\" id=\"cpu_load\">
		<div class=\"panel-heading\"><h5 class=\"panel-title\">CPU Load</h5></div>
		<div class=\"panel-body\">
			<div id=\"ajaxloader3\">
			  <div class=\"outer\"></div>
			  <div class=\"inner\"></div>
			  <p class=\"text-primary text-center\">Loading...</p>
			</div>	
		</div>
	</div>
	";
}

/*
	RAM Usage/Free meter
*/
function ram_status()
{
	$class = "progress-bar";
		
	echo "
	<div class=\"panel panel-primary\" id=\"ram_status\">
		<div class=\"panel-heading\"><h5 class=\"panel-title\">Memory Usage</h5></div>
		<div class=\"panel-body\">	
			<div id=\"ajaxloader3\">
			  <div class=\"outer\"></div>
			  <div class=\"inner\"></div>
			  <p class=\"text-primary text-center\">Loading...</p>
			</div>	
		</div>
	</div>
	";
}

/*
	Disk usage/free meter
*/

function disk_status()
{
	$class = "-primary";

	echo "
	<div class=\"panel panel$class\" id=\"disk_status\">
		<div class=\"panel-heading\"><h5 class=\"panel-title\">Disk Usage</h5></div>
		<div class=\"panel-body\">		
			<div id=\"ajaxloader3\">
			  <div class=\"outer\"></div>
			  <div class=\"inner\"></div>
			  <p class=\"text-primary text-center\">Loading...</p>
			</div>	
		</div>
	</div>
	";
	
}

function page_header()
{
    global $g_admin;
	$user = "Local User";
	$user_icon = "user";
	if ($g_admin) { $user_icon = "fire"; $user_text = "text-danger"; }
	
echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<title>Universal PXE Installer Admin</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="css/bootstrap.css" rel="stylesheet">
	<link href="css/loading.css" rel="stylesheet">
	<link href="css/jquery.jqplot.min.css" rel="stylesheet">
	</head>
	<body>
	<div class="container" style="padding-top: 10px;">
		<nav class ="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-header">
				<a class="navbar-brand" href="#">PXE Installer Admin</a>
			</div>
			<p class="navbar-text navbar-right hidden-xs ';echo $user_text; echo'">Signed in as <span class=" glyphicon glyphicon-';echo $user_icon; echo'"></span> <strong>';echo $user; echo '</strong></p>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li name="add" id="add" class><a href="#"><span class="glyphicon glyphicon-cloud-upload"></span> Add</a></li>
					<li name="remove" id="remove" class><a href="#"><span class="glyphicon glyphicon-trash"></span> Remove</a></li>
					<li name="modify" id="modify" class><a href="#"><span class="glyphicon glyphicon-pencil"></span> Modifiy</a></li>
					<li name="templates" id="templates" class><a href="#"><span class="glyphicon glyphicon-list-alt"></span> Templates</a></li>
					<li name="control" id="control" class><a href="#"><span class="glyphicon glyphicon-tasks"></span> Control Panels</a></li>
					<li name="help" id="help" class><a href="#"><span class="glyphicon glyphicon-question-sign"></span> Help</a></li>
					<li name="stats" id="stats" class><a href="#"><span class="glyphicon glyphicon-stats"></span> Stats</a></li>
					<li name="status" id="status" class="active"><a href="#"><span class="glyphicon glyphicon-list"></span> Status</a></li>
				</ul>
			</div>
		</nav>
		<div class="row" style="padding-top: 70px;">
			<div class="col-md-12">
				<div id="alertHolder"></div>
			</div>
		</div>		
';
}

function page_footer()
{
echo "
	<script id=\"alertTemplate\" type=\"text/plain\">
    <div class=\"alert alert-success\">
        <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
		 <span class=\"message\"></span>
    </div>
	</script>
	<script id=\"progressbar\" type=\"text/plain\">
	<div id=\"progressOverlay\">
		<p class=\"text-info\">Uploading file...</p>
		<div class=\"progress progress-striped active\">
			<div class=\"progress-bar\" role=\"pogressbar\" aria-valuenow=\"0\" aria-valuemin=\"0\" aria-valuemanx=\"100\" id=\"progressBar\" style=\"width: 0%;\">0%</div>
		</div>
	</div>
	</script>	
	</body>
	<script src=\"//code.jquery.com/jquery-1.11.1.min.js\"></script>
	<script src=\"//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js\"></script>
	<script src=\"//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/jquery.validate.min.js\"></script>
	<script src=\"//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/additional-methods.min.js\"></script>
	<script src=\"js/admin.js\"></script>
	</html>
";
}

function os_stats()
{
	page_header();
	echo '<img src="stats.php">';
	page_footer();
}

function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error($dbcnx));
	//mysql_select_db($mysql_database, $dbcnx);
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

function update_script()
{
	global $dbcnx;
	db_connect();
	
	$id = $_REQUEST['panel'];
	$name = $_REQUEST['pname'];
	$script = $_REQUEST['pscript'];
	
	// Update existing entry
	if ($id > 0)
	{
		$script = mysqli_real_escape_string($dbcnx,$script);
		$query = "UPDATE `UPI`.`panels` SET `name` = '$name',`script` = '$script' WHERE `id` = '$id'";
		$result = mysqli_query($dbcnx,$query);
		if (!$result) { die('MySQL query Error: ' . mysqli_error($dbcnx)); }
		else { echo "Entry updated sucessfully!"; }
	}

	// New entry
	else if ($id == 0)
	{
		$script = mysqli_real_escape_string($dbcnx,$script);
		$query = "INSERT INTO `UPI`.`panels` (name,script) VALUES('$name','$script')";
		$result = mysqli_query($dbcnx,$query);
		if (!$result) { die('MySQL query Error: ' . mysqli_error($dbcnx)); }
		else { echo "Entry added sucessfully!"; }	
	}
	else { echo "Unknown error occured"; }
}

function update_template()
{
	global $dbcnx;
	db_connect();
	
	$id = $_REQUEST['tflavor'];
	$flavorname = $_REQUEST['tflavorname'];
	$template = $_REQUEST['ttemplate'];
	$config = $_REQUEST['tconfig'];
	$uefi = $_REQUEST['tuefi'];
	
	if ($_REQUEST['tuse_ks'] == "on") { $use_ks = 1; }
	else { $use_ks = 0; }
	
	if ($_REQUEST['tuse_preseed'] == "on") { $use_preseed = 1; }
	else { $use_preseed = 0; }
	
	if ($_REQUEST['tuse_bsd'] == "on") { $use_bsd = 1; }
	else { $use_bsd = 0; }
	
	if ($_REQUEST['tuse_xen'] == "on") { $use_xen = 1; }
	else { $use_xen = 0; }
	
	if ($_REQUEST['tuse_unattended'] == "on") { $use_unattended = 1; }
	else { $use_unattended = 0; }
	
	// Update existing entry
	if ($id > 0)
	{
		$config = mysqli_real_escape_string($dbcnx,$config);
		$template = mysqli_real_escape_string($dbcnx,$template);
		$uefi = mysqli_real_escape_string($dbcnx,$uefi);
		$query = "UPDATE `UPI`.`flavor` SET `name` = '$flavorname',`template` = '$template',`config` = '$config',`uefi` = '$uefi',`use_ks` = '$use_ks',`use_preseed` = '$use_preseed',`use_bsd` = '$use_bsd',`use_xen` = '$use_xen', `use_unattended` = '$use_unattended'  WHERE `id` = '$id'";
		$result = mysqli_query($dbcnx,$query);
		if (!$result) { die('MySQL query Error: ' . mysqli_error($dbcnx)); }
		else { echo "Entry updated sucessfully!"; }
	}

	// New entry
	else if ($id == 0)
	{
		$config = mysqli_real_escape_string($dbcnx,$config);
		$template = mysqli_real_escape_string($dbcnx,$template);
		$uefi = mysqli_real_escape_string($dbcnx,$uefi);
		$query = "INSERT INTO `UPI`.`flavor` (name,template,config,uefi,use_ks,use_preseed,use_bsd,use_xen) VALUES('$flavorname','$template','$config','$uefi','$use_ks','$use_preseed','$use_bsd','$use_xen')";
		$result = mysqli_query($dbcnx,$query);
		if (!$result) { die('MySQL query Error: ' . mysqli_error($dbcnx)); }
		else { echo "Entry added sucessfully!"; }	
	}
	else { echo "Unknown error occured"; }
}

function modify_os()
{
	global $dbcnx; // database connection global
	db_connect(); // connect to the database
	
	$id = $_REQUEST['modOS']; //The ID of the OS we are updating
	$osname = $_REQUEST['modosname'];
	$osver = $_REQUEST['modosver'];
	$oscomment = $_REQUEST['modoscomment'];
	$flavor = $_REQUEST['modflavor'];
	$can_cpanel = $_REQUEST['mod_can_cpanel'];
	$can_plesk = $_REQUEST['mod_can_plesk'];
	$wim = addslashes($_REQUEST['modwimlocation']);
	
	$query = "SELECT * FROM `flavor` WHERE `id` = '$flavor'";
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$flavorname = $row['name'];
	
	$query = "UPDATE `os` SET `name` = '$osname', `version` = '$osver', `comment` = '$oscomment', `can_cpanel` = '$can_cpanel', `can_plesk` = '$can_plesk', `flavor` = '$flavorname' , `wim` = '$wim' WHERE `id` = '$id'";
	$result = mysqli_query($dbcnx,$query);
		
	// If an error occured stop the script and display the error message
	if (!$result)
	{
		die('MySQL query Error: ' . mysqli_error($dbcnx));
	}
}

function restore_os()
{
	global $dbcnx; // database connection global
	db_connect(); // connect to the database
	
	$id = $_REQUEST['id']; //The ID of the OS we are nuking
	
	$query = "UPDATE `os` SET `trashed` = '0', `trashed_time` = '0' WHERE `id` = '$id'";
	$result = mysqli_query($dbcnx,$query); // Execute the query
	
	// If an error occured stop the script and display the error message
	if (!$result)
	{
		die('MySQL query Error: ' . mysqli_error($dbcnx));
	}
	echo "OS restored successfully.";
}

function force_remove_os()
{
	global $dbcnx; // database connection global
	db_connect(); // connect to the database
	
	$id = $_REQUEST['id']; //The ID of the OS we are nuking
	
	// query current database info of OS for location
	$query = "SELECT * from`os` WHERE `id` = '$id'";
	$result = mysqli_query($dbcnx,$query); // Execute the query
	
	$row = mysqli_fetch_array($result);
	$location = $row['location'];
	$iso_directory = $location."iso/";
	$clone = $row['clone'];

	$query = "DELETE from `os` WHERE `id` = '$id'"; // MySQL query to delete the selected OS
	$result = mysqli_query($dbcnx,$query); // Execute the query
	
	// If an error occured stop the script and display the error message
	if (!$result)
	{
		die('MySQL query Error: ' . mysqli_error($dbcnx));
	}
	
	/*
			If not a cloned entry we will run the removal script to remove the data from the drive
	*/
	
	if ($clone == "0")
	{
		exec("ssh root@localhost  /root/remove_os.sh ".$location." ".$iso_directory);
	}
	echo "OS removed successfully.";
}

function remove_os()
{
	global $dbcnx; // database connection global
	db_connect(); // connect to the database
	
	$id = $_REQUEST['os']; //The ID of the OS we are nuking
	
	// query current database info of OS for location
	$query = "SELECT * from`os` WHERE `id` = '$id'";
	$result = mysqli_query($dbcnx,$query); // Execute the query
	
	$row = mysqli_fetch_array($result);
	$location = $row['location'];
	$iso_directory = $location."iso/";
	
	$ctime = time();
	
	$query = "UPDATE `os` SET `trashed` = '1', `trashed_time` = '$ctime' WHERE `id` = '$id'";
	//$result = mysqli_query($dbcnx,$query);
		
	//$query = "DELETE from `os` WHERE `id` = '$id'"; // MySQL query to delete the selected OS
	$result = mysqli_query($dbcnx,$query); // Execute the query
	
	// If an error occured stop the script and display the error message
	if (!$result)
	{
		die('MySQL query Error: ' . mysqli_error($dbcnx));
	}
	// execute umount/delete bash script here
	//exec("ssh root@localhost  /root/remove_os.sh ".$location." ".$iso_directory);

}

function main()
{
	sso_init();
	auth_check();
	page_header();
	global $dbcnx, $SSO_UserData, $g_admin,$services;
	db_connect(); // Connect to database
	
	/*
		Disable some functionality if use is deemed "admin" or not
	*/
	
	$disabled = "disabled";
	if ($g_admin) { $disabled = ""; }
	
	echo '
	<div id="mainCarousel" class="carousel slide">
		<div class="carousel-inner">
			<div id="addPage" class="item">
				<form class="form-horizontal" id="uploadForm" name="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
						<div class="panel panel-primary">
							<div class="panel-heading"><h3 class="panel-title">Add an OS</h3></div>
							<div class="panel-body">
								<div class="form-group">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Prefer short names/names without spaces ie: OEL (Oracle Enterprise Linux)">OS Name</a></label>
									<div class="col-sm-4">
										<input type="text" id="osname" class="form-control" name="osname" placeholder="ie: CentOS, ESXi, XenServer, etc" value="' . $_REQUEST['osname'] . '">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">OS Version</label>
									<div class="col-sm-4">
										<input type="text" id="osver" class="form-control" name="osver" placeholder="ie: 6.4,5.9,5.1" value="' . $_REQUEST['osver'] . '">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">OS Comments</label>
									<div class="col-sm-8">
										<input type="text" class="form-control" id="oscomment" name="oscomment" placeholder="ie: Ubuntu 12.04 (Precise Pangolin)" value="' . $_REQUEST['oscomment'] . '">
									</div>
								</div>				
								<div class="form-group ">
									<label class="control-label col-sm-4">Available Control Panel(s)</label>
									<div class="col-sm-4">
										<input type="checkbox" id="can_cpanel" name="can_cpanel" value="1"> cPanel<br>
										<input type="checkbox" id="can_plesk" name="can_plesk" value="1"> Plesk<br>
									</div>
								</div>
								<div class="form-group ">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="TFTP/Automated Install scripts. Select one or create a new one.">Template</a></label>
									<div class="col-sm-4">
										<select name="flavor" id="flavor" class="form-control">
										';
										$query = "SELECT * from `flavor` ORDER BY `name`";
										$result = mysqli_query($dbcnx,$query);
										
										if (!$result) {
											die('MySQL query Error: ' . mysqli_error($dbcnx));
										}		
										
										while ($row1 = mysqli_fetch_array($result))
										{
											$id = $row1['id'];
											$string = '<option value="'.$id.'"';
											$string .= '>'.$row1['name'].'</option>';
											echo "$string\n";
										}
										echo '
										<option value="0" selected>NEW</option>
										</select>
									</div>		
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">Template Name</label>
									<div class="col-sm-4">
										<input type="text" id="flavorname" class="form-control" name="flavorname" placeholder="ie: centos,esxi,xenserver">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="bottom" title data-original-title="Boot file that starts the installer kernel">PXELinux Template</a></label>
									<div class="col-sm-8">
										<textarea id="template" class="form-control" name="template" rows="20" class="col-md-7"> </textarea>
									</div>
								</div>		
								<div class="form-group">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Automation script that answers the installer questions">Automation Template</a></label>
									<div class="col-sm-8">
										<textarea id="config" class="form-control" name="config" rows="20" class="col-md-7"> </textarea>
									</div>
								</div>	
								<div class="form-group ">
									<label class="control-label col-sm-4">Template Type</label>
									<div class="col-sm-4">
										<input type="checkbox" id="use_ks" name="use_ks"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="Used for Anaconda installers (ie: CentOS, Oracle, Ubuntu, ESXi...">KickStart</a><br>
										<input type="checkbox" id="use_preseed" name="use_preseed"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="Mainly only used on Debian">PreSeed</a><br>
										<input type="checkbox" id="use_bsd" name="use_bsd"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For BSD PC-SYSINSTALL">BSD</a><br>
										<input type="checkbox" id="use_xen" name="use_xen"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For Xen Server XML">Xen</a><br>
										<input type="checkbox" id="use_unattended" name="use_unattended"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For Windows unattended XML">Windows</a><br>
									</div>
								</div>		
								<!--  style="background-color:#bce8f1" -->
								<div class="panel panel-danger" style="background-color:#CCCCFF" >
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Used to download DVD ISO images">URL</a></label>
										<div class="col-sm-8">
											<input type="text" id="osurl" class="form-control" name="osurl" placeholder="Enter a URL">
										</div>
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Name of WIM file (ie: W2k8_STD_R2_091913.wim)">WIM</a></label>
										<div class="col-sm-8">
											<input type="text" id="wimurl" class="form-control" name="wimurl" placeholder="Enter WIM Filename">
										</div>									
									</div>		
									<div class="form-group"><div class="controls col-md-6"><h4>---OR---</h4></div></div>
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Only used to upload CD ISO images (file size limit < 1GB)">Local File</a></label>
										<div class="col-md-4">
											<input type="file" id="osfile" name="osfile">
										</div>
									</div>
									<div id="uploadStatus"></div>
								</div>				
							</div>
						</div>
					</div>
				</div>
					
				<div class="row" style="padding-left: 10px; padding-bottom: 50px;">
					<div class="col-md-10">
						<button type="reset" id="reset" class="btn btn-danger pull-left">
							<span class="glyphicon glyphicon-trash"></span> Clear
						</button>
						<button type="submit" class="btn btn-primary pull-right" id="submit">
								<span class="glyphicon glyphicon-cloud-upload"></span> Upload
						</button>
					</div>
				</div>	
				</form>
			</div>
			<div id="removePage" class="item">
				<form class="form-horizontal" name="removeForm" id="removeForm" action="admin.php" method="post">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
							<div class="panel panel-primary">
								<div class="panel-heading"><h3 class="panel-title">Remove OS</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Operating system to remove">Remove OS</a></label>
										<div class="col-sm-4">
											<select name="os" id="os" class="form-control">
											';
											$query = "SELECT * from `os` WHERE `trashed` = 0 ORDER BY `name`,`version`";
											$result = mysqli_query($dbcnx,$query);
											
											while ($row1 = mysqli_fetch_array($result))
											{
												$nice_name = $row1['name'] . " " . $row1['version'];
												$id = $row1['id'];
												
												$string = '<option value="'.$id.'">'.$nice_name.'</option>';
												
												echo "$string\n";
											}
											echo '
											</select>
										</div>
										<div id="osComment"></div>
									</div>			
								</div>
							</div>
						</div>
					</div>
					<div class="row" style="padding-left: 10px; padding-bottom: 50px;">
						<div class="col-md-10">
							<button id=mRemove class="btn btn-warning pull-right" type="button"'. $disabled .'>
								<span class="glyphicon glyphicon-trash"></span> Remove
							</button>
						</div>
					</div>	
					<div id="mConfirm" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="confirmLabel" aria-hidden="true">
						<div class="modal-dialog">
							<div class="modal-content">	
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									<h3 id="myModalLabel">Confirm OS Removal</h3>
								</div>
								<div class="modal-body">
									<p class="message">Be advised this will remove this OS from the PXE Installer</p>
								</div>
								<div class="modal-footer">
									<input type="hidden" id="mode" name="mode" value="remove">
									<button class="btn" data-dismiss="modal" aria-hidden="true">
										<span class="glyphicon glyphicon-ban-circle"></span> Cancel
									</button>
									<button type="submit" class="btn btn-danger" id="confirm">
										<span class="glyphicon glyphicon-ok"></span> Confirm
									</button>
								</div>
							</div>
						</div>
					</div>					
				</form>				
				<form class="form-horizontal" id="restoreFom" action="admin.php" method="post">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
							<div class="panel panel-primary">
								<div class="panel-heading"><h3 class="panel-title">Restore OS</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Operating system to restore from the bit bucket">Restore OS</a></label>
										<div class="col-sm-4">
											<select name="r_os" id="r_os" class="form-control">
											';
											$query = "SELECT * from `os` WHERE `trashed` = 1 ORDER BY `name`,`version`";
											$result = mysqli_query($dbcnx,$query);
											
											if (mysqli_num_rows($result) > 0)
											{
												while ($row1 = mysqli_fetch_array($result))
												{
													$nice_name = $row1['name'] . " " . $row1['version'];
													$id = $row1['id'];
													
													$string = '<option value="'.$id.'">'.$nice_name.'</option>';
													
													echo "$string\n";
												}
											}
											else { echo '<option value="0">No entries</option>'; }
											echo '
											</select>
										</div>
									</div>			
									<p class="col-md-4"></p>
									<p class="col-sm-8">Auto deleted after &#x221e; days</p>
									<p class="col-sm-12"></p>
								</div>
							</div>
						</div>
					</div>
					<div class="row" style="padding-left: 10px; padding-bottom: 50px;">
						<div class="col-md-10">
							<button id="mForceDel" class="btn btn-danger pull-left" type="button"'. $disabled .'>
								<span class="glyphicon glyphicon-floppy-remove"></span> Force Delete
							</button>
							<input type="hidden" id="mode" name="mode" value="restore"'. $disabled .'>
							<button id=mRestore class="btn btn-success pull-right" type="button">
								<span class="glyphicon glyphicon-thumbs-up"></span> Restore
							</button>
						</div>
					</div>	
				</form>					
			</div>
			<div id="modifyPage" class="item">
				<form class="form-horizontal" id="modifyForm" action="admin.php" method="post">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
							<div class="panel panel-primary">
								<div class="panel-heading"><h3 class="panel-title">Modify OS</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Operating system to modify">Modify OS</a></label>
										<div class="col-sm-4">
											<select name="modOS" id="modOS" class="form-control">
											';
											$query = "SELECT * from `os` WHERE `trashed` = 0 ORDER BY `name`,`version`";
											$result = mysqli_query($dbcnx,$query);
											
											while ($row1 = mysqli_fetch_array($result))
											{
												$nice_name = $row1['name'] . " " . $row1['version'];
												$id = $row1['id'];
												
												$string = '<option value="'.$id.'">'.$nice_name.'</option>';
												
												echo "$string\n";
											}
											echo '
											<option value="0" selected>Select an OS</option>
											</select>
										</div>
									</div>			
								</div>
							</div>
						</div>
					</div>
				
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
						<div class="panel panel-primary">
							<div class="panel-body">
								<div class="form-group ">
									<label class="control-label col-sm-4">OS Name</label>
									<div class="col-sm-4">
										<input type="text" class="form-control" id="modosname" name="modosname">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">OS Version</label>
									<div class="col-sm-4">
										<input type="text" class="form-control" id="modosver" name="modosver">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">OS Comments</label>
									<div class="col-sm-8">
										<input type="text" class="form-control" id="modoscomment" name="modoscomment">
									</div>
								</div>				
								<div class="form-group ">
									<label class="control-label col-sm-4">Available Control Panel(s)</label>
									<div class="col-sm-4">
										<input type="checkbox" id="mod_can_cpanel" name="mod_can_cpanel" value="1"> cPanel<br>
										<input type="checkbox" id="mod_can_plesk" name="mod_can_plesk" value="1"> Plesk<br>
									</div>
								</div>
								<div class="form-group ">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="TFTP/Automated Install scripts.">Template</a></label>
									<div class="col-sm-4">
										<select name="modflavor" id="modflavor" class="form-control">
										';
										$query = "SELECT * from `flavor` ORDER BY `name`";
										$result = mysqli_query($dbcnx,$query);
										
										if (!$result) {
											die('MySQL query Error: ' . mysqli_error($dbcnx));
										}		
										
										while ($row1 = mysqli_fetch_array($result))
										{
											$id = $row1['id'];
											$string = '<option value="'.$id.'"';
											$string .= '>'.$row1['name'].'</option>';
											echo "$string\n";
										}
										echo '
										<option value="0" selected>n/a</option>
										</select>
									</div>		
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Name of WIM file (ie: W2k8_STD_R2_091913.wim)">WIM Filename</a></label>
									<div class="col-sm-8">
										<input type="text" class="form-control" id="modwimlocation" name="modwimlocation">
									</div>
								</div>								
							</div>
					</div>
				</div>
					
				<div class="row" style="padding-left: 15px; padding-bottom: 50px;">
					<div class="col-md-10">
						<input type="hidden" id="mode" name="mode" value="modify">
						<button class="btn btn-warning pull-left" id="modclone">
							<span class="glyphicon glyphicon-export"></span> Clone Entry
						</button>
						<button type="submit" class="btn btn-primary pull-right" id="modsubmit">
							<span class="glyphicon glyphicon-pencil"></span> Update Entry
						</button>
					</div>
				</div>	
				</form>
			</div>
			</div>
			<div id="templatePage" class="item">
				<form class="form-horizontal" id="templateForm" action="admin.php" method="post">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
						<div class="panel panel-primary">
							<div class="panel-heading"><h3 class="panel-title">Add/Modify Template</h3></div>
							<div class="panel-body">
								<div class="form-group ">
									<div class="form-group ">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="TFTP/Automated Install scripts. Select one or create a new one.">Template</a></label>
										<div class="col-sm-4">
											<select name="tflavor" id="tflavor" class="form-control">
											';
											$query = "SELECT * from `flavor` ORDER BY `name`";
											$result = mysqli_query($dbcnx,$query);
											
											if (!$result) {
												die('MySQL query Error: ' . mysqli_error($dbcnx));
											}		
											
											while ($row1 = mysqli_fetch_array($result))
											{
												$id = $row1['id'];
												$string = '<option value="'.$id.'"';
												$string .= '>'.$row1['name'].'</option>';
												echo "$string\n";
											}
											echo '
											<option value="0" selected>NEW</option>
											</select>
										</div>		
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Template Name</label>
										<div class="col-sm-4">
											<input type="text" id="tflavorname" class="form-control" name="tflavorname" placeholder="ie: centos,esxi,xenserver">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="bottom" title data-original-title="Boot file that starts the installer kernel">PXELinux Template</a></label>
										<div class="col-sm-8">
											<textarea id="ttemplate" class="form-control" name="ttemplate" rows="20" class="col-md-7"> </textarea>
										</div>
									</div>		
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Automation script that answers the installer questions">Automation Template</a></label>
										<div class="col-sm-8">
											<textarea id="tconfig" class="form-control" name="tconfig" rows="20" class="col-md-7"> </textarea>
										</div>
									</div>	
									<div class="form-group">
										<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Boot file that starts the installer kernel (grub.conf format)">UEFI Template</a></label>
										<div class="col-sm-8">
											<textarea id="tuefi" class="form-control" name="tuefi" rows="20" class="col-md-7"> </textarea>
										</div>
									</div>							
									<div class="form-group ">
										<label class="control-label col-sm-4">Template Type</label>
										<div class="col-sm-4">
											<input type="checkbox" id="tuse_ks" name="tuse_ks"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="Used for Anaconda installers (ie: CentOS, Oracle, Ubuntu, ESXi...">KickStart</a><br>
											<input type="checkbox" id="tuse_preseed" name="tuse_preseed"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="Mainly only used on Debian">PreSeed</a><br>
											<input type="checkbox" id="tuse_bsd" name="tuse_bsd"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For BSD PC-SYSINSTALL">BSD</a><br>
											<input type="checkbox" id="tuse_xen" name="tuse_xen"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For Xen Server XML">Xen</a><br>
											<input type="checkbox" id="tuse_unattended" name="tuse_unattended"> <a href="#" rel="tooltip" data-toggle="tooltip" data-placement="right" title data-original-title="For Windows unattended XML">Windows</a><br>
										</div>
									</div>		
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row" style="padding-left: 10px; padding-bottom: 50px;">
					<div class="col-md-10">
						<input type="hidden" id="mode" name="mode" value="template">
						<button type="treset" id="treset" class="btn btn-danger pull-left">
							<span class="glyphicon glyphicon-trash"></span> Clear
						</button>
						<button type="submit" class="btn btn-primary pull-right" id="tsubmit">
							<span class="glyphicon glyphicon-pencil"></span> Add / Update
						</button>
					</div>
				</div>	
				</form>
			</div>			
			<div id="controlPage" class="item">
				<form class="form-horizontal" id="controlForm" action="admin.php" method="post">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
						<div class="panel panel-primary">
							<div class="panel-heading"><h3 class="panel-title">Modify Control Panel Installers</h3></div>
							<div class="panel-body">
								<div class="form-group ">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Control panel install scripts">Control Panel</a></label>
									<div class="col-sm-4">
										<select name="panel" id="panel" class="form-control">
										';
										$query = "SELECT * from `panels` ORDER BY `name`";
										$result = mysqli_query($dbcnx,$query);
										
										if (!$result) {
											die('MySQL query Error: ' . mysqli_error($dbcnx));
										}		
										
										while ($row1 = mysqli_fetch_array($result))
										{
											$id = $row1['id'];
											$string = '<option value="'.$id.'"';
											$string .= '>'.$row1['name'].'</option>';
											echo "$string\n";
										}
										echo '
										<option value="0" selected>Select a Panel</option>
										</select>
									</div>		
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">Panel Name</label>
									<div class="col-sm-4">
										<input type="text" id="pname" class="form-control" name="pname" placeholder="ie: cPanel, Plesk">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Script ran during post install to automate install of the control panel">Script</a></label>
									<div class="col-sm-8">
										<textarea id="pscript" class="form-control" name="pscript" rows="20" class="col-md-7"> </textarea>
									</div>
								</div>		
							</div>
						</div>
					</div>
				</div>
				<div class="row" style="padding-left: 10px; padding-bottom: 50px;">
					<div class="col-md-10">
						<input type="hidden" id="mode" name="mode" value="panel">
						<button type="submit" class="btn btn-primary pull-right" id="psubmit"'. $disabled .'>
							<span class="glyphicon glyphicon-pencil"></span> Update
						</button>
					</div>
				</div>	
				</form>
			</div>						
			<div id="helpPage" class="item">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-10">
						<div class="panel panel-primary">
							<div class="panel-heading"><h3 class="panel-title">Template Variables</h3></div>
							<ul>
								<h4>Internally Generated</h4>
								<p>%image_name% -- wim install image name ie: W2k8_STD_R2_091913 <strong>(Windows Only)</strong></p>
								<p>%myip% -- Installer\'s IP address (usually used to indicate where install media is located via HTTP/FTP/NFS/etc)</p>
								<p>%pre% -- pre-install commands mainly used for setting up GPT partitions</p>
								<p>%suite% -- tells debian based installers what suite to install <strong>(Ubuntu Only)</strong></p>
							</ul>
							<br>
							<ul>
								<h4 >Generated from Provisioning Form</h4>
								<p >%controlpanel% -- cPanel / Plesk automatic install scripts option enabled via provisioning form</p>
								<p >%diskpart% -- Disk partitioning information entered into provisioning form</p>
								<p >%dns1% -- DNS Server 1 entered into provisioning form</p>
								<p >%dns2% -- DNS Server 2 entered into provisioning form</p>
								<p >%gateway% -- Gateway entered into provisioning form</p>
								<p >%hostname% -- Automaticly generated from provisioning form ie: <em>192-168-1-100</em></p>
								<p >%ip% -- IP entered into provisioning form</p>
								<p >%mac% -- altered MAC address replacing <strong>:</strong> with <strong>-</strong></p>
								<p >%netmask% -- Subnet mask entered into provisioning form</p>
								<p >%omac% -- Hardware MAC address entered into provisioning form (<em>original mac</em>)</p>
								<p >%password% -- Password entered into provisioning form</p>
								<p >%privatenet% -- Private network information enter into provisioning form</p>
								<p >%version% -- Version number of the OS to be installed</p>
								<p >%vnc% -- <em>(PXELinux Variable)</em> VNC option for monitoring Anaconda installs remotely via vncviewer in listen mode (<strong>vncviewer --listen</strong>)</p>
							</ul>
						</div>			
					</div>
				</div>
			</div>
			<div id="statsPage" class="item">
				<div class="row" style="padding-left: 5px; padding-top: 40px">
					<div class="col-md-12">
						<div class="panel panel-primary">
							<div class="panel-heading">Top 16 (Re)Installs</div>
							<div class="panel-body">
								<div id="osChart" style="height:768px;width:1024px; "></div>
								<!--  <img src="stats.php" class="img-rounded"> -->
							</div>
						</div>
					</div>
				</div>
			</div>			
			<div id="statusPage" class="item active">
				<div class="row" style="padding-left: 5px; padding-top: 40px;padding-bottom: 40px">
					<div class="col-md-4">
						<div class="panel panel-primary" id="services">
							<div class="panel-heading"><h3 class="panel-title">Services</h3></div>					
							<div id="ajaxloader3">
								<div class="outer"></div>
								<div class="inner"></div>
								<p class="text-primary text-center">Loading...</p>
							</div>	
						</div>
					</div>
					<div class="col-md-4">
						';
						echo disk_status();
						echo ram_status();
						echo cpu_load();
						echo '
					</div>
					<div class="col-md-4">
						';
						echo mirror_updates();
						echo suite_names();
						echo '
					</div>
					<div class="col-md-8">
					<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#mBox" id="isobtn">
						<span class="glyphicon glyphicon-floppy-disk"></span> Show Mounted ISOs
					</button>					
					</div>
					<nav class="navbar navbar-inverse navbar-fixed-bottom" role="navigation" id="timestamp">
						<div class="container">
						';
							$timestamp = date("m-d-Y H:i T");
							echo "<p class=\"navbar-text navbar-right\"><em><strong>Refreshed at $timestamp</strong></em></p>";
						echo '
						</div>
					</nav>
				</div>
			</div>
		</div>
	</div>
	<div id="mBox" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="boxLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">	
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 id="myModalLabel">Mounted ISOs</h3>
				</div>
				<div class="modal-body">
					<div id="ajaxloader3">
					  <div class="outer"></div>
					  <div class="inner"></div>
					  <p class="text-primary text-center">Loading...</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="close" data-dismiss="modal">
						<span class="glyphicon glyphicon-thumbs-up"></span> Close
					</button>
				</div>
			</div>
		</div>
	</div>						
	';
	page_footer();
}

switch($mode) {

	case "panel":
	update_script();
	break;

	case "template":
	update_template();
	break;
	
	case "modify":
	modify_os();
	break;

	case "restore":
	restore_os();
	break;
	
	case "force":
	force_remove_os();
	break;
	
	case "remove":
	remove_os();
	break;
	
	case "stats":
	os_stats();
	break;

	default:
	main();
	break;

}	

?>
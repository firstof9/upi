<?PHP
/*
	Universal PXE Installer - Installer? :P
	
	TODO:
		Precheck for TFTP server
		Precheck directory structure
		More prechecks
*/

$dbcnx = 0;

/*
	Function to start database connection
*/
function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("Error connecting to MySQL server: ".mysqli_error());
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

function import_sql()
{
	global $dbcnx;
	db_connect();
	
	$filename = "mysql/UPI.sql";
	
	// Temporary variable, used to store current query
	$templine = '';
	// Read in entire file
	$lines = file($filename);
	// Loop through each line
	foreach ($lines as $line)
	{
		// Skip it if it's a comment
		if (substr($line, 0, 2) == '--' || $line == '') { continue; }

		// Add this line to the current segment
		$templine .= $line;
		// If it has a semicolon at the end, it's the end of the query
		if (substr(trim($line), -1, 1) == ';')
		{
			// Perform the query
			mysqli_query($dbcnx,$templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysqli_error() . '<br /><br />');
			// Reset temp variable to empty
			$templine = '';
		}
	}
	 echo "Tables imported successfully";
}

function page_header()
{
echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<title>UPI Installer</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	</head>
	<body style="padding-bottom: 70px;">
	<div class="container" style="padding-top: 10px;">
	<div class="row">
		<div class="col-xs-12 col-md-12">
			<div id="alertHolder"></div>
		</div>
	</div>
';
}

function page_footer()
{
echo '
	<div id="mAlert" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mAlertLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">	
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 id="mAlertLabel">Note</h3>
				</div>
				<div class="modal-body">
					<span class="message"></span>
				</div>
				<div class="modal-footer">
					<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
				</div>
			</div>
		</div>
	</div>
	<script id="alertTemplate" type="text/plain">
	<div class="alert alert-success alert-dismissable">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		 <span class="message"></span>
	</div>
	</script>
	</body>
	<script src=\"//code.jquery.com/jquery-1.11.1.min.js\"></script>
	<script src=\"//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js\"></script>
	<script src=\"//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/jquery.validate.min.js\"></script>
	<script src=\"//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/additional-methods.min.js\"></script>
	<script src=\"js/install.js\"></script>
	</html>
	';
}

function main ()
{
	require('config.php');
	page_header();
	
	echo "
		<div class=\"row center-block\">
			<form class=\"form-horizontal\" id=\"installForm\" name=\"installForm\" action=\"install.php\" method=\"post\" role=\"form\">
				<div class=\"row\" style=\"padding-left: 5px; padding-top: 10px\">
					<div class=\"col-xs-6 col-md-6\">
						<div class=\"panel panel-primary\">
							<div class=\"panel-heading\"><h2 class=\"panel-title\"><strong>Installer </strong></h2></div>
							<div class=\"panel-body\">
								<div class=\"form-group\">
									<label class=\"control-label col-sm-4 col-xs-5\">mySQL Server</label>
									<div class=\"col-sm-8 col-lg-8\">
										<div class=\"input-group\">
											<input type=\"text\" id=\"mysql_server\" class=\"form-control\" name=\"mysql_server\" placeholder=\"localhost\" value=\"$mysql_hostname\" disabled>
										</div>
									</div>
								</div>
								<div class=\"form-group\">
									<label class=\"control-label col-sm-4 col-xs-5\">mySQL User</label>
									<div class=\"col-sm-8 col-lg-8\">
										<div class=\"input-group\">
											<input type=\"text\" id=\"mysql_user\" class=\"form-control\" name=\"mysql_user\" placeholder=\"mySQL username\" value=\"$mysql_username\" disabled>
										</div>
									</div>
								</div>
								<div class=\"form-group\">
									<label class=\"control-label col-sm-4 col-xs-5\">mySQL Password</label>
									<div class=\"col-sm-8 col-lg-8\">
										<div class=\"input-group\">
											<input type=\"text\" id=\"mysql_password\" class=\"form-control\" name=\"mysql_password\" placeholder=\"mySQL password\" value=\"$mysql_password\" disabled>
										</div>
									</div>
								</div>
								<div class=\"form-group\">
									<label class=\"control-label col-sm-4 col-xs-5\">mySQL Database</label>
									<div class=\"col-sm-8 col-lg-8\">
										<div class=\"input-group\">
											<input type=\"text\" id=\"mysql_database\" class=\"form-control\" name=\"mysql_database\" placeholder=\"mySQL database\" value=\"$mysql_database\" disabled>
										</div>
									</div>
								</div>								
								<button class=\"btn btn-success pull-right\" id=\"next\">Install <span class=\"glyphicon glyphicon-wrench\"></span></button>
							</div>
						</div>
					</div>
					<div class=\"col-md-6\">
						<div class=\"panel panel-primary\">
						<div class=\"panel-heading\"><h2 class=\"panel-title\"><strong>Instructions</strong></h2></div>
						<div class=\"panel-body\">							
							<p class=\"text-info\">If this data is not correct please edit your config.php file with the correct data.</p>
						</div>
					</div>
				</div>
			</form>
		</div>
	";
	page_footer();
}

switch($mode) {

	case "database":
	import_sql();
	break;
	
	default:
	main();
	break;
}



?>

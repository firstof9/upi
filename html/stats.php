<?PHP
date_default_timezone_set('America/Phoenix');
$dbcnx = 0;
$data = array();
$legend = array();
$labels = array();

function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error($dbcnx));
	//mysql_select_db($mysql_database, $dbcnx);
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_pie.php');
require_once ('jpgraph/jpgraph_pie3d.php');

db_connect();

$nquery = "SELECT `os` FROM `stats` ORDER BY `installed` DESC LIMIT 16";
$result = mysqli_query($dbcnx,$nquery);

while ($nrow = mysqli_fetch_array($result))
{
	$legend[] = $nrow[0];
	$labels[] = $nrow[0]." (%.0f%%)";
}

$iquery = "SELECT `installed` FROM `stats` ORDER BY `installed` DESC LIMIT 16";
$result = mysqli_query($dbcnx,$iquery);

while ($irow = mysqli_fetch_array($result))
{
	$data[] = $irow[0];
}

//var_dump($legend);
//var_dump($data);

$graph = new PieGraph(1024,1024);
$graph->SetShadow();
$graph->title->Set("");
$graph->subtitle->Set("mmmm pie...");
$graph->title->SetFont(FF_FONT1,FS_BOLD,14);	
$graph->legend->SetPos(0.5,0.98,'center','bottom');

$p1 = new PiePlot3D($data);
$p1->SetSize(0.5);
$p1->ExplodeSlice(0);
$p1->SetCenter(0.45);	
$p1->SetLegends($labels);

$graph->Add($p1);
$graph->Stroke();	

?>

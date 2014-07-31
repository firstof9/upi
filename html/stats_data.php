<?PHP

$mode = $_REQUEST['mode'];

/*
	Services list to check
*/
$services = array('/var/run/httpd/httpd.pid' => 'Apache Web Server',
									'/var/run/dhcpd.pid' => 'DHCP Server',
									'/var/run/mysqld/mysqld.pid' => 'MySQL Database Server',
									'/var/run/rpcbind.pid' => 'Network File System (NFS)',
									'/var/run/smbd.pid' => 'Samba File Server (Windows Shares)',
									'/var/run/sshd.pid' => 'Secure Shell',
									'/var/run/tftpd.pid' => 'Trivial File Transfer Protocol Daemon',
									'/var/webmin/miniserv.pid' => 'Webmin'
									);

/*
	Return the status of requested service via PID lookup
*/

function service_status($service)
{
	$command = "cat $service";
	$pid = exec($command);
	$command = "ps -p $pid";
	$status = exec($command);
	
	if (!isset($status[1])) { return false; }
	else { return true; }
}									
									
									
function getSystemMemInfo() 
{       
    $data = explode("\n", file_get_contents("/proc/meminfo"));
    $meminfo = array();
    foreach ($data as $line) {
    	@list($key, $val) = explode(":", $line);
    	$meminfo[$key] = trim($val);
    }
    return $meminfo;
}

function formatSize($bytes)
{
	$types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
	return( round( $bytes, 2 ) . " " . $types[$i] );
}

/*
	Services Status
*/

function services_check()
{
	global $services;
	echo '<tr><th>Status</th><th>Service</th></tr>';
	while (list ($service, $description) = each($services))
	{
		$status = service_status($service);
		//$status = true;
		if ($status) { echo "<tr class=\"success\"><td><img src=\"greendot.png\" height=32 width=32></td><td>{$description}</td></tr>"; }
		else { echo "<tr class=\"danger\"><td><img src=\"reddot.png\" height=32 width=32></td><td>{$description}</td></tr>"; }
	}	
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
	
	if ($load5 > 0.75) { $class = "-danger"; }
	else if ($load5 > 0.5) { $class = "-warning"; }
	
	echo "
		<div class=\"panel-heading\"><h5 class=\"panel-title\"><strong>CPU Load</strong></h5></div>
		<div class=\"panel-body\">
			<p class=text$class>$load5 / $load10 / $load15</p>
		</div>
	";
}

/*
	RAM Usage/Free meter
*/
function ram_status()
{
	$memory = getSystemMemInfo();
	
	$temp = explode(" ",$memory["MemFree"]);
	$mem_free = $temp[0] * 1000;
	$temp = explode(" ",$memory["MemTotal"]);
	$mem_total = $temp[0] * 1000;
	$mem_used = $mem_total - $mem_free;
	$mem_per =  sprintf('%.2f',($mem_used / $mem_total) * 100);
	
	$temp = explode(" ",$memory["Cached"]);
	$mem_cache = $temp[0] *1000;
	
	$mc = formatSize($mem_cache);
	$mu = formatSize($mem_used);
	$mt = formatSize($mem_total);
	
	$class = "progress-bar";
		
	echo "
		<div class=\"panel-heading\"><h5 class=\"panel-title\"><strong>Memory Usage</strong></h5></div>
		<div class=\"panel-body\">	
			<p class=\"text-info\">$mu of $mt (Cached: $mc)</p>
			<div class=\"progress progress-striped\">
				<div class=\"$class\" role=\"progressbar\" aria-valuenow=\"$mem_per\" aria-valuemin=\"0\" aria-valuemanx=\"100\" id=\"disusageBar\" style=\"width: $mem_per%;\">$mem_per%</div>
			</div>
		</div>
	";
}

/*
	Disk usage/free meter
*/

function disk_status()
{
	$disk_free = disk_free_space("/");
	$disk_total = disk_total_space("/");
	$disk_used = $disk_total - $disk_free;
	$disk_per =  sprintf('%.2f',($disk_used / $disk_total) * 100);
	
	$du = formatSize($disk_used);
	$dt = formatSize($disk_total);
	
	$class = "-success";
	
	if ($disk_per > 75.00) { $class = "-danger"; }
	else if ($disk_per > 50.00) { $class = "-warning"; }
		
	echo "
		<div class=\"panel-heading\"><h5 class=\"panel-title\"><strong>Disk Usage</strong></h5></div>
		<div class=\"panel-body\">		
			<p class=\"text-info\">$du of $dt</p>
			<div class=\"progress progress-striped\">
				<div class=\"progress-bar progress-bar$class\" role=\"progressbar\" aria-valuenow=\"$disk_per\" aria-valuemin=\"0\" aria-valuemanx=\"100\" id=\"disusageBar\" style=\"width: $disk_per%;\">$disk_per%</div>
			</div>
		</div>
	";
	
}

switch($mode) {

	case "services":
	services_check();
	break;
	
	case "disk":
	disk_status();
	break;

	case "ram":
	ram_status();
	break;
	
	case "cpu":
	cpu_load();
	break;

	default:
	cpu_load();
	break;

}	

?>
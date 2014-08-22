<?PHP
/*
	Universal PXE Installer
	
	TODO:
	
				Create global rewrite function to replace the variables
				
	UPDATE:
				
				Added auto fix dir permissions function to help resolve errors automagicly
*/

/* 
	HTML Header
*/

$version = "0.2.21";
$mode = $_REQUEST['mode'];
$dbcnx = 0;
$g_admin = false;
date_default_timezone_set('America/Phoenix');

/*
	Insert SSO Functions here
*/

function auth_check() {
    global $g_admin;

    $g_admin = true;
}

/*
	Rewrite function to replace variables in the automation template
*/

function rewrite_template($version,$ip,$mac,$myip,$netmask,$gateway,$hostname,$namserver,$password,$diskpart,$dns1,$dns2,$location,$omac,$cmd,$privatenet,$pre,$suite,$config)
{
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",strtolower($mac),$config); // replace the %mac%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$netmask,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%nameserver%",$nameserver,$config); // replace the %nameserver%
	$config = str_replace("%password%",$password,$config); // replace the %password%
	$config = str_replace("%diskpart%",$diskpart,$config); // replace the %diskpart%
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2	
	$config = str_replace("%location%",$location,$config); // WIM File with path ie: \\WDS-04\Win2k12STDR2.wim
	$config = str_replace("%omac%",$omac,$config); // replace the %version%
	$config = str_replace("%controlpanel%",$cmd,$config); // replace the %controlpanel%	
	$config = str_replace("%privatenet%",$privatenet,$config); // replace the %privatenet%	
	$config = str_replace("%pre%",$pre,$config); // replace %pre%
	$config = str_replace("%suite%",$suite,$config); // replace the %suite%
}

/*
	Auto repair directory access errors
*/

function auto_fix()
{
	exec("ssh root@localhost  /root/fix_everything.sh",$output);
	return $output;
}

/*
	HTML header data
*/

function page_header()
{
    global $g_admin;
	$user = "Local User";
	
echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<title>Universal PXE Installer</title>
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

function findIPMI()
{
	$mac = strtolower($_REQUEST['mac']);
	$ip = findMAC($mac);
	if ($ip != "") { echo $ip; }
	else { echo ""; }
}

/*
		Finds the IP address of given MAC address in /var/log/messages
*/

function findMAC($mac)
{
	$ipmi = exec("sudo tail -n 150 /var/log/messages | grep $mac | grep DHCPACK | head -n1 | awk '{print $8;}'");
	return $ipmi;
}

/*
	Sets server to PXE boot and resets the server via ipmitool
*/
function ipmiReboot($user,$pass,$mac)
{
	$ip = findMAC(strtolower($mac));
	$log = exec("sudo ipmitool -I lanplus -H ".$ip." -U ".$user." -P ".$pass." chassis bootdev pxe");
	$log .= exec("sudo ipmitool -I lanplus -H ".$ip." -U ".$user." -P ".$pass." power reset");
	
}


/*
	Converts Subnet Mask to CIDR (for Windows)
*/
function mask2cidr($mask)
{
  $long = ip2long($mask);
  $base = ip2long('255.255.255.255');
  return 32-log(($long ^ $base)+1,2);
}


/*
	Calculates the private NIC's MAC automagicly
*/

function PrivMac($mac) {
    $mac = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
    $macDec = hexdec($mac);
    $macDec -= 1;
    $macHex = dechex($macDec);
    $PrivMac = $macHex;
	if (strlen($PrivMac) == 1) { $PrivMac = "0".$PrivMac; } // Add the padding 0 if only 1 digit exists
    return $PrivMac;
}

function error_msg($message) { echo $message; }

/*
	Function to start database connection
*/
function db_connect()
{
	global $dbcnx;
	require('config.php');
	$dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error());
	mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds
}

function generate_win($flavor,$version,$password,$ip_address,$gateway,$netmask,$panel,$mac,$dns1,$dns2,$diskpart)
{
	global $dbcnx;
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	db_connect();
	$query = "SELECT * from `flavor` WHERE `name` = '$flavor'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$config = $row['config'];
	
	$hostname = str_replace(".","-",$ip_address); // Generated from IP address
	
	/*
		Create CIDR for Windows netmask
	*/
	
	$pub_cidr = mask2cidr($netmask); // Convert the netmask to CIDR for windows XML
	
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",str_replace(":","-",$mac),$config); // replace the %mac%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$pub_cidr,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%password%",$password,$config); // replace the %password%		
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2
		
	$config = str_replace("%diskpart%",$diskpart,$config); // Disk Partitons
	
	$osid = $_REQUEST["os"];
	$query = "SELECT * from `os` WHERE `id` = '$osid'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);	
	
	$location = $row['wim'];
	$version = $row['name']." ".$row['version'];
	$omac = $_REQUEST['public_mac'];
	
	$config = str_replace("%location%",$location,$config); // WIM File with path ie: \\WDS-04\Win2k12STDR2.wim
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%omac%",$omac,$config); // replace the %version%
	
	$image_name = explode(".",$location);
	
	$config = str_replace("%image_name%",$image_name[0],$config); // image Name
	
	/* 
		Control Pannel Install Script(s)
	*/
	$cmd = ""; // make it empty JIC no control panel
	
	if ($panel == "plesk")
	{
		$query = "SELECT * from `panels` WHERE `name` = 'plesk-win'"; // Get the panel script
		$result = mysqli_query($dbcnx,$query);
		$rpanel = mysqli_fetch_array($result);
		$cmd = $rpanel['script'];
	}
	
	$config = str_replace("%controlpanel%",$cmd,$config); // replace the %controlpanel%

	/*
	
		Private Networking Interface
	
		         <Interface wcm:action='add'>
                    <Ipv4Settings>
                        <DhcpEnabled>false</DhcpEnabled>
                        <Metric>10</Metric>
                        <RouterDiscoveryEnabled>false</RouterDiscoveryEnabled>
                    </Ipv4Settings>
                    <Ipv6Settings>
                        <DhcpEnabled>false</DhcpEnabled>
                        <Metric>30</Metric>
                        <RouterDiscoveryEnabled>false</RouterDiscoveryEnabled>
                    </Ipv6Settings>
                    <Identifier>%mac%</Identifier>
                    <Routes>
                        <Route wcm:action='add'>
                            <Identifier>2</Identifier>
                            <Metric>10</Metric>
                            <NextHopAddress>%gateway%</NextHopAddress>
                            <Prefix>0.0.0.0/0</Prefix>
                        </Route>
                    </Routes>
                    <UnicastIpAddresses>
                        <IpAddress wcm:action='add' wcm:keyValue='1'>%ip%/24</IpAddress>
                        <IpAddress wcm:action='add' wcm:keyValue='2'>%omac%</IpAddress>
                    </UnicastIpAddresses>
                </Interface>
	
	*/
	$priv_ip = $_REQUEST['private_ip'];
	$priv_netmask = $_REQUEST['private_netmask'];
	$priv_mac = $_REQUEST['private_mac'];
	$privatenet = "";
	
	if ($priv_ip != "")
	{
		$priv_cidr = mask2cidr($priv_netmask); // Convert the netmask to CIDR for windows XML
		
		$privatenet = "
		         <Interface wcm:action='add'>
                    <Ipv4Settings>
                        <DhcpEnabled>false</DhcpEnabled>
                        <Metric>10</Metric>
                        <RouterDiscoveryEnabled>false</RouterDiscoveryEnabled>
                    </Ipv4Settings>
                    <Ipv6Settings>
                        <DhcpEnabled>false</DhcpEnabled>
                        <Metric>30</Metric>
                        <RouterDiscoveryEnabled>false</RouterDiscoveryEnabled>
                    </Ipv6Settings>
                    <Identifier>$priv_mac</Identifier>
                    <Routes>
                        <Route wcm:action='add'>
                            <Identifier>2</Identifier>
                            <Metric>10</Metric>
                            <NextHopAddress></NextHopAddress>
                            <Prefix>0.0.0.0/0</Prefix>
                        </Route>
                    </Routes>
                    <UnicastIpAddresses>
                        <IpAddress wcm:action='add' wcm:keyValue='1'>$priv_ip/$priv_cidr</IpAddress>
                        <IpAddress wcm:action='add' wcm:keyValue='2'>$priv_mac</IpAddress>
                    </UnicastIpAddresses>
                </Interface>		
		";
	}
	$config = str_replace("%privatenet%",$privatenet,$config); // replace the %privatenet%
	
	// Write out XML file
	$filename = str_replace(":","-",$mac).".cfg"; // macaddres.cfg
	$fp = fopen("/var/www/html/".$filename,'w'); // create file if doesn't exist and set for writing only
	if (fwrite($fp,$config) == FALSE)
	{
		$error = auto_fix(); // attempt to fix errors
		if (fwrite($fp,$config) == FALSE)
		{
			error_msg("Unable to write file: ".$filename);
			exit();
		}
	}

	// File write success!
	fclose($fp); // close the file
	exec("dos2unix /var/www/html/".$filename); // make sure the file is in unix format
		$log_file = date('mdy_Hi_').$filename.".log";
	exec("cp /var/www/html/".$filename." /var/www/html/log/".$log_file); // copy file to log dir
}

function generate_xen($flavor,$version,$password,$ip_address,$gateway,$netmask,$panel,$mac,$dns1,$dns2)
{
	global $dbcnx;
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	db_connect();
	$query = "SELECT * from `flavor` WHERE `name` = '$flavor'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$config = $row['config'];
	
	// Generate XML file for XenServer
	
	$is_dell = 0;
	if (strpos(strtolower($_REQUEST['public_mac']),strtolower("D4:AE:52")) !== false) { $is_dell = 1; }
	
	$hostname = str_replace(".","-",$ip_address); // Generated from IP address
	
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",$mac,$config); // replace the %mac%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$netmask,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%password%",$password,$config); // replace the %password%		
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2
	
	// Write out kickstart file
	$filename = str_replace(":","-",$mac).".cfg"; // macaddres.cfg
	$fp = fopen("/var/www/html/".$filename,'w'); // create file if doesn't exist and set for writing only
	if (fwrite($fp,$config) == FALSE)
	{
		auto_fix(); // attempt to fix errors
		if (fwrite($fp,$config) == FALSE)
		{
			error_msg("Unable to write file: ".$filename);
			exit();
		}
	}

	// File write success!
	fclose($fp); // close the file
	exec("dos2unix /var/www/html/".$filename); // make sure the file is in unix format
		$log_file = date('mdy_Hi_').$filename.".log";
	exec("cp /var/www/html/".$filename." /var/www/html/log/".$log_file); // copy file to log dir
}

function generate_ks($flavor,$version,$password,$ip_address,$gateway,$netmask,$panel,$mac,$dns1,$dns2,$diskpart)
{
	global $dbcnx;
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	db_connect();
	$query = "SELECT * from `flavor` WHERE `name` = '$flavor'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$config = $row['config'];
		
	// Generate KS file for CentOS/RHEL installs
	
	$is_dell = 0;
	if (strpos(strtolower($_REQUEST['public_mac']),strtolower("D4:AE:52")) !== false) { $is_dell = 1; }
	else if (strpos(strtolower($_REQUEST['public_mac']),strtolower("BC:30:5B")) !== false) { $is_dell = 1; }
	
	$hostname = str_replace(".","-",$ip_address); // Generated from IP address
	$nameserver = $dns1.",".$dns2;
	
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",strtolower($mac),$config); // replace the %mac%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$netmask,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%nameserver%",$nameserver,$config); // replace the %nameserver%
	$config = str_replace("%password%",$password,$config); // replace the %password%
	$config = str_replace("%diskpart%",$diskpart,$config); // replace the %diskpart%
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2	
	
	/* 
		Control Pannel Install Script(s)
	*/
	$cmd = ""; // make it empty JIC no control panel
	
	if ($panel != "none")
	{
		$query = "SELECT * from `panels` WHERE `name` = '$panel'"; // Get the panel script
		$result = mysqli_query($dbcnx,$query);
		$rpanel = mysqli_fetch_array($result);
		$cmd = $rpanel['script'];
	}
	
	$config = str_replace("%controlpanel%",$cmd,$config); // replace the %controlpanel%
	
	/*
		Pre-Installer Commands used for creating GPT partitions
	*/
	$pre = "";
	if ($_REQUEST['is_gpt']) { $pre = "/usr/sbin/parted -s /dev/sda mklabel gpt\n/usr/sbin/parted -s /dev/sda set 1 bios_grub on"; } // setup GPT
	$config = str_replace("%pre%",$pre,$config); // replace %pre%
	
	/* Commented due to no longer needing to rename interfaces with udev rules (except for ESXi)
	if ($is_dell && $flavor == "centos") { $config = str_replace("eth1","em1",$config); } // replace eth1 with em1 (broadcom NIC for Dell servers)
	else if ($is_dell || $is_dual == 0) { $config = str_replace("eth1","eth0",$config); } // replace eth1 with eth0 (broadcom NIC for Dell servers)
	*/
	
	if ($is_dell && $flavor == "esxi") { $config = str_replace("vmnic0","vmnic2",$config); } // replace vmnic0 with vmnic2 (broadcom NIC for Dell servers)
	
	/* 
		Private network settings 
	*/
	
	$pmac = explode(":",$_REQUEST['public_mac']);
	$pmac[5] = PrivMac($pmac[5]);
	$privmac = strtolower(implode(":",$pmac));
	
	$privatenet = "echo 'SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$privmac\", ATTR{type}==\"1\", KERNEL==\"eth*\", NAME=\"eth0\"' >> /etc/udev/rules.d/99-persistent-net.rules; \\";
	
	if ($_REQUEST['private_ip'] != "")
	{
	$priv_ip = $_REQUEST['private_ip'];
	$priv_netmask = $_REQUEST['private_netmask'];
	$priv_mac = $_REQUEST['private_mac'];
	
		if ($flavor != "ubuntu")
		{
			$privatenet = "
echo 'SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$priv_mac\", ATTR{type}==\"1\", KERNEL==\"eth*\", NAME=\"eth0\"' >> /etc/udev/rules.d/99-persistent-net.rules; \
echo 'DEVICE=eth0' > /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'BOOTPROTO=static' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'HWADDR=$priv_mac' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'IPADDR=$priv_ip' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'NETMASK=$priv_netmask' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'ONBOOT=yes' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \
echo 'NM_CONTROLLED=no' >> /etc/sysconfig/network-scripts/ifcfg-eth0; \		
";
		}
		else
		{
			$privatenet = "
echo 'SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$priv_mac\", ATTR{type}==\"1\", KERNEL==\"eth*\", NAME=\"eth0\"' >> /etc/udev/rules.d/99-persistent-net.rules; \
echo 'auto eth0' >> /etc/network/interfaces; \
echo 'iface eth0 inet static' >> /etc/network/interfaces; \
echo 'hwaddress $priv_mac' >> /etc/network/interfaces; \
echo 'address $priv_ip' >> /etc/network/interfaces; \
echo 'netmask $priv_netmask' >> /etc/network/interfaces; \
";
		}
	}
	$config = str_replace("%privatenet%",$privatenet,$config); // replace the %privatenet%
		
	// Write out kickstart file
	$filename = str_replace(":","-",$mac).".cfg"; // macaddres.cfg
	$fp = fopen("/var/www/html/".$filename,'w'); // create file if doesn't exist and set for writing only
	if (fwrite($fp,$config) == FALSE)
	{
		auto_fix(); // attempt to fix errors
		if (fwrite($fp,$config) == FALSE)
		{
			error_msg("Unable to write file: ".$filename);
			exit();
		}
	}
	
	// File write success!
	fclose($fp); // close the file
	exec("dos2unix /var/www/html/".$filename); // make sure the file is in unix format
		$log_file = date('mdy_Hi_').$filename.".log";
	exec("cp /var/www/html/".$filename." /var/www/html/log/".$log_file); // copy file to log dir
}

function generate_preseed($flavor,$version,$password,$ip_address,$gateway,$netmask,$panel,$mac,$dns1,$dns2,$diskpart)
{
	global $dbcnx;
	// Generate preseed file for Debian based installs
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	db_connect();
	$query = "SELECT * from `flavor` WHERE `name` = '$flavor'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$config = $row['config'];
	
	$is_dell = 0;
	if (strpos(strtolower($_REQUEST['public_mac']),strtolower("D4:AE:52")) !== false) { $is_dell = 1; }
	else if (strpos(strtolower($_REQUEST['public_mac']),strtolower("BC:30:5B")) !== false) { $is_dell = 1; }
	
	$hostname = str_replace(".","-",$ip_address); // Generated from IP address
	$nameserver = $dns1." ".$dns2; // config nameserver IPs CSV format
	
	/*
	
		Suite detection for Ubuntu / Debian
	
	*/
	
	$suite = "";
	
	if (strstr($flavor,"ubuntu"))
	{
		$query = "SELECT * FROM `suites` WHERE `version` = '$version'";
		$result = mysqli_query($dbcnx,$query);
		if (!$result) { die('An error occured attempting to access the suite table.'); }
		
		$data = mysqli_fetch_array($result);		
		$suite = $data['name'];
	}
	
	$config = str_replace("%suite%",$suite,$config); // replace the %suite%
	
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",$mac,$config); // replace the %mac%
	//$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$netmask,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%nameserver%",$nameserver,$config); // replace the %nameserver%
	$config = str_replace("%password%",$password,$config); // replace the %password%
	$config = str_replace("%diskpart%",$diskpart,$config); // replace the %diskpart%
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2	
	
	//if ($is_dell || $is_dual == 0) { $config = str_replace("eth1","eth0",$config); } // replace eth1 with eth0 (broadcom NIC for Dell servers)
	
	/* 
		Private network settings 
	*/
		
	$pmac = explode(":",$_REQUEST['public_mac']);
	$pmac[5] = PrivMac($pmac[5]);
	$privmac = strtolower(implode(":",$pmac));
	
	$privatenet = "echo 'SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$privmac\", ATTR{type}==\"1\", KERNEL==\"eth*\", NAME=\"eth1\"' >> /target/etc/udev/rules.d/99-persistent-net.rules; \\";
	//$privatenet = "";
	
	if ($_REQUEST['private_ip'] != "")
	{
		$priv_ip = $_REQUEST['private_ip'];
		$priv_netmask = $_REQUEST['private_netmask'];
		$priv_mac = $_REQUEST['private_mac'];
		
		$privatenet = "
echo 'SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$priv_mac\", ATTR{type}==\"1\", KERNEL==\"eth*\", NAME=\"eth1\"' >> /target/etc/udev/rules.d/99-persistent-net.rules; \
echo 'auto eth1' >> /target/etc/network/interfaces; \
echo 'iface eth1 inet static' >> /target/etc/network/interfaces; \
echo 'hwaddress $priv_mac' >> /target/etc/network/interfaces; \
echo 'address $priv_ip' >> /target/etc/network/interfaces; \
echo 'netmask $priv_netmask' >> /target/etc/network/interfaces; \
";
	}
	$config = str_replace("%privatenet%",$privatenet,$config); // replace the %privatenet%

	/* 
		Control Pannel Install Script(s)
	*/
	$cmd = ""; // make it empty JIC no control panel
	
	if ($panel != "none")
	{
		if ($panel == "plesk") { $panel = "plesk-deb"; }
		
		$query = "SELECT * from `panels` WHERE `name` = '$panel'"; // Get the panel script
		$result = mysqli_query($dbcnx,$query);
		$rpanel = mysqli_fetch_array($result);
		$cmd = $rpanel['script'];
	}
	
	$config = str_replace("%controlpanel%",$cmd,$config); // replace the %controlpanel%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	
	// Write out kickstart file
	$filename = str_replace(":","-",$mac).".cfg"; // macaddres.cfg
	$fp = fopen("/var/www/html/".$filename,'w'); // create file if doesn't exist and set for writing only
	if (fwrite($fp,$config) == FALSE)
	{
		auto_fix(); // attempt to fix errors
		if (fwrite($fp,$config) == FALSE)
		{
			error_msg("Unable to write file: ".$filename);
			exit();
		}
	}
	
	// File write success!
	fclose($fp); // close the file	
	exec("dos2unix /var/www/html/".$filename); // make sure the file is in unix format
		$log_file = date('mdy_Hi_').$filename.".log";
	exec("cp /var/www/html/".$filename." /var/www/html/log/".$log_file); // copy file to log dir
}

function generate_bsd($flavor,$version,$password,$ip_address,$gateway,$netmask,$panel,$mac,$diskpart,$dns1,$dns2)
{
	global $dbcnx;
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	db_connect();
	$query = "SELECT * from `flavor` WHERE `name` = '$flavor'"; // Get the selected flavor config template
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	$config = $row['config'];
		
	// Generate KS file for CentOS/RHEL installs
	
	$is_dell = 0;
	if (strpos(strtolower($_REQUEST['public_mac']),strtolower("D4:AE:52")) !== false) { $is_dell = 1; }
	else if (strpos(strtolower($_REQUEST['public_mac']),strtolower("BC:30:5B")) !== false) { $is_dell = 1; }
	
	$hostname = str_replace(".","-",$ip_address); // Generated from IP address
	
	$config = str_replace("%version%",$version,$config); // replace the %version%
	$config = str_replace("%ip%",$ip_address,$config); // replace the %ip%
	$config = str_replace("%mac%",strtolower($mac),$config); // replace the %mac%
	$config = str_replace("%myip%",$myip,$config); // replace the %myip%
	$config = str_replace("%netmask%",$netmask,$config); // replace the %netmask%
	$config = str_replace("%gateway%",$gateway,$config); // replace the %gateway%
	$config = str_replace("%hostname%",$hostname,$config); // replace the %hostname%
	$config = str_replace("%nameserver%",$nameserver,$config); // replace the %nameserver%
	$config = str_replace("%password%",$password,$config); // replace the %password%
	$config = str_replace("%diskpart%",$diskpart,$config); // replace the %diskpart%
	$config = str_replace("%dns1%",$dns1,$config); // DNS Server 1
	$config = str_replace("%dns2%",$dns2,$config); // DNS Server 2
	
	/* 
		Control Pannel Install Script(s)
	*/
	$cmd = ""; // make it empty JIC no control panel
	$config = str_replace("%controlpanel%",$cmd,$config); // replace the %controlpanel%
	
	$privatenet = "";
	$config = str_replace("%privatenet%",$privatenet,$config); // replace the %privatenet%
		
	// Write out PC-SYSINSTALL file
	$filename = $mac.".cfg"; // macaddres.cfg
	$fp = fopen("/var/www/html/".$filename,'w'); // create file if doesn't exist and set for writing only
	if (fwrite($fp,$config) == FALSE)
	{
		auto_fix(); // attempt to fix errors
		if (fwrite($fp,$config) == FALSE)
		{
			error_msg("Unable to write file: ".$filename);
			exit();
		}
	}
	// File write success!
	fclose($fp); // close the file
	exec("dos2unix /var/www/html/".$filename); // make sure the file is in unix format
	$log_file = date('mdy_Hi_').$filename.".log";
	exec("cp /var/www/html/".$filename." /var/www/html/log/".$log_file); // copy file to log dir
	if ($version == "9.1") { copy("/var/www/html/freebsd.91.conf","/var/www/html/freebsd.conf"); }
	else if ($version == "9.2") { copy("/var/www/html/freebsd.92.conf","/var/www/html/freebsd.conf"); }
	else if ($version == "10.0") { copy("/var/www/html/freebsd.100.conf","/var/www/html/freebsd.conf"); }
	exec("ssh root@localhost  refresh_dhcp.sh"); // reload DHCP for updated FreeBSD root directory
}

function verify()
{
	global $dbcnx;
	//var_dump($_REQUEST); // debugging code
	
	// Get IP address of "install interface" currently eth2
	$command = "/sbin/ifconfig eth2 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$myip = exec($command);
	
	$osid = $_REQUEST["os"]; // ID number associated with selected OS in the MySQL database
	$is_gpt = $_REQUEST["is_gpt"]; // populate GPT variable
	
	db_connect();
	$query = "SELECT * from `os` WHERE `id` = '$osid'"; // Get the selected OS info
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	/*
		Stats logging
	*/
	
	$osname = $row['name'] ." ".$row['version'];
	
	if ($osname != "")
	{
	
		$squery = "SELECT `id` from `stats` WHERE `os` = '$osname'";
		
		$sresult = mysqli_query($dbcnx,$squery);
		$row_cnt = mysqli_num_rows($sresult);
			
		if ($row_cnt == 0)
		{
			$squery = "INSERT INTO `stats` (os,installed) VALUES('$osname',1)";
		}
		else
		{
			$srow = mysqli_fetch_array($sresult);
			
			$id = $srow['id'];
			$squery = "UPDATE `stats` SET installed=installed+1 WHERE `id` = '$id'";
		}
		
		$sresult = mysqli_query($dbcnx,$squery);
		// No error check as this function is not critical and just for stats
	}
	/*
		End stats loggin
	*/
	
	// Error checks moved to JavaScript app.js
	
	// Prepare the MAC address for file naming
	
	$fmac = strtolower("01-".str_replace(":","-",$_REQUEST['public_mac'])); // Replace : in MAC addres with - for PXE Booting
	$mac = str_replace(":","-",$_REQUEST['public_mac']);
	$omac = $_REQUEST['public_mac'];
		
	// Open file for writing
	if ($is_gpt == 1) { $fp = fopen('/tftpboot/'.strtoupper($fmac),'w'); } // write the UEFI tftpboot file (grub.conf formatting)
	else { $fp = fopen('/tftpboot/pxelinux.cfg/'.$fmac,'w'); } // create file if doesn't exist and set for writing only
	
	if (!$fp) 
	{ 
		auto_fix();
		if ($is_gpt == 1) { $fp = fopen('/tftpboot/'.strtoupper($fmac),'w'); } // write the UEFI tftpboot file (grub.conf formatting)
		else { $fp = fopen('/tftpboot/pxelinux.cfg/'.$fmac,'w'); } // create file if doesn't exist and set for writing only
		
		if (!$fp) { echo "Unable to write TFTP file!";  exit();  }
	}
	
	/*
	// Variables to replace via str_replace:
	// %myip% - this server's IP
	// %version% - version of OS from MySQL query
	// %mac% - MAC address from $fmac
	*/
		
	// Move this check to javascript
	$flavor = $row['flavor'];
	if ($flavor == "")
	{
		error_msg("Database error occured: Missing template data!");
		exit();
	}
	
	// Getting the template info from the database
	$tquery = "SELECT * from `flavor` WHERE `name` = '$flavor'";
	$tresult = mysqli_query($dbcnx,$tquery);
	
	if (!$tresult) { die('MySQL query Error: ' . mysqli_error($dbcnx)); } // output error message if selected template somehow does not exist
	
	$trow = mysqli_fetch_array($tresult);
	
	if ($is_gpt == 1 && $trow['uefi'] != "") { $template = $trow['uefi']; } // Use the UEFI template rather than the standard pxelinux template
	else { $template = $trow['template']; } // legacy pxelinux template
	
	$template = str_replace("%version%",$row['version'],$template); // replace the %version%
	$template = str_replace("%mac%",$mac,$template); // replace the %mac%
	$template = str_replace("%myip%",$myip,$template); // replace %myip%
	$template = str_replace("%omac%",$omac,$template); // replace %omac% with original mac (unaltered)
	
	/* VNC Remote install screen */	
	$vnc = ""; // blank option if VNC remote view is disabled
	if ($_REQUEST['vnc']) { $vnc = "vnc vncconnect=".$_SERVER['REMOTE_ADDR']; } // enable VNCconnect option for remote install views
	$template = str_replace("%vnc%",$vnc,$template); // replace the %vnc%
	
	if (fwrite($fp,$template) == FALSE)
	{
		auto_fix(); // attempt to auto repair dir permissions
		if (fwrite($fp,$template) == FALSE)
		{
			error_msg("Unable to write PXE boot file");
			exit();
		}
	}
	
	// File write success!
	fclose($fp); // close the file
	
	$pmac = explode(":",$_REQUEST['public_mac']);
	$pmac[5] = PrivMac($pmac[5]);
	$privmac = strtolower(implode(":",$pmac));
	
	$second_file = strtolower("01-".str_replace(":","-",$privmac));
	
	if ($is_gpt == 1) { exec("cp /tftpboot/".strtoupper($fmac)." /tftpboot/".strtoupper($second_file)); } 
	else { exec("cp /tftpboot/pxelinux.cfg/$fmac /tftpboot/pxelinux.cfg/$second_file"); } 
	
	/*
		Disk partition generator
		This is where the disk partitions get generated for the type of automated install
	*/
	
	if ($trow['use_ks'])
	{
		// Build the disk partitions (Kickstart style)
		$y = 0;
		$use_disks = "";
		
		while ($y <= 1)
		{
			$x = 1;
			while ($x <= 10)
			{
				$var = "disk".$y."mount".$x;
				$var2 = "disk".$y."fs".$x;
				$var3 = "disk".$y."size".$x;
				$type = "part";
				$vg = "";
				$name = "";
				$disk = "sda";
				
				if ($y == 1) { $disk = "sdb"; }
				
				if ($_REQUEST[$var] != "")
				{
					$ondisk = "";
					if ($x == 1) { $use_disks .= $disk.","; }
					if ($_REQUEST['is_gpt'] == 0 && $x == 1 && $y == 0) { $diskpart .= "clearpart --all --initlabel\n\n"; } //clear partitions if not GPT
					if ($_REQUEST['is_gpt'] == 1 && $x == 1 && $y == 0)
					{
						// Add GPT BIOS Boot Partition and required /boot/efi directory
						$diskpart .= "part /boot/efi/ --fstype=efi --size=200\n";
					}
					if ($_REQUEST['is_lvm'] == 1 && $x == 1)
					{
						$diskpart .= "part pv.01 --size=1 --grow --ondisk=$disk\n";
						$diskpart .="volgroup VolGroup00 pv.01\n";
						$type = "logvol";
						$vg = "--vgname=VolGroup00";
						$name = "--name=vol".$x;
						$ondisk = "--ondisk=$disk";
					}
					else if ($_REQUEST['is_lvm'] == 1 && $x > 1)
					{
						$type = "logvol";
						$vg = "--vgname=VolGroup00";
						$name = "--name=vol".$x;
					}
					if ($_REQUEST[$var3] == "grow")
					{
						$size = "--size=1 --grow";
					}
					else { $size = "--size=$_REQUEST[$var3]"; }
					
					if ($flavor != "ubuntu" && $_REQUEST['is_lvm']  == 0)
					{
						$ondisk = "--ondisk=$disk";
					}
					
					if ($_REQUEST[$var] == "swap")
					{
						if ($_REQUEST['is_lvm']) { $name = "--name=swap"; }
						$diskpart .= "$type swap $vg $size $name $ondisk\n";
					}
					else
					{
						if ($x == 1) { $diskpart .= "part $_REQUEST[$var] --fstype $_REQUEST[$var2] $size $ondisk\n"; }
						else { $diskpart .= "$type $_REQUEST[$var] $vg --fstype $_REQUEST[$var2] $size $name $ondisk\n"; }
					}
				}
				$x++;
			}
			$y++;
		}
		generate_ks($flavor,$row['version'],$_REQUEST['password'],$_REQUEST['public_ip'],$_REQUEST['public_gateway'],$_REQUEST['public_netmask'],$_REQUEST['control_panel'],$_REQUEST['public_mac'],$_REQUEST['dns1'],$_REQUEST['dns2'],$diskpart);
	}
	
	else if ($trow['use_preseed'])
	{
		$diskpart ="";
		
		if ($_REQUEST[is_lvm] == 1)
		{
			$diskpart .= "d-i partman-auto/method string lvm\n";
			$diskpart .= "d-i partman-auto-lvm/new_vg_name string system\n\n";
		}
		else { $diskpart .= "d-i partman-auto/method string regular\n\n"; }
		
		if ($_REQUEST[is_gpt] == 1)
		{
			$diskpart .= "d-i partman-basicfilesystems/choose_label string gpt\nd-i partman-basicfilesystems/default_label string gpt\nd-i partman-partitioning/choose_label string gpt\nd-i partman-partitioning/default_label string gpt\nd-i partman/choose_label string gpt\nd-i partman/default_label string gpt\npartman-partitioning partman-partitioning/choose_label select gpt\n\n";
		}
		
		$diskpart .= "d-i partman-auto/expert_recipe string custom :: ";
		$x = 1;
		while ($x <= 10)
		{
			$var = "disk0mount".$x;
			$var2 = "disk0fs".$x;
			$var3 = "disk0size".$x;
			
			if($_REQUEST[$var] != "")
			{
				if ($_REQUEST[$is_gpt] == 1 && $x == 1)
				{
					// Add GPT BIOS Boot Partition
					$diskpart .= "1 1 1 free $iflabel{ gpt } method{ biosgrub } . ";
				}
				$tmp = "";
				if ($x == 1) { $tmp = " \$bootable{ }"; } // make the first partition bootable
				if ($_REQUEST[is_lvm] == 1 && $x > 1) { $tmp = " \$lvmok{ }"; }
				if ($_REQUEST[is_gpt] == 1 && $x > 1) { $tmp .= " \$gptonly{ }"; }
				
				if ($_REQUEST[$var3] == "grow")
				{
					$size0 = "20";
					$size1 = "-1";
				}
				else { $size0 = $_REQUEST[$var3]; $size1 = $_REQUEST[$var3]; }
				
				if ($_REQUEST[$var] == "swap")
				{
					// linux swap partition
					$diskpart .= "$size0 10000 $size1 linux-swap \$primary{ }$tmp method{ swap } format{ } . ";
				}
				// regular ext3/ext4 partition
				else
				{
					$diskpart .= "$size0 512 $size1 $_REQUEST[$var2] \$primary{ }$tmp method{ format } format{ } use_filesystem{ } filesystem{ $_REQUEST[$var2] } mountpoint{ $_REQUEST[$var] } . ";
				}
			}
			$x++;
		}
		// Generate preseed (deb based installers)
		generate_preseed($flavor,$row['version'],$_REQUEST['password'],$_REQUEST['public_ip'],$_REQUEST['public_gateway'],$_REQUEST['public_netmask'],$_REQUEST['control_panel'],$_REQUEST['public_mac'],$_REQUEST['dns1'],$_REQUEST['dns2'],$diskpart);
	}
	else if ($trow['use_bsd'])
	{
		$diskpart ="";
		
		$x = 1;
		while ($x <= 10)
		{
			$var = "disk0mount".$x;
			$var2 = "disk0fs".$x;
			$var3 = "disk0size".$x;
			
			if($_REQUEST[$var] != "")
			{
				
				if ($_REQUEST[$var3] == "grow") { $size = "0"; }
				else { $size = $_REQUEST[$var3]; }
				
				if ($_REQUEST[$var] == "swap")
				{
					//swap partition
					$diskpart .= "disk0-part=$_REQUEST[$var2] $_REQUEST[$var3] none\n";
				}
				// regular partition(s)
				else
				{
					$diskpart .= "disk0-part=$_REQUEST[$var2] $size $_REQUEST[$var]\n";
				}
			}
			$x++;
		}
		generate_bsd($flavor,$row['version'],$_REQUEST['password'],$_REQUEST['public_ip'],$_REQUEST['public_gateway'],$_REQUEST['public_netmask'],$_REQUEST['control_panel'],$_REQUEST['public_mac'],$diskpart,$_REQUEST['dns1'],$_REQUEST['dns2']);
	}
	else if ($trow['use_xen'])
	{
		// No partition setup for Xen Server
		generate_xen($flavor,$row['version'],$_REQUEST['password'],$_REQUEST['public_ip'],$_REQUEST['public_gateway'],$_REQUEST['public_netmask'],$_REQUEST['control_panel'],$_REQUEST['public_mac'],$_REQUEST['dns1'],$_REQUEST['dns2']);
	}	
	else if ($trow['use_unattended'])
	{
		$diskpart = "<DiskConfiguration>\n
                <WillShowUI>OnError</WillShowUI>\n
                <Disk wcm:action='add'>\n
                    <CreatePartitions>\n";
		$use_gpt = $_REQUEST['is_gpt'];
		$x = 1;
		
		while ($x <= 10)
		{
			$var = "disk0mount".$x;
			$var2 = "disk0fs".$x;
			$var3 = "disk0size".$x;
			
			if ($_REQUEST[$var] == "") { break; }
			$diskpart .= "<CreatePartition wcm:action='add'>\n";
			$diskpart .= "<Order>$x</Order>\n";
			if ($_REQUEST[$var3] == "grow") { $diskpart .= "<Extend>true</Extend>\n"; }
			else { $diskpart .= "<Size>$_REQUEST[$var3]</Size>\n"; }
			if ($_REQUEST[$var2] != "NTFS") { $diskpart .= "<Type>$_REQUEST[$var2]</Type>\n</CreatePartition>\n"; }
			else { $diskpart .= "<Type>Primary</Type>\n</CreatePartition>\n"; }
			$x++;
		}
		$diskpart .= "</CreatePartitions>\n";
		$diskpart .= " <ModifyPartitions>\n";
		$x = 1;
		while ($x <= 10)
		{
			$var = "disk0mount".$x;
			$var2 = "disk0fs".$x;
			$var3 = "disk0size".$x;
			
			if ($_REQUEST[$var] == "") { break; }
			// if ($_REQUEST[$var2] != "NTFS") { continue; }
			$diskpart .= " <ModifyPartition wcm:action='add'>\n<Active>true</Active>\n";
			$diskpart .= "<Label>$_REQUEST[$var]</Label>\n";
			$diskpart .= "<Format>$_REQUEST[$var2]</Format>\n";
			$diskpart .= "<Order>$x</Order>\n";
			$diskpart .= "<PartitionID>$x</PartitionID>\n";
			$diskpart .= "</ModifyPartition>\n";
			$x++;
		}
		$diskpart .= "</ModifyPartitions>\n<DiskID>0</DiskID>\n<WillWipeDisk>true</WillWipeDisk>\n</Disk>\n</DiskConfiguration>\n";

		if ($use_gpt)
		{
			$diskpart .= "<ImageInstall>\n
   <OSImage>\n
      <InstallTo>\n
         <PartitionID>3</PartitionID> \n
         <DiskID>0</DiskID> \n
      </InstallTo>\n";
		}
		else
		{
			$diskpart .= "<ImageInstall>\n
   <OSImage>\n
      <InstallTo>\n
         <PartitionID>1</PartitionID> \n
         <DiskID>0</DiskID> \n
      </InstallTo>\n";
		}

		generate_win($flavor,$row['version'],$_REQUEST['password'],$_REQUEST['public_ip'],$_REQUEST['public_gateway'],$_REQUEST['public_netmask'],$_REQUEST['control_panel'],$_REQUEST['public_mac'],$_REQUEST['dns1'],$_REQUEST['dns2'],$diskpart);
	}		
	else
	{
		// Manual interactive install
	}
	
	$ipmi_user = $_REQUEST['ipmi_user'];
	$ipmi_pass = $_REQUEST['ipmi_pass'];
	$ipmi_mac = $_REQUEST['ipmi_mac'];
	
	if ($ipmi_mac) { ipmiReboot($ipmi_user,$ipmi_pass,$ipmi_mac); }
	
	echo "success"; // value returned to javascript to display the success message defined in app.js
}

function main()
{
	/* Start SSO Checks here */
	auth_check();
	page_header();

	global $dbcnx, $version, $g_admin;	
	$user = "Local User";
	$user_icon = "user";
	
	/*
		Disable some functionality if use is deemed "admin" or not
	*/
	
	$disabled = "disabled";
	if ($g_admin) { $disabled = ""; }
	
	db_connect(); // Connect to database
	
	$query = "SELECT * from `os` ORDER BY `name`"; // Query all available OS
	$result = mysqli_query($dbcnx,$query);
	
	$row = mysqli_fetch_array($result);
	
	// Setting defaults
	if ($_REQUEST['control_pannel'] == "plesk") { $plesk = ' checked'; }
	if ($_REQUEST['control_pannel'] == "cpanel") { $cpanel = ' checked'; }
	else { $none = ' checked'; }
	
	if ($_REQUEST['disk0mount1'] == "") { $_REQUEST['disk0mount1'] = "/boot"; }
	if ($_REQUEST['disk0size1'] == "") { $_REQUEST['disk0size1'] = "500"; }
	if ($_REQUEST['disk0fs1'] == "") { $_REQUEST['disk0fs1'] = "ext3"; }

	if ($_REQUEST['disk0mount2'] == "") { $_REQUEST['disk0mount2'] = "swap"; }
	if ($_REQUEST['disk0size2'] == "") { $_REQUEST['disk0size2'] = "4096"; }
	if ($_REQUEST['disk0fs2'] == "") { $_REQUEST['disk0fs2'] = "swap"; }

	if ($_REQUEST['disk0mount3'] == "") { $_REQUEST['disk0mount3'] = "/"; }
	if ($_REQUEST['disk0size3'] == "") { $_REQUEST['disk0size3'] = "grow"; }
	if ($_REQUEST['disk0fs3'] == "") { $_REQUEST['disk0fs3'] = "ext4"; }

	if ($_REQUEST['disk0mount4'] == "") { $_REQUEST['disk0mount4'] = ""; }
	if ($_REQUEST['disk0size4'] == "") { $_REQUEST['disk0size4'] = ""; }
	if ($_REQUEST['disk0fs4'] == "") { $_REQUEST['disk0fs4'] = ""; }

	if ($_REQUEST['disk0mount5'] == "") { $_REQUEST['disk0mount5'] = ""; }
	if ($_REQUEST['disk0size5'] == "") { $_REQUEST['disk0size5'] = ""; }
	if ($_REQUEST['disk0fs5'] == "") { $_REQUEST['disk0fs5'] = ""; }

	if ($_REQUEST['disk0mount6'] == "") { $_REQUEST['disk0mount6'] = ""; }
	if ($_REQUEST['disk0size6'] == "") { $_REQUEST['disk0size6'] = ""; }
	if ($_REQUEST['disk0fs6'] == "") { $_REQUEST['disk0fs6'] = ""; }

	if ($_REQUEST['disk0mount7'] == "") { $_REQUEST['disk0mount7'] = ""; }
	if ($_REQUEST['disk0size7'] == "") { $_REQUEST['disk0size7'] = ""; }
	if ($_REQUEST['disk0fs7'] == "") { $_REQUEST['disk0fs7'] = ""; }	
	
	if ($_REQUEST['disk0mount8'] == "") { $_REQUEST['disk0mount8'] = ""; }
	if ($_REQUEST['disk0size8'] == "") { $_REQUEST['disk0size8'] = ""; }
	if ($_REQUEST['disk0fs8'] == "") { $_REQUEST['disk0fs8'] = ""; }	
	
	if ($_REQUEST['public_netmask'] == "") { $_REQUEST['public_netmask'] = "255.255.255.0"; }
	if ($_REQUEST['private_netmask'] == "") { $_REQUEST['private_netmask'] = "255.255.255.0"; }
	
	if ($_REQUEST['ipmi_user'] == "") { $_REQUEST['ipmi_user'] = "ADMIN"; }
	if ($_REQUEST['ipmi_pass'] == "") { $_REQUEST['ipmi_pass'] = "ADMIN"; }
		
	echo '
	<div class="row center-block">
	<div id="mainCarousel" class="carousel slide">
		<div class="carousel-inner">
			<div id="startPage" class="item active">
				<form class="form-horizontal" id="requiredVariables" name="requiredVariables" action="index.php" method="post" role="form">
				<div class="row" style="padding-left: 5px; padding-top: 10px">
					<div class="col-xs-6 col-md-6">
						<div class="panel panel-primary">
							<div class="panel-heading"><h2 class="panel-title"><strong>OS Information</strong></h2></div>
							<div class="panel-body">
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Server Password</label>
									<div class="col-sm-8 col-lg-8">
										<div class="input-group">
											<input type="text" id="password" class="form-control" name="password" minlength="8" placeholder="root password">
											<span class="input-group-btn">
												<button id="generate" class="btn btn-info btn-default pull-left" type="button"><span class="glyphicon glyphicon-repeat"></span> Generate</button>
											</span>
										</div>
									</div>
								</div>
								
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="top" title data-original-title="Operating system to install">OS</a></label>
									<div class="col-sm-8 col-lg-8">
										<select name="os" id="os" class="form-control">
										';
										$query = "SELECT * from `os` WHERE `trashed` = 0 ORDER BY `name`,`version`";
										$result = mysqli_query($dbcnx,$query);
										
										while ($row1 = mysqli_fetch_array($result))
										{
											$nice_name = $row1['name'] . " " . $row1['version'];
											$id = $row1['id'];
											
											$string = '<option value="'.$id.'"';
											if ($id == $_REQUEST['id']) { $string .= ' selected'; }
											$string .= '>'.$nice_name.'</option>';
											
											echo "$string\n";
										}
										echo '
										<option value="0" selected>Select an OS</option>
										</select>
									</div>
									<div class="control-label col-sm-4 col-xs-5"></div>
									<div id="osComment" class="col-md-8 col-xs-7"></div>
								</div>

								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Control Panel</label>
									<div class="col-sm-4 col-xs-7">
										<input type="radio" id="can_cpanel" name="control_panel" value="cpanel"'.$cpanel.'> cPanel<br>
										<input type="radio" id="can_plesk" name="control_panel" value="plesk"'.$plesk.'> Plesk<br>
										<input type="radio" id="none" name="control_panel" value="none"'.$none.'> None
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">IPMI Username</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="ipmi_user" class="form-control" name="ipmi_user" value="' . $_REQUEST['ipmi_user'] . '"'.$disabled.'>
									</div>
								</div>		
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">IPMI Password</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="ipmi_pass" class="form-control" name="ipmi_pass" value="' . $_REQUEST['ipmi_pass'] . '"'.$disabled.'>
									</div>
								</div>									
							</div>
						</div>
					</div>
					<div class="col-xs-6 col-md-6">
						<div class="panel panel-primary">
							<div class="panel-heading"><h2 class="panel-title"><strong>Network Information</strong></h2></div>
							<div class="panel-body">						
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Public IP</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="public_ip" class="form-control" name="public_ip" placeholder="x.x.x.x" value="' . $_REQUEST['public_ip'] . '">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Public MAC</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="public_mac" class="form-control" name="public_mac" placeholder="00:00:00:00:00:00" value="' . $_REQUEST['public_mac'] . '">
									</div>
								</div>
								
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Will automagicly reboot and set the server for PXE boot if filled in">IPMI MAC</a></label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="ipmi_mac" class="form-control" name="ipmi_mac" placeholder="00:00:00:00:00:00" value="' . $_REQUEST['ipmi_mac'] . '"'.$disabled.'>
									</div>
								</div>
								
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="left" title data-original-title="Auto populated from DHCP Logs">IPMI IP</a></label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="ipmi_ip" class="form-control" name="ipmi_ip" placeholder="x.x.x.x" value="' . $_REQUEST['ipmi_ip'] . '" disabled>
									</div>
								</div>								

								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Public Netmask</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="public_netmask" class="form-control" name="public_netmask" value="' . $_REQUEST['public_netmask'] . '">
									</div>
								</div>

								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">Public Gateway</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="public_gateway" class="form-control" name="public_gateway" placeholder="x.x.x.x" value="' . $_REQUEST['public_gateway'] . '">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">DNS 1</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="dns1" class="form-control" name="dns1" placeholder="x.x.x.x" value="8.8.8.8">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4 col-xs-5">DNS 2</label>
									<div class="col-sm-6 col-xs-7">
										<input type="text" id="dns2" class="form-control" name="dns2" placeholder="x.x.x.x" value="8.8.4.4">
									</div>
								</div>
								<button id="privnet" class="btn btn-warning" type="button" data-toggle="modal" data-target="#PrivNet">
									<span class="glyphicon glyphicon-home"></span> Add Private Net
								</button>
								<div id="infoHolder"></div>
							</div>
						</div>
					</div>
				</div>
					<button id="resetForm" class="btn btn-danger pull-left">
						<span class="glyphicon glyphicon-trash"> Clear</span>
					</button>
					<button class="btn btn-success pull-right" id="next">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</button>
			</div>
			<div id="partitionPage" class="item">
				<div class="row" style="padding-left: 10px; padding-top: 10px">
					<div class="col-xs-2 col-md-2"></div>
					<div class="col-xs-8 col-md-8">
						<div class="panel panel-primary">
							<div class="panel-heading"><h2 class="panel-title"><strong>Disk Partitions - Disk 0 (/dev/sda)</strong></h2></div>
							<div class="panel-body form-inline">								
								<div class="form-group">
									<label class="control-label col-sm-6 col-xs-6"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="bottom" title data-original-title="GPT is used for disks over 2TB Note: UEFI boot MUST be enabled for GPT">Use GPT?</a></label>
									<div class="col-sm-6 col-xs-6">
										<select class="form-control col-sm-3 col-xs-3 input-sm" name="is_gpt" id="is_gpt">
										<option value="1">Yes</option>
										<option value="0" selected>No</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-sm-6 col-xs-6"><a href="#" rel="tooltip" data-toggle="tooltip" data-placement="bottom" title data-original-title="LVM allows you to resize your disk partitions easily as needed">Use LVM?</a></label>
									<div class="col-sm-6 col-xs-6">
										<select class="form-control col-sm-3 col-xs-3 input-sm" name="is_lvm" id="is_lvm">
										<option value="1">Yes</option>
										<option value="0" selected>No</option>
										</select>
									</div>
								</div>
								<br>
								<table class="table table-striped table-hover">
								<tr><th>Partition</th><th>Mount Point</th><th>Size (MB)</th><th>Filesystem</th></tr>
								<tr class="success"><td>1 </td><td><input type="text" class="form-control" id="disk0mount1" name="disk0mount1" list="mounts" value="'.$_REQUEST['disk0mount1'].'"></td><td><input type="text" class="form-control" id="disk0size1" name="disk0size1" list="size" value="'.$_REQUEST['disk0size1'].'"></td><td><input type="text" class="form-control" id="disk0fs1" name="disk0fs1" list="fs" value="'.$_REQUEST['disk0fs1'].'"></td></tr>
								<tr class="success"><td>2 </td><td><input type="text" class="form-control" id="disk0mount2" name="disk0mount2" list="mounts" value="'.$_REQUEST['disk0mount2'].'"></td><td><input type="text" class="form-control" id="disk0size2" name="disk0size2" list="size" value="'.$_REQUEST['disk0size2'].'"></td><td><input type="text" class="form-control" id="disk0fs2" name="disk0fs2" list="fs" value="'.$_REQUEST['disk0fs2'].'"></td></tr>
								<tr class="success"><td>3 </td><td><input type="text" class="form-control" id="disk0mount3" name="disk0mount3" list="mounts" value="'.$_REQUEST['disk0mount3'].'"></td><td><input type="text" class="form-control" id="disk0size3" name="disk0size3" list="size" value="'.$_REQUEST['disk0size3'].'"></td><td><input type="text" class="form-control" id="disk0fs3" name="disk0fs3" list="fs" value="'.$_REQUEST['disk0fs3'].'"></td></tr>
								<tr class="warning"><td>4 </td><td><input type="text" class="form-control" id="disk0mount4" name="disk0mount4" list="mounts" value="'.$_REQUEST['disk0mount4'].'"></td><td><input type="text" class="form-control" id="disk0size4" name="disk0size4" list="size" value="'.$_REQUEST['disk0size4'].'"></td><td><input type="text" class="form-control" id="disk0fs4" name="disk0fs4" list="fs" value="'.$_REQUEST['disk0fs4'].'"></td></tr>
								<tr class="warning"><td>5 </td><td><input type="text" class="form-control" id="disk0mount5" name="disk0mount5" list="mounts" value="'.$_REQUEST['disk0mount5'].'"></td><td><input type="text" class="form-control" id="disk0size5" name="disk0size5" list="size" value="'.$_REQUEST['disk0size5'].'"></td><td><input type="text" class="form-control" id="disk0fs5" name="disk0fs5" list="fs" value="'.$_REQUEST['disk0fs5'].'"></td></tr>
								<tr class="warning"><td>6 </td><td><input type="text" class="form-control" id="disk0mount6" name="disk0mount6" list="mounts" value="'.$_REQUEST['disk0mount6'].'"></td><td><input type="text" class="form-control" id="disk0size6" name="disk0size6" list="size" value="'.$_REQUEST['disk0size6'].'"></td><td><input type="text" class="form-control" id="disk0fs6" name="disk0fs6" list="fs" value="'.$_REQUEST['disk0fs6'].'"></td></tr>
								<tr class="warning"><td>7 </td><td><input type="text" class="form-control" id="disk0mount7" name="disk0mount7" list="mounts" value="'.$_REQUEST['disk0mount7'].'"></td><td><input type="text" class="form-control" id="disk0size7" name="disk0size7" list="size" value="'.$_REQUEST['disk0size7'].'"></td><td><input type="text" class="form-control" id="disk0fs7" name="disk0fs7" list="fs" value="'.$_REQUEST['disk0fs7'].'"></td></tr>
								<tr class="warning"><td>8 </td><td><input type="text" class="form-control" id="disk0mount8" name="disk0mount8" list="mounts" value="'.$_REQUEST['disk0mount8'].'"></td><td><input type="text" class="form-control" id="disk0size8" name="disk0size8" list="size" value="'.$_REQUEST['disk0size8'].'"></td><td><input type="text" class="form-control" id="disk0fs8" name="disk0fs8" list="fs" value="'.$_REQUEST['disk0fs8'].'"></td></tr>
								<tr class="warning"><td>9 </td><td><input type="text" class="form-control" id="disk0mount9" name="disk0mount9" list="mounts" value="'.$_REQUEST['disk0mount9'].'"></td><td><input type="text" class="form-control" id="disk0size9" name="disk0size9" list="size" value="'.$_REQUEST['disk0size9'].'"></td><td><input type="text" class="form-control" id="disk0fs9" name="disk0fs9" list="fs" value="'.$_REQUEST['disk0fs9'].'"></td></tr>
								<tr class="warning"><td>10 </td><td><input type="text" class="form-control" id="disk0mount10" name="disk0mount10" list="mounts" value="'.$_REQUEST['disk0mount10'].'"></td><td><input type="text" class="form-control" id="disk0size10" name="disk0size10" list="size" value="'.$_REQUEST['disk0size10'].'"></td><td><input type="text" class="form-control" id="disk0fs10" name="disk0fs10" list="fs" value="'.$_REQUEST['disk0fs10'].'"></td></tr>
								</table>
								<span class="help-block">Double click a field to list options. "Grow" means to fill/use the remaining space.<br>The UFS filesystems are for FreeBSD default to "UFS"</span>
								<datalist id="fs">
								<option value="ext3">ext3</option>
								<option value="ext4">ext4</option>
								<option value="swap">swap</option>
								<option value="NTFS">NTFS</option>
								<option value="UFS">UFS</option>
								<option value="UFS+S">UFS+S</option>
								<option value="UFS+J">UFS+J</option>
								<option value="UFS+SUJ">UFS+SUJ</option>
								<!-- <option value="lvm">lvm</option> -->
								</datalist>
								<datalist id="size">
								<option value="grow">Fill/Max</option>
								</datalist>
								<datalist id="mounts">
								<option value="swap">swap</option>
								<option value="/">/</option>
								<option value="/boot">/boot</option>
								<option value="/usr">/usr</option>
								<option value="/var">/var</option>
								<option value="/home">/home</option>
								<option value="/tmp">/tmp</option>
								<option value="System">System</option>
								<option value="Data">Data</option>
								<option value="Backup">Backup</option>
								</datalist>
							</div>
							</div>
						</div>
						<div class="col-xs-2 col-md-2"></div>
					</div>
					<input type="hidden" id="mode" name="mode" value="submit">
					<div class="btn-group pull-left">
						<button id="back" class="btn btn-info">
							<span class="glyphicon glyphicon-chevron-left"></span> Back
						</button>
						<button id="resetForm1" class="btn btn-danger">
							<span class="glyphicon glyphicon-trash"> Clear</span>
						</button>
					</div>
					<div class="btn-group pull-right">
						<button class="btn btn-warning" id="disk1" disabled>
							<span class="glyphicon glyphicon-hdd"></span> Disk 1
						</button>						
						<button type="submit" class="btn btn-primary" id="submit" disabled>
							<span class="glyphicon glyphicon-download-alt"></span> PXE Install
						</button>			
				</div>
			</div>
			<div id="partition1Page" class="item">
				<div class="row" style="padding-left: 10px; padding-top: 10px">
					<div class="col-xs-2 col-md-2"></div>
					<div class="col-xs-8 col-md-8">
						<div class="panel panel-primary">
							<div class="panel-heading"><h2 class="panel-title"><strong>Disk Partitions - Disk 1 (/dev/sdb)</strong></h2></div>
							<div class="panel-body form-inline">	
								<br>
								<table class="table table-striped table-hover">
								<tr><th>Partition</th><th>Mount Point</th><th>Size (MB)</th><th>Filesystem</th></tr>
								<tr class="success"><td>1 </td><td><input type="text" class="form-control" name="disk1mount1" list="mounts" value="'.$_REQUEST['disk1mount1'].'"></td><td><input type="text" class="form-control" name="disk1size1" list="size" value="'.$_REQUEST['disk1size1'].'"></td><td><input type="text" class="form-control" name="disk1fs1" list="fs" value="'.$_REQUEST['disk1fs1'].'"></td></tr>
								<tr class="success"><td>2 </td><td><input type="text" class="form-control" name="disk1mount2" list="mounts" value="'.$_REQUEST['disk1mount2'].'"></td><td><input type="text" class="form-control" name="disk1size2" list="size" value="'.$_REQUEST['disk1size2'].'"></td><td><input type="text" class="form-control" name="disk1fs2" list="fs" value="'.$_REQUEST['disk1fs2'].'"></td></tr>
								<tr class="success"><td>3 </td><td><input type="text" class="form-control" name="disk1mount3" list="mounts" value="'.$_REQUEST['disk1mount3'].'"></td><td><input type="text" class="form-control" name="disk1size3" list="size" value="'.$_REQUEST['disk1size3'].'"></td><td><input type="text" class="form-control" name="disk1fs3" list="fs" value="'.$_REQUEST['disk1fs3'].'"></td></tr>
								<tr class="warning"><td>4 </td><td><input type="text" class="form-control" name="disk1mount4" list="mounts" value="'.$_REQUEST['disk1mount4'].'"></td><td><input type="text" class="form-control" name="disk1size4" list="size" value="'.$_REQUEST['disk1size4'].'"></td><td><input type="text" class="form-control" name="disk1fs4" list="fs" value="'.$_REQUEST['disk1fs4'].'"></td></tr>
								<tr class="warning"><td>5 </td><td><input type="text" class="form-control" name="disk1mount5" list="mounts" value="'.$_REQUEST['disk1mount5'].'"></td><td><input type="text" class="form-control" name="disk1size5" list="size" value="'.$_REQUEST['disk1size5'].'"></td><td><input type="text" class="form-control" name="disk1fs5" list="fs" value="'.$_REQUEST['disk1fs5'].'"></td></tr>
								<tr class="warning"><td>6 </td><td><input type="text" class="form-control" name="disk1mount6" list="mounts" value="'.$_REQUEST['disk1mount6'].'"></td><td><input type="text" class="form-control" name="disk1size6" list="size" value="'.$_REQUEST['disk1size6'].'"></td><td><input type="text" class="form-control" name="disk1fs6" list="fs" value="'.$_REQUEST['disk1fs6'].'"></td></tr>
								<tr class="warning"><td>7 </td><td><input type="text" class="form-control" name="disk1mount7" list="mounts" value="'.$_REQUEST['disk1mount7'].'"></td><td><input type="text" class="form-control" name="disk1size7" list="size" value="'.$_REQUEST['disk1size7'].'"></td><td><input type="text" class="form-control" name="disk1fs7" list="fs" value="'.$_REQUEST['disk1fs7'].'"></td></tr>
								<tr class="warning"><td>8 </td><td><input type="text" class="form-control" name="disk1mount8" list="mounts" value="'.$_REQUEST['disk1mount8'].'"></td><td><input type="text" class="form-control" name="disk1size8" list="size" value="'.$_REQUEST['disk1size8'].'"></td><td><input type="text" class="form-control" name="disk1fs8" list="fs" value="'.$_REQUEST['disk1fs8'].'"></td></tr>
								<tr class="warning"><td>9 </td><td><input type="text" class="form-control" name="disk1mount9" list="mounts" value="'.$_REQUEST['disk1mount9'].'"></td><td><input type="text" class="form-control" name="disk1size9" list="size" value="'.$_REQUEST['disk1size9'].'"></td><td><input type="text" class="form-control" name="disk1fs9" list="fs" value="'.$_REQUEST['disk1fs9'].'"></td></tr>
								<tr class="warning"><td>10 </td><td><input type="text" class="form-control" name="disk1mount10" list="mounts" value="'.$_REQUEST['disk1mount10'].'"></td><td><input type="text" class="form-control" name="disk1size10" list="size" value="'.$_REQUEST['disk1size10'].'"></td><td><input type="text" class="form-control" name="disk1fs10" list="fs" value="'.$_REQUEST['disk1fs10'].'"></td></tr>
								</table>
								<span class="help-block">Double click a field to list options. "Grow" means to fill/use the remaining space.<br>The UFS filesystems are for FreeBSD default to "UFS"</span>
							</div>
						</div>
					</div>
					<div class="col-xs-2 col-md-2"></div>
				</div>
				<input type="hidden" id="mode" name="mode" value="submit">
				<div class="btn-group pull-left">
					<button id="back1" class="btn btn-info">
						<span class="glyphicon glyphicon-chevron-left"></span> Back
					</button>
					<button id="resetForm2" class="btn btn-danger">
						<span class="glyphicon glyphicon-trash"> Clear</span>
					</button>
				</div>
				<div class="btn-group pull-right">
					<button type="submit" class="btn btn-primary" id="submit1" disabled>
						<span class="glyphicon glyphicon-download-alt"></span> PXE Install
					</button>						
				</div>
			</div>			
		</div>
	</div>
	<div id="PrivNet" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="PrivNetLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 id="myModalLabel">Private Net Setup</h3>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="control-label col-sm-4 col-xs-5">Private IP</label>
						<div class="col-md-6 col-xs-6">
							<input type="text" class="form-control" id="private_ip" name="private_ip" placeholder="x.x.x.x" value="' . $_REQUEST['private_ip'] . '">
						</div>
					</div>	  
					<div class="form-group">
						<label class="control-label col-sm-4 col-xs-5">Private MAC</label>
						<div class="col-md-6 col-xs-6">
							<input type="text" class="form-control" id="private_mac" name="private_mac" placeholder="00:00:00:00:00:00" value="' . $_REQUEST['private_mac'] . '">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4 col-xs-5">Private Netmask</label>
						<div class="col-md-6 col-xs-6">
							<input type="text" class="form-control" id="private_netmask" name="private_netmask" placeholder="255.255.255.0" value="' . $_REQUEST['private_netmask'] . '">
						</div>
					</div>		
					<br><br><br>
				</div>
				<div class="modal-footer">
					<button class="btn btn-success" data-dismiss="modal" aria-hidden="true">Close</button>
				</div>
			</div>
		</div>
	</div>
	</form>
	<nav class="navbar navbar-inverse navbar-fixed-bottom" role="navigation">
		<div class="navbar-header">';
		
		$file = "version.track";
		$fread = file_get_contents($file);
		$data = explode(",",$fread);
		$updated = "";
		
		//var_dump($fread);
		if ($data[1] != $version)
		{
			$updated = "<span class=\"label label-primary\">Updated</span>";
			$count = 1;
			$info = $count.",".$version;
			file_put_contents($file,$info);
		}
		else if ($data[0] <= 15 && $data[1] == $version)
		{
			$updated = "<span class=\"label label-primary\">Updated</span>";
			$count = $data[0]+1;
			$info = $count.",".$version;
			file_put_contents($file,$info);		
		}
		
		echo '
			<p class="navbar-brand">Version '.$version.' '.$updated.'</p>
		</div>
		<p class="navbar-text navbar-right hidden-xs ';echo $user_text; echo'"><span class="glyphicon glyphicon-volume-up" id="audio" style="padding-right:5em"></span>Signed in as <span class=" glyphicon glyphicon-';echo $user_icon; echo'"></span> <strong>';echo $user; echo '</strong></p>
	</nav>
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
	<script id="infoTemplate" type="text/plain">
    <div class="text-info">
		 <span class="message"></span>
    </div>
	</script>	
	<script id="osComments" type="text/plain">
    <div class="col-md-10 col-xs-12">
		 <span class="message"></span>
    </div>
	</script>		
	</body>
	<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script src="//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/jquery.validate.min.js"></script>
	<script src="//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.0/additional-methods.min.js"></script>
	<script src="js/app.js"></script>
	</html>
	';
}


switch($mode) {

	case "findipmi":
	findIPMI();
	break;

	case "submit":
	verify();
	break;
	
	default:
	main();
	break;
}

?>

<?PHP 
header('Content-Type: application/json'); 
$dbcnx = 0; 

/* 
  Database connection function 
*/ 
function db_connect() 
{ 
  global $dbcnx; 
  require('config.php'); 
  $dbcnx = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_database) or die("&error1=".mysqli_error($dbcnx)); 
  //mysql_select_db($mysql_database, $dbcnx); 
  mysqli_query($dbcnx,"set session wait_timeout=600"); // set session timeout to 600 seconds 
} 

function main() 
{ 
  global $dbcnx; 
  db_connect(); 
  
  $query = "SELECT `os`,`installed` FROM `stats` ORDER BY `installed` DESC LIMIT 16"; 
  $result = mysqli_query($dbcnx,$query); 
  
  while ($row = mysqli_fetch_array($result)) 
  { 
    $chartData[] = array($row['os'], intval($row['installed'])); 
  } 
  echo json_encode($chartData); 
} 

main(); 

?> 

<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$sql_db_host = "localhost";
$sql_db_user = "root";
$sql_db_pass = "==Z9C2ZQ@MfqebQ";
$sql_db_name = "sample_CL_data_v1";
$site_url = "https://sb236.cryptoland.io";

$servername = $sql_db_host;
$username = $sql_db_user;
$password = $sql_db_pass;
$db = $sql_db_name;



$conn = mysqli_connect($servername, $username, $password, $db);
//echo "Connected successfully"; 


$where = "";
$sql = "SELECT * from vials_nft";
//echo $sql;
$result = mysqli_query($conn, $sql) or die("Error in Selecting " . mysqli_error($conn));
echo $result;
while ($row = mysqli_fetch_assoc($result)) {
    $jsonArray[] = $row;
}

return $jsonArray;

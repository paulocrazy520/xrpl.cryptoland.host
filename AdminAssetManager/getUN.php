<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

//die;


$sql_db_host = "localhost";
$sql_db_user = "sbDBAdmin";
$sql_db_pass = "xP@~~w0rd@2o22x";
$sql_db_name = "community.cryptoland.io.srv1.sandboxit.dev_0302202301_t";

$servername = $sql_db_host;
$username = $sql_db_user;
$password = $sql_db_pass;
$db = $sql_db_name;

$conn = mysqli_connect($servername, $username, $password, $db);

$where = "WHERE user_id = 7";

// $sql = "SELECT JSON_ARRAYAGG(JSON_OBJECT('nft_uuid', nft_uuid, 'nft_id', nft_id, 'user_id', user_id, 'date_created', date_created, 'owner_wallet', owner_wallet)) from user_nft $where";
$sql = "SELECT * from user_nft $where";
// $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

$sql2 = mysqli_query($conn, $sql);
// if (mysqli_num_rows($sql2)) {
//     $fetched_data = mysqli_fetch_assoc($sql);
// }
// //}
// //$sql

// if (empty($fetched_data)) {
//     return array();
// }

if (mysqli_num_rows($sql2)) {
    while ($fetched_data = mysqli_fetch_assoc($sql2)) {
        //$fetched_data            = NRG_GameData($fetched_data["id"]);
        //$fetched_data["nft_id"] = NRG_CountGamePlayers($fetched_data["id"]);
        $data[]                  = $fetched_data;
    }
}
echo json_encode($data);
var_dump($data);

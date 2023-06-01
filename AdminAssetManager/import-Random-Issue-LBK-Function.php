<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

//die;
function getdb()
{

  $sql_db_host = "localhost";
  $sql_db_user = "root";
  $sql_db_pass = "==Z9C2ZQ@MfqebQ";
  $sql_db_name = "sample_CL_data_v1";
  $site_url = "https://dev2.cryptoland.io";

  $servername = $sql_db_host;
  $username = $sql_db_user;
  $password = $sql_db_pass;
  $db = $sql_db_name;

  try {

    $conn = mysqli_connect($servername, $username, $password, $db);
    //echo "Connected successfully"; 
  } catch (exception $e) {
    echo "Connection failed: " . $e->getMessage();
  }
  return $conn;
}

if (isset($_POST["Import"])) {

  $filename = $_FILES["file"]["tmp_name"];
  if ($_FILES["file"]["size"] > 0) {
    $file = fopen($filename, "r");
    while (($getData = fgetcsv($file, NULL, ",")) !== FALSE) {
      $timeNow = time(); //(int)UNIX_TIMESTAMP(now());
      $user_id = $getData[0];
      $con = getdb();
      $sql = "INSERT INTO user_nft
             (
             nft_uuid
             ,nft_id
             ,user_id
             ,date_created
             ,last_update
             ,owner_wallet
             ,assetType
             )
             VALUES
             (
              (SELECT nft_uuid FROM lbk_nft WHERE nft_id = '$getData[2]')
, '$getData[2]'
, '" . (int)$user_id . "'
, $timeNow
, $timeNow
, '$getData[7]'
, '1'
);
             ";

      /*


             (SELECT nft_uuid FROM lbk_nft WHERE nft_id = '$getData[2]')
             ,'$getData[2]'
             ,$user_id
             ,$timeNow
             ,$timeNow
             ,'$getData[1]'
             ,'1'
             );

             */

      // ".$getData[0]."


      //$sql = "INSERT INTO NRG_Users (`username`, `email`, `password`, `email_code`, `first_name`, `last_name`, `avatar`, `src`, `startup_image`, `lastseen`, `social_login`, `active`, `totalLand`, `totalAvatar`,`ref_community`)values ('".$getData[0]."','".$getData[1]."','$2y$10$KYwHgASDYo9VPMpgNASD0.CiXp.EEUuKn6WASDbyS1zULmFc73QS','','','','upload/photos/d-avatar.jpg','import',1,'1657028903',0,1,'".$getData[2]."','".$getData[3]."','".$getData[4]."')";
      echo $sql . "<br>";


      $result = mysqli_query($con, $sql);


      if (!isset($result)) {
        // echo "<script type=\"text/javascript\">
        //     alert(\"Invalid File:Please Upload CSV File.\");
        //     window.location = \"index.php\"
        //     </script>";    
      } else {
        //   echo "<script type=\"text/javascript\">
        //   alert(\"CSV File has been successfully Imported.\");
        //   window.location = \"index.php\"
        // </script>";
      }
    }

    fclose($file);
  }
}


function get_all_records()
{
  $con = getdb();
  $Sql = "SELECT * FROM user_nft";
  $result = mysqli_query($con, $Sql);
  if (mysqli_num_rows($result) > 0) {
    echo "<div class='table-responsive'><table id='myTable' class='table table-striped table-bordered'>
      <thead> 
       <tr>
         <th>id</th>
         <th>nft_uuid</th>
         <th>nft_id</th>
         <th>user_id</th>
         <th>date_created</th>
         <th>last_update</th>
         <th>owner_wallet</th>
       </tr>
     </thead>
   <tbody>";
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr>
         <td>" . $row['id'] . "</td>
         <td>" . $row['nft_uuid'] . "</td>
         <td>" . $row['nft_id'] . "</td>
         <td>" . $row['user_id'] . "</td>
         <td>" . $row['date_created'] . "</td>
         <td>" . $row['last_update'] . "</td>
         <td>" . $row['owner_wallet'] . "</td>
       </tr>";
    }

    echo "</tbody></table></div>";
  } else {
    echo "you have no records";
  }
}

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
    //$metaURI = $getData[3]+1;
    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
      $variable = $getData[3] + 1;
      $timeNow = time(); //(int)UNIX_TIMESTAMP(now());
      $con = getdb();
      $sql = "INSERT INTO vials_nft (
              nft_uuid
              , nft_id
              , issuer_wallet
              , owner_wallet
              , nft_serial
              , minted_date
              , base_uri
              , taxon
              , burnable
              , only_xrp
              , transferable
              , claimed
              , claimed_user_id
              , claimed_date
              , revealed
              , revealed_user_id
              , revealed_date
              )
              VALUES
              (
                UUID()
                , '$getData[0]'
                , '$getData[1]'
                , '$getData[1]'
                , $getData[3]
                , $timeNow
                , 'https://ingameassets.cryptoland.host/testNet/metadata/{$variable}.json'
                , '0'
                , '1'
                , '0'
                , '1'
                , '0'
                , 0
                , 0
                , '0'
                , 0
                , 0
                )";

      //echo $sql . "<br>";
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
  $Sql = "SELECT * FROM vials_nft";
  $result = mysqli_query($con, $Sql);
  if (mysqli_num_rows($result) > 0) {
    echo "<div class='table-responsive'><table id='myTable' class='table table-striped table-bordered'>
      <thead> 
       <tr>
        <th>id</th>
         <th>nft_uuid</th>
         <th>nft_id</th>
         <th>issuer_wallet</th>
         <th>owner_wallet</th>
         <th>nft_serial</th>
         <th>minted_date</th>
         <th>base_uri</th>
         <th>taxon</th>
         <th>burnable</th>
         <th>only_xrp</th>
         <th>transferable</th>
         <th>claimed</th>
         <th>claimed_user_id</th>
         <th>claimed_date</th>
       </tr>
     </thead>
   <tbody>";
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr>
         <td>" . $row['id'] . "</td>
         <td>" . $row['nft_uuid'] . "</td>
         <td>" . $row['nft_id'] . "</td>
         <td>" . $row['issuer_wallet'] . "</td>
         <td>" . $row['owner_wallet'] . "</td>
         <td>" . $row['nft_serial'] . "</td>
         <td>" . $row['minted_date'] . "</td>
         <td>" . $row['base_uri'] . "</td>
         <td>" . $row['taxon'] . "</td>
         <td>" . $row['burnable'] . "</td>
         <td>" . $row['only_xrp'] . "</td>
         <td>" . $row['transferable'] . "</td>
         <td>" . $row['claimed'] . "</td>
         <td>" . $row['claimed_user_id'] . "</td>
         <td>" . $row['claimed_date'] . "</td>
       </tr>";
    }

    echo "</tbody></table></div>";
  } else {
    echo "you have no records";
  }
}

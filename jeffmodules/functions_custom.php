<?php
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

/********************Load Environment Variables********************* */
$apiKey = $_ENV['API_KEY'];  //jeff
$apiSecret =  $_ENV['API_SECRET']; //jeff
$default_issuer_address = $_ENV['DEFAULT_ISSUER_ADDRESS'];
$num_results_on_page = $_ENV['SHOW_ITEMS_PER_PAGE'];; 

$endpoint_url = $_ENV['API_ENDPOINT_URL'];
$server_url = $_ENV['NODEBACKEND_SERVER_URL'];

$xummSdk = new \Xrpl\XummSdkPhp\XummSdk($apiKey, $apiSecret);

    //*************Only for test*************/
    //rff8Vm4iHRZ9xJgzDzGLJ6ME74ugByUKvE is for user_id 3
    //rwfvmEP2yGy3vxhqvsSBkZcPFpHxfuNv5v is for user_id 4
    //rez9qjAodo6NnX8Rm14RJiagt5JZ3d76a is for user_id 5
    
    $userWalletPairs= array("3"=>"rff8Vm4iHRZ9xJgzDzGLJ6ME74ugByUKvE", "4"=>"rwfvmEP2yGy3vxhqvsSBkZcPFpHxfuNv5v", "5"=>"rez9qjAodo6NnX8Rm14RJiagt5JZ3d76a");
/****************************************************************** */

if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]))
{    
    $userInfo = GetUserInfoByUserId($_SESSION["user_id"]);

    if($userInfo["xumm_user_token"])
    {
        if($result = $xummSdk->verifyUserToken($userInfo["xumm_user_token"]))
        {
            $timestamp = $userInfo["xumm_timestamp"];
            $twenty_four_hours_ago = time() - (24 * 60 * 60);

            if ($timestamp < $twenty_four_hours_ago) { // The timestamp is older than 24 hours
                //own code
            }
            else{
                $current_user = $userInfo['xumm_address'];
                $nrg["user"] = $userInfo; //For using database functions provided existing project
            }
        }
    }
}


/*****************Create asset folder for test*************************/
//This functions can be called optionally//
function CreateAssetFolder(){

    $typeArray = ["vials", "lbk", "box", "land", "cryptopian"];

    $patternArray = ["https://ingameassets.cryptoland.host/testNet/vials/live-metadata/",
    "https://ingameassets.cryptoland.host/testNet/vials/revealed-metadata/",
    "https://ingameassets.cryptoland.host/testNet/vials/archived-metadata/",
    "https://ingameassets.cryptoland.host/testNet/vials/image/",
    "https://ingameassets.cryptoland.host/testNet/vials/video/",
    "https://ingameassets.cryptoland.host/testNet/vials/animation/"];

    $remotePath = "https://ingameassets.cryptoland.host";
    $localPath = "/var/www/htdocs/ingameassets.cryptoland.host";
    
    foreach($patternArray as $pattern)
    {
        foreach($typeArray as $type)
        {
            $convertedFolder =  str_replace("vials", $type, str_replace($remotePath, $localPath, $pattern));
            if (!file_exists($convertedFolder)) {
                $oldmask = umask(0);
                @mkdir($convertedFolder, 0777, true);
                @umask($oldmask);
            }
        }
    }

    // $fn = "xxx.json";
    // $file = $convertedFolder.$fn;
    // if (!is_file($convertedFolder . $fn)) {
    //     $contents = "New File! \n\n";
    //     file_put_contents($convertedFolder . $fn, $contents);
    // }

    // $fileName = $convertedFolder . $fn;
    // $handle   = fopen($fileName, 'a');
    // fwrite($handle, $q . "\n\n");
    // fclose($handle);
}

/*****************Update db with nfts owned by the owner from XRPL Server******************* */
//This functions can be called optionally//
//Update infos with xrpl data from server
//owner_address and user_id of CryptoLand_NFTs table

function UpdateDBForOwner($account){
    global $server_url, $sqlConnect,  $default_issuer_address;

    if(!$account || !isset($_SESSION["user_id"]))
        return;

    $query_params = [
        'menuCollection' => "",
		'account' => $account
    ]; 
            
    $nft_array =  GetOwnedNftsFromServer($query_params);

    try{
        foreach($nft_array as $nft_id)
        {

            $sql = "SELECT nft_id, owner_wallet from CryptoLand_NFTs WHERE nft_id = '$nft_id'";
            $result = mysqli_query($sqlConnect, $sql);

            $jsonArray = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $jsonArray[] = $row;
            }

            if($jsonArray && count($jsonArray) >= 1)
            {
                if($account == $default_issuer_address){
                    // $sql =  "UPDATE CryptoLand_NFTs 
                    // SET 
                    // owner_wallet='".$account."',
                    // transferred='0', revealed='0'
                    // claimed_date=CASE WHEN TIMESTAMPDIFF(HOUR, FROM_UNIXTIME(claimed_date), NOW()) >= 1,
                    // claimed=CASE WHEN TIMESTAMPDIFF(HOUR, FROM_UNIXTIME(vials_nft.claimed_date), NOW()) >= 1   
                    // WHERE nft_id ='".$nft_id."'";
                    // $result = mysqli_query($sqlConnect, $sql) ;   
                }
                else
                {
                    if($jsonArray[0]["owner_wallet"] == $account)
                        continue;

                    $sql =  "UPDATE CryptoLand_NFTs
                    SET 
                    owner_wallet='".$account."',
                    user_id ='".$_SESSION["user_id"]."',
                    transferred='1', claimed='1'
                    WHERE nft_id ='".$nft_id."' ";
                    $result = mysqli_query($sqlConnect, $sql) ;   

                }
            }
        }
    }
    catch(Exception $e){
        //print_r($e);
        //echo $sql;
    }
}

/*****Update CryptoLand_NFTs table for specific issuer address using infos from xrpl server****** */
//This function will be used when add new nfts to database
function UpdateDBByIssuersFromServer($issuer = null){
    global $sqlConnect, $current_user, $default_issuer_address;

    if(!$issuer)
        $issuer = $default_issuer_address;

    $ownedNfts = GetIssuedNftsFromServer($issuer);

    $index = 0;
    $user_id = 0;
    $countArray1 = [0,0,0,0,0];
    $countArray2 = [0,0,0,0,0];
    $countArray3 = [0,0,0,0,0];

    foreach($ownedNfts as $nft){
        // if(!GetNftInfoFromDatabase($nft->NFTokenID))
        {
            $nft_id = $nft->NFTokenID;
            $owner_wallet = $nft->Owner;
            $issuer_wallet = $nft->Issuer;

            $base_uri = GetAsciiStringFromHex($nft->URI);
            $path_parts = pathinfo($base_uri);
            $base_uri_directory = $path_parts["dirname"];
            $file = $path_parts["basename"];

            $nft_serial = $nft->Sequence;
            $taxon = $nft->Taxon;

            $jsonString = file_get_contents($base_uri);
            $json = json_decode($jsonString, true);
            $name = $json['name']; // Pull Name data from URI

            $reserved = 0;

            if(strpos($name, "Key #") !== false) 
            {
                $assetType = 1;

                if($countArray1[0] < 15){
                    $countArray1[0]++;
                    $user_id = 3;
                }
                else if($countArray2[0] < 15){
                    $countArray2[0]++;
                    $user_id = 4;
                }
                else if($countArray3[0] < 15){
                    $countArray3[0]++;
                    $user_id = 5;
                }
                else
                    $user_id = 0;

            }
            else if(strpos($name, "Vial #") !== false)
            {
                $assetType = 2;

                if($countArray1[1] < 15){
                    $countArray1[1]++;
                    $user_id = 3;
                }
                else if($countArray2[1] < 15){
                    $countArray2[1]++;
                    $user_id = 4;
                }
                else if($countArray3[1] < 15){
                    $countArray3[1]++;
                    $user_id = 5;
                }
                else
                    $user_id = 0;

            }
            else if(strpos($name, "Box #") !== false){
                $assetType = 3;

                if($countArray1[2] < 15){
                    $countArray1[2]++;
                    $user_id = 3;
                }
                else if($countArray2[2] < 15){
                    $countArray2[2]++;
                    $user_id = 4;
                }
                else if($countArray3[2] < 15){
                    $countArray3[2]++;
                    $user_id = 5;
                }
                else
                    $user_id = 0;

            }
            else if(strpos($name, "Land #") !== false)
            {
                $assetType = 4;

                if($countArray1[3] < 15){
                    $countArray1[3]++;
                    $user_id = 3;
                }
                else if($countArray2[3] < 15){
                    $countArray2[3]++;
                    $user_id = 4;
                }
                else if($countArray3[3] < 15){
                    $countArray3[3]++;
                    $user_id = 5;
                }
                else
                    $user_id = 0;
            }
            else if(strpos($name, "Avatar #") !== false || strpos($name, "Cryptopian #") !== false)
            {
                $assetType = 5;

                if($countArray1[4] < 15){
                    $countArray1[4]++;
                    $user_id = 3;
                }
                else if($countArray2[4] < 15){
                    $countArray2[4]++;
                    $user_id = 4;
                }
                else if($countArray3[4] < 15){
                    $countArray3[4]++;
                    $user_id = 5;
                }else
                $user_id = 0;
            }
            else if(strpos($name, "Crystal #") !== false )
            {
                $assetType = 6;

                if($countArray1[5] < 15){
                    $countArray1[5]++;
                    $user_id = 3;
                }
                else if($countArray2[5] < 15){
                    $countArray2[5]++;
                    $user_id = 4;
                }
                else if($countArray3[5] < 15){
                    $countArray3[5]++;
                    $user_id = 5;
                }else
                $user_id = 0;
            }

            $timeNow = time();

            $sql = "SELECT nft_id from CryptoLand_NFTs WHERE nft_id = '$nft_id'";
            $result = mysqli_query($sqlConnect, $sql);

            $jsonArray = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $jsonArray[] = $row;
            }


            if(empty($jsonArray))
            {
                $sql = "INSERT INTO CryptoLand_NFTs (
                    nft_uuid, nft_id, nft_serial, minted_date, base_uri, file, taxon, burnable, only_xrp, transferable, 
                    claimed, claimed_user_id, claimed_date, 
                    transfered, transfered_user_id, transfered_date, tx_id,
                    revealed,revealed_user_id, revealed_date, 
                    user_id, date_created, last_update, issuer_wallet, owner_wallet, reserved, assetType
                  )
                    VALUES
                    (
                        UUID(), '$nft_id',  '$nft_serial' , $timeNow, '$base_uri_directory', '$file', '0', '1', '0', '1',
                        '0', '0', '0',
                        '0', '0', '0', '',
                        '0', '0', '0',
                        '$user_id', $timeNow, $timeNow, '$issuer_wallet', '$owner_wallet','$reserved', '$assetType'
                      )";

                echo "<br/>";
                $result = mysqli_query($sqlConnect, $sql);
            }
            else
            {
                $sql = "UPDATE CryptoLand_NFTs SET 
                        nft_uuid = UUID(),
                        nft_serial = '$nft_serial',
                        minted_date = $timeNow,
                        base_uri = '$base_uri_directory',
                        file = '$file',
                        taxon = '0',
                        burnable = '1',
                        only_xrp = '0',
                        transferable = '1',
                        claimed = '0',
                        claimed_user_id = '0',
                        claimed_date = '0',
                        transfered = '0',
                        transfered_user_id = '0',
                        transfered_date = '0',
                        tx_id = '',
                        revealed = '0',
                        revealed_user_id = '0',
                        revealed_date = '0',
                        user_id = '$user_id',
                        last_update = $timeNow,
                        issuer_wallet = '$issuer_wallet',
                        owner_wallet = '$owner_wallet',
                        reserved = '$reserved',
                        assetType = '$assetType'
                WHERE nft_id = '$nft_id';";

                print_r($sql);
                echo "<br/>";
                $result = mysqli_query($sqlConnect, $sql);
            }
            $index++;
        }
    }

}

/*****************Get revealed and unrevealed items from Database and claim items from Node server*****************/
function GetRevealNftArraysFromDatabase($claimedArray)
{
    global $sqlConnect, $current_user, $default_issuer_address;

    if(!$current_user)
    {
        return;
        //$account = $default_issuer_address;
    }
    else
        $account = $current_user;

    $sql =  "SELECT nft_id, base_uri, file, revealed FROM CryptoLand_NFTs
    WHERE owner_wallet= '$account'";

    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }

    if($jsonArray && count($jsonArray) >= 1)
    {
        $revealedArray = array();
        $unrevealedArray = array();

        foreach($claimedArray as $nft_id){
            $flag = false;
            $nft = null;
            foreach($jsonArray as $item){
                if ($item['nft_id'] == $nft_id)
                {   
                    $nft = $item;
                    $flag = true;
                    break;
                }
            }

            if($flag == false) continue;

            if($nft['revealed'] == 1)
                array_push($revealedArray, $nft);
            else
                array_push($unrevealedArray, $nft);
            
        }

        return ["revealedArray"=>$revealedArray, "unrevealedArray" =>$unrevealedArray];
    }
}


/*****************Get Nft Infos from Database*****************/
function GetNftInfoFromDatabase($nft_id)
{
    global $sqlConnect;

    $sql =  "SELECT nft_id FROM CryptoLand_NFTs WHERE nft_id = '$nft_id'";
    
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }

    if($jsonArray && count($jsonArray) >= 1)
    return $jsonArray[0];
}


/*****************Get owned items from the all issuers and user id from Server and Database*****************/
function GetOwnedNftArrayByIssuersFromDatabase($issuedNfts)
{
    global $sqlConnect, $current_user, $default_issuer_address;

    if(!$issuedNfts ||  !count($issuedNfts) || !isset($_SESSION["user_id"]))
        return;

    if(!$current_user)
    {
        return;
        //$account = $default_issuer_address;
    }
    else
        $account = $current_user;


    //Get un transferred nft array from datbase
    $sql =  "SELECT nft_id, base_uri, file, transferred, claimed FROM CryptoLand_NFTs
    WHERE  user_id='".$_SESSION["user_id"]."' AND transferred = '0'";

    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));
    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }


    if($jsonArray && count($jsonArray) >= 1)
    {
        $finalArray = array();

        foreach($jsonArray as $nft){
            if (in_array( $nft["nft_id"], $issuedNfts)) {
                    array_push($finalArray, $nft);
            }
        }

        return $finalArray;
    }
}

/*****************Get UnClaimed(Owned) Nfts from all issuers by owned account from Node Server******************* */
function GetOwnedNftsByIssuersFromServer($query_params){
    global  $current_user, $server_url, $default_issuer_address;

    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' => $server_url 
    ]);

    $response = $client->request('GET', '/owned_nfts_issuers', ['query' => $query_params]);
    
    // Convert the JSON response to an array for easier processing
    $claimedArray = json_decode($response->getBody());
    return $claimedArray;    
}


/*****************Get Issued Nft Infos by account from Node Server******************* */
function GetIssuedNftsFromServer($account = null){
    global $current_user, $server_url, $default_issuer_address;
    
    if(!$account)
    {
        if(!$current_user)
        {
            return;
            //$account = $default_issuer_address;
        }
        else
            $account = $current_user;
    }
        
    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' =>$server_url 
    ]);

    
    $query_params = ['account' => $account];
    $response = $client->request('GET', '/issued_nfts', ['query' => $query_params]);

    // Convert the JSON response to an array for easier processing
    $response_array = json_decode($response->getBody());

    return $response_array;    
}

/*****************Get Owned Nft Infos by account from Node Server******************* */
function GetOwnedNftsFromServer($query_params){
    global $current_user, $server_url, $default_issuer_address;
    
    $client = new \GuzzleHttp\Client();
    $client = new Client([
        'base_uri' => $server_url 
    ]);

    $response = $client->request('GET', '/owned_nfts', ['query' => $query_params]);

    // Convert the JSON response to an array for easier processing
    $response_array = json_decode($response->getBody());

    return $response_array;    
}

/*****************Get Marketplace Nft Infos by account from Node Server******************* */
//$totalArray = GetNftArrayForMarketplaceFromServer($menuCollection, $menuRarity, $menuColor, $menuSale, $menuBid, $cardsCount);
function GetNftArrayForMarketplaceFromServer($menuCollection, $menuRarity, $menuColor, $menuSale, $menuBid, $cardsCount){
    global $current_user, $server_url, $default_issuer_address;

    if(!$current_user)
    {
        return;
        //$account = $default_issuer_address;
    }
    else
        $account = $current_user;
        
    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' =>$server_url 
    ]);

    $query_params = [
        'account' => $account,
        'menuCollection' => $menuCollection,
        'menuRarity' => $menuRarity,
        'menuColor' => $menuColor,
        'menuSale' => $menuSale,
        'menuBid' => $menuBid,
        'cardsCount' => $cardsCount
    ];

    $response = $client->request('GET', '/marketplace_infos', ['query' => $query_params]);
    // Convert the JSON response to an array for easier processing
    $response_array = json_decode($response->getBody());

    return $response_array;    
}

/******************Get full deatiled nft info from bithomp server************************ */
function GetDetailNftInfoFromBithomp($filter, $nftTokenId){
    global $apiKey, $apiSecret;

    $client = new \GuzzleHttp\Client();
    $client = new Client([
        'base_uri' => $_ENV['XRPL_BITHOMP_URL'].'/api/cors/v2/nft/'
    ]);

    if(!$filter)
        $filter = "uri=true&metadata=true&history=true&sellOffers=true&buyOffers=true&offersValidate=true&offersHistory=true";

    $request = $client->getAsync("$nftTokenId?$filter");
    $response = $request->wait();

    // Convert the JSON response to an array for easier processing
    $response_array = json_decode($response->getBody());
    return $response_array;    
}

/******************Get offer info from bithomp server************************ */
function GetOfferInfoFromBithomp($offerIndex){ 
    global $apiKey, $apiSecret;
    
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetOfferInfoFromBithomp Start:".$offerIndex);

    $client = new Client([
        'base_uri' => $_ENV['XRPL_BITHOMP_URL'].'/api/cors/v2/search/', 'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $apiKey,
            'X-API-Secret' => $apiSecret,
        ],
    ]);
    $request = $client->getAsync($offerIndex);
    $response = $request->wait();

    $result = json_decode($response->getBody()->getContents())->data;

    return $result;
}

/*************Get nft offers from token id and offer type******************** */
function GetNftOffersByParams($offerType, $nftTokenId){ 
    global $apiKey, $apiSecret;
    
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetNftOffersByParams Start:".$offerType.",".$nftTokenId);

    $client = new Client([
        'base_uri' => $_ENV['XRPL_DATA_URL'].'/api/v1/xls20-nfts/', 'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $apiKey,
            'X-API-Secret' => $apiSecret,
        ],
    ]);
    $request = $client->getAsync("offers/nft/$nftTokenId");
    $response = $request->wait();
    
    if($offerType == "sell_offers")
        $result = json_decode($response->getBody()->getContents())->data->offers->sell;
    else  if($offerType == "buy_offers")//buy_offers
        $result = json_decode($response->getBody()->getContents())->data->offers->buy;
    else
        $result = json_decode($response->getBody()->getContents())->data->offers;

    return $result;
}

/*************Get nft info from xrpl data api************** */
function GetNftInfoFromApi($nftTokenId){
    global $apiKey, $apiSecret;
    
    $client = new Client([
        'base_uri' => $_ENV['XRPL_DATA_URL'].'/api/v1/xls20-nfts/', 'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $apiKey,
            'X-API-Secret' => $apiSecret,
        ],
    ]);

    $promises = [
        'result' => $client->getAsync("nft/$nftTokenId")
    ];

    $results = Promise\unwrap($promises);
    $result = json_decode($results['result']->getBody()->getContents())->data;
    return $result;
}

/*************Get nft infos for marketplacve from xrpl data api************** */
function GetNftArrayForMarketplacFromApi()
{
    global $current_user, $default_issuer_address, $apiKey, $apiSecret;

    if (!$current_user) {
        $account = $default_issuer_address; 
    }
    else
        $account = $current_user;

    $client = new Client([
        'base_uri' => $_ENV['XRPL_DATA_URL'].'/api/v1/xls20-nfts/',
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $apiKey,
            'X-API-Secret' => $apiSecret,
        ],
    ]);

    try {
        $issuer_issue_file = "payload_issuer_issue.log";
        $issuer_issue_file_path = GetLogFilePath($issuer_issue_file);

        if(file_exists($issuer_issue_file_path) && time() - filemtime($issuer_issue_file_path) < 3600){
            $jsonString = file_get_contents($issuer_issue_file_path);
            $result1 = json_decode($jsonString);
        }
        else
        {
            $request = $client->getAsync("issuer/$default_issuer_address");
            $response = $request->wait();
            $result1 = json_decode($response->getBody()->getContents())->data->nfts;
            JEFF_writeFileByMode($issuer_issue_file, json_encode($result1), 'w');
        }

        $request = $client->getAsync("offers/issuer/$default_issuer_address");
        $response = $request->wait();
        $offerResult = json_decode($response->getBody()->getContents())->data->offers;
       
        $result = [];

        foreach ($result1 as $r1) {
          if($r1->Owner == $default_issuer_address)
            continue;

            // add item
            $item = [
                'NFTokenID' => $r1->NFTokenID,
                'Owner' => $r1->Owner,
                'Issuer' => $r1->Issuer,
                'URI' => GetAsciiStringFromHex($r1->URI),
                'IsUser' => $r1->Owner == $account ? "true" : "false",
                // has sell offer
                'hasSellOffer' => ($offerResult && array_filter($offerResult, fn($offer) => $offer->sell && array_filter($offer->sell, fn($data) => $data->NFTokenID ==$r1->NFTokenID))) ? "true" : "false",
                // has sell offer by user
                'hasSellOfferByUser' => ($offerResult && array_filter($offerResult, fn($offer) => $offer->sell && array_filter($offer->sell, fn($data) => $data->Owner == $account && $data->NFTokenID == $r1->NFTokenID))) ? "true" : "false",
                // has buy offer
                'hasBuyOffer' => ($offerResult && array_filter($offerResult, fn($offer) => $offer->buy && array_filter($offer->buy, fn($data) => $data->NFTokenID == $r1->NFTokenID))) ? "true" : "false",
                // has buy offer by user
                'hasBuyOfferByUser' => ($offerResult && array_filter($offerResult, fn($offer) => $offer->buy && array_filter($offer->buy, fn($data) => $data->Owner == $account && $data->NFTokenID == $r1->NFTokenID))) ? "true" : "false",
            ];
            $result[] = $item;
        }

        return $result;
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
}

/*********Write log file with mode*********** */
function JEFF_writeFileByMode($fn, $q, $mode = 'a')
{
    $year = date("Y");
    $month = date("m");
    $day = date("d");
    $rootFolder = '/var/www/htdocs/logs/';
    $calFolder = "$year/$month/$day/";

    if (!file_exists($rootFolder . $calFolder)) {
        $oldmask = umask(0);
        @mkdir($rootFolder . $calFolder, 0777, true);
        @umask($oldmask);
    }

    if (!is_file($rootFolder . $calFolder . $fn)) {
        $contents = "New File! \n\n";
        file_put_contents($rootFolder . $calFolder . $fn, $contents);
    }

    $fileName = $rootFolder . $calFolder . $fn;
    $handle   = fopen($fileName, $mode);
    fwrite($handle, $q . "\n");
    fclose($handle);
}

/*********Get User Info from database including xumm session from user id*********** */
function GetUserInfoByUserId($user_id)
{
    global $sqlConnect;
    $sql2 = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1";
    $result = mysqli_query($sqlConnect, $sql2);
    if($row = mysqli_fetch_assoc($result))
        return $row;
}

/*********This is only for test color*********** */
function GetTestHexColorFromColorString($color = "")
{
    if ($color == 'Red') {
        $r = "#FF4E4E35";
    } else if ($color == 'Green') {
        $r = "#21A85A35";
    } else if ($color == 'Purple') {
        $r = "#EF5DA835";
    } else {
        $r = "#05002335";
    }
    return $r;
}

/****************** Metadata String from UUID string***********/
function GetAsciiStringFromHex($hexString) {
    // convert hex string to binary string
    $binaryString = hex2bin($hexString);
    
    // convert binary string to ASCII string
    $asciiString = pack('H*', bin2hex($binaryString));
    
    return $asciiString;
}

/********************************************** */
function GetLogFilePath($fileName)
{
    $year = date("Y");
    $month = date("m");
    $day = date("d");
    $rootFolder = '/var/www/htdocs/logs/';
    $calFolder = "$year/$month/$day/";
    $historyFilePath = $rootFolder . $calFolder . $fileName;
    
    return $historyFilePath;
}

function GetContentsFromValuableUrl($url){
    try{
        $options = [
            'http' => [
                'timeout' => 2, // Set a timeout value of 10 seconds
            ],
        ];
        $context = stream_context_create($options);
        $contents = file_get_contents($url, false, $context);
        return $contents;
    }
    catch(Exception $e){
        return;
    }
}

//**********************************Functions for database ****************************/
//*****************Customized functions from functions_custom_old.php******************/

//JEFF_getNFT: get nft info from CryptoLand_NFTs//
//JEFF_updateNFTAsClaimed set claimed info to CryptoLand_NFTs//
//JEFF_UpdateNFT set nft info to CryptoLand_NFTs//
//JEFF_UpdateNFTHistory set nft history to CryptoLand_NFTs//

function JEFF_getNFT($nft_id)
{
    global $sqlConnect;

    $sql = "SELECT * from CryptoLand_NFTs WHERE nft_id = '$nft_id'";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }

    if (!empty($jsonArray) && count($jsonArray) === 1) {
        return $jsonArray[0];
    }
    return $jsonArray;
}

function JEFF_updateNFTAsTransferred($nftId, $tx)
{
    global $nrg;

    $nft = JEFF_getNFT($nftId);
    $assetType = $nft['assetType'];
    $timestamp = time();
    $userId = $nrg['user']['user_id'];
    $userWallet = $nrg['user']['xumm_address'];


    $result = JEFF_UpdateNFT('', '', '', 0, 0, 0, $nftId, $userWallet, 1, $timestamp, $userId, $tx);
    $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 0, $timestamp, 1, $timestamp, $userId, $userId, $_ENV['DEFAULT_ISSUER_ADDRESS'], $userWallet, '', '', '');

    echo $result;
}

function JEFF_updateNFTAsRevealed($nftId)
{
    global $nrg;

    $nft = JEFF_getNFT($nftId);
    $assetType = $nft['assetType'];

    $timestamp = time();
    $userId = $nrg['user']['user_id'];
    $userWallet = $nrg['user']['xumm_address'];

    $result = JEFF_UpdateNFT('', '', '', 1, $userId, $timestamp, $nftId, $userWallet, 0, 0, 0, '');
    $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 1, $timestamp, 1, $timestamp, $userId, $userId, $_ENV['DEFAULT_ISSUER_ADDRESS'], $userWallet, '', '', '');

    echo $result;
}

function JEFF_updateNFTAsClaimed($nftId, $value)
{
    global $nrg;

    $nft = JEFF_getNFT($nftId);
    $assetType = $nft['assetType'];
    $timestamp = time();
    $userId = $nrg['user']['user_id'];
    $userWallet = $nrg['user']['xumm_address'];

    $result = JEFF_UpdateNFT($value, $userId, $timestamp, 0, 0, 0, $nftId, $userWallet, 0, 0, 0, '');
    $result2 = JEFF_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 0, $timestamp, 1, $timestamp, $userId, $userId, $_ENV['DEFAULT_ISSUER_ADDRESS'], $userWallet, '', '', '');
}

function JEFF_UpdateNFT($claimed, $claimed_user_id, $claimed_date, $revealed, $revealed_user_id, $revealed_date, $nft_id, $userWallet, $transferred, $transferred_user_id, $transferred_date, $tx_id = '')
{
    global $nrg, $sqlConnect, $cache;

    $where = "WHERE nft_id = '$nft_id'";

    if ($claimed == '1' && $claimed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE CryptoLand_NFTs SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed='$claimed', claimed_user_id=$claimed_user_id, claimed_date=$claimed_date, revealed=revealed, revealed_user_id=revealed_user_id, revealed_date=revealed_date, transferred=transferred, transferred_user_id=transferred_user_id, transferred_date=transferred_date  $where";
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs.log", "L" . __LINE__ . " | " . $query_one);
    }  
    else if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE CryptoLand_NFTs SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='$revealed', revealed_user_id=$revealed_user_id, revealed_date=$revealed_date, transferred=transferred, transferred_user_id=transferred_user_id, transferred_date=transferred_date $where";
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs.log", "L" . __LINE__ . " | " . $query_one);
    }
    else if ($transferred == '1') {
        $query_one = "UPDATE CryptoLand_NFTs SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed=revealed, revealed_user_id=revealed_user_id, revealed_date=revealed_date, transferred='$transferred',transferred_user_id='$transferred_user_id', transferred_date='$transferred_date', tx_id='$tx_id' $where";
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs.log", "L" . __LINE__ . " | " . $query_one);
    }
    else{ //set unclaim data
        $query_one = "UPDATE CryptoLand_NFTs SET issuer_wallet=issuer_wallet, owner_wallet=owner_wallet, claimed='$claimed', claimed_user_id='0', claimed_date='0', revealed=revealed, revealed_user_id=revealed_user_id, revealed_date=revealed_date, transferred=transferred,  transferred_user_id=transferred_user_id,transferred_date=transferred_date  $where";
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs.log", "L" . __LINE__ . " | " . $query_one);   
    }


    $query = mysqli_query($sqlConnect, $query_one);

    if ($query) {
        if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
            $result = Jeffrey_UpdateNFT_Metadata_File($nft_id, $userWallet);
            if($result != "success")
            return "failed";
        }
    } else {
        return "failed";
    }
    return "true";
}

function JEFF_UpdateNFTHistory($nft_uuid, $nft_id, $nft_serial, $action_type, $action_created_date, $action_complete, $action_complete_date, $created_by, $user_id, $issuer_wallet, $owner_wallet, $transaction_hash, $xumm_txid, $xumm_payload_uuidv4)
{
    global $nrg, $sqlConnect, $cache;

    if (!isset($nrg['user'])) {
        return false;
    }

    $date_now = date("d/m/Y");
    $time_now = time();
    $dateTime = time();
    $userId = $nrg['user']['user_id'];

    $browser_info = NRG_getBrowser() . "|" . NRG_getOS();
    $user_ip = get_ip_address();
    $owner_wallet = $nrg['user']['xumm_address'];

    $query_one = "INSERT INTO nft_history(nft_uuid, nft_id, nft_serial, action_type, action_created_date, action_complete, action_complete_date, created_by, user_id, issuer_wallet, owner_wallet, transaction_hash, xumm_txid, xumm_payload_uuidv4) VALUES('$nft_uuid', '$nft_id', $nft_serial, $action_type, $action_created_date, $action_complete, $action_complete_date, $created_by, $user_id , '$issuer_wallet', '$owner_wallet', '$transaction_hash', '$xumm_txid', '$xumm_payload_uuidv4');";

    NRG_writeFile("NRG_UpdateNFTHistory.log", "L" . __LINE__ . " | " . $query_one);
    $query = mysqli_query($sqlConnect, $query_one);
}



//************Added newly by Jeffery ***************/
function Jeffrey_UpdateNFT_Metadata_File($nft_id, $userWallet)
{
    global $nrg, $sqlConnect, $cache;

    $sql = "SELECT base_uri, file FROM CryptoLand_NFTs WHERE nft_id = '$nft_id' AND owner_wallet = '$userWallet'";

    $query = mysqli_query($sqlConnect, $sql);
    NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . $sql);

    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $assetPath = $fetched_data['base_uri']."/".$fetched_data['file'];

    }
    $liveRenameFrom =   str_replace("https://sb236.cryptoland.host/testNet", "/var/www/htdocs/sb236.cryptoland.host/testNet", $assetPath);
    $archiveRenameTo =     str_replace("/live-metadata/", "/archived-metadata/", $liveRenameFrom);
    $revealedMoveFrom =     str_replace("/live-metadata/", "/revealed-metadata/", $liveRenameFrom);

    NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . $liveRenameFrom. " | ". $archiveRenameTo . " | ". $revealedMoveFrom);

    if (rename($liveRenameFrom, $archiveRenameTo)) {
        if (copy($revealedMoveFrom, $liveRenameFrom)) {
            NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . "[Success] $revealedMoveFrom -> $liveRenameFrom");
            return "success";
        } else {
            $where = "WHERE nft_id = '$nft_id'";
            $query_one = "UPDATE CryptoLand_NFTs SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
            $query = mysqli_query($sqlConnect, $query_one);
            NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
            NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . "[Failed] $revealedMoveFrom -> $liveRenameFrom");
            return "failed";
        }
    } else {
        $where = "WHERE nft_id = '$nft_id'";
        $query_one = "UPDATE CryptoLand_NFTs SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
        $query = mysqli_query($sqlConnect, $query_one);
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
        NRG_writeFile("NRG_UpdateCryptoLand_NFTs_Metadata_File.log", "L" . __LINE__ . " | " . "[Failed] $liveRenameFrom -> $archiveRenameTo ");
        return "failed";
    }
}
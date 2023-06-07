<?php
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

/********************Load Environment Variables********************* */
$apiKey = $_ENV['API_KEY'];  //jeff
$apiSecret =  $_ENV['API_SECRET']; //jeff
$issuer_address = $_ENV['DEFAULT_ISSUER_ADDRESS'];
$num_results_on_page = $_ENV['SHOW_ITEMS_PER_PAGE'];; 

$endpoint_url = $_ENV['API_ENDPOINT_URL'];
$server_url = $_ENV['NODEBACKEND_SERVER_URL'];

/****************************************************************** */

if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]))
{    
    $userInfo = GetUserInfoByUserId($_SESSION["user_id"]);
    $current_user = $userInfo['xumm_address'];
    $nrg["user"] = $userInfo; //For using database functions provided existing project
}


/*****************Get revealed and unrevealed items from Database and claim items from Node server*****************/
function GetRevealNftArraysFromDatabase($claimedArray)
{
    global $sqlConnect, $current_user, $issuer_address;

    if(!$current_user)
    {
        $account = $issuer_address;
    }
    else
        $account = $current_user;

    $sql =  "SELECT user_nft.nft_id as nft_id, 
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.base_uri
        WHEN user_nft.assetType = 2 THEN vials_nft.base_uri
        ELSE NULL
    END AS base_uri,
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.revealed
        WHEN user_nft.assetType = 2 THEN vials_nft.revealed
        ELSE NULL
    END AS revealed

    FROM user_nft
    LEFT JOIN lbk_nft ON user_nft.nft_id = lbk_nft.nft_id
    LEFT JOIN vials_nft ON user_nft.nft_id = vials_nft.nft_id 
    WHERE user_nft.owner_wallet= '$account'";

    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }

    if($jsonArray && count($jsonArray) >= 1)
    {
        $revealedArray = array();
        $unrevealedArray = array();

        foreach($jsonArray as $nft){
            if (in_array( $nft["nft_id"], $claimedArray)) {
                if($nft['revealed'] == 1)
                    array_push($revealedArray, $nft);
                else
                    array_push($unrevealedArray, $nft);
            }
        }

        return ["revealedArray"=>$revealedArray, "unrevealedArray" =>$unrevealedArray];
    }
}

/*****************Get Nft Infos from Database*****************/
function GetNftInfoByNftIdFromDatabase($nft_id)
{
    global $sqlConnect;

    $sql =  "SELECT 
    user_nft.nft_id, 
    user_nft.assetType, 
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.base_uri
        WHEN user_nft.assetType = 2 THEN vials_nft.base_uri
        ELSE NULL
    END AS base_uri,
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.claimed
        WHEN user_nft.assetType = 2 THEN vials_nft.claimed
        ELSE NULL
    END AS claimed,
		CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.revealed
        WHEN user_nft.assetType = 2 THEN vials_nft.revealed
        ELSE NULL
    END AS revealed
    FROM user_nft
    LEFT JOIN lbk_nft ON user_nft.nft_id = lbk_nft.nft_id AND user_nft.assetType = 1
    LEFT JOIN vials_nft ON user_nft.nft_id = vials_nft.nft_id AND user_nft.assetType = 2
    WHERE user_nft.nft_id = '$nft_id'";

    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }

    if($jsonArray && count($jsonArray) >= 1)
    return $jsonArray[0];
}

/*****************Get UnClaimed Nfts by owned account from Node Server******************* */
function GetUnClaimedNftsFromServer(){
    global  $current_user, $server_url, $issuer_address;
    if(!$current_user)
    {
        $account = $issuer_address;
    }
    else
        $account = $current_user;
        
    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' => $server_url 
    ]);

    $filter = "account=$account";
    $request = $client->getAsync("unclaimed_offers?$filter");
    $response = $request->wait();

    // Convert the JSON response to an array for easier processing
    $claimedArray = json_decode($response->getBody());
    return $claimedArray;    
}


/*****************Get Owned Nft Infos by account from Node Server******************* */
function GetClaimedNftsFromServer($account = null){
    global $current_user, $server_url, $issuer_address;
    
    if(!$account)
    {
        if(!$current_user)
        {
            $account = $issuer_address;
        }
        else
            $account = $current_user;
    }
        
    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' =>$server_url 
    ]);


    $filter = "account=$account";
    $request = $client->getAsync("account_nfts?$filter");
    $response = $request->wait();

    // Convert the JSON response to an array for easier processing
    $transfer_history = json_decode($response->getBody());

    return $transfer_history;    
}

/*****************Get Owned Nft Infos by account from Node Server******************* */
function GetNftArrayForMarketplaceFromServer($account = null){
    global $current_user, $server_url, $issuer_address;
    
    if(!$account)
    {
        if(!$current_user)
        {
            $account = $issuer_address;
        }
        else
            $account = $current_user;
    }
        
    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' =>$server_url 
    ]);


    $filter = "account=$account";
    $request = $client->getAsync("marketplace_infos?$filter");
    $response = $request->wait();

    // Convert the JSON response to an array for easier processing
    $transfer_history = json_decode($response->getBody());

    return $transfer_history;    
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
    $transfer_history = json_decode($response->getBody());
    return $transfer_history;    
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
    global $current_user, $issuer_address, $apiKey, $apiSecret;

    if (!$current_user) {
        $account = $issuer_address; 
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
            $request = $client->getAsync("issuer/$issuer_address");
            $response = $request->wait();
            $result1 = json_decode($response->getBody()->getContents())->data->nfts;
            JEFF_writeFileByMode($issuer_issue_file, json_encode($result1), 'w');
        }

        $request = $client->getAsync("offers/issuer/$issuer_address");
        $response = $request->wait();
        $offerResult = json_decode($response->getBody()->getContents())->data->offers;
       
        $result = [];

        foreach ($result1 as $r1) {
          if($r1->Owner == $issuer_address)
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

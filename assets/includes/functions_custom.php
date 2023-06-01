<?php

/********************Following statements can be updated********************* */
$apiKey = "04b42479-cc50-4410-a783-1686eeebe65f";  //dev2
$apiSecret = "f53c6edc-1fb1-4c7f-8b79-f1ffef28037d"; // dev2
$xummSdk = new \Xrpl\XummSdkPhp\XummSdk($apiKey, $apiSecret);
$endpoint_url = "https://s.altnet.rippletest.net:51234";
$issuerAddress = "rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b"; //for test

/*********Followings are only used for test********** */
$userIdsByAddress = ["r97nyzoijsUYp5CQUQNf8dMzh4XyqZpFCU" => 3, "r3FoWNS5sHMv9nhx9H9YEsGEqJWqvFsXRn" => 4, "r9FELhVcmqCyz3QdCWPYQLR3GBz5MzdvRx" => 5, "rJAHd21L5Lwcj1PENcE2rGmUMHZSRzqwJi" => 6, "rMUzoNUXBZVh43groaeaU2dd7fBFc8N1gz" => 7];
//$issued_user_token = "da36f1ee-b173-4a8b-a266-3721a1e86849"; //for test

// if(!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]))
//     $_SESSION['user_id'] = 3; // for test

//     $_SESSION['user_id'] = 4; // for test
/**************************************************************************** */

if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]))
{    
    $userInfo = getUserInfo($_SESSION["user_id"]);
    $current_user = $userInfo['xumm_address'];
}


/*********Write log file with mode*********** */
function NRG_writeFileByMode($fn, $q, $mode = 'a')
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
function getUserInfo($user_id)
{
    global $sqlConnect, $xummSdk;
    $sql2 = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1";
    $result = mysqli_query($sqlConnect, $sql2);
    if($row = mysqli_fetch_assoc($result))
        return $row;
}

/*********This is only for test color*********** */
function getHexColor($color = "")
{
    if ($color == 'Red') {
        $r = "#80000035";
    } else if ($color == 'Green') {
        $r = "#00800035";
    } else if ($color == 'Purple') {
        $r = "#80008035";
    } else {
        $r = "#00008035";
    }
    return $r;
}

/*============================================================*/
/*------------ Metadata String from UUID string---------------*/
/*============================================================*/
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

use GuzzleHttp\Client;
use GuzzleHttp\Promise;


/*****************Newly added******************* */
function GetFullNftInfoFromParam($filter, $nftTokenId){
    
    global $apiKey, $apiSecret;

    $client = new \GuzzleHttp\Client();
    
    $client = new Client([
        'base_uri' => 'https://test.bithomp.com/api/cors/v2/nft/'
    ]);

    if(!$filter)
        $filter = "uri=true&metadata=true&history=true&sellOffers=true&buyOffers=true&offersValidate=true&offersHistory=true";

    $request = $client->getAsync("$nftTokenId?$filter");
    $response = $request->wait();

    // Convert the JSON response to an array for easier processing
    $transfer_history = json_decode($response->getBody());
    return $transfer_history;    
}

/*****************Newly added******************* */
function GetOfferInfoById($offerIndex){ 
    global $apiKey, $apiSecret;
    
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetOfferInfoById Start:".$offerIndex);

    $client = new Client([
        'base_uri' => 'https://test.bithomp.com/api/cors/v2/search/', 'headers' => [
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



/********************************************** */
function GetOffersByParams($offerType, $nftTokenId){ 
    global $apiKey, $apiSecret;
    
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetOffersByParams Start:".$offerType.",".$nftTokenId);

    $client = new Client([
        'base_uri' => 'https://test-api.xrpldata.com/api/v1/xls20-nfts/', 'headers' => [
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


/********************************************** */
function GetNftInfoFromTokenId($nftTokenId){
    global $apiKey, $apiSecret;
    
    $client = new Client([
        'base_uri' => 'https://test-api.xrpldata.com/api/v1/xls20-nfts/', 'headers' => [
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

function LoadNftInfosFromCurrentUser()
{
    global $current_user, $issuerAddress, $apiKey, $apiSecret;
    $issuer = $issuerAddress; // test address from header
    $account = $current_user; // test address from header

    if (!$account) {
        return;
    }

    $client = new Client([
        'base_uri' => 'https://test-api.xrpldata.com/api/v1/xls20-nfts/',
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
            $request = $client->getAsync("issuer/$issuer");
            $response = $request->wait();
            $result1 = json_decode($response->getBody()->getContents())->data->nfts;
            NRG_writeFileByMode($issuer_issue_file, json_encode($result1), 'w');
        }

        $request = $client->getAsync("offers/issuer/$issuer");
        $response = $request->wait();
        $offerResult = json_decode($response->getBody()->getContents())->data->offers;
       
        $result = [];

        foreach ($result1 as $r1) {
          if($r1->Owner == $issuer)
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
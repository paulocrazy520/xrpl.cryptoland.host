<?php

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

set_time_limit(120);

require_once "assets/init.php";

// Create a new instance of the SDK and store it in the session.
$xummSdk = new \Xrpl\XummSdkPhp\XummSdk($apiKey, $apiSecret);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_body = file_get_contents('php://input');
    try{
        $request_data = json_decode($request_body);
    }catch(Exception $e){
        echo "Non Type";
        return;
    }
}

// *********************ToMarcus**************************
// *****************Switch by type***********************
// *******************************************************

switch ($request_data->type) {
    case "Login":
        JEFF_Login();
        break;
    case "Logout":
        JEFF_Logout();
        break;
    case "UpdateUserInfo":
        JEFF_UpdateUserInfo($_SESSION["user_id"]);
        break;
    case "RemoveUserInfo":
        JEFF_RemoveUserInfo($_SESSION["user_id"]);
        break;
    case "GetUserInfo":
        JEFF_GetUserInfo($_SESSION["user_id"]);
        break;
    case "SubscribePayload":
        JEFF_SubscribePayload($_SESSION["user_id"]);
        break;
    case "CancelPayload":
        JEFF_CancelPayload($_SESSION["user_id"]);
        break;
    case "RevealItem":
        JEFF_RevealItem($_SESSION["user_id"]);
        break;
    case "ClaimItem":
        JEFF_ClaimItem($_SESSION["user_id"]);
        break;
    case "UnclaimItem":
        JEFF_UnclaimItem($_SESSION["user_id"]);
        break;
    default:
        break;
}

function Jeff_Login(){
    global $request_data;
    if( !isset($request_data->user_name) || empty($request_data->user_name) ||  !isset($request_data->user_password) || empty($request_data->user_password)){
        return;
    }

    if(NRG_Login($request_data->user_name, $request_data->user_password) == true)
    {
        $user_id = NRG_UserIdForLogin($request_data->user_name);
        $_SESSION['user_id'] = $user_id;
        echo $user_id;
    }
}

function Jeff_Logout(){
    Jeff_RemoveUserInfo($_SESSION['user_id']);
    echo $_SESSION['user_id'];
    unset($_SESSION['user_id']);
}

// *********************ToMarcus****************************
// ***** Get user token info by user_id to NRG_Users table**
// *********************************************************
function Jeff_GetUserInfo($user_id)
{
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    global $sqlConnect, $xummSdk;
    $row = GetUserInfoByUserId($user_id);
    
    if($row["xumm_user_token"])
    {
        if($result = $xummSdk->verifyUserToken($row["xumm_user_token"]))
        {
            $timestamp = $row["xumm_timestamp"];
            $twenty_four_hours_ago = time() - (24 * 60 * 60);

            if ($timestamp < $twenty_four_hours_ago) { // The timestamp is older than 24 hours
                return;
            }

            echo json_encode($row);
        }
    }
}

// *********************ToMarcus**************************
// *********Update user token info to NRG_Users table*****
// *******************************************************
function JEFF_UpdateUserInfo($user_id)
{
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    global $sqlConnect, $request_data, $issued_user_token, $xummSdk;

    if (!isset($request_data->payload) || empty($request_data->payload)) {
        echo "none_payload";
        return;
    }

    //NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($request_data->payload));

    $payload_json = json_decode(json_encode($request_data->payload), true);
    $userToken = $payload_json["application"]["issued_user_token"];

    if(!$userToken) // This is for test using static user token
        return;
    //$userToken = $issued_user_token;

    $txid = $payload_json["response"]["txid"];
    $address = $payload_json["response"]["account"];

    $user_info  = GetUserInfoByUserId($user_id);

    if(!$user_info) //Check user exist
        return;
    
    if ($user_info["xumm_address"] && $user_info["xumm_txid"]) {
        
        if($user_info["xumm_user_token"])
        {
            if($result = $xummSdk->verifyUserToken($user_info["xumm_user_token"]))
            {
                $timestamp = $user_info["xumm_timestamp"];
                $twenty_four_hours_ago = time() - (24 * 60 * 60);
    
                if ($timestamp < $twenty_four_hours_ago) { // The timestamp is older than 24 hours
                    //
                }
                else
                {
                    echo "user_exist";
                    return;
                }
            }
        }
    }

    NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", json_encode($user_info));

    $timestamp = time();
    $query = "UPDATE NRG_Users SET xumm_user_token='$userToken', xumm_timestamp='$timestamp',  xumm_txid='$txid' , xumm_address='$address' WHERE user_id = '$user_id' ";
    $result = mysqli_query($sqlConnect, $query);

    //After updated user info, record the result to log file
    NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", json_encode($result));
    
    if($result)
        echo "success";
    else
        echo "error";
}

// *********************ToMarcus**************************
// ************Remove user info from NRG_Users table*****
// *******************************************************
function Jeff_RemoveUserInfo($user_id)
{
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    global $sqlConnect;

    $query = "UPDATE NRG_Users SET xumm_user_token=null, xumm_timestamp=null, xumm_txid=null , xumm_address=null WHERE user_id = '$user_id' ";
    $result = mysqli_query($sqlConnect, $query);

    //After removing user info, record the result to log file
    NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", json_encode($result));

    if($result)
    {
        unset($_SESSION['user_id']);
        echo "success";
    }
    else
        echo "error";
}

function JEFF_CancelPayload($user_id){
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    echo "**cancel**";

    return;

    
    global $request_data;
    
    if(isset($request_data->createdPayload))
    {
        $createdPayload = $request_data->createdPayload;
        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------Response cancel payload For Xumm-------------");
        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($createdPayload));       

        try{
            $result = $xummSdk->Jeff_cancel($createdPayload->uuid);

        }catch(Exception $e){
            print_r($e);
        }
    }
}
// ******************************************************************************************
// ******Subscribe offer payload from current user token to make the user sign with phone****
// ******************************************************************************************
function JEFF_SubscribePayload($user_id){
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    global $xummSdk, $issued_user_token, $request_data, $sqlConnect, $apiKey, $apiSecret;

    $xummPayload = json_decode(json_encode($request_data->payload), true);

    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "-------------------New Offer Created-----------------");
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($xummPayload));
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($request_data));

    $user_info = GetUserInfoByUserId($user_id);

    if(!$user_info)
        return;

    $userToken = $user_info["xumm_user_token"];

    //Check if this payload is cancel offer, then make the array of token offers
    if( $xummPayload["txjson"]["TransactionType"] == "NFTokenCancelOffer" && isset($request_data->offeredNftTokenId)){
        $table_name = $request_data->tableName;
        $offers = GetNftOffersByParams($table_name, $request_data->offeredNftTokenId);

        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetNftOffersByParams Offers Result:".json_encode($offers));

        $offerArray = []; 
        for($i = 0 ; $i < count($offers) ; $i++)
        {
            $offerArray[$i] = $offers[$i]->OfferID;
        }
        

        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($offerArray));
        $xummPayload["txjson"]["NFTokenOffers"] = $offerArray;
    
    }
    
    $verifyInfo = $xummSdk->verifyUserToken($userToken);

    if($verifyInfo){
         try {

            $loop = \React\EventLoop\Factory::create();

            $callback = function(Xrpl\XummSdkPhp\Subscription\CallbackParams $event) use ( $loop, $owner_wallet, $request_data, $user_id, $user_info, $xummPayload, $sqlConnect): ?array
            {

                //global $user_id, $user_info, $xummPayload, $sqlConnect;
                if (!isset($event->data['signed'])) {
                    return null; // Don't do anything, wait for the next message.
                }

                $loop->stop();

                if ($event->data['signed'] === true) {
                    
                    //echo "🎉 Payment request accepted!\n";
    
                    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------After sign, return response data-------------");
                    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($event->data));
                                    
                    $payload_data = json_decode(json_encode($event->data), true);
    
                    if( $xummPayload["txjson"]["TransactionType"] == "NFTokenCreateOffer")
                    {
                        //$user_id from parameter
                        $user_wallet = $user_info["xumm_address"];
                        $uuid = $payload_data["payload_uuidv4"];
                        $nft_token_id =  $xummPayload["txjson"]["NFTokenID"];
                        $offer_date = time();
                        $offer_currency = "Xrp";               
                        $offer_amount =  intval($xummPayload["txjson"]["Amount"]);
                        $tx = $payload_data["txid"];
                        $offer_status = "active";
    
                        if($xummPayload["txjson"]["Owner"])
                            $table_name = "buy_offers";
                        else
                            $table_name = "sell_offers";
    
                        /**************Insert Data to table ***************/
                        try{
                            $query = "SELECT * FROM $table_name WHERE uuid = '$uuid' or (nft_token_id = '$nft_token_id' and offer_status = 'active') LIMIT 1";
                            $result = mysqli_query($sqlConnect, $query);
    
                            $subQuery = "INSERT INTO $table_name (user_id, user_wallet, uuid, nft_token_id, offer_date, offer_currency, offer_amount, tx, offer_status) 
                            VALUES  ($user_id, '$user_wallet', '$uuid', '$nft_token_id', $offer_date, '$offer_currency', $offer_amount, '$tx', '$offer_status');";
    
                            NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", $subQuery);
    
                            if (!mysqli_num_rows($result)) {
                                $subResult = mysqli_query($sqlConnect, $subQuery);
    
                                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($subResult));
                            }
                            else
                            {
                                $subQuery = "DELETE FROM $table_name WHERE nft_token_id = '$nft_token_id' and offer_status = 'active'";
                                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", $subQuery);
                                $subResult = mysqli_query($sqlConnect, $subQuery);
                            }
                        }
                        catch(Exception $e){
                            print_r($e);
                        }
                    }
                    else if( $xummPayload["txjson"]["TransactionType"] == "NFTokenCancelOffer" )
                    {
                        //$user_id from parameter
                        $user_wallet = $user_info["xumm_address"];
                        $uuid = $payload_data["payload_uuidv4"];
                        $offer_date = time();
                        $tx = $payload_data["txid"];
                        $offer_status = "cancelled";
                        $nft_token_id =  $request_data->offeredNftTokenId;
                        /**************Insert Data to table ***************/
                        $table_name = $request_data->tableName;
                        if(!isset($table_name) || empty($table_name))
                            $table_name = "sell_offers";
    
                        try{
                            $query = "SELECT * FROM $table_name WHERE nft_token_id = '$nft_token_id' and offer_status = 'active' LIMIT 1";
                            $result = mysqli_query($sqlConnect, $query);
    
                            if (mysqli_num_rows($result)) 
                            {
                                $subQuery = "UPDATE $table_name SET cancelled_by_userid = '$user_id', cancelled_by_user_wallet = '$user_wallet', cancelled_by_uuid = '$uuid', cancelled_date = '$offer_date', cancelled_tx = '$tx', offer_status = '$offer_status' WHERE nft_token_id = '$nft_token_id' and offer_status = 'active'";
                                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", $subQuery);
                                $subResult = mysqli_query($sqlConnect, $subQuery);
                            }
                        }
                        catch(Exception $e){
                            print_r($e);
                        }
                    }
                    else if( $xummPayload["txjson"]["TransactionType"] == "NFTokenAcceptOffer" )
                    {
    
                        //echo "NFTokenAcceptOffer";
                        //$user_id from parameter
                        $user_wallet = $user_info["xumm_address"];
                        $uuid = $payload_data["payload_uuidv4"];
                        $offer_date = time();
                        $tx = $payload_data["txid"];
                        $offer_status = "accepted";
                        $nft_token_id =  $request_data->offeredNftTokenId;
                        /**************Insert Data to table ***************/
                        $table_name = $request_data->tableName;
                        if(!isset($table_name) || empty($table_name))
                            $table_name = "sell_offers";
    
                        if($table_name == "claim_offers")
                        {
                    
                            NRG_updateNFTAsTransferred($nft_token_id, $tx);
                            //echo $table_name;
                        }
                        else
                        {
                            try{
                                $query = "SELECT * FROM $table_name WHERE nft_token_id = '$nft_token_id' and offer_status = 'active' LIMIT 1";
                                $result = mysqli_query($sqlConnect, $query);
    
                                if (mysqli_num_rows($result)) 
                                {
                                    $subQuery = "UPDATE $table_name SET accepted_by_userid = '$user_id', accepted_by_user_wallet = '$user_wallet', accepted_by_uuid = '$uuid', accepted_date = '$offer_date', accepted_tx = '$tx', offer_status = '$offer_status' WHERE  nft_token_id = '$nft_token_id' and offer_status = 'active'";
                                    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", $subQuery);
                                    $subResult = mysqli_query($sqlConnect, $subQuery);
                                }
                            }
                            catch(Exception $e){
                                print_r($e);
                            }
                        }
                    }
                    //
                    //**************************************************************** */
    
                    return $event->data;  // Returning a value ends the subscription.
                }
                
                echo false;
                //echo "Payment request rejected :(\n";
                return [];
                //NRG_updateNFTAsClaimed($$request_data->offeredNftTokenId);
            };

            if(isset($request_data->createdPayload))
            {
                $createdPayload = $request_data->createdPayload;
                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------Response payload For Xumm-------------");
                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($createdPayload));       

                // echo $createdPayload->uuid;
                // echo $createdPayload->refs->websocketStatus;
                try{
                    $result = $xummSdk->JEff_subscribe($createdPayload->uuid, $createdPayload->refs->websocketStatus, $callback);
                    $timeout = 50; // Set a timeout of 10 seconds
                    $loop->addTimer($timeout, function () use ($loop) {
                    $loop->stop(); // Stop the event loop after the timeout
                    });
                    $loop->run();
                }catch(Exception $e){
                    print_r($e);
                }
            }
            else
            {
                $options=  new Xrpl\XummSdkPhp\Payload\Options(
                    submit: true,
                    returnUrl: new Xrpl\XummSdkPhp\Payload\ReturnUrl(
                        $_ENV["REDIRECT_URL"],
                        $_ENV["REDIRECT_URL"]
                    )
                    );
        
                $payloadData = new Xrpl\XummSdkPhp\Payload\Payload(
                    transactionBody: $xummPayload["txjson"],
                    userToken: $userToken
                );
    
                $createdPayload = $xummSdk->createPayload($payloadData);
 
                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------Send claim payload to Xumm-------------");
                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($payloadData));
                NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($createdPayload));

                echo json_encode($createdPayload);
                
            }
        } catch(Exception $e) {
            print_r($e);
            return;
        }
        
        //After offer created and signed, record the result to log file
        //NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($createdPayload));
        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($result));

        // print_r(json_encode($result));
        return;
    }
}

// *********************ToMarcus**************************
// ************Reveal item and update database*********
// *******************************************************
function Jeff_RevealItem($user_id)
{
    global $request_data, $nrg;

    if(!isset($user_id) || empty($user_id) || !isset($nrg["user"])){
        return;
    }

    NRG_updateNFTAsRevealed($request_data->nftId); 
}




// *********************ToMarcus**************************
// *******************Unclaim Item data*******************
// *******************************************************
function Jeff_UnclaimItem($user_id)
{
    global $sqlConnect, $request_data, $nrg;
    $nft_id = $request_data->nftId;
    $user_info = GetUserInfoByUserId($user_id);
    
    if($user_info["xumm_user_token"]){
        
        $client = new Client([
            'base_uri' => $_ENV['NODEBACKEND_SERVER_URL']
        ]);
    
        $query_params = [
            'tokenID' =>  $nft_id ,
            'account' => $user_info["xumm_address"]
        ];
        $response = $client->request('GET', '/cancel', ['query' => $query_params]);
        
        $result = $response->getBody();
        $json = json_decode($result, true);

        if (!empty($json['offerId'])) {
            NRG_updateNFTAsClaimed($nft_id, '0');           
            echo  $result;
        }
    }

}

// *********************ToMarcus**************************
// ************Claim item and update database*********
// *******************************************************
function Jeff_ClaimItem($user_id)
{
    global $sqlConnect, $request_data, $nrg;

    $nft_id = $request_data->nftId;

    if(!isset($user_id) || empty($user_id) || !isset($nrg["user"])){
        return;
    }

    $sql =  "SELECT user_nft.nft_id as nft_id, 
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.transferred_status
        WHEN user_nft.assetType = 2 THEN vials_nft.transferred_status
        ELSE NULL
    END AS transferred_status,
    CASE
        WHEN user_nft.assetType = 1 THEN lbk_nft.transferred_date
        WHEN user_nft.assetType = 2 THEN vials_nft.transferred_date
        ELSE NULL
    END AS transferred_date
    FROM user_nft
    LEFT JOIN lbk_nft ON user_nft.nft_id = lbk_nft.nft_id
    LEFT JOIN vials_nft ON user_nft.nft_id = vials_nft.nft_id WHERE  user_nft.user_id='".$user_id."' AND  user_nft.nft_id ='".$nft_id."' AND (
    (user_nft.assetType = 1 AND lbk_nft.transferred_status = '0') OR 
    (user_nft.assetType = 2 AND vials_nft.transferred_status = '0'))";

    try{
        $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

        $jsonArray = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $jsonArray[] = $row;
        }

        if($jsonArray && count($jsonArray) >= 1)
        {
            $json = $jsonArray[0];
            $status = $json['transferred_status'];
            $date = $json['transferred_date'];

            $user_info = GetUserInfoByUserId($user_id);
    
            if(!$status && $user_info["xumm_user_token"]){
                
                $client = new Client([
                    'base_uri' => $_ENV['NODEBACKEND_SERVER_URL']
                ]);
            
                $query_params = [
                    'tokenID' =>  $nft_id ,
                    'account' => $user_info["xumm_address"]
                ];
                $response = $client->request('GET', '/claim', ['query' => $query_params]);
                
                $result = $response->getBody();
                $json = json_decode($result, true);

                if (!empty($json['offerId'])) {
                    NRG_updateNFTAsClaimed($nft_id);           
                    echo  $result;
                }
            }
        }
    }
    catch(Exception $e){
        print_r($e);
    }

    //NRG_updateNFTAsRevealed($request_data->nftId); 
}

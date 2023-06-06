<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once "assets/init.php";

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
    case "RevealItem":
        JEFF_RevealItem($_SESSION["user_id"]);
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
    $row = getUserInfo($user_id);
    
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

    global $sqlConnect, $request_data, $issued_user_token;

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

    $user_info  = getUserInfo($user_id);

    if(!$user_info) //Check user exist
        return;
    
    if ($user_info["xumm_address"] && $user_info["xumm_txid"]) {
            echo "user_exist";

        return;
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


// ******************************************************************************************
// ******Subscribe offer payload from current user token to make the user sign with phone****
// ******************************************************************************************
function JEFF_SubscribePayload($user_id){
    if(!isset($user_id) || empty($user_id)){
        return;
    }

    global $xummSdk, $issued_user_token, $request_data, $sqlConnect;

    $xummPayload = json_decode(json_encode($request_data->payload), true);

    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "-------------------New Offer Created-----------------");
    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($xummPayload));

    NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", json_encode($request_data));

    $user_info = getUserInfo($user_id);

    if(!$user_info)
        return;

    $userToken = $user_info["xumm_user_token"];

    //Check if this payload is cancel offer, then make the array of token offers
    if( $xummPayload["txjson"]["TransactionType"] == "NFTokenCancelOffer" && isset($request_data->offeredNftTokenId)){
        $table_name = $request_data->tableName;
        $offers = GetOffersByParams($table_name,$request_data->offeredNftTokenId);

        NRG_writeFile("Payload_UpdateTransactionStausAndQty.log", "--------GetOffersByParams Offers Result:".json_encode($offers));

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
        $options=  new Xrpl\XummSdkPhp\Payload\Options(
            submit: true,
            returnUrl: new Xrpl\XummSdkPhp\Payload\ReturnUrl(
                $_ENV["REDIRECT_URL"],
                $_ENV["REDIRECT_URL"]
            )
            );

        $payloadData = new Xrpl\XummSdkPhp\Payload\Payload(
            transactionBody: $xummPayload["txjson"],
            userToken: $userToken,
            options: $options
        );

        $callback = function(Xrpl\XummSdkPhp\Subscription\CallbackParams $event) use ($owner_wallet, $request_data, $user_id, $user_info, $xummPayload, $sqlConnect): ?array
        {
            //global $user_id, $user_info, $xummPayload, $sqlConnect;
            if (!isset($event->data['signed'])) {
                return null; // Don't do anything, wait for the next message.
            }
        
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
                        NRG_updateNFTAsClaimed($nft_token_id);
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
        
        try {
            $createdPayload = $xummSdk->createPayload($payloadData);
            $loop = \React\EventLoop\Factory::create();
            $result = $xummSdk->subscribe($createdPayload, $callback);
            $loop->run();
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

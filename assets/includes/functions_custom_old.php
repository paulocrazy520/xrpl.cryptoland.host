<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ERROR);

function NRG_New_Backup($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, $tables = false, $backup_name = false)
{
    $file_path = '../script_backups/';
    $mysqli = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
    $mysqli->select_db($sql_db_name);
    $mysqli->query("SET NAMES 'utf8'");
    $queryTables = $mysqli->query('SHOW TABLES');
    while ($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
    }
    if ($tables !== false) {
        $target_tables = array_intersect($target_tables, $tables);
    }
    $content = "-- phpMyAdmin SQL Dump
-- http://www.phpmyadmin.net
--
-- Host Connection Info: " . $mysqli->host_info . "
-- Generation Time: " . date('F d, Y \a\t H:i A ( e )') . "
-- Server version: " . mysqli_get_server_info($mysqli) . "
-- PHP Version: " . PHP_VERSION . "
--\n
SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
SET time_zone = \"+00:00\";\n
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;\n\n";
    foreach ($target_tables as $table) {
        $result        = $mysqli->query('SELECT * FROM ' . $table);
        $fields_amount = $result->field_count;
        $rows_num      = $mysqli->affected_rows;
        $res           = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $TableMLine    = $res->fetch_row();
        $content       = (!isset($content) ? '' : $content) . "
-- ---------------------------------------------------------
--
-- Table structure for table : `{$table}`
--
-- ---------------------------------------------------------
\n" . $TableMLine[1] . ";\n";
        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
            while ($row = $result->fetch_row()) {
                if ($st_counter % 100 == 0 || $st_counter == 0) {
                    $content .= "\n--
-- Dumping data for table `{$table}`
--\n\nINSERT INTO " . $table . " VALUES";
                }
                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) {
                        $content .= '"' . $row[$j] . '"';
                    } else {
                        $content .= '""';
                    }
                    if ($j < ($fields_amount - 1)) {
                        $content .= ',';
                    }
                }
                $content .= ")";
                if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                    $content .= ";\n";
                } else {
                    $content .= ",";
                }
                $st_counter = $st_counter + 1;
            }
        }
        $content .= "";
    }
    $content .= "
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
    if (!file_exists($file_path . date('d-m-Y'))) {
        @mkdir($file_path . date('d-m-Y'), 0777, true);
    }
    if (!file_exists($file_path . date('d-m-Y') . '/' . time())) {
        mkdir($file_path . date('d-m-Y') . '/' . time(), 0777, true);
    }
    if (!file_exists($file_path . date('d-m-Y') . '/' . time() . "/index.html")) {
        $f = @fopen($file_path . date('d-m-Y') . '/' . time() . "/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    if (!file_exists($file_path . '/.htaccess')) {
        $f = @fopen($file_path . "/.htaccess", "a+");
        @fwrite($f, "deny from all\nOptions -Indexes");
        @fclose($f);
    }
    if (!file_exists($file_path . date('d-m-Y') . "/index.html")) {
        $f = @fopen($file_path . date('d-m-Y') . "/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    if (!file_exists($file_path . '/index.html')) {
        $f = @fopen($file_path . "/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    $folder_name = $file_path . date('d-m-Y') . '/' . time();
    $put         = @file_put_contents($folder_name . '/SQL-Backup-' . time() . '-' . date('d-m-Y') . '.sql', $content);
    if ($put) {
        $rootPath = realpath('./');
        $zip      = new ZipArchive();
        $open     = $zip->open($folder_name . '/Files-Backup-' . time() . '-' . date('d-m-Y') . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            return false;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!preg_match($file_path, $file)) {
                if (!$file->isDir()) {
                    $filePath     = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        $mysqli->query("UPDATE " . T_CONFIG . " SET `value` = '" . date('d-m-Y') . "' WHERE `name` = 'last_backup'");
        $mysqli->close();
        return true;
    } else {
        return false;
    }
}

function NRG_CountDGPData($type)
{
    global $nrg, $sqlConnect;
    //$type == $type;
    $data       = array();
    $type_table = T_USERS;
    $type_id    = NRG_Secure("user_id");
    $time       = time() - 60;
    $query_one  = mysqli_query($sqlConnect, "SELECT SUM(`{$type}`) as count FROM {$type_table} ");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}

/*Govind Code*/

$Cipher_iv = '0123456789abcdef'; #Same as in JAVA
$Cipher_key = '0123456789abcdef'; #Same as in JAVA

//////////////////////////////////////////////////////////
class PHP_AES_Cipher
{

    private static $OPENSSL_CIPHER_NAME = "aes-128-cbc"; //Name of OpenSSL Cipher 
    private static $CIPHER_KEY_LEN = 16; //128 bits

    /**
     * Encrypt data using AES Cipher (CBC) with 128 bit key
     * 
     * @param type $key - key to use should be 16 bytes long (128 bits)
     * @param type $iv - initialization vector
     * @param type $data - data to encrypt
     * @return encrypted data in base64 encoding with iv attached at end after a :
     */

    static function encrypt($key, $iv, $data)
    {
        if (strlen($key) < PHP_AES_Cipher::$CIPHER_KEY_LEN) {
            $key = str_pad("$key", PHP_AES_Cipher::$CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > PHP_AES_Cipher::$CIPHER_KEY_LEN) {
            $key = substr($key, 0, PHP_AES_Cipher::$CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $encodedEncryptedData = base64_encode(openssl_encrypt($data, PHP_AES_Cipher::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;

        return $encryptedPayload;
    }

    /**
     * Decrypt data using AES Cipher (CBC) with 128 bit key
     * 
     * @param type $key - key to use should be 16 bytes long (128 bits)
     * @param type $data - data to be decrypted in base64 encoding with iv attached at the end after a :
     * @return decrypted data 
     */
    static function decrypt($key, $data)
    {
        if (strlen($key) < PHP_AES_Cipher::$CIPHER_KEY_LEN) {
            $key = str_pad("$key", PHP_AES_Cipher::$CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > PHP_AES_Cipher::$CIPHER_KEY_LEN) {
            $key = substr($key, 0, PHP_AES_Cipher::$CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $parts = explode(':', $data); //Separate Encrypted data from iv.
        $decryptedData = openssl_decrypt(base64_decode($parts[0]), PHP_AES_Cipher::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));

        return $decryptedData;
    }
}

function NRG_EncryptData($gc_data)
{
    return PHP_AES_Cipher::encrypt("0123456789abcdef", "0123456789abcdef", $gc_data);
}

function NRG_DecryptData($enc_data)
{
    return  PHP_AES_Cipher::decrypt("0123456789abcdef", $enc_data);
}

//////////////////////////////////////////////////////////

function NRG_XummResponseStatus($json)
{
    $data = json_decode($json);
    $payloadResponse = $data->payloadResponse;
    $custom_meta      = $data->custom_meta;
    $sucess = $payloadResponse->signed;
    $order_id = $custom_meta->identifier;
    $xrp_tx_id = $payloadResponse->txid;
    return ["order_id" => $order_id, "sucess" => $sucess, "xrp_tx_id" => $xrp_tx_id];
}

function NRG_CoinbasePayment($tokenType, $tokenQuantity, $description2, $payment_data, $payload_response)
{
    $doRun = true;
    if (NRG_IsLogged()) {
        if ($doRun/*$nrg['config']['xumm_wallet'] == '1' /*&& $nrg['config']['can_buy_dgp'] == '1'/* || NRG_IsAdmin() || NRG_IsModerator()*/) {
            global $nrg, $sqlConnect;

            $dgp_delivery = 1;
            $dgp_tc = 1;
            $dgp_risk = 1;

            if ($nrg['config']['xumm_mode'] != "live") {
                if ($nrg["user"]["ref_user_id"] == "4296" /* || $nrg["user"]["ref_community"] == "CL"*/) {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_1"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_1"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_1"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_1"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_1"];
                } elseif ($nrg["user"]["ref_user_id"] == "1248" || $nrg["user"]["ref_user_id"] == "14" || $nrg["user"]["ref_user_id"] == "4182" || $nrg["user"]["ref_user_id"] == "4249") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_2"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_2"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_2"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_2"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_2"];
                } elseif ($nrg["user"]["ref_community"] == "WG1") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_3"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_3"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_3"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_3"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_3"];
                } elseif ($nrg["user"]["ref_community"] == "WG2") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_4"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_4"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_4"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_4"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_4"];
                } elseif ($nrg["user"]["ref_community"] == "WG3") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_5"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_5"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_5"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_5"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_5"];
                } elseif ($nrg["user"]["ref_community"] == "CCS") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_6"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_6"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_6"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_6"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_6"];
                } else {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_7"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_7"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_7"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_7"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_7"];
                }
            } else {
                if ($nrg["user"]["ref_user_id"] == "4296" /* || $nrg["user"]["ref_community"] == "CL"*/) {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_1"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_1"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_1"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_1"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_1"];
                } elseif ($nrg["user"]["ref_user_id"] == "1248" || $nrg["user"]["ref_user_id"] == "14" || $nrg["user"]["ref_user_id"] == "4182" || $nrg["user"]["ref_user_id"] == "4249") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_2"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_2"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_2"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_2"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_2"];
                } elseif ($nrg["user"]["ref_community"] == "WG1") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_3"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_3"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_3"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_3"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_3"];
                } elseif ($nrg["user"]["ref_community"] == "WG2") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_4"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_4"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_4"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_4"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_4"];
                } elseif ($nrg["user"]["ref_community"] == "WG3") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_5"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_5"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_5"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_5"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_5"];
                } elseif ($nrg["user"]["ref_community"] == "CCS") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_6"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_6"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_6"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_6"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_6"];
                } else {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_7"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_7"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_7"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_7"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_7"];
                }
            }

            $buyFee = 0;
            $landPrice = $refLandPrice;
            $avatarPrice = $refAvatarPrice;
            $cryptoPrice = $refCryptoPrice;
            $comboPrice = $refComboPrice;

            if (isset($tokenType) && !empty($tokenType)) {
                if ($tokenType == 'land') {

                    $cLandAssets = $tokenQuantity;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'land';
                    $skuPrice = $landPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'avatar') {

                    $cLandAssets = 0;
                    $cAvatarAssets = $tokenQuantity;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'avatar';
                    $skuPrice = $avatarPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'crypto') {

                    $cLandAssets = 0;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = $tokenQuantity;
                    $cComboAssets = 0;

                    $skuType = 'crypto';
                    $skuPrice = $cryptoPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'combotkn') {

                    $cCryptoAssets = 0;
                    $cLandAssets = $tokenQuantity;
                    $cAvatarAssets = $tokenQuantity;
                    $cCryptoAssets = 0;
                    $cComboAssets = $tokenQuantity;

                    $skuType = 'combotkn';
                    $skuPrice = $comboPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else {

                    $cLandAssets = 0;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'unknown_error';
                    $skuPrice = 0;
                    $skuQty = 0;

                    $finalPurchasePrice = 0;
                    return false;
                }
            }

            $order_id = uniqid("cl_", true);
            $fee = $finalPurchasePrice;
            $t = time();
            $destinationWalletAddress = $refDGP2Wallet; //$nrg['config']['dgp_to_wallet_1'];

            if (!isset($fee)) {
                echo "not valid request !!!";
                die;
            }

            $fee_orig = $fee;
            $totalFee = $fee;
            //$fee  = $fee * 1000000;
            $userId = $nrg['user']['user_id'];
            $dgp_to_wallet_1 = $refDGP2Wallet; //$nrg['config']['dgp_to_wallet_1'];
            $user_ip = get_ip_address();
            $dgp_from_wallet = $nrg['user']['xumm_address'];

            NRG_CreateCryptoUserOrder($userId, $order_id, date("d/m/Y"), time(), $fee_orig, "XRP", $landPrice, $cLandAssets, $avatarPrice, $cAvatarAssets, $dgp_delivery, $dgp_tc, $dgp_risk, $dgp_to_wallet_1, $user_ip, $dgp_from_wallet, $cryptoPrice, $cCryptoAssets, $payment_data, $payload_response, $comboPrice, 'Coinbase');
            // NRG_CreateCryptoUserOrder_NEW(
            //     $userId, 
            //     $order_id, 
            //     time(), 
            //     $totalFee, 
            //     "XRP", 
            //     $dgp_to_wallet_1, 
            //     $user_ip, 
            //     $dgp_from_wallet, 
            //     $payment_data,
            //     $payload_response, 
            //     $skuType,
            //     $skuPrice,
            //     $skuQty
            // );

        } else {

            header("Location: index.php");
            exit();
        }
    }
}

function NRG_XummPayment()
{
    $doRun = true;
    if (NRG_IsLogged()) {
        if ($doRun/*$nrg['config']['xumm_wallet'] == '1' /*&& $nrg['config']['can_buy_dgp'] == '1'/* || NRG_IsAdmin() || NRG_IsModerator()*/) {
            global $nrg, $sqlConnect;

            $tokenType = $_POST['tokenType'];
            $tokenQuantity = $_POST['tokenQuantity'];

            $dgp_delivery = 1;
            $dgp_tc = 1;
            $dgp_risk = 1;

            if ($nrg['config']['xumm_mode'] != "live") {
                if ($nrg["user"]["ref_user_id"] == "4296" /* || $nrg["user"]["ref_community"] == "CL"*/) {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_1"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_1"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_1"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_1"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_1"];
                } elseif ($nrg["user"]["ref_user_id"] == "1248" || $nrg["user"]["ref_user_id"] == "14" || $nrg["user"]["ref_user_id"] == "4182" || $nrg["user"]["ref_user_id"] == "4249") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_2"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_2"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_2"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_2"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_2"];
                } elseif ($nrg["user"]["ref_community"] == "WG1") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_3"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_3"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_3"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_3"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_3"];
                } elseif ($nrg["user"]["ref_community"] == "WG2") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_4"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_4"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_4"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_4"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_4"];
                } elseif ($nrg["user"]["ref_community"] == "WG3") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_5"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_5"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_5"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_5"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_5"];
                } elseif ($nrg["user"]["ref_community"] == "CCS") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_6"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_6"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_6"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_6"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_6"];
                } else {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_7"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_7"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_7"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_7"];
                    $refDGP2Wallet = $nrg["config"]["dgp_to_wallet_7"];
                }
            } else {
                if ($nrg["user"]["ref_user_id"] == "4296" /* || $nrg["user"]["ref_community"] == "CL"*/) {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_1"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_1"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_1"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_1"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_1"];
                } elseif ($nrg["user"]["ref_user_id"] == "1248" || $nrg["user"]["ref_user_id"] == "14" || $nrg["user"]["ref_user_id"] == "4182" || $nrg["user"]["ref_user_id"] == "4249") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_2"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_2"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_2"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_2"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_2"];
                } elseif ($nrg["user"]["ref_community"] == "WG1") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_3"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_3"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_3"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_3"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_3"];
                } elseif ($nrg["user"]["ref_community"] == "WG2") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_4"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_4"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_4"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_4"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_4"];
                } elseif ($nrg["user"]["ref_community"] == "WG3") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_5"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_5"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_5"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_5"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_5"];
                } elseif ($nrg["user"]["ref_community"] == "CCS") {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_6"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_6"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_6"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_6"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_6"];
                } else {

                    $refAvatarPrice = $nrg["config"]["dgp_avatar_price_7"];
                    $refLandPrice = $nrg["config"]["dgp_land_price_7"];
                    $refCryptoPrice = $nrg["config"]["purchase_crypto_7"];
                    $refComboPrice = $nrg["config"]["dgp_combo_price_7"];
                    $refDGP2Wallet = $nrg["config"]["dev_dgp_to_wallet_7"];
                }
            }

            $buyFee = 0;
            $landPrice = $refLandPrice;
            $avatarPrice = $refAvatarPrice;
            $cryptoPrice = $refCryptoPrice;
            $comboPrice = $refComboPrice;

            if (isset($tokenType) && !empty($tokenType)) {
                if ($tokenType == 'land') {

                    $cLandAssets = $tokenQuantity;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'land';
                    $skuPrice = $landPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'avatar') {

                    $cLandAssets = 0;
                    $cAvatarAssets = $tokenQuantity;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'avatar';
                    $skuPrice = $avatarPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'crypto') {

                    $cLandAssets = 0;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = $tokenQuantity;
                    $cComboAssets = 0;

                    $skuType = 'crypto';
                    $skuPrice = $cryptoPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else if ($tokenType == 'combotkn') {

                    $cCryptoAssets = 0;
                    $cLandAssets = $tokenQuantity;
                    $cAvatarAssets = $tokenQuantity;
                    $cCryptoAssets = 0;
                    $cComboAssets = $tokenQuantity;

                    $skuType = 'combotkn';
                    $skuPrice = $comboPrice;
                    $skuQty = $tokenQuantity;

                    $finalPurchasePrice = $skuQty * $skuPrice;
                } else {

                    $cLandAssets = 0;
                    $cAvatarAssets = 0;
                    $cCryptoAssets = 0;
                    $cComboAssets = 0;

                    $skuType = 'unknown_error';
                    $skuPrice = 0;
                    $skuQty = 0;

                    $finalPurchasePrice = 0;
                    return false;
                }
            }

            $order_id = uniqid("cl_", true);
            $fee = $finalPurchasePrice;
            $t = time();
            $destinationWalletAddress = $refDGP2Wallet; //$nrg['config']['dgp_to_wallet_1'];

            if (!isset($fee)) {
                echo "not valid request !!!";
                die;
            }

            $fee_orig = $fee;
            $fee  = $fee * 1000000;
            $userId = $nrg['user']['user_id'];
            $dgp_to_wallet_1 = $refDGP2Wallet; //$nrg['config']['dgp_to_wallet_1'];
            $user_ip = get_ip_address();
            $dgp_from_wallet = $nrg['user']['xumm_address'];

            NRG_CreateCryptoUserOrder($userId, $order_id, date("d/m/Y"), time(), $fee_orig, "XRP", $landPrice, $cLandAssets, $avatarPrice, $cAvatarAssets, $dgp_delivery, $dgp_tc, $dgp_risk, $dgp_to_wallet_1, $user_ip, $dgp_from_wallet, $cryptoPrice, $cCryptoAssets, '', '', $comboPrice, 'XUMM');
            // NRG_CreateCryptoUserOrder_NEW(
            //     $userId, 
            //     $order_id, 
            //     time(), 
            //     $totalFee, 
            //     "XRP", 
            //     $dgp_to_wallet_1, 
            //     $user_ip, 
            //     $dgp_from_wallet, 
            //     $payment_data,
            //     $payload_response, 
            //     $skuType,
            //     $skuPrice,
            //     $skuQty,
            //     $skuOwner
            // );
            $return_url = $nrg['config']['site_url'] . '/xpl.php?order_id=' . $order_id;
            //$return_url = $nrg['config']['site_url'].'/themes/cryptoland/cryptoland-buy-now-payload.php?order_id='.$order_id;
            $client = new \GuzzleHttp\Client();
            $xumm_api_key = $nrg['config']['dev_xumm_api_key'];
            $xumm_api_secret = $nrg['config']['dev_xumm_api_secret'];

            if ($nrg['config']['xumm_mode'] == "live") {
                $xumm_api_key = $nrg['config']['xumm_api_key'];
                $xumm_api_secret = $nrg['config']['xumm_api_secret'];
            }

            $response = $client->request('POST', 'https://xumm.app/api/v1/platform/payload', [
                'body' => '{
                "txjson":{
                    "TransactionType":"Payment",
                    "Destination":"' . $destinationWalletAddress . '"
                    ,"Amount":"' . $fee . '"
                },
                "custom_meta":{
                    "identifier":"' . $order_id . '"
                },
                "options":{
                    "return_url":{
                        "web":"' . $return_url . '",
                        "app":"' . $return_url . '"
                    }
                }
            }',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $xumm_api_key,
                    'X-API-Secret' => $xumm_api_secret,
                ],
            ]);

            $reponseData =  json_decode($response->getBody());
            //return $reponseData->next->always;
            //header("Location: ".$reponseData->next->always);  exit;
            echo $reponseData->next->always;
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        header("Location: index.php");
        exit();
    }
}

function NRG_GetXRPLedgerTransactionDetails($txId, $orderId)
{
    global $sqlConnect, $nrg;

    $xrp_ledger_url = 'https://s.altnet.rippletest.net:51234';

    if ($nrg['config']['xumm_mode'] == "live") {
        $xrp_ledger_url = 'https://s2.ripple.com:51234';
    }

    $querySelect = mysqli_query($sqlConnect, "SELECT * FROM xrp_ledger_data WHERE transaction_id = '$txId' ");
    if (!mysqli_num_rows($querySelect)) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $xrp_ledger_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"method":"tx","params":[{"transaction":"' . $txId . '","binary":false}]}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response_string = curl_exec($curl);
        $response = json_decode($response_string);
        curl_close($curl);
        if ($response->result->status == 'success') {
            $toWalletId = $response->result->Account;
            $sql = "UPDATE user_orders_crypto SET dgp_from_wallet = '$toWalletId'  WHERE transaction_id = '$orderId' ";
            $query       = mysqli_query($sqlConnect, $sql);
            // store xrp ledger data
        }

        $sql = "INSERT INTO xrp_ledger_data (`transaction_id`, `order_id`, `response`) VALUES ('$txId', '$orderId', '$response_string')";



        mysqli_query($sqlConnect, $sql);
    }
}

function NRG_getIP()
{
    return $_SERVER['REMOTE_ADDR'];
}

function NRG_getOS()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $os_platform  = "Unknown OS Platform";

    $os_array     = array(
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }

    return $os_platform;
}


function NRG_getBrowser()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser        = "Unknown Browser";
    $browser_array = array(
        '/msie/i'      => 'Internet Explorer',
        '/firefox/i'   => 'Firefox',
        '/safari/i'    => 'Safari',
        '/chrome/i'    => 'Chrome',
        '/edge/i'      => 'Edge',
        '/opera/i'     => 'Opera',
        '/netscape/i'  => 'Netscape',
        '/maxthon/i'   => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i'    => 'Handheld Browser'
    );

    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }

    return $browser;
}

function NRG_writeFile($fn, $q) 
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
    $handle   = fopen($fileName, 'a');
    fwrite($handle, $q . "\n\n");
    fclose($handle);
}


function NRG_UpdateTransactionStausAndQty($transaction_id, $status)
{
    global $sqlConnect;
    $querySelect = mysqli_query($sqlConnect, "SELECT * FROM user_orders_crypto WHERE transaction_id = '$transaction_id' ");
    //if ($logDebug){
    NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", "SELECT * FROM user_orders_crypto WHERE transaction_id = '$transaction_id' ");
    //}
    if (mysqli_num_rows($querySelect)) {
        $fetched_data = mysqli_fetch_assoc($querySelect);

        $user_id      = $fetched_data['user_id'];
        // update
        //$sql = "UPDATE user_orders_crypto SET status = '$status'  WHERE transaction_id = '$transaction_id' ";
        //$query       = mysqli_query($sqlConnect, $sql);

        $sql = "UPDATE user_orders_crypto SET status = '$status' WHERE transaction_id = '$transaction_id' ";
        $query       = mysqli_query($sqlConnect, $sql);
        //if ($logDebug){
        NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", "UPDATE user_orders_crypto SET status = '$status' WHERE transaction_id = '$transaction_id' ");
        //}
        /*if ($query) {
            //

            $sqlSums = "select SUM(land_qty) as total_land , SUM(avatar_qty) as total_avatar , SUM(land_price) as total_land_price , SUM(avatar_price) as total_avtar_price from user_orders_crypto where status = 'true' AND user_id =  '$user_id' ";
        
            
            $querySums       = mysqli_query($sqlConnect, $sqlSums);
            if (mysqli_num_rows($querySums)) {
                $fetched_data_sum = mysqli_fetch_assoc($querySums);

                $uNewLand   = $fetched_data_sum['total_land'];
                $uNewAvatar = $fetched_data_sum['total_avatar'];
                
                $sql2 = "UPDATE NRG_Users SET totalLand = totalLand + '$uNewLand' , totalAvatar = totalAvatar + '$uNewAvatar' WHERE user_id = '$user_id'";
                //$sql2 = "UPDATE NRG_Users SET totalLand = '$uNewLand' , totalAvatar = '$uNewAvatar' WHERE user_id = '$user_id'";
            
            
                $query       = mysqli_query($sqlConnect, $sql2);
            }
            return true;
        } else {
            return false;
        }
    }*/
        if ($query) {
            //

            // $sqlSums = "select land_qty as total_land , avatar_qty as total_avatar , land_price as total_land_price , avatar_price as total_avtar_price from user_orders_crypto where status = 'Confirmed' AND user_id =  '$user_id' AND transaction_id = '$orderID_Now'  ";
            // //$sqlSums = "select SUM(land_qty) as total_land , SUM(avatar_qty) as total_avatar , SUM(land_price) as total_land_price , SUM(avatar_price) as total_avtar_price from user_orders_crypto where status = 'true' AND user_id =  '$user_id' AND transaction_id = '$transaction_id'  ";
            // $querySums       = mysqli_query($sqlConnect, $sqlSums);
            // if (mysqli_num_rows($querySums)) {
            //     $fetched_data_sum = mysqli_fetch_assoc($querySums);

            //     $uNewLand   = $fetched_data_sum['total_land'];
            //     $uNewAvatar = $fetched_data_sum['total_avatar'];


            //     $sql2 = "UPDATE NRG_Users SET totalLand = totalLand + '$uNewLand' , totalAvatar = totalAvatar + '$uNewAvatar' WHERE user_id = '$user_id'";
            //     //$sql2 = "UPDATE NRG_Users SET totalLand = '$uNewLand' , totalAvatar = '$uNewAvatar' WHERE user_id = '$user_id'";
            //     $query2       = mysqli_query($sqlConnect, $sql2);
            //     if ($query2) {
            //         $status = 'Complete';
            //         $sql3 = "UPDATE user_orders_crypto SET status = '$status'  WHERE user_id = '$user_id' AND transaction_id = '$transaction_id' ";
            //         $query3 = mysqli_query($sqlConnect, $sql3);

            //         $query_pdata    = mysqli_query($sqlConnect, " SELECT * FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
            //         $result_pdata = mysqli_fetch_assoc($query_pdata);
            //         $oldResult = $result_pdata;

            //         //$insert = NRG_UpdateAdminUserAction($user_id, $update_data);
            //         if (isset($uNewLand) && $oldResult['totalLand'] != $uNewLand) {
            //             $admin_user_id = $nrg["user"]["user_id"];
            //                 NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Land Adjustment", $actionType_2 = "", $oldResult['totalLand'], $uNewLand, $status,'$transaction_id');
            //         }
            //         if (isset($uNewAvatar) && $oldResult['totalAvatar'] != $uNewAvatar) {
            //             $admin_user_id = $nrg["user"]["user_id"];
            //                 NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Avatar Adjustment", $actionType_2 = "", $oldResult['totalAvatar'], $uNewAvatar, $status, '$transaction_id');
            //         }




            //     }

            // } 

            $sqlSums   = "select land_qty as total_land , avatar_qty as total_avatar , land_price as total_land_price , avatar_price as total_avtar_price from user_orders_crypto where status = 'Complete' AND user_id =  '$user_id' AND transaction_id = '$transaction_id'";
            $querySums = mysqli_query($sqlConnect, $sqlSums);
            //if ($logDebug){
            NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", $sqlSums);
            //}
            if (mysqli_num_rows($querySums)) {
                $fetched_data_sum = mysqli_fetch_assoc($querySums);

                $uNewLand   = $fetched_data_sum['total_land'];
                $uNewAvatar = $fetched_data_sum['total_avatar'];

                $query_pdata  = mysqli_query($sqlConnect, " SELECT * FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
                $result_pdata = mysqli_fetch_assoc($query_pdata);
                $oldResult    = $result_pdata;


                $sql2   = "UPDATE NRG_Users SET totalLand = totalLand + '$uNewLand' , totalAvatar = totalAvatar + '$uNewAvatar' WHERE user_id = '$user_id'";
                // $sql2 = "UPDATE NRG_Users SET totalLand = '$uNewLand' , totalAvatar = '$uNewAvatar' WHERE user_id = '$user_id'";
                $query2 = mysqli_query($sqlConnect, $sql2);
                //if ($logDebug){
                NRG_writeFile("NRG_UpdateTransactionStausAndQty.log", $sql2);
                //}
                if ($query2) {
                    $status = 'Complete';
                    //$sql3   = "UPDATE user_orders_crypto SET status = '$status'  WHERE user_id = '$user_id' AND transaction_id = '$orderID_Now' ";
                    $sql3   = "UPDATE user_orders_crypto SET status = '$status'  WHERE user_id = '$user_id' AND transaction_id = '$transaction_id' ";
                    $query3 = mysqli_query($sqlConnect, $sql3);


                    //$insert = NRG_UpdateAdminUserAction($user_id, $update_data);
                    if (isset($uNewLand) && $oldResult['totalLand'] != $uNewLand) {
                        $admin_user_id = $user_id; //'00100';//$nrg["user"]["user_id"];
                        //echo $admin_user_id." | ".$user_id, $actionType_1 = "Land Adjustment"." | ".$actionType_2 = "CB_CRON.log"." | ".$oldResult['totalLand']." | ".$uNewLand." | ".$status." | ".$transaction_id;
                        //NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Land Adjustment", $actionType_2 = "XUMM_AUTO", $oldResult['totalLand'], $oldResult['totalLand'] + $uNewLand, $status, $transaction_id);


                        NRG_writeFile("XUMM_AUTO.log", "--------------------------------------------------------------------------------[START Vial]-");
                        //NRG_Assign_XUMM_Vial($uNewLand, $user_id);
                        NRG_writeFile("XUMM_AUTO.log", "----------------------------------------------------------------------------------[END Vial]-" . "\n");
                    }
                    if (isset($uNewAvatar) && $oldResult['totalAvatar'] != $uNewAvatar) {
                        $admin_user_id = $user_id; //'00100';//$nrg["user"]["user_id"];$nrg["user"]["user_id"];
                        //NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Avatar Adjustment", $actionType_2 = "XUMM_AUTO", $oldResult['totalAvatar'], $oldResult['totalAvatar'] + $uNewAvatar, $status, $transaction_id);

                        NRG_writeFile("XUMM_AUTO.log", "--------------------------------------------------------------------------------[START LBK]-");
                        //NRG_Assign_XUMM_LBK($uNewAvatar, $user_id);
                        NRG_writeFile("XUMM_AUTO.log", "----------------------------------------------------------------------------------[END LBK]-" . "\n");
                    }

                    //$insert = NRG_UpdateAdminUserAction($user_id, $update_data);
                    // if (isset($uNewLand) && $oldResult['totalLand'] != $uNewLand) {
                    //     $admin_user_id = $user_id; //'00100';//$nrg["user"]["user_id"];
                    //     //echo $admin_user_id." | ".$user_id, $actionType_1 = "Land Adjustment"." | ".$actionType_2 = "CB_CRON.log"." | ".$oldResult['totalLand']." | ".$uNewLand." | ".$status." | ".$transaction_id;
                    //     NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Land Adjustment", $actionType_2 = "FUNCTIONS-1", $oldResult['totalLand'], $oldResult['totalLand'] + $uNewLand, $status, $transaction_id);
                    // }
                    // if (isset($uNewAvatar) && $oldResult['totalAvatar'] != $uNewAvatar) {
                    //     $admin_user_id = $user_id; //'00100';//$nrg["user"]["user_id"];$nrg["user"]["user_id"];
                    //     NRG_insertUpdateLog($admin_user_id, $user_id, $actionType_1 = "Avatar Adjustment", $actionType_2 = "FUNCTIONS-1", $oldResult['totalAvatar'], $oldResult['totalAvatar'] + $uNewAvatar, $status, $transaction_id);
                    // }

                }
            }


            return true;
        } else {
            return false;
        }
    }



    //return $data;
}


function NRG_GetCryptoUserOrderDetails($transaction_id)
{
    global $sqlConnect;
    $sql = "SELECT * FROM user_orders_crypto where transaction_id = '$transaction_id' ";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
}


function NRG_CreateCryptoUserOrder_NEW($userId, $order_id, $time, $totalFee, $currency, $dgp_to_wallet_1, $user_ip, $dgp_from_wallet, $payment_data, $payload_response, $skuType, $skuPrice, $skuQty, $skuOwner)
{
    global $sqlConnect;

    $sql = "INSERT INTO UserDGPOrders
    (hash_id, user_id, product_owner_id, product_id, address_id, price, commission, final_price, units, tracking_url, tracking_id, status, `time`, order_id, currency, dgp_to_wallet, user_ip, dgp_from_wallet, api_payment_data, api_payload_response)
    VALUES
    ('$order_id', $userId, $skuOwner, 0, 0, 0, 0, $totalFee, 0, '', '', 'C1', $time, '', '', '', '', '', '', '')";


    //$sql = "INSERT INTO user_orders_crypto (`user_id`, `transaction_id`, `date`,`time`,`amount`,`currency`,`land_price`,`land_qty`,`avatar_price`,`avatar_qty`, `dgp_delivery`, `dgp_tc`, `dgp_risk`, `dgp_to_wallet`,`user_ip`,`dgp_from_wallet`, `crypto_price`, `crypto_qty`, `payment_data`,`payload_response`,`combo_price`) 
    //VALUES ('$user_id', '$transaction_id', '$date', '$time', '$amount', '$currency', '$land_price', '$land_qty', '$avatar_price', '$avatar_qty', '$dgp_delivery', '$dgp_tc', '$dgp_risk', '$dgp_to_wallet_1','$user_ip','$dgp_from_wallet', '$cryptoPrice', '$cCryptoAssets', '$payment_data','0',$comboPrice)";
    $query  = mysqli_query($sqlConnect, $sql);
    if ($query) {

        // UPDATE USER_TABLE

        /*$uNewLand = $land_qty;
        $uNewAvatar = $avatar_qty;
        $sql2 = "UPDATE NRG_Users SET totalLand = totalLand + '$uNewLand' , totalAvatar = totalAvatar + '$uNewAvatar' WHERE user_id = '$user_id'";
        $query       = mysqli_query($sqlConnect, $sql2);
        */
        return true;
    } else {
        return false;
    }
}

function NRG_CreateCryptoUserOrder($user_id, $transaction_id, $date, $time, $amount, $currency, $land_price, $land_qty, $avatar_price, $avatar_qty, $dgp_delivery, $dgp_tc, $dgp_risk, $dgp_to_wallet_1, $user_ip, $dgp_from_wallet, $cryptoPrice, $cCryptoAssets, $payment_data, $payload_response, $comboPrice, $payment_source)
{
    global $sqlConnect;

    $inPayloadResponse = json_encode($payload_response);

    $sql = "INSERT INTO user_orders_crypto (`user_id`, `transaction_id`, `date`,`time`,`amount`,`currency`,`land_price`,`land_qty`,`avatar_price`,`avatar_qty`, `dgp_delivery`, `dgp_tc`, `dgp_risk`, `dgp_to_wallet`,`user_ip`,`dgp_from_wallet`, `crypto_price`, `crypto_qty`, `payment_data`,`combo_price`,`payment_source`,`payload_response`) 
    VALUES ('$user_id', '$transaction_id', '$date', '$time', '$amount', '$currency', '$land_price', '$land_qty', '$avatar_price', '$avatar_qty', '$dgp_delivery', '$dgp_tc', '$dgp_risk', '$dgp_to_wallet_1','$user_ip','$dgp_from_wallet', '$cryptoPrice', '$cCryptoAssets', '$payment_data','$comboPrice','$payment_source','$inPayloadResponse')";
    $query  = mysqli_query($sqlConnect, $sql);
    if ($query) {

        // UPDATE USER_TABLE

        /*$uNewLand = $land_qty;
        $uNewAvatar = $avatar_qty;
        $sql2 = "UPDATE NRG_Users SET totalLand = totalLand + '$uNewLand' , totalAvatar = totalAvatar + '$uNewAvatar' WHERE user_id = '$user_id'";
        $query       = mysqli_query($sqlConnect, $sql2);
        */
        return true;
    } else {
        return false;
    }
}


function NRG_GetXummTransactionPayload($txnId)
{
    global $sqlConnect;
    $sql = "SELECT * FROM xumm_transaction_payload where transaction_id = '$txnId' ";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
}



function NRG_GetTransactionIdBYPaymentData($payment_data)
{
    global $sqlConnect;
    /*
    $sql = "SELECT transaction_id FROM user_orders_crypto where payment_data = '$payment_data' ";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
*/

    $sql = "SELECT transaction_id FROM user_orders_crypto WHERE payment_data = '$payment_data' AND status = 'created'";
    $query    = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $transaction_id = $fetched_data['transaction_id'];
    }

    return $transaction_id;
}


function NRG_GetUserIdBYPaymentData($orderID_Now)
{
    global $sqlConnect;
    /*
    $sql = "SELECT transaction_id FROM user_orders_crypto where payment_data = '$payment_data' ";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
*/

    $sql = "SELECT user_id FROM user_orders_crypto WHERE transaction_id = '$orderID_Now'/* AND status = 'Confirmed'*/";
    $query    = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $user_id = $fetched_data['user_id'];
    }

    return $user_id;
}





function NRG_InsertXummTransactionPayload($txnId, $payloadResponse, $status)
{
    global $sqlConnect;
    $ip_address  = NRG_getIP();
    $os          = NRG_getOS();
    $browser     = NRG_getBrowser();


    $querySelect = mysqli_query($sqlConnect, "SELECT * FROM xumm_transaction_payload WHERE transaction_id = '$txnId' ");
    NRG_writeFile("NRG_InsertXummTransactionPayload.log", "L" . __LINE__ . " | " . $querySelect);
    if (mysqli_num_rows($querySelect)) {
        // update
        $sql = "UPDATE xumm_transaction_payload SET ip_address = '$ip_address' , os = '$os' , browser = '$browser' ,   payload_response = '$payloadResponse' , status = '$status' WHERE transaction_id = '$txnId' ";
        $query       = mysqli_query($sqlConnect, $sql);
        NRG_writeFile("NRG_InsertXummTransactionPayload.log", "L" . __LINE__ . " | " . $sql);
        if ($query) {
            return true;
        } else {
            return false;
        }
    } else {
        $sql = "INSERT INTO xumm_transaction_payload (`ip_address`, `os`, `browser`,`transaction_id`, `payload_response`, `status`) VALUES ('$ip_address', '$os', '$browser','$txnId', '$payloadResponse', '$status')";
        NRG_writeFile("NRG_InsertXummTransactionPayload.log", "L" . __LINE__ . " | " . $sql);
        $query  = mysqli_query($sqlConnect, $sql);
        if ($query) {
            return true;
        } else {
            return false;
        }
    }
}


function NRG_GetXummPayload($txnId)
{
    global $sqlConnect;
    $sql = "SELECT * FROM xump_payload where payload_txn_id = '$txnId' ";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }
    NRG_writeFile("NRG_GetXummPayload.log", "L" . __LINE__ . " | " . $data);
    return $data;
}

function NRG_InsertXumpPayload($txnId, $payload)
{
    global $sqlConnect;
    $date = date('Y-m-d');
    /// check tansaction id exist or not

    $querySelect = mysqli_query($sqlConnect, "SELECT * FROM xump_payload WHERE payload_txn_id = '$txnId' ");
    if (mysqli_num_rows($querySelect)) {
        // update
        $sql = "UPDATE xump_payload SET payload_response = '$payload' , modified_at = '$date' WHERE payload_txn_id = '$txnId' ";
        $query       = mysqli_query($sqlConnect, $sql);
        NRG_writeFile("NRG_InsertXumpPayload.log", "L" . __LINE__ . " | " . $sql);
        if ($query) {
            return true;
        } else {
            return false;
        }
    } else {
        $sql = "INSERT INTO xump_payload (`payload_txn_id`, `payload_response`, `created_at`,`modified_at`) VALUES ('$txnId', '$payload', '$date', '$date')";
        NRG_writeFile("NRG_InsertXumpPayload.log", "L" . __LINE__ . " | " . $sql);
        $query  = mysqli_query($sqlConnect, $sql);
        if ($query) {
            return true;
        } else {
            return false;
        }
    }
}

function NRG_TxIdExists($txid)
{
    global $sqlConnect;
    if (empty($txid)) {
        return false;
    }
    $txid = NRG_Secure($txid);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `xumm_txid` = '{$txid}'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}



function NRG_UpdateKycStatusBytxId($txid, $xumm_kyc_status)
{
    global $sqlConnect;
    $sql = "UPDATE " . T_USERS . " SET xumm_kyc_status = '$xumm_kyc_status' WHERE xumm_txid = '$txid' ";
    $query       = mysqli_query($sqlConnect, $sql);

    if ($query) {
        return true;
    } else {
        return false;
    }
}

function NRG_SetLoginWithSessionWithTxId($txid)
{
    if (empty($txid)) {
        return false;
    }
    $txid          = NRG_Secure($txid);
    $_SESSION['user_id'] = NRG_CreateLoginSession(NRG_UserIdFromTxId($txid));
    setcookie("user_id", $_SESSION['user_id'], time() + (10 * 365 * 24 * 60 * 60));
    setcookie('ad-con', htmlentities(json_encode(array(
        'date' => date('Y-m-d'),
        'ads' => array()
    ))), time() + (10 * 365 * 24 * 60 * 60));
}

function NRG_UserIdFromTxId($txid)
{
    global $sqlConnect;
    if (empty($txid)) {
        return false;
    }
    $txid = NRG_Secure($txid);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `xumm_txid` = '{$txid}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
/*Govind Code*/





function GetAllNFT($limit = 0)
{
    global $sqlConnect_nft, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $limit_text = "";
    //$limit = "6";
    if (!empty($limit) && is_numeric($limit) && $limit > 0) {
        $limit      = NRG_Secure($limit);
        $limit_text = " LIMIT " . $limit;
    }
    $data    = array();
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (!$user_id || !is_numeric($user_id) || $user_id < 1) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    $offset_text = "";
    if (!empty($offset) && is_numeric($offset) && $offset > 0) {
        $offset      = NRG_Secure($offset);
        $offset_text = " AND `nft_id` > " . $offset;
    }
    $query_text = "SELECT * FROM test ORDER BY RAND() $limit_text";
    $query_one  = mysqli_query($sqlConnect_nft, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NFTData($fetched_data["nft_id"]);
            }
        }
    }
    return $data;
}

function NFTData($nft_id = 0)
{
    global $nrg, $sqlConnect_nft, $cache;
    if (empty($nft_id) || !is_numeric($nft_id) || $nft_id < 0) {
        return false;
    }
    $data          = array();
    $nft_id        = NRG_Secure($nft_id);
    $query_one     = "SELECT * FROM test WHERE `nft_id` = {$nft_id}";
    $hashed_nft_Id = md5($nft_id);

    // we do not need to cache NFT data - Web3 + Moralis will supply Live Data Only

    //if ($nrg["config"]["cacheSystem"] == 1) {
    //    $fetched_data = $cache->read($hashed_nft_Id . "_NFT_Data.tmp");
    //    if (empty($fetched_data)) {
    //        $sql = mysqli_query($sqlConnect_nft, $query_one);
    //        if (mysqli_num_rows($sql)) {
    //            $fetched_data = mysqli_fetch_assoc($sql);
    //            $cache->write($hashed_page_Id . "_NFT_Data.tmp", $fetched_data);
    //        }
    //    }
    //} else {
    $sql = mysqli_query($sqlConnect_nft, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
    }
    //}
    if (empty($fetched_data)) {
        return array();
    }
    /*
        $fetched_data["id"]             = (int)$fetched_data["id"];//NRG_GetMedia($fetched_data["avatar"]);
        $fetched_data["nft_id"]         = (int)$fetched_data["nft_id"];//NRG_GetMedia($fetched_data["avatar"]);
        $fetched_data["dna"]            = (string)$fetched_data["dna"];//NRG_GetMedia($fetched_data["avatar"]);
        $fetched_data["background"]     = $fetched_data["background"];//NRG_GetMedia($fetched_data["cover"]);
        $fetched_data["border"]         = $fetched_data["border"];//$fetched_data["page_description"];
        $fetched_data["skin"]           = $fetched_data["skin"];//$fetched_data["page_id"];
        $fetched_data["eyes_solid"]     = $fetched_data["eyes_solid"];//"page";
        $fetched_data["eyes_normal"]    = $fetched_data["eyes_normal"];//NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["page_name"]);
        $fetched_data["facial_expressions"] = $fetched_data["facial_expressions"];//$fetched_data["page_title"];
        $fetched_data["burn"]           = $fetched_data["burn"];//NRG_PageRating($fetched_data["page_id"]);
        $fetched_data["tattoos"]        = $fetched_data["tattoos"];//"";
        $fetched_data["war_paint"] = $fetched_data["war_paint"];
        //$fetched_data["scars"]        = $fetched_data["nft_id"];//NRG_IsReportExists($fetched_data["page_id"], "page");
        $fetched_data["scars"] = $fetched_data["scars"];
        $fetched_data["facial_hair"]    = $fetched_data["facial_hair"];
        $fetched_data["hair"] = $fetched_data["hair"];
        $fetched_data["accessories"]    = $fetched_data["accessories"];
        $fetched_data["family"] = $fetched_data["family"];
        $fetched_data["metadata"] = $fetched_data["metadata"];
        */
    /*
    if (!empty($nrg["page_categories"][$fetched_data["page_category"]])) {
        $fetched_data["category"] = $nrg["page_categories"][$fetched_data["page_category"]];
    }
    if (!empty($fetched_data["sub_category"]) && !empty($nrg["page_sub_categories"][$fetched_data["page_category"]])) {
        foreach ($nrg["page_sub_categories"][$fetched_data["page_category"]] as $key => $value) {
            if ($value["id"] == $fetched_data["sub_category"]) {
                $fetched_data["page_sub_category"] = $value["lang"];
            }
        }
    }
    $fetched_data["is_page_onwer"] = false;
    $fetched_data["username"]      = $fetched_data["page_name"];
    if ($nrg["loggedin"] == true) {
        $fetched_data["is_page_onwer"] = NRG_IsPageOnwer($fetched_data["page_id"]) ? true : false;
    }
    $fetched_data["fields"] = array();
    $fields                 = NRG_GetCustomFields("page");
    if (!empty($fields)) {
        foreach ($fields as $key => $field) {
            if (in_array($field["fid"], array_keys($fetched_data))) {
                $fetched_data["fields"][$field["fid"]] = $fetched_data[$field["fid"]];
            }
        }
    }
    */
    return $fetched_data;
}

// function NRG_New_Backup2($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, $tables = false, $backup_name = false)
// {
//     $file_path = '../script_backups/';
//     $mysqli = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
//     $mysqli->select_db($sql_db_name);
//     $mysqli->query("SET NAMES 'utf8'");
//     $queryTables = $mysqli->query('SHOW TABLES');
//     while ($row = $queryTables->fetch_row()) {
//         $target_tables[] = $row[0];
//     }
//     if ($tables !== false) {
//         $target_tables = array_intersect($target_tables, $tables);
//     }
//     $content = "-- phpMyAdmin SQL Dump
// -- http://www.phpmyadmin.net
// --
// -- Host Connection Info: " . $mysqli->host_info . "
// -- Generation Time: " . date('F d, Y \a\t H:i A ( e )') . "
// -- Server version: " . mysqli_get_server_info($mysqli) . "
// -- PHP Version: " . PHP_VERSION . "
// --\n
// SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
// SET time_zone = \"+00:00\";\n
// /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
// /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
// /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
// /*!40101 SET NAMES utf8mb4 */;\n\n";
//     foreach ($target_tables as $table) {
//         $result        = $mysqli->query('SELECT * FROM ' . $table);
//         $fields_amount = $result->field_count;
//         $rows_num      = $mysqli->affected_rows;
//         $res           = $mysqli->query('SHOW CREATE TABLE ' . $table);
//         $TableMLine    = $res->fetch_row();
//         $content       = (!isset($content) ? '' : $content) . "
// -- ---------------------------------------------------------
// --
// -- Table structure for table : `{$table}`
// --
// -- ---------------------------------------------------------
// \n" . $TableMLine[1] . ";\n";
//         for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
//             while ($row = $result->fetch_row()) {
//                 if ($st_counter % 100 == 0 || $st_counter == 0) {
//                     $content .= "\n--
// -- Dumping data for table `{$table}`
// --\n\nINSERT INTO " . $table . " VALUES";
//                 }
//                 $content .= "\n(";
//                 for ($j = 0; $j < $fields_amount; $j++) {
//                     $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
//                     if (isset($row[$j])) {
//                         $content .= '"' . $row[$j] . '"';
//                     } else {
//                         $content .= '""';
//                     }
//                     if ($j < ($fields_amount - 1)) {
//                         $content .= ',';
//                     }
//                 }
//                 $content .= ")";
//                 if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
//                     $content .= ";\n";
//                 } else {
//                     $content .= ",";
//                 }
//                 $st_counter = $st_counter + 1;
//             }
//         }
//         $content .= "";
//     }
//     $content .= "
// /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
// /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
// /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
//     if (!file_exists($file_path . date('d-m-Y'))) {
//         @mkdir($file_path . date('d-m-Y'), 0777, true);
//     }
//     if (!file_exists($file_path . date('d-m-Y') . '/' . time())) {
//         mkdir($file_path . date('d-m-Y') . '/' . time(), 0777, true);
//     }
//     if (!file_exists($file_path . date('d-m-Y') . '/' . time() . "/index.html")) {
//         $f = @fopen($file_path . date('d-m-Y') . '/' . time() . "/index.html", "a+");
//         @fwrite($f, "");
//         @fclose($f);
//     }
//     if (!file_exists($file_path.'/.htaccess')) {
//         $f = @fopen($file_path."/.htaccess", "a+");
//         @fwrite($f, "deny from all\nOptions -Indexes");
//         @fclose($f);
//     }
//     if (!file_exists($file_path . date('d-m-Y') . "/index.html")) {
//         $f = @fopen($file_path . date('d-m-Y') . "/index.html", "a+");
//         @fwrite($f, "");
//         @fclose($f);
//     }
//     if (!file_exists($file_path.'/index.html')) {
//         $f = @fopen($file_path."/index.html", "a+");
//         @fwrite($f, "");
//         @fclose($f);
//     }
//     $folder_name = $file_path . date('d-m-Y') . '/' . time();
//     $put         = @file_put_contents($folder_name . '/SQL-Backup-' . time() . '-' . date('d-m-Y') . '.sql', $content);
//     if ($put) {
//         $rootPath = realpath('./');
//         $zip      = new ZipArchive();
//         $open     = $zip->open($folder_name . '/Files-Backup-' . time() . '-' . date('d-m-Y') . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
//         if ($open !== true) {
//             return false;
//         }
//         $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
//         foreach ($files as $name => $file) {
//             if (!preg_match($file_path, $file)) {
//                 if (!$file->isDir()) {
//                     $filePath     = $file->getRealPath();
//                     $relativePath = substr($filePath, strlen($rootPath) + 1);
//                     $zip->addFile($filePath, $relativePath);
//                 }
//             }
//         }
//         $zip->close();
//         $mysqli->query("UPDATE " . T_CONFIG . " SET `value` = '" . date('d-m-Y') . "' WHERE `name` = 'last_backup'");
//         $mysqli->close();
//         return true;
//     } else {
//         return false;
//     }
// }


function NRG_RegisterImportedUser($registration_data, $invited = false)
{
    global $nrg, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    if ($nrg['config']['user_registration'] == 0 && !$invited) {
        return false;
    }
    $ip     = '0.0.0.0';
    $get_ip = get_ip_address();
    if (!empty($get_ip)) {
        $ip = $get_ip;
    }
    if ($nrg['config']['login_auth'] == 1) {
        $getIpInfo = fetchDataFromURL("http://ip-api.com/json/$get_ip");
        $getIpInfo = json_decode($getIpInfo, true);
        if ($getIpInfo['status'] == 'success' && !empty($getIpInfo['regionName']) && !empty($getIpInfo['countryCode']) && !empty($getIpInfo['timezone']) && !empty($getIpInfo['city'])) {
            $registration_data['last_login_data'] = json_encode($getIpInfo);
        }
    }
    $registration_data['registered'] = date('n') . '/' . date("Y");
    $registration_data['joined']     = time();
    $registration_data['password']   = NRG_Secure(password_hash($registration_data['password'], PASSWORD_DEFAULT));
    $registration_data['ip_address'] = NRG_Secure($ip);
    $registration_data['language']   = $nrg['config']['defualtLang'];
    if (!empty($_SESSION['lang'])) {
        $lang_name = strtolower($_SESSION['lang']);
        $langs     = NRG_LangsNamesFromDB();
        if (in_array($lang_name, $langs)) {
            $registration_data['language'] = NRG_Secure($lang_name);
        }
    }
    $registration_data['order_posts_by'] = $nrg['config']['order_posts_by'];
    $fields                              = '`' . implode('`,`', array_keys($registration_data)) . '`';
    $data                                = '\'' . implode('\', \'', $registration_data) . '\'';
    $query                               = mysqli_query($sqlConnect, "INSERT INTO " . T_USERS . " ({$fields}) VALUES ({$data})");
    $user_id                             = mysqli_insert_id($sqlConnect);
    $query_2                             = mysqli_query($sqlConnect, "INSERT INTO " . T_USERS_FIELDS . " (`user_id`) VALUES ({$user_id})");
    if ($query) {
        if ($invited) {
            @NRG_DeleteAdminInvitation('code', $invited);
            NRG_AddInvitedUser($user_id, $invited);
        }
        return true;
    } else {
        return false;
    }
}


function NRG_CryptoLand_Buy_Now($cLandAssets, $cAvatarAssets)
{

    /*

    NEED TO ADD CHECK BOX STATUS TO TO RECORD IN DB

     */

    global $nrg, $sqlConnect;

    $buyFee = 0;
    $landPrice = 0;
    $avatarPrice = 0;

    $finalPurchasePrice = ($cLandAssets * $landPrice) + ($cAvatarAssets * $avatarPrice);

    $order_id = guid();
    $fee = $finalPurchasePrice;
    $tFee = 10;
    $t = time();
    $identifier = $t . "-" . "0777888999";
    $destinationWalletAddress = $nrg['config']['dgp_to_wallet_1'];

    if (!isset($fee)) {
        echo "not valid request !!!";
        die;
    }

    $fee  = $fee * 1000000;

    //NRG_writeFile("NRG_CryptoLand_Buy_Now.log","L".__LINE__." | ".$reponseData);

    require_once('assets/init.php');
    require_once('assets/libraries/XUMM/vendor/autoload.php');


    $return_url = $nrg['config']['site_url'] . '/xpl.php?order_id=' . $identifier;

    $client = new \GuzzleHttp\Client();

    /*$response = $client->request('POST','https://xumm.app/api/v1/platform/payload',[
    'body' => '{"txjson":{"TransactionType":"Payment","Destination":"'.$destinationWalletAddress.'","Amount":"'.$fee.'"},"custom_meta":{"identifier":"'.$order_id.'"},"options":{"return_url":{"web":"'.$return_url.'"}}}',
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-API-Key' => 'cd97eec4-e3c4-40a5-aae0-db440c68101b',
        'X-API-Secret' => '7baa993f-71dd-4e09-b036-009841abec96',
    ],
    ]);*/

    if ($nrg['config']['xumm_mode'] == "live") {
        $response = $client->request('POST', 'https://xumm.app/api/v1/platform/payload', [
            'body' => '{
            "txjson":{
                "TransactionType":"Payment",
                "Destination":"' . $destinationWalletAddress . '",
                "Amount":"' . $fee . '"
            },
            "custom_meta":{
                "identifier":"' . $order_id . '"
            },
            "options":{
                "return_url":{
                    "web":"' . $return_url . '",
                    "app":"' . $return_url . '"
                }
            }
        }',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key' => $nrg['config']['xumm_api_key'],
                'X-API-Secret' => $nrg['config']['xumm_api_secret'],
            ],
        ]);
    } else {
        $response = $client->request('POST', 'https://xumm.app/api/v1/platform/payload', [
            'body' => '{
                "txjson":{
                    "TransactionType":"Payment",
                    "Destination":"' . $destinationWalletAddress . '",
                    "Amount":"' . $fee . '"
                },
                "custom_meta":{
                    "identifier":"' . $order_id . '"
                },
                "options":{
                    "return_url":{
                        "web":"' . $return_url . '",
                        "app":"' . $return_url . '"
                    }
                }
            }',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key' => $nrg['config']['dev_xumm_api_key'],
                'X-API-Secret' => $nrg['config']['dev_xumm_api_secret'],
            ],
        ]);
    }



    $reponseData =  json_decode($response->getBody());
    NRG_writeFile("NRG_CryptoLand_Buy_Now.log", "L" . __LINE__ . " | " . $reponseData);
    header("Location: " . $reponseData->next->always);
}

function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((float)microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123) // "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125); // "}"
        return $uuid;
    }
}

function NRG_CryptoLand_My_Land_DGP()
{
    global $sqlConnect, $nrg;

    if ($nrg["loggedin"] == false) {
        return false;
    }

    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (!$user_id || !is_numeric($user_id) || $user_id < 1) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    /*
       $query_text = "SELECT * FROM NRG_User ORDER BY RAND() $limit_text";
       $query_one  = mysqli_query($sqlConnect_nft, $query_text);
       if (mysqli_num_rows($query_one)) {
           while ($fetched_data = mysqli_fetch_assoc($query_one)) {
               if (is_array($fetched_data)) {
                   $data[] = NFTData($fetched_data["nft_id"]);
               }
           }
       }

       return $data;
       */
}

function NRG_Get_My_DGP_Transactions($args = array())
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $options = array(
        "id" => false,
        "offset" => 0,
        'user_id' => 0
    );
    $args    = array_merge($options, $args);
    $offset  = NRG_Secure($args['offset']);
    $id      = NRG_Secure($args['id']);
    $user_id = $nrg['user']['user_id'];
    if (!empty($args['user_id'])) {
        $user_id = NRG_Secure($args['user_id']);
    }
    $data = array();
    if ($offset > 0) {
        $query_and = " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $sql   = "SELECT * FROM user_orders_crypto WHERE `user_id` = '$user_id' $query_and ORDER BY `id` DESC LIMIT 30";

    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data;
        }
    }
    return $data;
}

function NRG_UserCanPost($user_id)
{
    global $sqlConnect, $nrg;

    if ($nrg['user']['totalLand'] == '0' && $nrg['user']['totalAvatar'] == '0' && ((NRG_IsModerator() === false) && (NRG_IsAdmin() === false))) {
        $Update_data['verified'] = 0;
        $update_data['verify_override'] = true;
        NRG_UpdateUserData($nrg['user']['user_id'], $Update_data, $unverify = true);
        return false;
    } else {
        $Update_data['verified'] = 1;
        NRG_UpdateUserData($nrg['user']['user_id'], $Update_data);
        return true;
    }
}

function NRG_UserCanComment($user_id)
{
    global $sqlConnect, $nrg;

    if ($nrg['user']['totalLand'] == '0' && $nrg['user']['totalAvatar'] == '0' && ((NRG_IsModerator() === false) && (NRG_IsAdmin() === false))) {
        return false;
    } else {
        return true;
    }
}

function NRG_UpdateUserCLData($user_id, $update_data, $loggedin = true)
{
    global $nrg, $sqlConnect, $cache;

    if ($nrg['loggedin'] == false) {
        return false;
    }
    /*if ($loggedin == true) {
        if ($nrg["loggedin"] == false) {
            return false;
        }
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if ($loggedin == true) {
        if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
            if ($nrg["user"]["user_id"] != $user_id) {
                return false;
            }
        }
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        foreach ($data as $key => $value) {
            $update[] = "`" . $key . '` = \'' . NRG_Secure($value, 0) . '\'';
        }
    }
    $impload   = implode(", ", $update);
    $query_one = "UPDATE " . T_USERS_FIELDS . " SET {$impload} WHERE `user_id` = {$user_id}";
    $query_1   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_USERS_FIELDS . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query_1)) {
        $query_1_sql = mysqli_fetch_assoc($query_1);
        $query       = false;
        if ($query_1_sql["count"] == 1) {
            $query = mysqli_query($sqlConnect, $query_one);
        } else {$nrg["user"]
            $query_2 = mysqli_query($sqlConnect, "INSERT INTO " . T_USERS_FIELDS . " (`user_id`) VALUES ({$user_id})");
            if ($query_2) {
                $query = mysqli_query($sqlConnect, $query_one);
            }
        }
        if ($query) {
            return true;
        }
    }
    return false;
    */
}

// function NRG_insertUpdateLog($by_user_id, $to_user_id = 0, $actionType_1 = "", $actionType_2 = "", $oldValue = "", $newValue = "", $status, $fullPayload)
// {
//     global $nrg, $sqlConnect, $cache;



//     if ($nrg['loggedin'] == false) {
//         //return false;
//     }

//     $date_now = date("d/m/Y");
//     $time_now = time();
//     $uip = get_ip_address();
//     $i_payload = json_encode($fullPayload);
//     $from_user_id = $nrg["user"]["user_id"];

//     $query_one = "INSERT INTO transactionUpdateLog (by_user_id, to_user_id, `date`, `time`, actionType_1, actionType_2, oldValue, newValue, `status`, user_ip, payload) VALUES ('$by_user_id','$to_user_id', '$date_now', '$time_now', '$actionType_1', '$actionType_2', '$oldValue', '$newValue', '$status', '$uip',  '$i_payload');";

//     // $myfile = fopen("/var/www/htdocs/transLogs.txt", "w") or die("Unable to open file!");
//     // $txt = $query_one;//"John Doe\n";
//     // fwrite($myfile, $txt);
//     // // $txt = $query_one;//"Jane Doe\n";
//     // // fwrite($myfile, $txt);
//     // fclose($myfile);

//     NRG_writeFile("NRG_insertUpdateLog.log", "L" . __LINE__ . " | " . $query_one);

//     // $fp = fopen('/var/www/htdocs/transLogs.txt', 'a');//opens file in append mode  

//     // fwrite($fp, "\ninsertUpdateLog (".date('m/d/Y H:i:s', time())."):\n");
//     // fwrite($fp, $query_one);
//     // //fwrite($fp, "<br>"."\r\n");
//     // //fwrite($fp, '/n');  
//     // fclose($fp);  

//     //echo "File appended successfully";  


//     $query = mysqli_query($sqlConnect, $query_one);

//     //return $query_one;
// }

// function NRG_CountDGPData($type)
// {
//     global $nrg, $sqlConnect;
//     //$type == $type;
//     $data       = array();
//     $type_table = T_USERS;
//     $type_id    = NRG_Secure("user_id");
//     $time       = time() - 60;
//     $query_one  = mysqli_query($sqlConnect, "SELECT SUM(`{$type}`) as count FROM {$type_table} ");
//     if (mysqli_num_rows($query_one)) {
//         $fetched_data = mysqli_fetch_assoc($query_one);
//         return $fetched_data["count"];
//     }
//     return false;
// }

function NRG_GetUserSessionData($user_id)
{
    global $nrg, $sqlConnect;
    //$_SESSION["hash_id"]

    $sql   = "SELECT max(id), platform_details FROM NRG_AppsSessions WHERE `user_id` = '$user_id'";

    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data;
            //$t1=$data['platform_details'];
        }
    }
    return $data; //['platform_details'];
}

function NRG_GetUserReferredCount($user_id)
{
    global $nrg, $sqlConnect;
    //$_SESSION["hash_id"]

    $sql   = "SELECT COUNT(*) FROM NRG_Users WHERE `referrer` = $user_id";

    $query = mysqli_query($sqlConnect, $sql);
    //$data = mysql_num_rows($query);
    return '0';
    //echo $data;//$query;//['platform_details'];
}
/*
function NRG_UpdateAdminUserAction($user_id, $update_data)
{
    global $nrg, $sqlConnect, $cache;

    if ($nrg['loggedin'] == false) {
        return false;
    }

    
    //INSERT INTO dev_community_cryptoland_io_0718202201.admin_actions
    //(admin_user_id, user_id, `date`, `time`, action_type, action_value, new_value, current_value, status, user_ip, payload)
    //VALUES(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'created', '0.0.0.0', NULL);
    

    $date_now = date("d/m/Y");
    $time_now = time();
    $i_payload = json_encode($update_data);

    $admin_user_id = $nrg["user"]["user_id"];


    $action_value = $update_data['totalLand'];
    $actionType = 'Land Adjustment';

    $sql = "SELECT totalLand FROM " . T_USERS . " WHERE user_id = '$user_id'";
    $query    = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $current_value = $fetched_data['totalLand'];
    }
    //$current_value = $data;

    if ($current_value >= $action_value) {
        $new_value = $landValueNow + $action_value;
    } elseif ($landValueNow <= $action_value) {
        $new_value = $landValueNow - $action_value;
    } else {
        $new_value = '0';
    }
    //$new_value = $current_value+$action_value;


    $query_one = "INSERT INTO admin_actions (admin_user_id, user_id, `date`, `time`, action_type, action_value, new_value, current_value, status, user_ip, payload) VALUES ('$admin_user_id','$user_id', '$date_now', '$time_now', '$actionType', '$action_value', '$new_value', '$current_value', 'created', '0.0.0.0','$i_payload');";
    $query = mysqli_query($sqlConnect, $query_one);
}
*/
function NRG_GetAccountTransactions($which)
{
    global $sqlConnect;
    /*
    $sql = "SELECT * FROM transactionUpdateLog";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
    */

    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM transactionUpdateLog WHERE by_user_id != '1010101012'";
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    $limit = 250;
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            //$fetched_data            = NRG_GameData($fetched_data["id"]);
            //$fetched_data["players"] = NRG_CountGamePlayers($fetched_data["id"]);
            $data[]                  = $fetched_data;
        }
    }
    return $data;
}

function NRG_GetUserOrdersCryptoTransactionReport($which)
{
    global $sqlConnect;
    /*
    $sql = "SELECT * FROM transactionUpdateLog";
    $query = mysqli_query($sqlConnect, $sql);
    $data = array();
    if (mysqli_num_rows($query)) {
        $data[] = mysqli_fetch_assoc($query);
    }

    return $data;
    */

    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM user_orders_crypto WHERE payment_source = '$which'/*WHERE id > 129*/";
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    $limit = 250;
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            //$fetched_data            = NRG_GameData($fetched_data["id"]);
            //$fetched_data["players"] = NRG_CountGamePlayers($fetched_data["id"]);
            $data[]                  = $fetched_data;
        }
    }
    return $data;
}

function NRG_XUMMWalletExists($wid)
{
    global $sqlConnect;
    if (empty($wid)) {
        return false;
    }
    $wid = NRG_Secure($wid);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `xumm_address` = '{$wid}'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}

function NRG_getUserNFTs($user_id)
{
    global $sqlConnect;


    $where = "WHERE user_id = $user_id";
    $sql = "SELECT * from user_nft $where";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }
    //echo json_encode($jsonArray);
    return $jsonArray;
}


function NRG_getNFT($nft_id)
{
    global $sqlConnect;

    $where = "WHERE nft_id = $nft_id";
    $sql = "SELECT * from lbk_nft $where";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }
    //echo json_encode($jsonArray);
    return $jsonArray;
}


function NRG_getNFT_URI($nft_id)
{
    global $sqlConnect;

    $where = "WHERE nft_id = $nft_id";
    $sql = "SELECT base_uri from lbk_nft $where";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    return $result;
}


function NRG_UpdateUserTC($action_type)
{
    global $nrg, $sqlConnect, $cache;

    if ($nrg['loggedin'] == false) {
        return false;
    }

    $date_now = date("d/m/Y");
    $time_now = time();
    $timestamp = time();
    $dateTime = $timestamp;
    $userId = $nrg['user']['user_id'];
    $browser_info = NRG_getBrowser() . "|" . NRG_getOS();
    $user_ip = get_ip_address();
    $owner_wallet = $nrg['user']['xumm_address'];
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | UPDATE: " . $action_type . " | " . $date_now . " | " . $userId . " | " . $browser_info . " | " . $user_ip . " | " . $owner_wallet);
    $tempVal = "INSERT INTO user_tc (user_id, user_ip, browser_info, action_type, action_created_date, action_complete, created_by, last_update, owner_wallet) VALUES ($userId, '$user_ip', '$browser_info', $action_type, $timestamp , $timestamp, $userId, $timestamp, $owner_wallet);";
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | UPDATE: " . $tempVal);
    $query_one = "INSERT INTO user_tc (
        user_id, user_ip, browser_info, action_type, action_created_date, action_complete, created_by, last_update, owner_wallet
        ) VALUES (
        $userId, '$user_ip', '$browser_info', $action_type, $timestamp , $timestamp, $userId, $timestamp, $owner_wallet
        );";
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | " . $query_one);
    $query = mysqli_query($sqlConnect, $query_one);
}


function NRG_CheckUserTC($user_id)
{
    global $sqlConnect, $nrg;
    $browser_info = NRG_getBrowser() . "|" . NRG_getOS();
    $user_ip = get_ip_address();
    $owner_wallet = $nrg['user']['xumm_address'];
    NRG_writeFile("NRG_CheckUserTC.log", "L" . __LINE__ . " | " . $browser_info . " | " . $user_ip . " | " . $owner_wallet);
    $where = "WHERE user_id = $user_id";
    $sql = "SELECT * from user_tc $where";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));
    NRG_writeFile("NRG_CheckUserTC.log", "L" . __LINE__ . " | " . $sql);
    $jsonArray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }
    //echo json_encode($jsonArray);
    NRG_writeFile("NRG_CheckUserTC.log", "L" . __LINE__ . " | " . json_encode($jsonArray));
    return $jsonArray;
}

function NRG_UpdateNFTHistory($nft_uuid, $nft_id, $nft_serial, $action_type, $action_created_date, $action_complete, $action_complete_date, $created_by, $user_id, $issuer_wallet, $owner_wallet, $transaction_hash, $xumm_txid, $xumm_payload_uuidv4)
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

function NRG_getNFT2($nft_id)
{
    global $sqlConnect;

    $where = "WHERE nft_id = '$nft_id'";
    $sql = "SELECT * from lbk_nft $where";
    $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));

    $jsonArray = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $jsonArray[] = $row;
    }
    if (empty($jsonArray)) {
        $sql = "SELECT * from vials_nft $where";
        $result = mysqli_query($sqlConnect, $sql) or die("Error in Selecting " . mysqli_error($sqlConnect));
        $tempVar = '';
        while ($row = mysqli_fetch_assoc($result)) {
            $jsonArray[] = $row;
        }
    }
    if (count($jsonArray) === 1) {
        return $jsonArray[0];
    }
    return $jsonArray;
}

function NRG_UpdateUserTC2($action_type)
{
    global $nrg, $sqlConnect, $cache;

    if ($nrg['loggedin'] == false) {
        return false;
    }

    $date_now = date("d/m/Y");
    $time_now = time();
    $timestamp = $time_now;
    $userId = $nrg['user']['user_id'];
    $browser_info = NRG_getBrowser() . "|" . NRG_getOS();
    $user_ip = get_ip_address();
    $owner_wallet = $nrg['user']['xumm_address'];
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | UPDATE: " . $action_type . " | " . $date_now . " | " . $userId . " | " . $browser_info . " | " . $user_ip . " | " . $owner_wallet);
    $tempVal = "INSERT INTO user_tc (user_id, user_ip, browser_info, action_type, action_created_date, action_complete, created_by, last_update, owner_wallet) VALUES ($userId, '$user_ip', '$browser_info', $action_type, $timestamp , $timestamp, $userId, $timestamp, $owner_wallet);";
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | UPDATE: " . $tempVal);

    $query_one = "INSERT INTO user_tc (user_id, user_ip, browser_info, action_type, action_created_date, action_complete, created_by, last_update, owner_wallet) VALUES ('" . $userId . "', '" . $user_ip . "', '" . $browser_info . "', '" . $action_type . "', '" . $time_now . "', '1', '" . $userId . "', '" . $time_now . "', '" . $owner_wallet . "');";
    $query = mysqli_query($sqlConnect, $query_one);
    NRG_writeFile("NRG_UpdateUserTC.log", "L" . __LINE__ . " | " . $query_one);
    return true;
}

function NRG_getUserNFTs2($userId)
{
    $items = NRG_getUserNFTs($userId);

    if (!empty($items)) {
        foreach ($items as $key => $item) {
            $nft = NRG_getNFT2($item['nft_id']);
            $items[$key]['nft'] = $nft;
        }
    }

    return $items;
}

function NRG_updateNFTAsClaimed($nftId)
{
    global $nrg;

    $nft = NRG_getNFT2($nftId);
    $assetType = $nft['assetType'];
    $timestamp = time();
    $userId = $nrg['user']['user_id'];
    $userWallet = $nrg['user']['xumm_address'];

    if ($assetType == 1) {
        $result = NRG_UpdateLBKNFT(1, $userId, $timestamp, 0, 0, 0, $nftId, $userWallet);
        $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 0, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
    } elseif ($assetType == 2) {
        $result = NRG_UpdateVialsNFT(1, $userId, $timestamp, 0, 0, 0, $nftId, $userWallet);
        $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 0, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
    } else {
    }
    //$result = NRG_UpdateLBKNFT(1, $userId, $timestamp, 0, 0, 0, $nftId, $userWallet);
    //$result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 0, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
}

function NRG_updateNFTAsRevealed($nftId)
{
    global $nrg;

    $nft = NRG_getNFT2($nftId);
    $assetType = $nft['assetType'];

    $timestamp = time();
    $userId = $nrg['user']['user_id'];
    $userWallet = $nrg['user']['xumm_address'];

    if ($assetType == 1) {
        $result = NRG_UpdateLBKNFT('', '', '', 1, $userId, $timestamp, $nftId, $userWallet);
        $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 1, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
    } elseif ($assetType == 2) {
        $result = NRG_UpdateVialsNFT('', '', '', 1, $userId, $timestamp, $nftId, $userWallet);
        $result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 1, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
    } else {
    }

    echo $result;
    //$result = NRG_UpdateLBKNFT('', '', '', 1, $userId, $timestamp, $nftId, $userWallet);
    //$result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 1, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
}

function NRG_UpdateLBKNFT($claimed, $claimed_user_id, $claimed_date, $revealed, $revealed_user_id, $revealed_date, $nft_id, $userWallet)
{
    global $nrg, $sqlConnect, $cache;

    $where = "WHERE nft_id = '$nft_id'";

    if ($claimed == '1' && $claimed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE lbk_nft SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed='$claimed', claimed_user_id=$claimed_user_id, claimed_date=$claimed_date, revealed=revealed, revealed_user_id=revealed_user_id, revealed_date=revealed_date $where";
        NRG_writeFile("NRG_UpdateLBKNFT.log", "L" . __LINE__ . " | " . $query_one);
    }

    
    if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE lbk_nft SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='$revealed', revealed_user_id=$revealed_user_id, revealed_date=$revealed_date $where";
        NRG_writeFile("NRG_UpdateLBKNFT.log", "L" . __LINE__ . " | " . $query_one);
    }
    $query = mysqli_query($sqlConnect, $query_one);

    if ($query) {
        //if (($claimed == '1' && $revealed == '1') && ($claimed_user_id == $revealed_user_id && $revealed_user_id == $nrg["user"]["user_id"])){
        if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
    //        NRG_UpdateLBKNFT_Metadata_File($nft_id, $userWallet);
        }
    } else {
        return "failed";
    }
    return "true";
}

function NRG_UpdateVialsNFT($claimed, $claimed_user_id, $claimed_date, $revealed, $revealed_user_id, $revealed_date, $nft_id, $userWallet)
{
    global $nrg, $sqlConnect, $cache;

    $where = "WHERE nft_id = '$nft_id'";

    if ($claimed == '1' && $claimed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE vials_nft SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed='$claimed', claimed_user_id=$claimed_user_id, claimed_date=$claimed_date, revealed=revealed, revealed_user_id=revealed_user_id, revealed_date=revealed_date $where";
        NRG_writeFile("NRG_UpdateVialsNFT.log", "L" . __LINE__ . " | " . $query_one);
    }
    if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
        $query_one = "UPDATE vials_nft SET issuer_wallet=issuer_wallet, owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='$revealed', revealed_user_id=$revealed_user_id, revealed_date=$revealed_date $where";
        NRG_writeFile("NRG_UpdateVialsNFT.log", "L" . __LINE__ . " | " . $query_one);
    }
    $query = mysqli_query($sqlConnect, $query_one);

    if ($query) {
        //if (($claimed == '1' && $revealed == '1') && ($claimed_user_id == $revealed_user_id && $revealed_user_id == $nrg["user"]["user_id"])){
        if ($revealed == '1' && $revealed_user_id == $nrg["user"]["user_id"]) {
         //   NRG_UpdateVialsNFT_Metadata_File($nft_id, $userWallet);
        }
    } else {
        return "failed";
    }
    return "true";
}

function NRG_UpdateLBKNFT_Metadata_File($nft_id, $userWallet)
{
    global $nrg, $sqlConnect, $cache;

    $sql = "SELECT nft_serial FROM lbk_nft WHERE nft_id = '$nft_id' AND owner_wallet = '$userWallet'";

    $query = mysqli_query($sqlConnect, $sql);
    NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $nft_serial = $fetched_data['nft_serial'];
        $final_nft_serial = $nft_serial + 1;
    }

    $file1RenameFrom = '/var/www/htdocs/ingameassets.cryptoland.host/lbk/metadata/' . $final_nft_serial . '.json';
    $file1RenameTo = '/var/www/htdocs/ingameassets.cryptoland.host/lbk/metadata/unrevealed_bak/' . $final_nft_serial . '_UNREVEALED_' . time() . '_bak.json';
    $file2MoveFrom = '/var/www/htdocs/ingameassets.cryptoland.host/lbk/metadata/revealed/' . $final_nft_serial . '.json';
    $file2MoveTo = '/var/www/htdocs/ingameassets.cryptoland.host/lbk/metadata/' . $final_nft_serial . '.json';

    if (rename($file1RenameFrom, $file1RenameTo)) {
        if (copy($file2MoveFrom, $file2MoveTo)) {
            NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . "[Success] $file2MoveFrom -> $file2MoveTo");
            //return "success";

        } else {


            $where = "WHERE nft_id = '$nft_id'";
            $query_one = "UPDATE lbk_nft SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
            $query = mysqli_query($sqlConnect, $query_one);
            NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
            NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . "[Failed] $file2MoveFrom -> $file2MoveTo");
            return "failed";
        }
        NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . "[Success] $file1RenameFrom -> $file1RenameTo ");
        return "success";
    } else {


        $where = "WHERE nft_id = '$nft_id'";
        $query_one = "UPDATE lbk_nft SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
        $query = mysqli_query($sqlConnect, $query_one);
        NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
        NRG_writeFile("NRG_UpdateLBKNFT_Metadata_File.log", "L" . __LINE__ . " | " . "[Failed] $file1RenameFrom -> $file1RenameTo ");
        return "failed";
    }
    //echo $message;
}

function NRG_UpdateVialsNFT_Metadata_File($nft_id, $userWallet)
{
    global $nrg, $sqlConnect, $cache;

    $sql = "SELECT nft_serial FROM vials_nft WHERE nft_id = '$nft_id' AND owner_wallet = '$userWallet'";

    $query = mysqli_query($sqlConnect, $sql);
    NRG_writeFile("NRG_UpdateVialsNFT_Metadata_File.log", "L" . __LINE__ . " | " . $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $nft_serial = $fetched_data['nft_serial'];
        $final_nft_serial = $nft_serial + 1;
    }

    $file1RenameFrom = '/var/www/htdocs/ingameassets.cryptoland.host/vials/metadata/' . $final_nft_serial . '.json';
    $file1RenameTo = '/var/www/htdocs/ingameassets.cryptoland.host/vials/metadata/unrevealed_bak/' . $final_nft_serial . '_UNREVEALED_' . time() . '_bak.json';
    $file2MoveFrom = '/var/www/htdocs/ingameassets.cryptoland.host/vials/metadata/revealed/' . $final_nft_serial . '.json';
    $file2MoveTo = '/var/www/htdocs/ingameassets.cryptoland.host/vials/metadata/' . $final_nft_serial . '.json';

    if (rename($file1RenameFrom, $file1RenameTo)) {
        if (copy($file2MoveFrom, $file2MoveTo)) {

            //return "success";

        } else {


            $where = "WHERE nft_id = '$nft_id'";
            $query_one = "UPDATE vials_nft SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
            $query = mysqli_query($sqlConnect, $query_one);
            NRG_writeFile("NRG_UpdateVialsNFT_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
            return "failed";
        }

        return "success";
    } else {


        $where = "WHERE nft_id = '$nft_id'";
        $query_one = "UPDATE vials_nft SET owner_wallet='$userWallet', claimed=claimed, claimed_user_id=claimed_user_id, claimed_date=claimed_date, revealed='0', revealed_user_id=0, revealed_date=0 $where";
        $query = mysqli_query($sqlConnect, $query_one);
        NRG_writeFile("NRG_UpdateVialsNFT_Metadata_File.log", "L" . __LINE__ . " | " . $query_one);
        return "failed";
    }
    //echo $message;
}

function NRG_Assign_Coinbase_LBK($uNewAvatar, $user_id)
{
    global $sqlConnect;

    NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | NRG_Assign_Coinbase_LBK: " . $uNewAvatar . " | " . $user_id);

    for ($x = 1; $x <= $uNewAvatar; $x++) {
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $uNewAvatar . " | " . $x);
        $sqlLBK   = "SELECT lbk_nft.* FROM lbk_nft INNER JOIN user_nft ON lbk_nft.nft_id = user_nft.nft_id WHERE user_nft.user_id = '0' ORDER BY RAND() LIMIT 1";
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sqlLBK);
        $querysqlLBK = mysqli_query($sqlConnect, $sqlLBK);
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . mysqli_num_rows($querysqlLBK));

        $result = $querysqlLBK;
        if ($result) {
            $fetched_data = mysqli_fetch_assoc($querysqlLBK);

            $nft_uuid = $fetched_data['nft_uuid']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            $nft_id = $fetched_data['nft_id']; //'000927100F9E923BB432E9761F33B9B369E32BFF1EA71FC90000099B00000000', 
            $issuer_wallet = $fetched_data['issuer_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $owner_wallet = $fetched_data['owner_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $nft_serial = $fetched_data['nft_serial']; //0, 
            $minted_date = $fetched_data['minted_date']; //1678213042, 
            $base_uri = $fetched_data['base_uri']; //'https://ingameassets.cryptoland.host/lbk/metadata/1.json', 
            $taxon = $fetched_data['taxon']; //'0', 
            $burnable = $fetched_data['burnable']; //'1', 
            $only_xrp = $fetched_data['only_xrp']; //'0', 
            $transferable = $fetched_data['transferable']; //'1', 
            $claimed = $fetched_data['claimed']; //'0', 
            $claimed_user_id = $fetched_data['claimed_user_id']; //0, 
            $claimed_date = $fetched_data['claimed_date']; //0, 
            $revealed = $fetched_data['revealed']; //'0', 
            $revealed_user_id = $fetched_data['revealed_user_id']; //0, 
            $revealed_date = $fetched_data['revealed_date']; //0

            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $nft_uuid);

            $sql21   = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1  ";
            $query21 = mysqli_query($sqlConnect, $sql21);
            $result21 = $query21;
            if ($result) {
                $fetched_data = mysqli_fetch_assoc($query21);
                $user_wallet = $fetched_data['xumm_address']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            }

            $last_update = time();
            $sql12   = "UPDATE user_nft SET user_id=$user_id, last_update=$last_update, owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sql12);
            $query12 = mysqli_query($sqlConnect, $sql12);

            $sql13   = "UPDATE lbk_nft SET owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sql13);
            $query13 = mysqli_query($sqlConnect, $sql13);
        }
    }
}

function NRG_Assign_Coinbase_Vial($uNewAvatar, $user_id)
{
    global $sqlConnect;

    NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | NRG_Assign_Coinbase_Vial: " . $uNewAvatar . " | " . $user_id);

    for ($x = 1; $x <= $uNewAvatar; $x++) {
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $uNewAvatar . " | " . $x);
        $sqlLBK   = "SELECT vial_nft.* FROM vial_nft INNER JOIN user_nft ON vial_nft.nft_id = user_nft.nft_id WHERE user_nft.user_id = '0' ORDER BY RAND() LIMIT 1";
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sqlLBK);
        $querysqlLBK = mysqli_query($sqlConnect, $sqlLBK);
        NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . mysqli_num_rows($querysqlLBK));

        $result = $querysqlLBK;
        if ($result) {
            $fetched_data = mysqli_fetch_assoc($querysqlLBK);

            $nft_uuid = $fetched_data['nft_uuid']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            $nft_id = $fetched_data['nft_id']; //'000927100F9E923BB432E9761F33B9B369E32BFF1EA71FC90000099B00000000', 
            $issuer_wallet = $fetched_data['issuer_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $owner_wallet = $fetched_data['owner_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $nft_serial = $fetched_data['nft_serial']; //0, 
            $minted_date = $fetched_data['minted_date']; //1678213042, 
            $base_uri = $fetched_data['base_uri']; //'https://ingameassets.cryptoland.host/lbk/metadata/1.json', 
            $taxon = $fetched_data['taxon']; //'0', 
            $burnable = $fetched_data['burnable']; //'1', 
            $only_xrp = $fetched_data['only_xrp']; //'0', 
            $transferable = $fetched_data['transferable']; //'1', 
            $claimed = $fetched_data['claimed']; //'0', 
            $claimed_user_id = $fetched_data['claimed_user_id']; //0, 
            $claimed_date = $fetched_data['claimed_date']; //0, 
            $revealed = $fetched_data['revealed']; //'0', 
            $revealed_user_id = $fetched_data['revealed_user_id']; //0, 
            $revealed_date = $fetched_data['revealed_date']; //0

            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $nft_uuid);

            $sql21   = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1  ";
            $query21 = mysqli_query($sqlConnect, $sql21);
            $result21 = $query21;
            if ($result) {
                $fetched_data = mysqli_fetch_assoc($query21);
                $user_wallet = $fetched_data['xumm_address']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            }

            $last_update = time();
            $sql12   = "UPDATE user_nft SET user_id=$user_id, last_update=$last_update, owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sql12);
            $query12 = mysqli_query($sqlConnect, $sql12);

            $sql13   = "UPDATE vial_nft SET owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("CB_CRON.log", "L" . __LINE__ . " | " . $sql13);
            $query13 = mysqli_query($sqlConnect, $sql13);
        }
    }
}

function NRG_Assign_XUMM_LBK($uNewAvatar, $user_id)
{
    global $sqlConnect;

    NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | NRG_Assign_XUMM_LBK: " . $uNewAvatar . " | " . $user_id);

    for ($x = 1; $x <= $uNewAvatar; $x++) {
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $uNewAvatar . " | " . $x);
        $sqlLBK   = "SELECT lbk_nft.* FROM lbk_nft INNER JOIN user_nft ON lbk_nft.nft_id = user_nft.nft_id WHERE user_nft.user_id = '0' ORDER BY RAND() LIMIT 1";
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sqlLBK);
        $querysqlLBK = mysqli_query($sqlConnect, $sqlLBK);
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . mysqli_num_rows($querysqlLBK));

        $result = $querysqlLBK;
        if ($result) {
            $fetched_data = mysqli_fetch_assoc($querysqlLBK);

            $nft_uuid = $fetched_data['nft_uuid']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            $nft_id = $fetched_data['nft_id']; //'000927100F9E923BB432E9761F33B9B369E32BFF1EA71FC90000099B00000000', 
            $issuer_wallet = $fetched_data['issuer_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $owner_wallet = $fetched_data['owner_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $nft_serial = $fetched_data['nft_serial']; //0, 
            $minted_date = $fetched_data['minted_date']; //1678213042, 
            $base_uri = $fetched_data['base_uri']; //'https://ingameassets.cryptoland.host/lbk/metadata/1.json', 
            $taxon = $fetched_data['taxon']; //'0', 
            $burnable = $fetched_data['burnable']; //'1', 
            $only_xrp = $fetched_data['only_xrp']; //'0', 
            $transferable = $fetched_data['transferable']; //'1', 
            $claimed = $fetched_data['claimed']; //'0', 
            $claimed_user_id = $fetched_data['claimed_user_id']; //0, 
            $claimed_date = $fetched_data['claimed_date']; //0, 
            $revealed = $fetched_data['revealed']; //'0', 
            $revealed_user_id = $fetched_data['revealed_user_id']; //0, 
            $revealed_date = $fetched_data['revealed_date']; //0

            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $nft_uuid);

            $sql21   = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1  ";
            $query21 = mysqli_query($sqlConnect, $sql21);
            $result21 = $query21;
            if ($result) {
                $fetched_data = mysqli_fetch_assoc($query21);
                $user_wallet = $fetched_data['xumm_address']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            }

            $last_update = time();
            $sql12   = "UPDATE user_nft SET user_id=$user_id, last_update=$last_update, owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sql12);
            $query12 = mysqli_query($sqlConnect, $sql12);

            $sql13   = "UPDATE lbk_nft SET owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sql13);
            $query13 = mysqli_query($sqlConnect, $sql13);
        }
    }
}

function NRG_Assign_XUMM_Vial($uNewAvatar, $user_id)
{
    global $sqlConnect;

    NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | NRG_Assign_XUMM_Vial: " . $uNewAvatar . " | " . $user_id);

    for ($x = 1; $x <= $uNewAvatar; $x++) {
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $uNewAvatar . " | " . $x);
        $sqlLBK   = "SELECT vial_nft.* FROM vial_nft INNER JOIN user_nft ON vial_nft.nft_id = user_nft.nft_id WHERE user_nft.user_id = '0' ORDER BY RAND() LIMIT 1";
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sqlLBK);
        $querysqlLBK = mysqli_query($sqlConnect, $sqlLBK);
        NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . mysqli_num_rows($querysqlLBK));

        $result = $querysqlLBK;
        if ($result) {
            $fetched_data = mysqli_fetch_assoc($querysqlLBK);

            $nft_uuid = $fetched_data['nft_uuid']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            $nft_id = $fetched_data['nft_id']; //'000927100F9E923BB432E9761F33B9B369E32BFF1EA71FC90000099B00000000', 
            $issuer_wallet = $fetched_data['issuer_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $owner_wallet = $fetched_data['owner_wallet']; //'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', 
            $nft_serial = $fetched_data['nft_serial']; //0, 
            $minted_date = $fetched_data['minted_date']; //1678213042, 
            $base_uri = $fetched_data['base_uri']; //'https://ingameassets.cryptoland.host/lbk/metadata/1.json', 
            $taxon = $fetched_data['taxon']; //'0', 
            $burnable = $fetched_data['burnable']; //'1', 
            $only_xrp = $fetched_data['only_xrp']; //'0', 
            $transferable = $fetched_data['transferable']; //'1', 
            $claimed = $fetched_data['claimed']; //'0', 
            $claimed_user_id = $fetched_data['claimed_user_id']; //0, 
            $claimed_date = $fetched_data['claimed_date']; //0, 
            $revealed = $fetched_data['revealed']; //'0', 
            $revealed_user_id = $fetched_data['revealed_user_id']; //0, 
            $revealed_date = $fetched_data['revealed_date']; //0

            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $nft_uuid);

            $sql21   = "SELECT * FROM NRG_Users WHERE user_id = '$user_id' LIMIT 1  ";
            $query21 = mysqli_query($sqlConnect, $sql21);
            $result21 = $query21;
            if ($result) {
                $fetched_data = mysqli_fetch_assoc($query21);
                $user_wallet = $fetched_data['xumm_address']; //'4d6e6445-bd14-11ed-9d2d-b7f9b9b4589e', 
            }

            $last_update = time();
            $sql12   = "UPDATE user_nft SET user_id=$user_id, last_update=$last_update, owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sql12);
            $query12 = mysqli_query($sqlConnect, $sql12);

            $sql13   = "UPDATE lbk_nft SET owner_wallet='" . $user_wallet . "' WHERE nft_uuid='$nft_uuid'";
            NRG_writeFile("XUMM_AUTO.log", "L" . __LINE__ . " | " . $sql13);
            $query13 = mysqli_query($sqlConnect, $sql13);
        }
    }
}

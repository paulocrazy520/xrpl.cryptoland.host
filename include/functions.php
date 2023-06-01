<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
@ini_set("max_execution_time", 0);
@ini_set("memory_limit", "-1");
@set_time_limit(0);
//require_once "config.php";
require_once "include/DB/vendor/autoload.php";
//require_once('DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php');

define('T_USERS', 'NRG_Users');
define('T_COUNTRIES', 'NRG_Countries');
define('T_FOLLOWERS', 'NRG_Followers');
define('T_NOTIFICATION', 'NRG_Notifications');
define('T_MESSAGES', 'NRG_Messages');
define('T_BLOCKS', 'NRG_Blocks');
define('T_POSTS', 'NRG_Posts');
define('T_PINNED_POSTS', 'NRG_PinnedPosts');
define('T_LIKES', 'NRG_Likes');
define('T_SAVED_POSTS', 'NRG_SavedPosts');
define('T_WONDERS', 'NRG_Wonders');
define('T_COMMENTS', 'NRG_Comments');
define('T_COMMENT_WONDERS', 'NRG_CommentWonders');
define('T_COMMENT_LIKES', 'NRG_CommentLikes');
define('T_HASHTAGS', 'NRG_Hashtags');
define('T_REPORTS', 'NRG_Reports');
define('T_ADS', 'NRG_Ads');
define('T_ANNOUNCEMENT', 'NRG_Announcement');
define('T_ANNOUNCEMENT_VIEWS', 'NRG_Announcement_Views');
define('T_ACTIVITIES', 'NRG_Activities');
define('T_APPS', 'NRG_Apps');
define('T_APPS_PERMISSION', 'NRG_Apps_Permission');
define('T_TOKENS', 'NRG_Tokens');
define('T_PAGES', 'NRG_Pages');
define('T_PAGES_LIKES', 'NRG_Pages_Likes');
define('T_CONFIG', 'NRG_Config');
define('T_GROUPS', 'NRG_Groups');
define('T_GROUP_MEMBERS', 'NRG_Group_Members');
define('T_VERIFICATION_REQUESTS', 'NRG_Verification_Requests');
define('T_BANNED_IPS', 'NRG_Banned_Ip');
define('T_GAMES', 'NRG_Games');
define('T_GAMES_PLAYERS', 'NRG_Games_Players');
define('T_ALBUMS_MEDIA', 'NRG_Albums_Media');
define('T_COMMENTS_REPLIES', 'NRG_Comment_Replies');
define('T_COMMENT_REPLIES_LIKES', 'NRG_Comment_Replies_Likes');
define('T_COMMENT_REPLIES_WONDERS', 'NRG_Comment_Replies_Wonders');
define('T_RECENT_SEARCHES', 'NRG_RecentSearches');
define('T_PAGES_INVAITES', 'NRG_Pages_Invites');
define('T_TERMS', 'NRG_Terms');
define('T_EMAILS', 'NRG_Emails');
define('T_PAYMENTS', 'NRG_Payments');
define('T_CF_CATEGORIES', 'NRG_Classified_Categories');
define('T_VIDEOS_CALLES', 'NRG_VideoCalles');
define('T_AGORA', 'NRG_AgoraVideoCall');
define('T_APP_SESSIONS', 'NRG_AppsSessions');
define('T_FIELDS', 'NRG_ProfileFields');
define('T_USERS_FIELDS', 'NRG_UserFields');
define('T_PRODUCTS', 'NRG_Products');
define('T_PRODUCTS_MEDIA', 'NRG_Products_Media');
define('T_POLLS', 'NRG_Polls');
define('T_VOTES', 'NRG_Votes');
define('T_CUSTOM_PAGES', 'NRG_CustomPages');
define('T_A_REQUESTS', 'NRG_Affiliates_Requests');
define('T_U_CHATS', 'NRG_UsersChat');
define('T_APPS_HASH', 'NRG_Apps_Hash');
define('T_AUDIO_CALLES', 'NRG_AudioCalls');
define('T_LANGS', 'NRG_Langs');
define('T_CODES', 'NRG_Codes');
define('T_BLOG', 'NRG_Blog');
define('T_FORUM_SEC', 'NRG_Forum_Sections');
define('T_FORUM_THREADS', 'NRG_Forum_Threads');
define('T_FORUMS', 'NRG_Forums');
define('T_FORUM_THREAD_REPLIES', 'NRG_ForumThreadReplies');
define('T_EVENTS', 'NRG_Events');
define('T_EVENTS_INV', 'NRG_Einvited');
define('T_EVENTS_GOING', 'NRG_Egoing');
define('T_EVENTS_INT', 'NRG_Einterested');
define('T_MOVIES', 'NRG_Movies');
define('T_MOVIE_COMMS', 'NRG_MovieComments');
define('T_MOVIE_COMM_REPLIES', 'NRG_MovieCommentReplies');
define('T_BLOG_COMM', 'NRG_BlogComments');
define('T_BLOG_COMM_REPLIES', 'NRG_BlogCommentReplies');
define('T_USER_ADS', 'NRG_UserAds');
define('T_BM_LIKES', 'NRG_BlogMovieLikes');
define('T_BM_DISLIKES', 'NRG_BlogMovieDisLikes');
define('T_USER_STORY', 'NRG_UserStory');
define('T_USER_STORY_MEDIA', 'NRG_UserStoryMedia');
define('T_HIDDEN_POSTS', 'NRG_HiddenPosts');
define('T_INVITATIONS', 'NRG_AdminInvitations');
define('T_GROUP_ADMINS', 'NRG_GroupAdmins');
define('T_PAGE_ADMINS', 'NRG_PageAdmins');
define('T_GROUP_CHAT', 'NRG_GroupChat');
define('T_GROUP_CHAT_USERS', 'NRG_GroupChatUsers');
define('T_PAGE_RATING', 'NRG_PageRating');
define('T_FAMILY', 'NRG_Family');
define('T_REL_SHIP', 'NRG_Relationship');
define('T_PAYMENT_TRANSACTIONS', 'NRG_Payment_Transactions');
define('T_USERADS_DATA', 'NRG_UserAds_Data');
define('T_POKES', 'NRG_Pokes');
define('T_GIFTS', 'NRG_Gifts');
define('T_USERGIFTS', 'NRG_User_Gifts');
define('T_STICKERS', 'NRG_Stickers');
define('T_REACTIONS', 'NRG_Reactions');
define('T_STORY_SEEN', 'NRG_Story_Seen');
define('T_MANAGE_PRO', 'NRG_Manage_Pro');
define('T_PAGES_CATEGORY', 'NRG_Pages_Categories');
define('T_GROUPS_CATEGORY', 'NRG_Groups_Categories');
define('T_BLOGS_CATEGORY', 'NRG_Blogs_Categories');
define('T_PRODUCTS_CATEGORY', 'NRG_Products_Categories');
define('T_JOB_CATEGORY', 'NRG_Job_Categories');
define('T_BANK_TRANSFER', 'bank_receipts');
define('T_COLORS', 'NRG_Colored_Posts');
define('T_ADMIN', 'NRG_Admin_Pages');
define('T_GENDER', 'NRG_Gender');
define('T_JOB', 'NRG_Job');
define('T_JOB_APPLY', 'NRG_Job_Apply');
define('T_BLOG_REACTION', 'NRG_Blog_Reaction');
define('T_FUNDING', 'NRG_Funding');
define('T_FUNDING_RAISE', 'NRG_Funding_Raise');
define('T_REACTIONS_TYPES', 'NRG_Reactions_Types');
define('T_SUB_CATEGORIES', 'NRG_Sub_Categories');
define('T_CUSTOM_FIELDS', 'NRG_Custom_Fields');
define('T_REFUND', 'NRG_Refund');
define('T_OFFER', 'NRG_Offers');
define('T_BAD_LOGIN', 'NRG_Bad_Login');
define('T_INVITAION_LINKS', 'NRG_Invitation_Links');
define('T_LIVE_SUB', 'NRG_Live_Sub_Users');
define('T_MUTE', 'NRG_Mute');
define('T_MUTE_STORY', 'NRG_Mute_Story');
define('T_CAST', 'broadcast');
define('T_CAST_USERS', 'broadcast_users');
define('T_HTML_EMAILS', 'NRG_HTML_Emails');
define('T_USERCARD', 'NRG_UserCard');
define('T_USER_ADDRESS', 'NRG_UserAddress');
define('T_USER_ORDERS', 'NRG_UserOrders');
define('T_PURCHAES', 'NRG_Purchases');
define('T_PRODUCT_REVIEW', 'NRG_ProductReview');
define('T_PATREON_SUBSCRIBERS', 'NRG_PatreonSubscribers');
define('T_USER_EXPERIENCE', 'NRG_UserExperience');
define('T_USER_CERTIFICATION', 'NRG_UserCertification');
define('T_USER_PROJECTS', 'NRG_UserProjects');
define('T_USER_OPEN_TO', 'NRG_UserOpenTo');
define('T_USER_TIERS', 'NRG_UserTiers');
define('T_USER_SKILLS', 'NRG_UserSkills');
define('T_USER_LANGUAGES', 'NRG_UserLanguages');
define('T_LANG_ISO', 'NRG_LangIso');
define('T_PENDING_PAYMENTS', 'NRG_PendingPayments');
define('T_UPLOADED_MEDIA', 'NRG_UploadedMedia');

$nrg           = array();
// Connect to SQL Server
$sqlConnect   = $nrg["sqlConnect"] = mysqli_connect($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, 3306);
// create new mysql connection
$mysqlMaria   = new Mysql;
// Handling Server Errors
$ServerErrors = array();
if (mysqli_connect_errno()) {
    $ServerErrors[] = "Failed to connect to MySQL: " . mysqli_connect_error();
}
if (!function_exists("curl_init")) {
    $ServerErrors[] = "PHP CURL is NOT installed on your web server !";
}
if (!extension_loaded("gd") && !function_exists("gd_info")) {
    $ServerErrors[] = "PHP GD library is NOT installed on your web server !";
}
if (!extension_loaded("zip")) {
    $ServerErrors[] = "ZipArchive extension is NOT installed on your web server !";
}
$query = mysqli_query($sqlConnect, "SET NAMES utf8mb4");
if (isset($ServerErrors) && !empty($ServerErrors)) {
    foreach ($ServerErrors as $Error) {
        echo "<h3>" . $Error . "</h3>";
    }
    die();
}
$baned_ips = NRG_GetBanned("user");
if (in_array($_SERVER["REMOTE_ADDR"], $baned_ips)) {
    exit();
}
$config    = NRG_GetConfig();
if ($config['developer_mode'] == 1) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
$db        = new MysqliDb($sqlConnect);

foreach ($all_langs as $key => $value) {
    $insert = false;
    if (!in_array($value, array_keys($config))) {
        $db->insert(T_CONFIG, array(
            "name" => $value,
            "value" => 1
        ));
        $insert = true;
    }
}
if ($insert == true) {
    $config = NRG_GetConfig();
}

function NRG_GetConfig()
{
    global $sqlConnect;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_CONFIG);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['name']] = $fetched_data['value'];
        }
    }
    return $data;
}

function NRG_GetBanned($type = "", $userType = 1)
{
    global $sqlConnect;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_BANNED_IPS . " ORDER BY id DESC");
    if (mysqli_num_rows($query)) {
        if ($type == "user") {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                if (filter_var($fetched_data["ip_address"], FILTER_VALIDATE_IP)) {
                    $data[] = $fetched_data["ip_address"];
                }
            }
        } else {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[] = $fetched_data;
            }
        }
    }
    return $data;
}
function NRG_IsBanned($value = "")
{
    global $sqlConnect;
    $value     = NRG_Secure($value);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_BANNED_IPS . " WHERE `ip_address` = '{$value}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if ($fetched_data["count"] > 0) {
            return true;
        }
    }
    return false;
}
function NRG_BanNewIp($ip, $reason = "")
{
    global $sqlConnect;
    $ip        = NRG_Secure($ip);
    $reason    = NRG_Secure($reason);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_BANNED_IPS . " WHERE `ip_address` = '{$ip}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if ($fetched_data["count"] > 0) {
            return false;
        }
    }
    $time      = time();
    $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_BANNED_IPS . " (`ip_address`,`reason`,`time`) VALUES ('{$ip}','{$reason}','{$time}')");
    if ($query_two) {
        return true;
    }
}
function NRG_IsIpBanned($id)
{
    global $sqlConnect;
    $id        = NRG_Secure($id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_BANNED_IPS . " WHERE `id` = '{$id}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if ($fetched_data["count"] > 0) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function NRG_Secure($string, $censored_words = 0, $br = true, $strip = 0)
{
    global $sqlConnect, $mysqlMaria;
    $mysqlMaria->setSQLType($sqlConnect);
    $string = trim($string);
    $string = cleanString($string);
    $string = mysqli_real_escape_string($sqlConnect, $string);
    $string = htmlspecialchars($string, ENT_QUOTES);
    if ($br == true) {
        $string = str_replace('\r\n', " <br>", $string);
        $string = str_replace('\n\r', " <br>", $string);
        $string = str_replace('\r', " <br>", $string);
        $string = str_replace('\n', " <br>", $string);
    } else {
        $string = str_replace('\r\n', "", $string);
        $string = str_replace('\n\r', "", $string);
        $string = str_replace('\r', "", $string);
        $string = str_replace('\n', "", $string);
    }
    if ($strip == 1) {
        $string = stripslashes($string);
    }
    $string = str_replace('&amp;#', '&#', $string);
    if ($censored_words == 1) {
        global $config;
        $censored_words = @explode(",", $config['censored_words']);
        foreach ($censored_words as $censored_word) {
            $censored_word = trim($censored_word);
            $string        = str_replace($censored_word, '****', $string);
        }
    }
    return $string;
}

function cleanString($string)
{
    return $string = preg_replace("/&#?[a-z0-9]+;/i", "", $string);
}

function NRG_Sql_Result($res, $row = 0, $col = 0)
{
    $numrows = mysqli_num_rows($res);
    if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
        mysqli_data_seek($res, $row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])) {
            return $resrow[$col];
        }
    }
    return false;
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

function get_ip_address()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED']))
        return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
        return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED']))
        return $_SERVER['HTTP_FORWARDED'];
    return $_SERVER['REMOTE_ADDR'];
}

function validate_ip($ip)
{
    if (strtolower($ip) === 'unknown')
        return false;
    $ip = ip2long($ip);
    if ($ip !== false && $ip !== -1) {
        $ip = sprintf('%u', $ip);
        if ($ip >= 0 && $ip <= 50331647)
            return false;
        if ($ip >= 167772160 && $ip <= 184549375)
            return false;
        if ($ip >= 2130706432 && $ip <= 2147483647)
            return false;
        if ($ip >= 2851995648 && $ip <= 2852061183)
            return false;
        if ($ip >= 2886729728 && $ip <= 2887778303)
            return false;
        if ($ip >= 3221225984 && $ip <= 3221226239)
            return false;
        if ($ip >= 3232235520 && $ip <= 3232301055)
            return false;
        if ($ip >= 4294967040)
            return false;
    }
    return true;
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
    fwrite($handle, $q . "\n");
    fclose($handle);
}

function NRG_UpdateNFTHistory($nft_uuid, $nft_id, $nft_serial, $action_type, $action_created_date, $action_complete, $action_complete_date, $created_by, $user_id, $issuer_wallet, $owner_wallet, $transaction_hash, $xumm_txid, $xumm_payload_uuidv4)
{
    global $nrg, $sqlConnect, $cache;

    if ($nrg['loggedin'] == false) {
        return false;
    }

    $date_now = date("d/m/Y");
    $time_now = time();
    $dateTime = time();
    $userId = $nrg['user']['user_id'];
    $browser_info = NRG_getBrowser() . "|" . NRG_getOS();
    $user_ip = get_ip_address();
    $owner_wallet = $nrg['user']['xumm_address'];

    $query_one = "INSERT INTO nft_history
      (
        nft_uuid, nft_id, nft_serial, action_type
      , action_created_date, action_complete
      , action_complete_date, created_by, user_id
      , issuer_wallet, owner_wallet, transaction_hash
      , xumm_txid, xumm_payload_uuidv4
      )VALUES(
        '$nft_uuid', '$nft_id', $nft_serial, $action_type
        , $action_created_date, $action_complete
        , $action_complete_date, $created_by, $user_id
        , '$issuer_wallet', '$owner_wallet', '$transaction_hash'
        , '$xumm_txid', '$xumm_payload_uuidv4'
        );";
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

function NRG_XUMMWalletExists($wid)
{
    global $sqlConnect;
    if (empty($wid)) {
        return false;
    }
    $wid = NRG_Secure($wid);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM NRG_Users WHERE `xumm_address` = '{$wid}'");
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

/*  LBKs  */

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

            ////  NOT IN DEV !!!!!!!!!!!!!!!!!!
            /*
            
            NRG_UpdateLBKNFT_Metadata_File($nft_id, $userWallet);

            */
        }
    } else {
        return "failed";
    }
    return "true";
}


/*  VIALs  */

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

            ////  NOT IN DEV !!!!!!!!!!!!!!!!!!
            /*
            
            NRG_UpdateVialsNFT_Metadata_File($nft_id, $userWallet);

            */
        }
    } else {
        return "failed";
    }
    return "true";
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
    //$result = NRG_UpdateLBKNFT('', '', '', 1, $userId, $timestamp, $nftId, $userWallet);
    //$result2 = NRG_UpdateNFTHistory($nft['nft_uuid'], $nft['nft_id'], $nft['nft_serial'], 1, $timestamp, 1, $timestamp, $userId, $userId, 'rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b', $userWallet, '', '', '');
}

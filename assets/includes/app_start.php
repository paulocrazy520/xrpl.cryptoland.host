<?php
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
// error_reporting(0);
@ini_set("max_execution_time", 0);
@ini_set("memory_limit", "-1");
@set_time_limit(0);
require_once "config.php";
require_once "assets/libraries/DB/vendor/autoload.php";

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
$all_langs = NRG_LangsNamesFromDB();
$nrg['iso'] = GetIso();
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
if (isset($_GET["theme"]) && in_array($_GET["theme"], array(
    "default",
    "sunshine",
    "nrgSocial",
    "nrgSocial-V2"
))) {
    $_SESSION["theme"] = $_GET["theme"];
}
if (isset($_SESSION["theme"]) && !empty($_SESSION["theme"])) {
    $config["theme"] = $_SESSION["theme"];
    if ($_SERVER["REQUEST_URI"] == "/v2/wonderful" || $_SERVER["REQUEST_URI"] == "/v2/wowonder") {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
}
$config["withdrawal_payment_method"] = json_decode($config['withdrawal_payment_method'],true);
// Config Url
$config["theme_url"] = $site_url . "/themes/" . $config["theme"];
$config["site_url"]  = $site_url;
$nrg["site_url"]      = $site_url;
$config["wasabi_site_url"]         = "https://s3.".$config["wasabi_bucket_region"].".wasabisys.com";
if (!empty($config["wasabi_bucket_name"])) {
    $config["wasabi_site_url"] = "https://s3.".$config["wasabi_bucket_region"].".wasabisys.com/".$config["wasabi_bucket_name"];
}
$s3_site_url         = "https://test.s3.amazonaws.com";
if (!empty($config["bucket_name"])) {
    $s3_site_url = "https://{bucket}.s3.amazonaws.com";
    $s3_site_url = str_replace("{bucket}", $config["bucket_name"], $s3_site_url);
}
$config["s3_site_url"] = $s3_site_url;
$s3_site_url_2         = "https://test.s3.amazonaws.com";
if (!empty($config["bucket_name_2"])) {
    $s3_site_url_2 = "https://{bucket}.s3.amazonaws.com";
    $s3_site_url_2 = str_replace("{bucket}", $config["bucket_name_2"], $s3_site_url_2);
}
$config["s3_site_url_2"]   = $s3_site_url_2;
$nrg["config"]              = $config;
$ccode                     = NRG_CustomCode("g");
$ccode                     = is_array($ccode) ? $ccode : array();
$nrg["config"]["header_cc"] = !empty($ccode[0]) ? $ccode[0] : "";
$nrg["config"]["footer_cc"] = !empty($ccode[1]) ? $ccode[1] : "";
$nrg["config"]["styles_cc"] = !empty($ccode[2]) ? $ccode[2] : "";

$nrg["script_version"]      = $nrg["config"]["version"];
$http_header               = "http://";
if (!empty($_SERVER["HTTPS"])) {
    $http_header = "https://";
}
$nrg["actual_link"] = $http_header . $_SERVER["HTTP_HOST"] . urlencode($_SERVER["REQUEST_URI"]);
// Define Cache Vireble
$cache             = new Cache();
$cache->NRG_OpenCacheDir();
$nrg["purchase_code"] = "";
if (!empty($purchase_code)) {
    $nrg["purchase_code"] = $purchase_code;
}
// Login With Url
$nrg["facebookLoginUrl"]   = $config["site_url"] . "/login-with.php?provider=Facebook";
$nrg["twitterLoginUrl"]    = $config["site_url"] . "/login-with.php?provider=Twitter";
$nrg["googleLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=Google";
$nrg["linkedInLoginUrl"]   = $config["site_url"] . "/login-with.php?provider=LinkedIn";
$nrg["VkontakteLoginUrl"]  = $config["site_url"] . "/login-with.php?provider=Vkontakte";
$nrg["instagramLoginUrl"]  = $config["site_url"] . "/login-with.php?provider=Instagram";
$nrg["QQLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=QQ";
$nrg["WeChatLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=WeChat";
$nrg["DiscordLoginUrl"]    = $config["site_url"] . "/login-with.php?provider=Discord";
$nrg["MailruLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=Mailru";
$nrg["OkLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=OkRu";
// Defualt User Pictures
$nrg["userDefaultAvatar"]  = "upload/photos/d-avatar.jpg";
$nrg["userDefaultFAvatar"] = "upload/photos/f-avatar.jpg";
$nrg["userDefaultCover"]   = "upload/photos/d-cover.jpg";
$nrg["pageDefaultAvatar"]  = "upload/photos/d-page.jpg";
$nrg["groupDefaultAvatar"] = "upload/photos/d-group.jpg";
// Get LoggedIn User Data
$nrg["loggedin"]           = false;
$langs                    = NRG_LangsNamesFromDB();


if (NRG_IsLogged() == true) {
    $session_id         = !empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : $_COOKIE["user_id"];
    $nrg["user_session"] = NRG_GetUserFromSessionID($session_id);
    $nrg["user"]         = NRG_UserData($nrg["user_session"]);
    if (!empty($nrg["user"]["language"])) {
        if (in_array($nrg["user"]["language"], $langs)) {
            $_SESSION["lang"] = $nrg["user"]["language"];
        }
    }
    if ($nrg["user"]["user_id"] < 0 || empty($nrg["user"]["user_id"]) || !is_numeric($nrg["user"]["user_id"]) || NRG_UserActive($nrg["user"]["username"]) === false) {
        header("Location: " . NRG_SeoLink("index.php?link1=logout"));
    }
    $nrg["loggedin"] = true;
} else {
    $nrg["userSession"] = getUserProfileSessionID();
}

if (!empty($_GET["c_id"]) && !empty($_GET["user_id"])) {

    $application = "windows";
    if (!empty($_GET["application"])) {
        if ($_GET["application"] == "phone") {
            $application = NRG_Secure($_GET["application"]);
        }
    }
    $c_id             = NRG_Secure($_GET["c_id"]);
    $user_id          = NRG_Secure($_GET["user_id"]);
    $check_if_session = NRG_CheckUserSessionID($user_id, $c_id, $application);
    if ($check_if_session === true) {
        $nrg["user"]          = NRG_UserData($user_id);
        $session             = NRG_CreateLoginSession($user_id);
        $_SESSION["user_id"] = $session;
        setcookie("user_id", $session, time() + 10 * 365 * 24 * 60 * 60);
        if ($nrg["user"]["user_id"] < 0 || empty($nrg["user"]["user_id"]) || !is_numeric($nrg["user"]["user_id"]) || NRG_UserActive($nrg["user"]["username"]) === false) {
            header("Location: " . NRG_SeoLink("index.php?link1=logout"));
        }
        $nrg["loggedin"] = true;
    }
}

if (!empty($_POST["user_id"]) && (!empty($_POST["s"]) || !empty($_POST["access_token"]))) {
    $application  = "windows";
    $access_token = !empty($_POST["s"]) ? $_POST["s"] : $_POST["access_token"];
    if (!empty($_GET["application"])) {
        if ($_GET["application"] == "phone") {
            $application = NRG_Secure($_GET["application"]);
        }
    }
    if ($application == "windows") {
        $access_token = $access_token;
    }
    $s                = NRG_Secure($access_token);
    $user_id          = NRG_Secure($_POST["user_id"]);
    $check_if_session = NRG_CheckUserSessionID($user_id, $s, $application);
    if ($check_if_session === true) {
        $nrg["user"] = NRG_UserData($user_id);
        if ($nrg["user"]["user_id"] < 0 || empty($nrg["user"]["user_id"]) || !is_numeric($nrg["user"]["user_id"]) || NRG_UserActive($nrg["user"]["username"]) === false) {
            $json_error_data = array(
                "api_status" => "400",
                "api_text" => "failed",
                "errors" => array(
                    "error_id" => "7",
                    "error_text" => "User id is wrong."
                )
            );
            header("Content-type: application/json");
            echo json_encode($json_error_data, JSON_PRETTY_PRINT);
            exit();
        }
        $nrg["loggedin"] = true;
    } else {
        $json_error_data = array(
            "api_status" => "400",
            "api_text" => "failed",
            "errors" => array(
                "error_id" => "6",
                "error_text" => "Session id is wrong."
            )
        );
        header("Content-type: application/json");
        echo json_encode($json_error_data, JSON_PRETTY_PRINT);
        exit();
    }
}
// Language Function
if (isset($_GET["lang"]) and !empty($_GET["lang"])) {
    if (in_array($_GET["lang"], array_keys($nrg["config"])) && $nrg["config"][$_GET["lang"]] == 1) {
        $lang_name = NRG_Secure(strtolower($_GET["lang"]));
        if (in_array($lang_name, $langs)) {
            NRG_CleanCache();
            $_SESSION["lang"] = $lang_name;
            if ($nrg["loggedin"] == true) {
                mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `language` = '" . $lang_name . "' WHERE `user_id` = " . NRG_Secure($nrg["user"]["user_id"]));
                cache($nrg["user"]["user_id"], 'users', 'delete');
            }
        }
    }
}
if ($nrg["loggedin"] == true && $nrg["config"]["cache_sidebar"] == 1) {
    if (!empty($_COOKIE["last_sidebar_update"])) {
        if ($_COOKIE["last_sidebar_update"] < time() - 120) {
            NRG_CleanCache();
        }
    } else {
        NRG_CleanCache();
    }
}
if (empty($_SESSION["lang"])) {
    $_SESSION["lang"] = $nrg["config"]["defualtLang"];
}
$nrg["language"]      = $_SESSION["lang"];
$nrg["language_type"] = "ltr";
// Add rtl languages here.
$rtl_langs           = array(
    "arabic",
    "urdu",
    "hebrew",
    "persian"
);
if (!isset($_COOKIE["ad-con"])) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
$nrg["ad-con"] = array();
if (!empty($_COOKIE["ad-con"])) {
    $nrg["ad-con"] = json_decode(html_entity_decode($_COOKIE["ad-con"]));
    $nrg["ad-con"] = ToArray($nrg["ad-con"]);
}
if (!is_array($nrg["ad-con"]) || !isset($nrg["ad-con"]["date"]) || !isset($nrg["ad-con"]["ads"])) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
if (is_array($nrg["ad-con"]) && isset($nrg["ad-con"]["date"]) && strtotime($nrg["ad-con"]["date"]) < strtotime(date("Y-m-d"))) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
if (!isset($_COOKIE["_us"])) {
    setcookie("_us", time() + 60 * 60 * 24, time() + 10 * 365 * 24 * 60 * 60);
}
if ((isset($_COOKIE["_us"]) && $_COOKIE["_us"] < time()) || 1) {
    setcookie("_us", time() + 60 * 60 * 24, time() + 10 * 365 * 24 * 60 * 60);
}
// checking if corrent language is rtl.
foreach ($rtl_langs as $lang) {
    if ($nrg["language"] == strtolower($lang)) {
        $nrg["language_type"] = "rtl";
    }
}
// Icons Virables
$error_icon   = '<i class="fa fa-exclamation-circle"></i> ';
$success_icon = '<i class="fa fa-check"></i> ';
// Include Language File
$nrg["lang"]   = NRG_LangsFromDB($nrg["language"]);
if (file_exists("assets/languages/extra/" . $nrg["language"] . ".php")) {
    require "assets/languages/extra/" . $nrg["language"] . ".php";
}
if (empty($nrg["lang"])) {
    $nrg["lang"] = NRG_LangsFromDB();
}
$nrg["second_post_button_icon"] = $config["second_post_button"] == "wonder" ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="8"></line></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-down"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>';
$theme_settings                = array();
$theme_settings["theme"]       = "wowonder";
if (file_exists("./themes/" . $config["theme"] . "/layout/404/dont-delete-this-file.json")) {
    $theme_settings = json_decode(file_get_contents("./themes/" . $config["theme"] . "/layout/404/dont-delete-this-file.json"), true);
}
if ($theme_settings["theme"] == "wonderful") {
    $nrg["second_post_button_icon"] = $config["second_post_button"] == "wonder" ? "exclamation-circle" : "thumb-down";
}
$nrg["second_post_button_text"]  = $config["second_post_button"] == "wonder" ? $nrg["lang"]["wonder"] : $nrg["lang"]["dislike"];
$nrg["second_post_button_texts"] = $config["second_post_button"] == "wonder" ? $nrg["lang"]["wonders"] : $nrg["lang"]["dislikes"];
$nrg["marker"]                   = "?";
if ($nrg["config"]["seoLink"] == 0) {
    $nrg["marker"] = "&";
}
require_once "assets/includes/data.php";
$nrg["emo"]                           = $emo;
$nrg["profile_picture_width_crop"]    = 150;
$nrg["profile_picture_height_crop"]   = 150;
$nrg["profile_picture_image_quality"] = 70;
$nrg["redirect"]                      = 0;

$nrg["update_cache"]                  = "";
if (!empty($nrg["config"]["last_update"])) {
    $update_cache = time() - 21600;
    if ($update_cache < $nrg["config"]["last_update"]) {
        $nrg["update_cache"] = "?" . sha1(time());
    }
}

// night mode
if (empty($_COOKIE["mode"])) {
    setcookie("mode", "day", time() + 10 * 365 * 24 * 60 * 60, "/");
    $_COOKIE["mode"] = "day";
    $nrg["mode_link"] = "night";
    $nrg["mode_text"] = $nrg["lang"]["night_mode"];
} else {
    if ($_COOKIE["mode"] == "day") {
        $nrg["mode_link"] = "night";
        $nrg["mode_text"] = $nrg["lang"]["night_mode"];
    }
    if ($_COOKIE["mode"] == "night") {
        $nrg["mode_link"] = "day";
        $nrg["mode_text"] = $nrg["lang"]["day_mode"];
    }
}
if (!empty($_GET["mode"])) {
    if ($_GET["mode"] == "day") {
        setcookie("mode", "day", time() + 10 * 365 * 24 * 60 * 60, "/");
        $_COOKIE["mode"] = "day";
        $nrg["mode_link"] = "night";
        $nrg["mode_text"] = $nrg["lang"]["night_mode"];
    } elseif ($_GET["mode"] == "night") {
        setcookie("mode", "night", time() + 10 * 365 * 24 * 60 * 60, "/");
        $_COOKIE["mode"] = "night";
        $nrg["mode_link"] = "day";
        $nrg["mode_text"] = $nrg["lang"]["day_mode"];
    }
}
include_once "assets/includes/onesignal_config.php";

// manage packages
$nrg["pro_packages"]       = NRG_GetAllProInfo();
try {
    $nrg["genders"]             = NRG_GetGenders($nrg["language"], $langs);
    $nrg["page_categories"]     = NRG_GetCategories(T_PAGES_CATEGORY);
    $nrg["group_categories"]    = NRG_GetCategories(T_GROUPS_CATEGORY);
    $nrg["blog_categories"]     = NRG_GetCategories(T_BLOGS_CATEGORY);
    $nrg["products_categories"] = NRG_GetCategories(T_PRODUCTS_CATEGORY);
    $nrg["job_categories"]      = NRG_GetCategories(T_JOB_CATEGORY);
    $nrg["reactions_types"]     = NRG_GetReactionsTypes();
}
catch (Exception $e) {
    $nrg["genders"]             = array();
    $nrg["page_categories"]     = array();
    $nrg["group_categories"]    = array();
    $nrg["blog_categories"]     = array();
    $nrg["products_categories"] = array();
    $nrg["job_categories"]      = array();
    $nrg["reactions_types"]     = array();
}
NRG_GetSubCategories();
$nrg["config"]["currency_array"]        = (array) json_decode($nrg["config"]["currency_array"]);
$nrg["config"]["currency_symbol_array"] = (array) json_decode($nrg["config"]["currency_symbol_array"]);
$nrg["config"]["providers_array"]       = (array) json_decode($nrg["config"]["providers_array"]);
if (!empty($nrg["config"]["exchange"])) {
    $nrg["config"]["exchange"] = (array) json_decode($nrg["config"]["exchange"]);
}
$nrg["currencies"] = array();
foreach ($nrg["config"]["currency_symbol_array"] as $key => $value) {
    $nrg["currencies"][] = array(
        "text" => $key,
        "symbol" => $value
    );
}
if (!empty($_GET["theme"])) {
    NRG_CleanCache();
}
$nrg["post_colors"] = array();
if ($nrg["config"]["colored_posts_system"] == 1) {
    $nrg["post_colors"] = NRG_GetAllColors();
}


$nrg['manage_pro_features'] = array('funding_request' => 'can_use_funding',
                                   'job_request' => 'can_use_jobs',
                                   'game_request' => 'can_use_games',
                                   'market_request' => 'can_use_market',
                                   'event_request' => 'can_use_events',
                                   'forum_request' => 'can_use_forum',
                                   'groups_request' => 'can_use_groups',
                                   'pages_request' => 'can_use_pages',
                                   'audio_call_request' => 'can_use_audio_call',
                                   'video_call_request' => 'can_use_video_call',
                                   'offer_request' => 'can_use_offer',
                                   'blog_request' => 'can_use_blog',
                                   'movies_request' => 'can_use_movies',
                                   'story_request' => 'can_use_story',
                                   'stickers_request' => 'can_use_stickers',
                                   'gif_request' => 'can_use_gif',
                                   'gift_request' => 'can_use_gift',
                                   'nearby_request' => 'can_use_nearby',
                                   'video_upload_request' => 'can_use_video_upload',
                                   'audio_upload_request' => 'can_use_audio_upload',
                                   'shout_box_request' => 'can_use_shout_box',
                                   'colored_posts_request' => 'can_use_colored_posts',
                                   'poll_request' => 'can_use_poll',
                                   'live_request' => 'can_use_live',
                                   'profile_background_request' => 'can_use_background',
                                   'affiliate_request' => 'can_use_affiliate',
                                   'chat_request' => 'can_use_chat');
$nrg['available_pro_features'] = array();
$nrg['available_verified_features'] = array();

foreach ($nrg['manage_pro_features'] as $key => $value) {
    $nrg['config'][$value] = true;
    if ($nrg["loggedin"] && !empty($nrg['user'])) {
        if ($nrg['config'][$key] == 'verified' && !$nrg['user']['verified']) {
            $nrg['config'][$value] = false;
        }
        if ($nrg['config'][$key] == 'admin' && !$nrg['user']['admin']) {
            $nrg['config'][$value] = false;
        }
        if ($nrg['config'][$key] == 'pro' && !$nrg['user']['is_pro']) {
            $nrg['config'][$value] = false;
        }
        if ($nrg['config'][$key] == 'pro' && $nrg['user']['is_pro'] && !empty($nrg["pro_packages"][$nrg['user']['pro_type']]) && $nrg["pro_packages"][$nrg['user']['pro_type']][$value] != 1) {
            $nrg['config'][$value] = false;
        }
        if ($nrg['user']['admin']) {
            $nrg['config'][$value] = true;
        }
    }
    if ($nrg['config'][$key] == 'pro') {
        $nrg['available_pro_features'][$key] = $value;
    }
    if ($nrg['config'][$key] == 'verified') {
        $nrg['available_verified_features'][$key] = $value;
    }
}
if (!$nrg['config']['can_use_stickers']) {
    $nrg['config']['stickers_system'] = 0;
}
if (!$nrg['config']['can_use_gif']) {
    $nrg['config']['stickers'] = 0;
}
if (!$nrg['config']['can_use_gift']) {
    $nrg['config']['gift_system'] = 0;
}
if (!$nrg['config']['can_use_nearby']) {
    $nrg['config']['find_friends'] = 0;
}
if (!$nrg['config']['can_use_video_upload']) {
    $nrg['config']['video_upload'] = 0;
}
if (!$nrg['config']['can_use_audio_upload']) {
    $nrg['config']['audio_upload'] = 0;
}
if (!$nrg['config']['can_use_poll']) {
    $nrg['config']['post_poll'] = 0;
}
if (!$nrg['config']['can_use_background']) {
    $nrg['config']['profile_back'] = 0;
}
if (!$nrg['config']['can_use_chat']) {
    $nrg['config']['chatSystem'] = 0;
}
$nrg['config']['report_reasons'] = json_decode($nrg['config']['report_reasons'],true);


$nrg['config']['filesVersion'] = "4.1.5.0410202301-UI";

// if ($nrg['config']['filesVersion'] != $nrg['config']['version']) {
//     ini_set('display_errors', 0);
//     ini_set('display_startup_errors', 0);
//     error_reporting(0);
// }
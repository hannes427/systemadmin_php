<?php
/*if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
if(!function_exists('sysadmin_get_storage_email')){
function sysadmin_get_storage_email() {
	echo "Funktion wir ausgefuehrt<br>\n";
		return true;
	}
}*/
function systemadminFetchEmail() {
	$email = array();
    $dbh = \FreePBX::Database();
    $sql = 'SELECT value FROM systemadmin_settings WHERE `key` = \'notifications_settings\'';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
		$email = json_decode($result['value'], true);
	}
	return $email;
}

function systemadminFetchEmailConfig() {
	$emailconfig = array();
	$emailconfig['setup'] = "local";
	$emailconfig['relayhost'] = "";
	$emailconfig['port'] = "";
	$emailconfig['username'] = "";
    $dbh = \FreePBX::Database();
    $sql = 'SELECT value FROM systemadmin_settings WHERE `key` = \'email_config\'';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
		$emailconfig = json_decode($result['value'], true);
	}
	return $emailconfig;
}
?>

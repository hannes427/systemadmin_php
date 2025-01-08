<?php
if(isset($_POST['set_email']) && $_POST['set_email'] == "true") {
    echo "form submitted";
    $setup = $_POST['email_server'];
    $myhostname = trim($_POST['myhostname']);
    $mydomain = trim($_POST['mydomain']);
    $myorigin = trim($_POST['myorigin']);
    $myorigin_path = "";
    if(isset($_POST['myorigin_path'])) {
        $myorigin_path = $_POST['myorigin_path'];
    }
    $relayhost = trim($_POST['mailserver']);
    $smtp_sasl_auth_enable = $_POST['smtp_sasl_auth_enable'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $password_changed = $_POST['password_changed'];
    $smtp_use_tls = $_POST['smtp_use_tls'];
    if ($smtp_sasl_auth_enable == "no") {
        $username = "";
    }
    $port = trim($_POST['port']);
    //Vallidation
    $error = false;
     if ($smtp_use_tls == "yes" && ($port == "" || !preg_match("/^\d+$/", $port))) {
        $error = true;
    }
    if ($setup == "remote") {
        if ($smtp_sasl_auth_enable == "yes" && ($username == "" || $password == "")) {
            $error = true;
        }
        else if ($relayhost == ""  || (!preg_match("/^[a-z0-9]+[a-z0-9-\._]+\.[a-z]{2,}$/", $relayhost) && !filter_var($relayhost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($relayhost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
            $error = true;
            echo "<br><br>nein...<br><br>";
        }
    }
    if(!$error) {
        if($smtp_use_tls == "no") {
            $json_data = "{\"setup\": \"$setup\", \"relayhost\": \"$relayhost\", \"username\": \"$username\"}";
        }
        else {
            $json_data = "{\"setup\": \"$setup\", \"relayhost\": \"$relayhost:$port\", \"username\": \"$username\"}";
        }
        $dbh = \FreePBX::Database();
        $sql = "INSERT INTO systemadmin_settings (`key`, value) VALUES('email_config', '$json_data') ON DUPLICATE KEY UPDATE value='$json_data'";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        exec("/usr/local/freepbx/email_settings --setup \"$setup\" --myhostname \"$myhostname\" --mydomain \"$mydomain\" --myorigin \"$myorigin\"  --myorigin_path \"$myorigin_path\" --server \"$relayhost\" --use_auth \"$smtp_sasl_auth_enable\" --use_tls \"$smtp_use_tls\" --port \"$port\" --username \"$username\" --password \"$password\" --password_changed \"$password_changed\" 2>&1", $output, $rc);
        if ($rc != 0) {
            $err_msg = "";
            foreach($output AS $line) {
                $err_msg .= "$line\n";
            }
            throw new \Exception("Can't update email config: $err_msg");
        }
    }
    print_r($_POST);
}
$emailconfig = systemadminFetchEmailConfig();
$myhostname = "";
$mydomain = "";
$myorigin = "";
$myorigin_real = "";
$relayhost = "";
$smtp_sasl_auth_enable = "";
$smtp_use_tls = "";
$display_local = 'style = "display: none;"';
$display_remote = 'style = "display: none;"';
$display_auth = 'style = "display: none;"';
$display_tls_port = 'style = "display: none;"';
if (str_contains($emailconfig['relayhost'], ':')) {
    $temp = explode(':', $emailconfig['relayhost']);
    $emailconfig['relayhost'] = $temp[0];
    $emailconfig['port'] = $temp[1];
}
else {
    $emailconfig['port'] = "";
}
if ($emailconfig['setup'] == "" || $emailconfig['setup'] == "local") {
    $display_local = 'style = "display: inline;"';
}
exec("/usr/sbin/postconf -px myhostname 2>&1", $hostname_output, $hostname_rc);
if ($hostname_rc == 0) {
$myhostname_line = $hostname_output[0];
    if (preg_match("/myhostname\s=\s(.*)/i", $myhostname_line, $hostname_matches)) {
        {
            $myhostname = $hostname_matches[1];
        }
    }
}
exec("/usr/sbin/postconf -px mydomain 2>&1", $domain_output, $domain_rc);
if ($domain_rc == 0) {
    $mydomain_line = $domain_output[0];
    if (preg_match("/mydomain\s=\s(.*)/i", $mydomain_line, $mydomain_matches)) {
        {
            $mydomain = $mydomain_matches[1];
        }
    }
}
exec("/usr/sbin/postconf -px myorigin 2>&1", $origin_output, $origin_rc);
if ($origin_rc == 0) {
    $myorigin_line = $origin_output[0];
    if (preg_match("/myorigin\s=\s(.*)/i", $myorigin_line, $myorigin_matches)) {
        {
            $myorigin = $myorigin_matches[1];
        }
        if (preg_match("/^\/[a-z0-9\-_\/\.]+$/i", $myorigin, $matches)) {
            $myorigin_real = trim(file_get_contents($matches[0]));
        }
    }
}
else {
    $display_remote = 'style = "display: inline;"';
}
exec("/usr/sbin/postconf -px relayhost 2>&1", $relayhost_output, $relayhost_rc);
if ($relayhost_rc == 0) {
    $relayhost_line = $relayhost_output[0];
    if (preg_match("/relayhost\s=\s(.*)/i", $relayhost_line, $relayhost_matches)) {
        {
            $relayhost = $relayhost_matches[1];
        }
    }

}
exec("/usr/sbin/postconf -px smtp_sasl_auth_enable 2>&1", $sasl_output, $sasl_rc);
if ($sasl_rc == 0) {
    $smtp_sasl_auth_enable_line = $sasl_output[0];
    if (preg_match("/smtp_sasl_auth_enable\s=\s(.*)/i", $smtp_sasl_auth_enable_line, $smtp_sasl_auth_enable_matches)) {
        {
            $smtp_sasl_auth_enable = $smtp_sasl_auth_enable_matches[1];
            if ($smtp_sasl_auth_enable == "yes") {
                $display_auth = 'style = "display: inline;"';
            }
        }
    }
}
exec("/usr/sbin/postconf -px smtp_use_tls 2>&1", $tls_output, $tls_rc);
if ($tls_rc == 0) {
    $smtp_use_tls_line = $tls_output[0];
    if (preg_match("/smtp_use_tls\s=\s(.*)/i", $smtp_use_tls_line, $smtp_use_tls_matches)) {
        {
            $smtp_use_tls = $smtp_use_tls_matches[1];
            if ($smtp_use_tls == "yes") {
                $display_tls_port = 'style = "display: inline;"';
            }
        }
    }
}
echo "<script>\nvar setup = \"$emailconfig[setup]\" \nvar smtp_use_tls = \"$smtp_use_tls\" \nvar smtp_sasl_auth_enable = \"$smtp_sasl_auth_enable\"\n</script>";
?>
<script src="modules/systemadmin/assets/js/views/email.js"></script>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
			<form method="post" class="fpbx-submit" id="emailform">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
<div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Send e-mail via");?></div>
  <div class="col-md-9 radioset"><input type="radio" class="email_server" name="email_server" id="email_server-local" value="local"><label for="email_server-local"><?php echo _("Local");?></label>
  <input type="radio" class="email_server" name="email_server" id="email_server-remote" value="remote"/><label for="email_server-remote"><?php echo _("Remote server");?></label></div>
  </div>
  </div></div>
<?php
echo "<div $display_local class=\"remote_local\">";
?>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Myhostname");?></div>
  <div class="col-md-9"><input type="text" id="myhostname" name="myhostname" class="form-control"
  <?php
  if($myhostname != "") {
      echo "  value=\"$myhostname\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Mydomain");?></div>
  <div class="col-md-9"><input type="text" id="mydomain" name="mydomain" class="form-control"
  <?php
  if($mydomain != "") {
      echo "  value=\"$mydomain\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Myorigin");?>
			<?php
			if($myorigin_real != "") {
                echo "($myorigin)";
            }
            ?>
            </div>
  <div class="col-md-9"><input type="text" id="myorigin" name="myorigin" class="form-control"
  <?php
  if($myorigin_real != "") {
      echo " value=\"$myorigin_real\"";
  }
  else if($myorigin != "") {
      echo " value=\"$myorigin\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
  </div>
  <?php
  echo "<div $display_remote class=\"remote_local\">";
  ?>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Remote server");?></div>
  <div class="col-md-9"><input type="text" id="mailserver" name="mailserver" class="form-control"
  <?php
  if(array_key_exists('relayhost', $emailconfig)) {
      echo "  value=\"$emailconfig[relayhost]\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
    <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Use Authentication");?></div>
  <div class="col-md-9 radioset"><input type="radio" class="smtp_sasl_auth_enable" name="smtp_sasl_auth_enable" id="smtp_sasl_auth_enable-yes" value="yes"><label for="smtp_sasl_auth_enable-yes"><?php echo _("Yes");?></label>
  <input type="radio" class="smtp_sasl_auth_enable" name="smtp_sasl_auth_enable" id="smtp_sasl_auth_enable-no" value="no"/><label for="smtp_sasl_auth_enable-no"><?php echo _("No");?></label></div>
  </div>
  </div></div>
  <?php
  echo "<div $display_auth class=\"username_password\">";
  ?>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Username");?></div>
  <div class="col-md-9"><input type="text" id="username" name="username" class="form-control"
  <?php
  if($emailconfig['username'] != "") {
      echo " value=\"$emailconfig[username]\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
  </div>
  <?php
  echo "<div $display_auth class=\"username_password\">";
  ?>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Password");?></div>
  <div class="col-md-9"><input type="password" id="password" name="password" class="form-control"
  <?php
  if($emailconfig['username'] != "") {
      echo " value=\"******\"";
  }
  ?>
  >
  </div>
  </div>
  </div></div>
  </div>
  </div>
    <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Use TLS encrytion");?></div>
  <div class="col-md-9 radioset"><input type="radio" class="smtp_use_tls" name="smtp_use_tls" id="smtp_use_tls-yes" value="yes"/><label for="smtp_use_tls-yes"><?php echo _("Yes");?></label>
  <input type="radio" class="smtp_use_tls" name="smtp_use_tls" id="smtp_use_tls-no" value="no"/><label for="smtp_use_tls-no"><?php echo _("No");?></label></div>
  </div>
  </div></div>
  <?php
  echo "<div $display_tls_port class=\"tls_port\">";
  ?>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("TLS Port");?></div>
  <div class="col-md-9"><input type="text" id="port" name="port" class="form-control"
  <?php
  if($emailconfig['port'] != "") {
      echo " value=\"$emailconfig[port]\"";
  }
  ?>>
  </div>
  </div>
  </div></div>
  </div>
</div>
</div>
<input type="hidden" name="set_email" value="true">
<input type="hidden" name="password_changed" value="false">
<?php
if($myorigin_real != "") {
    echo "<input type=\"hidden\" name=\"myorigin_path\" value=\"$myorigin\">";
}
?>
</div>
</form>
</div>

<?php
if(isset($_POST['set_notifications']) && $_POST['set_notifications'] == "true") {
    $from_address = trim($_POST['from_address']);
    $storage_address = trim($_POST['storage_address']);
    $json_data = "{\"fromemail\": \"$from_address\", \"storageemail\": \"$storage_address\"}";
    $dbh = \FreePBX::Database();
    $sql = "INSERT INTO systemadmin_settings (`key`, value) VALUES('notifications_settings', '$json_data') ON DUPLICATE KEY UPDATE value='$json_data'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    exec("/usr/local/freepbx/bin/notifications --from-address \"$from_address\" --storage-address \"$storage_address\" 2>&1", $output, $rc);
    if ($rc != 0) {
      $err_msg = "";
      foreach($output AS $line) {
        $err_msg .= "$line\n";
      }
      throw new \Exception("Can't update notifications config: $err_msg");
    }
}
$email = systemadminFetchEmail();
?>
<script src="modules/systemadmin/assets/js/views/notifications.js"></script>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
			<form method="post" class="fpbx-submit" id="notficationsform">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Sender e-mail address");?></div>
  <div class="col-md-9"><input type="text" id="from_address" name="from_address" class="form-control"
  <?php
  if(array_key_exists('fromemail', $email)) {
      echo "  value=\"$email[fromemail]\"";
  }
  ?>
  ></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Storage e-mail address");?></div>
  <div class="col-md-9 radioset"><input type="text" id="storage_address" name="storage_address" class="form-control"
  <?php
  if(array_key_exists('storageemail', $email)) {
      echo "  value=\"$email[storageemail]\"";
  }
  ?>>
  </div>
  </div>
  </div></div>

</div>
</div>
<input type="hidden" name="set_notifications" value="true">
</div>
</form>
</div>


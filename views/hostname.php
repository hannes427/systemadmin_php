<?php
exec("hostname -s", $current_hostname);
exec("hostname -d", $current_domainname);
$err_hostname = false;
$err_domainname = false;
if(isset($_POST['set_hostname']) && $_POST['set_hostname'] == "true") {
  $arguments = " --oldhostname $current_hostname[0]";
  if($current_domainname[0] != "") {
    $arguments .= " --olddomainname $current_domainname[0]";
  }
  if (isset($_POST['new_hostname']) && $_POST['new_hostname'] != "") {
    if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9\-]*$/", $_POST['new_hostname'])) {
      $err_hostname = true;
    }
    else {
      $arguments .= " --hostname $_POST[new_hostname]";
    }
  }
  if (isset($_POST['new_domainname']) && $_POST['new_domainname'] != "") {
    if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9\-.]*[a-zA-Z0-9]$/", $_POST['new_domainname'])) {
      $err_domainname = true;
    }
    else {
      $arguments .= " --domainname $_POST[new_domainname]";
    }
  }
  if (!$err_hostname || !$err_domainname) {
    exec("/usr/local/freepbx/bin/set_hostname $arguments", $output);
    if (isset($_POST['new_hostname']) && $_POST['new_hostname'] != "" && $_POST['new_hostname'] != $current_hostname[0]) {
      $current_hostname[0] = $_POST['new_hostname'];
    }
    if (isset($_POST['new_domainname']) && $_POST['new_domainname'] != ""&& $_POST['new_domainname'] != $current_domainname[0]) {
      $current_domainname[0] = $_POST['new_domainname'];
    }
  }

}
?>

<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
						<?php if ($err_hostname || $err_domainname) { echo "Error! Could not save changes: Invalid entries!<br>"; } ?>
                      <form method="post" class="fpbx-submit" id="hostform">
                      <div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Current Hostname");?></div>
  <div class='col-md-4'><input type="text" class="form-control disabled" id="current_hostname" name="current_hostname" value="<?php echo $current_hostname[0]; ?>" disabled=""></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("New Hostname");?></div>
  <div class='col-md-4'><input type="text" class="form-control" id="new_hostname" name="new_hostname"></div>
  </div></div></div>
  <br><br>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Current Domainname");?></div>
  <div class='col-md-4'><input type="text" class="form-control disabled" id="current_domainname" name="current_domainname" value="<?php echo $current_domainname[0] ?? ''; ?>" disabled=""></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("New Domainname");?></div>
  <div class='col-md-4'><input type="text" class="form-control" id="new_domainname" name="new_domainname"></div>
  </div></div></div>
  <input type="hidden" name="set_hostname" value="true">
  </form>
</div>
</div>
</div>
</div>



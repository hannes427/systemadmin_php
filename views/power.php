<?php
if(isset($_POST['power']) && $_POST['power'] == "true") {
    $argument = "";
    if ($_POST['button'] == "Reboot") {
        $argument = "reboot";
    }
    else if ($_POST['button'] == "Power Off") {
        $argument = "shutdown";
    }

    if ($argument != "") {
        exec("/usr/local/freepbx/bin/powermgmt $argument 2>&1 &", $output, $rc);
        if ($rc != 0) {
            $err_msg = "";
            foreach($output AS $line) {
                $err_msg .= "$line\n";
            }
            throw new \Exception("Can't change power state: $err_msg");
        }
    }
}
?>
<script src="modules/systemadmin/assets/js/views/power.js"></script>

<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
			<form method="post" class="fpbx-submit" id="dnsform">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Reboot System");?></div>
  <div class='col-md-4'><input type="button" name="button" id="reboot" value="Reboot"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Power Off System");?></div>
   <div class='col-md-4'><input type="button" name="button" id="poweroff" value="Power Off"></div>
  </div></div></div>
</div>
</div>
</div>
<input type="hidden" name="power" value="true">
</form>
</div>

<div class='modal fade' id='rebootmodal'>
  <div class='modal-dialog'>
    <div class='modal-content'>
      <div class='modal-header'>
       <h3 class="mr-auto">Reboot system</h3>
      </div>
      <div class='modal-body'>
       Your system is rebooting now. Please wait at least 3 minutes to refresh this page...
      </div>
    </div>
  </div>
</div>
<div class='modal fade' id='shutdownmodal'>
  <div class='modal-dialog'>
    <div class='modal-content'>
      <div class='modal-header'>
       <h3 class="mr-auto">Shutdown system</h3>
      </div>
      <div class='modal-body'>
       This system is shutting down. You can close this windows.
      </div>
    </div>
  </div>
</div>

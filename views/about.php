<?php
$file = file_get_contents("/etc/os-release");
$file = explode("\n", $file);
array_pop($file);
$os_release = array();
foreach($file AS $tempfile) {
        $temp = explode("=", $tempfile);
		$temp[1] = str_replace('"', '', $temp[1]);
        $os_release[$temp[0]] = $temp[1];
}
$version = get_framework_version();
?>

<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>Operating System:</div>
  <div class='col-sm-3'><?php echo $os_release['PRETTY_NAME']; ?></div>
  </div>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>PBX Version:</div>
  <div class='col-sm-3'><?php echo $version; ?></div>
  </div>
IT WORKS!!!! Generated for Systemadmin
fokbdlkfbfkgb<br><br>fkljhkflkhjflhjflkghjkfl<br><br>
</div>
</div>
</div>
</div>



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
$system_id = \FreePBX::Config()->get("FREEPBX_SYSTEM_IDENT");
exec("/usr/sbin/asterisk -x 'core show version'", $output_version, $version_rc);
if ($version_rc == 0) {
  $asterisk_version = explode(" ", $output_version[0]);
  $asterisk_version = $asterisk_version[1];
}
else {
  $asterisk_version = "unknown";
}
//Get system uptime
$str   = @file_get_contents('/proc/uptime');
$num   = floatval($str);
$secs  = fmod($num, 60); $num = (int)($num / 60);
$mins  = $num % 60;      $num = (int)($num / 60);
$hours = $num % 24;      $num = (int)($num / 24);
$days  = $num;
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
  <div class='col-sm-offset-6 col-sm-3'>System Uptime:</div>
  <div class='col-sm-3'>
  <?php if ($days >0) {
   echo "$days "._("days").", ";
  }
  if ($hours >0) {
   echo "$hours "._("hours").", ";
  }
  if ($mins >0) {
   echo "$mins "._("minutes");
  }
 ?></div>
  </div>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>PBX Version:</div>
  <div class='col-sm-3'><?php echo $version; ?></div>
  </div>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>Asterisk Version:</div>
  <div class='col-sm-3'><?php echo $asterisk_version; ?></div>
  </div>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>System name:</div>
  <div class='col-sm-3'><?php echo $system_id; ?></div>
  </div>
</div>
</div>
</div>
</div>



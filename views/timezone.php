<?php
$timezone_identifiers = DateTimeZone::listIdentifiers();
$timezones = array();
foreach($timezone_identifiers AS $timezone) {
	$timezone = explode("/", $timezone);
    if($timezone[0] == "UTC") {
      $timezones[$timezone[0]][] = "UTC";
    }
    else {
      $timezones[$timezone[0]][] = $timezone[1];
    }
}
if(isset($_POST['set_timezone']) && $_POST['set_timezone'] == "true") {
  $new_timezone = $_POST['region'].'/'.$_POST['city'];
  if(in_array($new_timezone, $timezone_identifiers)) {
    exec("/usr/local/freepbx/set_timezone --time-zone $new_timezone", $output_setTZ);
  }
}
exec("timedatectl status | grep zone | sed -e 's/^[ ]*Time zone: \\(.*\\) (.*)$/\\1/g'", $output);
$local_tz = explode("/", $output[0]);
?>

<script>
var timezoneObject = {
<?php
foreach($timezones AS $key => $value) {
	echo "\"$key\": {";
	foreach($value AS $country) {
		echo "\"$country\": [], ";
	}
	echo "}, ";
}
?>
}
var region = <?php echo "\"$local_tz[0]\"\n"; ?>
var city = <?php echo "\"$local_tz[1]\"\n"; ?>
</script>
<script src="modules/systemadmin/assets/js/views/timezone.js"></script>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
	<form method="post" class="fpbx-submit" id="tzform">






  <div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>




  <div class="element-container">
	<div class="">
		<div class="row form-group">
		<p>
			Warning: Please reboot the system to take effect the changes!</p>
			</div></div></div>




  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Time Zone: </div>
  <div class='col-md-4'><select name="region" id="region">
    <option value="" selected="selected">Select region</option>
  </select>
<select name="city" id="city">
    <option value="" selected="selected">Please select region first</option>
  </select></div>
  </div></div></div>





  <input type="hidden" name="set_timezone" value="true">
</form>
</div>
</div>
</div>
</div>

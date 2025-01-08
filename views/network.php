<?php
if(isset($_POST['set_network']) && $_POST['set_network'] == "true") {
  echo "Form submitted!<br><br>";
  print_r($_POST);
  echo "<br><br>";
}
$interfaces = FreePBX::Systemadmin()->getInterfaces();
foreach($interfaces AS $interface) {
    if (FreePBX::Systemadmin()->check_ifupdown ($interface)) {
        echo "ifupdown()<br>\n";
        $interface = FreePBX::Systemadmin()->get_ifupdown_config($interface);
    }
    else if (FreePBX::Systemadmin()->check_netplan ($interface)) {
      echo "netplan()<br>\n";
      $interface = FreePBX::Systemadmin()->get_netplan_config ($interface);
    }
    else if (FreePBX::Systemadmin()->check_systemd_networkd ($interface)) {
        echo "systemd()<br>\n";
        $interface = FreePBX::Systemadmin()->get_systemd_networkd_config($interface);
    }
    else if (FreePBX::Systemadmin()->check_networkManager ($interface)) {
        echo "networkManager()<br>\n";
        $interface = FreePBX::Systemadmin()->get_nm_config($interface);
    }
    $interfaces[$interface['name']] = $interface;
}
ksort($interfaces);
print_r($interfaces);
echo "<script>\n";
$i = 1;
$len = count($interfaces);
echo "var interface = [ ";
foreach($interfaces AS $key => $interface) {
  echo "{ \"name\": \"$interface[name]\", \"unconfigured\": \"$interface[unconfigured]\", \"ipv4_assignment\": \"$interface[ipv4_assignment]\", \"ipv4_address\": \"$interface[ipv4_address]\", \"ipv4_gateway\": \"$interface[ipv4_gateway]\", \"ipv6_assignment\": \"$interface[ipv6_assignment]\", \"ipv6_address\": [ ";
  foreach($interface['ipv6_address'] AS $ipv6_address) {
    echo "{ \"address\": \"$ipv6_address\"}, ";
  }
  echo "], \"ipv6_gateway\": \"$interface[ipv6_gateway]\", \"ipv6_accept_ra\": \"$interface[ipv6_accept_ra]\", \"ipv6_autoconf\": \"$interface[ipv6_autoconf]\"}";
  if ($i < $len) {
    echo ", ";
  }
  $i++;
}
echo "]\n</script>\n";
?>
<script src="modules/systemadmin/assets/js/views/network.js"></script>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
			<form method="post" class="fpbx-submit" id="networkform">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Interface</div>
  <div class="col-md-9"><select name="network_interface" id="network_interface" class="form-control"><?php foreach($interfaces AS $interface) { echo "<option value=\"$interface[name]\">$interface[name]</option>\n"; } ?></select></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv4 Assignment Method</div>
  <div class="col-md-9 radioset"><input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-static" value="static"/><label for="ipv4_assignment-static">Static</label>
  <input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-dhcp" value="dhcp"/><label for="ipv4_assignment-dhcp">DHCP</label>
  <input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-unconfigured" value="unconfigured"/><label for="ipv4_assignment-unconfigured">Unconfigured</label></div>
  </div>
  </div></div>
  <div class="element-container static_only">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv4 address/netmask</div>
  <div class="col-md-9"><input type="text" id="ipv4_address" name="ipv4_address" class="ipv4_disabled form-control"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv4 Gateway</div>
  <div class='col-md-9'><input type="text" id="ipv4_gateway" name="ipv4_gateway" class="ipv4_disabled form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">&nbsp;
		<div class="row form-group">
  		</div></div></div>
    <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv6 Assignment Method</div>
  <div class="col-md-9 radioset"><input type="radio" name="ipv6_assignment" id="ipv6_assignment-static" value="static"/><label for="ipv6_assignment-static">Static</label>
  <input type="radio" name="ipv6_assignment" id="ipv6_assignment-dhcp" value="dhcp"/><label for="ipv6_assignment-dhcp">DHCP</label>
  <input type="radio" name="ipv6_assignment" id="ipv6_assignment-unconfigured" value="unconfigured"/><label for="ipv6_assignment-unconfigured">Unconfigured</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv6 address/netmask</div>
  <div class='col-md-4'><textarea id="ipv6_address" name="ipv6_address" rows="5" cols="31" class="ipv6_disabled form-control"></textarea></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv6 Gateway</div>
  <div class='col-md-9'><input type="text" id="ipv6_gateway" name="ipv6_gateway" class="ipv6_disabled form-control"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv6 Autoconf</div>
 <div class="col-md-9 radioset"><input type="radio" name="ipv6_autoconf" id="ipv6_autoconf_on" value="1"/><label for="ipv6_autoconf_on">On</label>
  <input type="radio" name="ipv6_autoconf" id="ipv6_autoconf_off" value="0"/><label for="ipv6_autoconf_off">Off</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">IPv6 Accept Router Advertisement</div>
  <div class="col-md-9 radioset"><input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_on" value="1"/><label for="ipv6_accept_ra_on">On</label>
  <input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_forwarding" value="2"/><label for="ipv6_accept_ra_forwarding">On + Forwarding</label>
  <input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_off" value="0"/><label for="ipv6_accept_ra_off">off</label></div>
  </div>
  </div></div>
</div>
</div>
<input type="hidden" name="set_network" value="true">
<?php
foreach ($interfaces AS $interface) {
  if(array_key_exists('nm_connection', $interface)) {
    echo "<input type=\"hidden\" name=\"nm_connection_$interface[name]\" value=\"$interface[nm_connection]\">";
  }
}
?>
</div>
</form>
</div>

<?php
if(isset($_POST['set_network']) && $_POST['set_network'] == "true") {
    $arguments = "";
    $arguments .= " --interface $_POST[network_interface]";
   if (array_key_exists("nm_connection_$_POST[network_interface]", $_POST) && $_POST["nm_connection_$_POST[network_interface]"] != "") {
        $conn =  $_POST["nm_connection_$_POST[network_interface]"];
        $arguments .= " --connection $conn";
    }
    $arguments .= " --ipv4-assignment $_POST[ipv4_assignment]";
    if (array_key_exists('ipv4_address', $_POST) && $_POST['ipv4_address'] != "") {
      $temp_ip4 = explode("/", $_POST['ipv4_address']);
    }
    if (array_key_exists('ipv4_gateway', $_POST) && trim($_POST['ipv4_gateway']) != "") {
      $arguments .= " --ipv4-gateway ".trim($_POST['ipv4_gateway']);
    }
    $arguments .= " --ipv6-assignment $_POST[ipv6_assignment]";
    if (array_key_exists('ipv6_address', $_POST)) {
        $temp_ip6 = explode("/", $_POST['ipv6_address']);
    }
    if (array_key_exists('ipv6_gateway', $_POST) && trim($_POST['ipv6_gateway']) != "") {
        $arguments .= " --ipv6-gateway ".trim($_POST['ipv6_gateway']);
    }
    if (array_key_exists('ipv6_autoconf', $_POST)) {
      $arguments .= " --ipv6-autoconf $_POST[ipv6_autoconf]";
    }
    if (array_key_exists('ipv6_accept_ra', $_POST)) {
      $arguments .= " --ipv6-accept-ra $_POST[ipv6_accept_ra]";
    }
    $arguments .= " --managed-by $_POST[managed_by]";

    //Vallidation
    $error = false;
    if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $_POST['network_interface'])) {
        $error = true;
    }
    if (isset($temp_ip4)) {
      if (count($temp_ip4) != "2") {
        $error = true;
      }
      else {
        if (!filter_var($temp_ip4[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)|| !preg_match("/^\d\d$/", $temp_ip4[1])) {
            $error = true;
          }
          else {
              $arguments .= " --ipv4-address $temp_ip4[0]/$temp_ip4[1]";
          }
      }
    }
    if ((isset($_POST['ipv4_gateway']) && $_POST['ipv4_gateway'] != "") && !filter_var($_POST['ipv4_gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $error = true;
    }
    if (isset($temp_ip6)) {
      if (count($temp_ip6) != "2") {
        $error = true;
      }
      else {
        if (!filter_var($temp_ip6[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $error = true;
          }
          else {
              $arguments .= " --ipv6-address $temp_ip6[0]/$temp_ip6[1]";
          }
      }
    }
     if ((isset($_POST['ipv6_gateway']) && $_POST['ipv6_gateway'] != "") && !filter_var($_POST['ipv6_gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $error = true;
    }
    if(!$error) {
        exec("/usr/local/freepbx/bin/network $arguments 2>&1", $output, $rc);
        if ($rc != 0) {
            $err_msg = "";
            foreach($output AS $line) {
                $err_msg .= "$line\n";
            }
            throw new \Exception("Can't update naetwork config: $err_msg");
        }
    }
}
$interfaces = FreePBX::Systemadmin()->getInterfaces();
foreach($interfaces AS $interface) {
    if (FreePBX::Systemadmin()->check_ifupdown ($interface)) {
        $managed_by = "ifupdown";
        $interface = FreePBX::Systemadmin()->get_ifupdown_config($interface);
    }
    else if (FreePBX::Systemadmin()->check_netplan ($interface)) {
      $managed_by = "netplan";
      $interface = FreePBX::Systemadmin()->get_netplan_config ($interface);
    }
    else if (FreePBX::Systemadmin()->check_systemd_networkd ($interface)) {
        $managed_by = "networkd";
        $interface = FreePBX::Systemadmin()->get_systemd_networkd_config($interface);
    }
    else if (FreePBX::Systemadmin()->check_networkManager ($interface)) {
        $managed_by = "network_manager";
        $interface = FreePBX::Systemadmin()->get_nm_config($interface);
    }
    if ($interface['ipv4_assignment'] == "dhcp") {
      exec("/usr/sbin/ip -4 r | grep default | grep \"dev $interface[name]\"", $output_routing, $rc_routing);
      if($rc_routing == 0) {
        if(preg_match("/default\svia\s(.*)\s/iU", $output_routing[0], $matches)) {
          $interface['ipv4_gateway'] = $matches[1];
        }
      }
    }
    if($interface['ipv6_accept_ra'] == "") {
      $interface['ipv6_accept_ra'] = "1";
    }
    $interfaces[$interface['name']] = $interface;
}
ksort($interfaces);
echo "<script>\n";
$i = 1;
$len = count($interfaces);
echo "var interface = [ ";
foreach($interfaces AS $key => $interface) {
  echo "{ \"name\": \"$interface[name]\", \"unconfigured\": \"$interface[unconfigured]\", \"ipv4_assignment\": \"$interface[ipv4_assignment]\", \"ipv4_address\": \"$interface[ipv4_address]\", \"ipv4_gateway\": \"$interface[ipv4_gateway]\", \"dyn_ipv4_address\": \"$interface[dyn_ipv4_address]\", \"ipv6_assignment\": \"$interface[ipv6_assignment]\", \"ipv6_address\": [ ";
  foreach($interface['ipv6_address'] AS $ipv6_address) {
    echo "{ \"address\": \"$ipv6_address\"}, ";
  }
  echo "], \"ipv6_gateway\": \"$interface[ipv6_gateway]\", \"ipv6_accept_ra\": \"$interface[ipv6_accept_ra]\", \"ipv6_autoconf\": \"$interface[ipv6_autoconf]\"}";
  if ($i < $len) {
    echo ", ";
  }
  $i++;
}
echo " ]\n</script>\n";
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
  <div class="col-md-9"><select name="network_interface" id="network_interface" class="form-control">
  <?php
  foreach($interfaces AS $interface) {
    echo "<option value=\"$interface[name]\"";
    if(isset($_POST['network_interface']) && $_POST['network_interface'] == $interface['name']) {
      echo " selected=\"selected\"";
    }
  echo ">$interface[name]</option>\n";
  }
  ?></select></div>
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
  <div class="col-md-9"><input type="text" id="ipv4_address" name="ipv4_address" class="ipv4_disabled form-control">
  </div>
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
  <div class='col-md-9'><input type="text" id="ipv6_address" name="ipv6_address" class="ipv6_disabled form-control"></div>
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
    echo "<input type=\"hidden\" name=\"nm_connection_$interface[name]\" value=\"$interface[nm_connection]\">\n";
  }
}
echo "<input type=\"hidden\" name=\"managed_by\" value=\"$managed_by\">\n";
?>
</div>
</form>
</div>

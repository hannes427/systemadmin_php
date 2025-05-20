<?php
if(isset($_POST['set_network']) && $_POST['set_network'] == "true") {
  print_r($_POST);
    $arguments = "";
    $arguments .= " --interface $_POST[network_interface]";
    $type = 'ethernet'; //type of the interface (possible values: 'ethernet' (default), 'bond'
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
    if (array_key_exists('selected_bond', $_POST)) {
      $type = 'bond';
      $mode = $_POST['mode'];
      if (array_key_exists('lacp_rate', $_POST)) {
        $lacp_rate = $_POST['lacp_rate'];
      }
      if (array_key_exists('mii_monitor_interval', $_POST)) {
        $mii_monitor_interval = $_POST['mii_monitor_interval'];
      }
      if (array_key_exists('min_links', $_POST)) {
        $min_links = $_POST['min_links'];
      }
      if (array_key_exists('transmit_hash_policy', $_POST)) {
        $transmit_hash_policy = $_POST['transmit_hash_policy'];
      }
      if (array_key_exists('ad_select', $_POST)) {
        $ad_select = $_POST['ad_select'];
      }
      if (array_key_exists('all_members_active', $_POST)) {
        $all_members_active = $_POST['all_members_active'];
      }
      if (array_key_exists('arp_interval', $_POST)) {
        $arp_interval = $_POST['arp_interval'];
      }
      if (array_key_exists('arp_ip_targets', $_POST)) {
        $arp_ip_targets = $_POST['arp_ip_targets'];
      }
      if (array_key_exists('arp_validate', $_POST)) {
        $arp_validate = $_POST['arp_validate'];
      }
      if (array_key_exists('arp_all_targets', $_POST)) {
        $arp_all_targets = $_POST['arp_all_targets'];
      }
      if (array_key_exists('up_delay', $_POST)) {
        $up_delay = $_POST['up_delay'];
      }
      if (array_key_exists('down_delay', $_POST)) {
        $down_delay = $_POST['down_delay'];
      }
      if (array_key_exists('fail_over_mac_policy', $_POST)) {
        $fail_over_mac_policy = $_POST['fail_over_mac_policy'];
      }
      if (array_key_exists('gratuitous_arp', $_POST)) {
        $gratuitous_arp = $_POST['gratuitous_arp'];
      }
      if (array_key_exists('packets_per_member', $_POST)) {
        $packets_per_member = $_POST['packets_per_member'];
      }
      if (array_key_exists('primary_reselect_policy', $_POST)) {
        $primary_reselect_policy = $_POST['primary_reselect_policy'];
      }
      if (array_key_exists('resend_igmp', $_POST)) {
        $resend_igmp = $_POST['resend_igmp'];
      }
      if (array_key_exists('learn_packet_interval', $_POST)) {
        $learn_packet_interval = $_POST['learn_packet_interval'];
      }
      if (array_key_exists('primary', $_POST)) {
        $primary = $_POST['primary'];
      }
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
    if ($type == "bond") {
      if (isset($mode)) {
        if ($mode != 'balance-rr' && $mode != 'active-backup' && $mode != 'balance-xor' && $mode != 'broadcast' && $mode != '802.3ad' && $mode != 'balance-tlb' && $mode != 'balance-alb') {
          $error = true;
        }
        else {
          $arguments .= "--bond-mode $mode";
        }
      }
      if (isset($lacp_rate)) {
        if ($lacp_rate != 'slow' || $lacp_rate != 'fast') {
          $error = true;
        }
        else {
          $arguments .= "--lacp-rate $lacp_rate";
        }
      }
      if (isset($mii_monitor_interval)) {
        if ($mii_monitor_interval != '' && !is_numeric($mii_monitor_interval)) {
          $error = true;
        }
        else {
          $arguments .= "--mii-monitor-interval $mii_monitor_interval";
        }
      }
      if (isset($min_links)) {
        if (!is_numeric($min_links)) {
          $error = true;
        }
        else {
          $arguments .= "--min-links $lacp_rate";
        }
      }
      if (isset($transmit_hash_policy)) {
        if ($transmit_hash_policy != 'layer2' && $transmit_hash_policy != 'layer3+4' && $transmit_hash_policy != 'layer2+3' && $transmit_hash_policy != 'encap2+3' && $transmit_hash_policy != 'encap3+4') {
          $error = true;
        }
        else {
          $arguments .= "--transmit-hash-policy $transmit_hash_policy";
        }
      }
      if (isset($ad_select)) {
        if ($ad_select != 'stable' && $ad_select != 'bandwidth' && $ad_select != 'count') {
          $error = true;
        }
        else {
          $arguments .= "--ad-select $ad_select";
        }
      }
      if (isset($all_members_active)) {
        if ($all_members_active != 'true' && $all_members_active != 'false') {
          $error = true;
        }
        else {
          $arguments .= "--all-members-active $all_members_active";
        }
      }
      if (isset($arp_interval)) {
        if (!is_numeric($arp_interval)) {
          $error = true;
        }
        else {
          $arguments .= "--arp-interval $arp_interval";
        }
      }
      if (isset($arp_ip_targets)) {
        if ($arp_ip_targets != '') {
          $temp_ip_targets = str_replace(' ', '', $arp_ip_targets);
          $temp_ip_targets = explode(',', $temp_ip_targets);
          $ip_targets = '';
          foreach($temp_ip_targets AS $ip_target) {
            if (!filter_var($ip_target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
              $error = true;
              break;
            }
            else {
              $ip_targets .= "$ip_target,";
            }
          }
          $arguments .= "--arp-ip-targets $arp_ip_targets";
        }
      }
      if (isset($arp_validate)) {
        if ($arp_validate != 'none' && $arp_validate != 'active' && $arp_validate != 'backup' && $arp_validate != 'all') {
          $error = true;
        }
        else {
          $arguments .= "--arp-validate $arp_validate";
        }
      }
      if (isset($arp_all_targets)) {
        if ($arp_all_targets != 'any' && $arp_all_targets != 'all') {
            $error = true;
        }
        else {
          $arguments .= "--arp-all-targets $arp_all_targets";
        }
      }
      if (isset($up_delay)) {
        if (!is_numeric($up_delay)) {
          $error = true;
        }
        else {
          $arguments .= "--up-delay $up_delay";
        }
      }
      if (isset($down_delay)) {
        if (!is_numeric($down_delay)) {
          $error = true;
        }
        else {
          $arguments .= "--down-delay $down_delay";
        }
      }
      if (isset($fail_over_mac_policy)) {
        if ($fail_over_mac_policy != 'none' && $fail_over_mac_policy != 'active' && $fail_over_mac_policy != 'follow') {
          $error = true;
        }
        else {
          $arguments .= "--fail-over-mac-policy $fail_over_mac_policy";
        }
      }
      if (isset($gratuitous_arp)) {
        if ($gratuitous_arp != 'slow' && $gratuitous_arp != 'fast') {
          $error = true;
        }
        else {
          $arguments .= "--gratuitous-arp $gratuitous_arp";
        }
      }
      if (isset($lacp_rate)) {
        if (!is_numeric($gratuitous_arp) || ($gratuitous_arp < 1 || $gratuitous_arp > 255)) {
          $error = true;
          }
          else {
            $arguments .= "--gratuitous-arp $gratuitous_arp";
        }
      }
      if (isset($packets_per_member)) {
        if (!is_numeric($packets_per_member) || ($packets_per_member < 0 || $packets_per_member > 65535)) {
          $error = true;
        }
        else {
          $arguments .= "--packets-per-member $packets_per_member";
        }
      }
      if (isset($primary_reselect_policy)) {
        if ($primary_reselect_policy != 'always' && $primary_reselect_policy != 'better' && $primary_reselect_policy != 'failure') {
          $error = true;
        }
        else {
          $arguments .= "--primary-reselect-policy $primary_reselect_policy";
        }
      }
      if (isset($resend_igmp)) {
        if (!is_numeric($resend_igmp) || ($resend_igmp < 0 || $resend_igmp > 255)) {
          $error = true;
        }
        else {
          $arguments .= "--resend-igmp $resend_igmp";
        }
      }
      if (isset($learn_packet_interval)) {
        if (!is_numeric($learn_packet_interval) || ($learn_packet_interval < 1 || $learn_packet_interval > 0x7fffffff)) {
          $error = true;
        }
        else {
          $arguments .= "--learn-packet-interval $learn_packet_interval";
        }
      }
      if (isset($primary)) {
        if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $primary)) {
          $error = true;
        }
        else {
          $arguments .= "--primary $primary";
        }
      }
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
  echo "], \"ipv6_gateway\": \"$interface[ipv6_gateway]\", \"ipv6_accept_ra\": \"$interface[ipv6_accept_ra]\", \"ipv6_autoconf\": \"$interface[ipv6_autoconf]\", \"bonding_status\": \"$interface[bonding_status]\", \"bonding_master\": \"$interface[bonding_master]\", \"bond_parameter\": [ ";
  foreach ($interface['bond_parameter'] AS $key => $value) {
    echo "{ \"$key\": \"$value\"}, ";
  }
  echo "] }";
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
  </div></div></div><div id="bonding_warning"></div>
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
  <div id="bond_section" style="display: none;">
  <br><br>
  <div class="element-container">
	<div class="">
		<div class="row">
			<div class="col-md-12">Bonding Options</div>
          </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Mode</div>
  <div class="col-md-9"><select name="mode" id="bond_mode" class="form-control">
  <option value="balance-rr">Balace-RR</option>
  <option value="active-backup">Active Backup</option>
  <option value="balance-xor">Balace XOR</option>
  <option value="broadcast">Broadcast</option>
  <option value="802.3ad">802.3ad</option>
  <option value="balance-tlb">Balance TLB</option>
  <option value="balance-slb">Balace ALB</option>
  </select>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">LACP Rate</div>
   <div class="col-md-9"><select name="lacp-rate" id="lacp_rate" class="8023ad form-control">
  <option value="slow">Slow (every 30 seconds)</option>
  <option value="fast">Fast (Every second)</option>
  </select>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">MII-Monitor-Intervalt</div>
  <div class='col-md-9'><input type="text" id="mii_monitor_interval" name="mii_monitor_interval" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Minimum Links</div>
 <div class='col-md-9'><input type="text" id="min_links" name="min_links" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Transmit Hash Policy</div>
  <div class="col-md-9"><select name="transmit_hash_policy" id="transmit_hash_policy" class="form-control balance-xor 8023ad balance-tlb">
  <option value="layer2">Layer 2</option>
  <option value="layer2+3">Layer 2 + 3</option>
  <option value="layer3+4">Layer 3 + 4</option>
  <option value="encap2+3">Encap 2 + 3</option>
  <option value="encap3+4">Encap 3 + 4)</option>
  </select>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Aggregation selection mode</div>
  <div class="col-md-9"><select name="ad_select" id="ad_select" class="form-control 8023ad">
  <option value="stable">Stable</option>
  <option value="bandwidth">Bandwidth</option>
  <option value="count">Count</option>
  </select>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">All members active</div>
  <div class="col-md-9 radioset"><input type="radio" name="all_members_active" id="all_members_active_off" value="false"/><label for="all_members_active_off">No</label>
  <input type="radio" name="all_members_active" id="all_members_active_on" value="true"/><label for="all_members_active_on">Yes</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">ARP Link monitoring intervall</div>
   <div class='col-md-9'><input type="text" id="arp_interval" name="arp_interval" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">ARP IP targets</div>
  <div class='col-md-9'><input type="text" id="arp_ip_targets" name="arp_ip_targets" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Method to validate ARP replies</div>
   <div class="col-md-9"><select name="arp_validate" id="arp_validate" class="form-control">
  <option value="active">Active</option>
  <option value="all">All</option>
  <option value="backup">Backup</option>
  <option value="none">None</option>
  </select>
  </div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Do all ARP targets need to be up for the port to be recognized as up</div>
  <div class="col-md-9 radioset"><input type="radio" name="arp_all_targets" id="arp_all_targets_any" value="any" class="active-backup"><label for="arp_all_targets_any">Any</label>
  <input type="radio" name="arp_all_targets" id="arp_all_targets_all" value="all"/><label for="arp_all_targets_all" class="active-backup">All</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Up delay</div>
  <div class='col-md-9'><input type="text" id="up_delay" name="up_delay" class="form-control"></div>
  </div>
  </div></div>
   <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Down delay</div>
  <div class='col-md-9'><input type="text" id="down_delay" name="down_delay" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Fail-over MAC policy</div>
  <div class="col-md-9 radioset"><input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_active" value="active"/><label for="fail_over_mac_policy_active">Active</label>
  <input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_follow" value="follow"/><label for="fail_over_mac_policy_follow">Follow</label>
  <input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_none" value="none"/><label for="fail_over_mac_policy_none">None</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Amount of packets to send after failover</div>
  <div class='col-md-9'><input type="text" id="gratuitous_arp" name="gratuitous_arp" class="active-backup form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Packets per port in balance-rr before switching to the next</div>
  <div class='col-md-9'><input type="text" id="packets_per_member" name="packets_per_member" class="balance-rr form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Reselection policy for the primary port</div>
  <div class="col-md-9 radioset"><input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_always" value="always"/><label for="primary_reselect_policy_always">Always</label>
  <input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_better" value="better"/><label for="primary_reselect_policy_better">Better</label>
  <input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_failure" value="failure"/><label for="primary_reselect_policy_failure">Failure</label></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Amount of IGMP membership reports on a failover event</div>
  <div class='col-md-9'><input type="text" id="resend_igmp" name="resend_igmp" class="form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Intervall of learning packets</div>
  <div class='col-md-9'><input type="text" id="learn_packet_interval" name="learn_packet_interval" class="balance-tlb balance-alb form-control"></div>
  </div>
  </div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">Primary port</div>
  <div class="col-md-9"><select name="primary" id="primary" class="active-backup balance-alb balance-tlb form-control">
  <?php
  foreach($interfaces AS $interface) {
    if($interface['bonding_status'] == 'none') {
      echo "<option value=\"$interface[name]\"";
      if(isset($_POST['network_interface']) && $_POST['network_interface'] == $interface['name']) {
        echo " selected=\"selected\"";
      }
    echo ">$interface[name]</option>\n";
    }
  }
  ?></select></div>
  </div>
  </div></div>
  </div>
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

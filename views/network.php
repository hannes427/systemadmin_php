<?php
if (isset($_POST['add_interface_modal']) && $_POST['add_interface_modal'] == "true") { // Add new interface
	$arguments = "";
	$interface_type = "";
	$bond_mode = "";
	$bond_member = "";
	$interface_name = trim($_POST['add_interface_name']);
	if (array_key_exists('interface_type', $_POST)) {
		$interface_type = $_POST['interface_type'];
	}
	if (array_key_exists('add_if_mode', $_POST)) {
		$mode = $_POST['add_if_mode'];
	}
	if (array_key_exists('bond_member', $_POST)) {
		$bond_member = $_POST['bond_member'];
	}
	$arguments .= " --create-new-interface --managed-by $_POST[add_interface_managed_by] --ipv4-assignment unconfigured --ipv6-assignment unconfigured";

	//Vallidation
	$error = false;
	$error_msg_add_interface = ""
	if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $interface_name)) {
		$error = true;
		$error_msg_add_interface .= "Interface Name, ";
	}
	else {
		$arguments .= " --interface $interface_name";
	}
	if ($interface_type == "") {
		$error = true;
		$error_msg_add_interface .= "Interface type, ";
	}
	else {
		$arguments .= " --type $interface_type";
	}
	if (isset($mode)) {
		if ($mode != 'balance-rr' && $mode != 'active-backup' && $mode != 'balance-xor' && $mode != 'broadcast' && $mode != '802.3ad' && $mode != 'balance-tlb' && $mode != 'balance-alb') {
			$error = true;
			$error_msg_add_interface .= "Bond mode, ";
		}
		else {
			$arguments .= " --mode $mode";
		}
	}
	if(isset($bond_member)) {
	$member = '';
	foreach($bond_member AS $temp_member) {
		if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $temp_member)) {
			$error = true;
			$error_msg_add_interface .= "Bond member, ";
		}
		else {
			$member .= "$temp_member,";
		}
	}
	//remove last comma from string
	$member = rtrim($member, ',');
	$arguments .= " --bond_member $member";
	}
	if(!$error) {
		exec("/usr/local/freepbx/bin/network $arguments 2>&1", $output, $rc);
		if ($rc != 0) {
			$err_msg = "";
			foreach($output AS $line) {
				$err_msg .= "$line\n";
			}
			throw new \Exception("Can't create interface: $err_msg");
		}
	}
	else {
		echo "<p style=\"color: red;\">There are errors in the following input fields: $error_msg_add_interface</p>";
	}
}

else if (isset($_POST['del_interface_modal']) && $_POST['del_interface_modal'] == "true") {
	$arguments = "";
	$interface_type = $_POST['del_interface_type'];
	$interface_name = trim($_POST['del_interface_name']);
	$arguments .= " --delete-interface --managed-by $_POST[del_interface_managed_by]";
	//Vallidation
	$error = false;
	$error_msg_del_interface = "";
	if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $interface_name)) {
		$error = true;
		$error_msg_del_interface .= "Interface name, ";
	}
	else {
		$arguments .= " --interface $interface_name";
	}
	if ($interface_type == "" && ($interface_type != 'ethernet' || $interface_type != 'bond')) {
		$error = true;
		$error_msg_del_interface .= "Interface type, ";
	}
	else {
		$arguments .= " --type $interface_type";
	}
	if(!$error) {
		exec("/usr/local/freepbx/bin/network $arguments 2>&1", $output, $rc);
		if ($rc != 0) {
			$err_msg = "";
			foreach($output AS $line) {
				$err_msg .= "$line\n";
			}
			throw new \Exception("Can't delete interface: $err_msg");
		}
	}
	else {
		echo "<p style=\"color: red;\">There are errors in the following input fields: $error_msg_del_interface</p>";
	}
}

else if (isset($_POST['set_network']) && $_POST['set_network'] == "true") { //Edit interface
	$arguments = "";
	$arguments .= " --interface $_POST[network_interface]";
	$type = 'ethernet'; //type of the interface (possible values: 'ethernet' (default), 'bond'
	if (array_key_exists("nm_connection_$_POST[network_interface]", $_POST) && $_POST["nm_connection_$_POST[network_interface]"] != "") {
		$conn = $_POST["nm_connection_$_POST[network_interface]"];
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
		$bond_member = $_POST['bond_member'];
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
	$arguments .= " --managed-by $_POST[managed_by] --type $type";

	//Vallidation
	$error = false;
	$error_msg_edit_interface = "";
	if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $_POST['network_interface'])) {
		$error = true;
		$error_msg_edit_interface .= "Interface, ";
	}
	if (isset($temp_ip4)) {
		if (count($temp_ip4) != "2") {
		$error = true;
		$error_msg_edit_interface .= "IPv4 address (no netmask given), ";
		}
		else {
		if (!filter_var($temp_ip4[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)|| !preg_match("/^\d\d$/", $temp_ip4[1])) {
			$error = true;
			$error_msg_edit_interface .= "IPv4 address (no valid IP address entered), ";
			}
			else {
				$arguments .= " --ipv4-address $temp_ip4[0]/$temp_ip4[1]";
			}
		}
	}
	if ((isset($_POST['ipv4_gateway']) && $_POST['ipv4_gateway'] != "") && !filter_var($_POST['ipv4_gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$error = true;
		$error_msg_edit_interface .= "IPv4 gateway (no valid IP address entered), ";
	}
	if (isset($temp_ip6)) {
		if (count($temp_ip6) != "2") {
		$error = true;
		$error_msg_edit_interface .= "IPv6 address (no netmask given), ";
		}
		else {
		if (!filter_var($temp_ip6[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$error = true;
			$error_msg_edit_interface .= "IPv6 address (no valid IP address entered), ";
			}
			else {
				$arguments .= " --ipv6-address $temp_ip6[0]/$temp_ip6[1]";
			}
		}
	}
	if ((isset($_POST['ipv6_gateway']) && $_POST['ipv6_gateway'] != "") && !filter_var($_POST['ipv6_gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$error = true;
		$error_msg_edit_interface .= "IPv6 gateway (no valid IP address entered), ";
	}
	if ($type == "bond") {
		if (isset($mode)) {
		if ($mode != 'balance-rr' && $mode != 'active-backup' && $mode != 'balance-xor' && $mode != 'broadcast' && $mode != '802.3ad' && $mode != 'balance-tlb' && $mode != 'balance-alb') {
			$error = true;
			$error_msg_edit_interface .= "Bond mode, ";
		}
		else {
			$arguments .= " --mode $mode";
		}
		}
		if(isset($bond_member)) {
		$member = '';
		foreach($bond_member AS $temp_member) {
			if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $temp_member)) {
			$error = true;
			$error_msg_edit_interface .= "Bond member, ";
			}
			else {
			$member .= "$temp_member,";
			}
		}
		//remove last comma from string
		$member = rtrim($member, ',');
		$arguments .= " --bond_member $member";
		}
		if (isset($lacp_rate)) {
		if ($lacp_rate != 'slow' && $lacp_rate != 'fast') {
			$error = true;
			$error_msg_edit_interface .= "LACP rate, ";
		}
		else if (!empty($lacp_rate)) {
			$arguments .= " --lacp-rate $lacp_rate";
		}
		}
		if (isset($mii_monitor_interval)) {
		if (!empty($mii_monitor_interval) && !is_numeric($mii_monitor_interval)) {
			$error = true;
			$error_msg_edit_interface .= "MII Monitor Interval, ";
		}
		else if (!empty($mii_monitor_interval)) {
			$arguments .= " --mii-monitor-interval $mii_monitor_interval";
		}
		}
		if (isset($min_links)) {
			if (!empty($min_links) && !is_numeric($min_links)) {
				$error = true;
				$error_msg_edit_interface .= "Min Links, ";
			}
			else if (!empty($min_links)) {
				$arguments .= " --min-links $min_links";
			}
		}
		if (isset($transmit_hash_policy)) {
			if ($transmit_hash_policy != 'layer2' && $transmit_hash_policy != 'layer3+4' && $transmit_hash_policy != 'layer2+3' && $transmit_hash_policy != 'encap2+3' && $transmit_hash_policy != 'encap3+4') {
				$error = true;
				$error_msg_edit_interface .= "Transmit hash policy, ";
			}
			else {
				$arguments .= " --transmit-hash-policy $transmit_hash_policy";
			}
		}
		if (isset($ad_select)) {
			if ($ad_select != 'stable' && $ad_select != 'bandwidth' && $ad_select != 'count') {
				$error = true;
				$error_msg_edit_interface .= "AD Select, ";
			}
			else {
				$arguments .= " --ad-select $ad_select";
			}
		}
		if (isset($all_members_active)) {
			if ($all_members_active != 'true' && $all_members_active != 'false') {
				$error = true;
				$error_msg_edit_interface .= "All members active, ";
			}
			else {
				$arguments .= " --all-members-active $all_members_active";
			}
		}
		if (isset($arp_interval)) {
			if (!empty($arp_interval) && !is_numeric($arp_interval)) {
				$error = true;
				$error_msg_edit_interface .= "ARP Link monitoring interval, ";
			}
			else if (!empty($arp_interval)) {
				$arguments .= " --arp-interval $arp_interval";
			}
		}
		if (isset($arp_ip_targets)) {
			if (!empty($arp_ip_targets)) {
				$temp_ip_targets = str_replace(' ', '', $arp_ip_targets);
				$temp_ip_targets = explode(',', $temp_ip_targets);
				$ip_targets = '';
				foreach($temp_ip_targets AS $ip_target) {
				if (!filter_var($ip_target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					$error = true;
					$error_msg_edit_interface .= "ARP IP targets, ";
					break;
				}
				else if (!empty($ip_targets)) {
					$ip_targets .= "$ip_target,";
				}
				}
				$arguments .= " --arp-ip-targets $arp_ip_targets";
			}
		}
		if (isset($arp_validate)) {
			if ($arp_validate != 'none' && $arp_validate != 'active' && $arp_validate != 'backup' && $arp_validate != 'all') {
				$error = true;
				$error_msg_edit_interface .= "Method to validate ARP replies, ";
			}
			else {
				$arguments .= " --arp-validate $arp_validate";
			}
		}
		if (isset($arp_all_targets)) {
			if ($arp_all_targets != 'any' && $arp_all_targets != 'all') {
				$error = true;
				$error_msg_edit_interface .= "ARP all targets, ";
			}
			else {
				$arguments .= " --arp-all-targets $arp_all_targets";
			}
		}
		if (isset($up_delay)) {
			if (!empty($up_delay) && !is_numeric($up_delay)) {
				$error = true;
				$error_msg_edit_interface .= "Up-Delay, ";
			}
			else if (!empty($up_delay)) {
				$arguments .= " --up-delay $up_delay";
			}
		}
		if (isset($down_delay)) {
			if (!empty($down_delay) && !is_numeric($down_delay)) {
				$error = true;
				$error_msg_edit_interface .= "Down-delay, ";
			}
			else if (!empty($down_delay)) {
				$arguments .= " --down-delay $down_delay";
			}
		}
		if (isset($fail_over_mac_policy)) {
			if ($fail_over_mac_policy != 'none' && $fail_over_mac_policy != 'active' && $fail_over_mac_policy != 'follow') {
				$error = true;
				$error_msg_edit_interface .= "Fail-over MAC policy, ";
			}
			else {
				$arguments .= " --fail-over-mac-policy $fail_over_mac_policy";
			}
		}
		if (isset($gratuitous_arp)) {
			if (!empty($gratuitous_arp && (!is_numeric($gratuitous_arp) || $gratuitous_arp <1 || $gratuitous_arp > 255))) {
				$error = true;
				$error_msg_edit_interface .= "Gratuitous ARP, ";
			}
			else if(!empty($gratuitous_arp)) {
				$arguments .= " --gratuitous-arp $gratuitous_arp";
			}
		}
		if (isset($packets_per_member)) {
			if (!empty($packets_per_member) && (!is_numeric($packets_per_member) || ($packets_per_member < 0 || $packets_per_member > 65535))) {
				$error = true;
				$error_msg_edit_interface .= "Packets per slave, ";
			}
			else if (!empty($packets_per_member)) {
				$arguments .= " --packets-per-member $packets_per_member";
			}
		}
		if (isset($primary_reselect_policy)) {
			if ($primary_reselect_policy != 'always' && $primary_reselect_policy != 'better' && $primary_reselect_policy != 'failure') {
				$error = true;
				$error_msg_edit_interface .= "Reselection policy, ";
			}
			else {
				$arguments .= " --primary-reselect-policy $primary_reselect_policy";
			}
		}
		if (isset($resend_igmp)) {
			if (!empty($resend_igmp) && (!is_numeric($resend_igmp) || ($resend_igmp < 0 || $resend_igmp > 255))) {
				$error = true;
				$error_msg_edit_interface .= "Resend IGMP, ";
			}
			else if (!empty($resend_igmp)) {
				$arguments .= " --resend-igmp $resend_igmp";
			}
		}
		if (isset($learn_packet_interval)) {
			if (!empty($learn_packet_interval) && (!is_numeric($learn_packet_interval) || ($learn_packet_interval < 1 || $learn_packet_interval > 0x7fffffff))) {
				$error = true;
				$error_msg_edit_interface .= "Learn packets interval, ";
			}
			else if (!empty($learn_packet_interval)) {
				$arguments .= " --learn-packet-interval $learn_packet_interval";
			}
		}
		if (isset($primary)) {
			if (!preg_match("/^[a-z]+[0-9]{1,3}$/i", $primary)) {
				$error = true;
				$error_msg_edit_interface .= "Primary port, ";
			}
			else {
				$arguments .= " --primary $primary";
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
			throw new \Exception("Can't update network config: $err_msg");
		}
	}
	else {
		echo "<p style=\"color: red;\">There are errors in the following input fields: $error_msg_edit_interface</p>";
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

//Under certain circumstances, the bonding status may be 'slave' and the bonding master may not be correctly identified. We use the array key bond_member to properly set the status of the corresponding interfaces
foreach($interfaces AS $interface) {
	if($interface['bonding_status'] == "master") {
		foreach($interface['bond_member'] AS $member) {
			$interfaces[$member]['bonding_status'] = "slave";
			$interfaces[$member]['bonding_master'] = $interface['name'];
		}
	}
}
ksort($interfaces);
include 'modal.network.php';
include 'modal.delete-interface.php';
echo "<script>\n";
$i = 1;
$len = count($interfaces);
echo "var interface = [ ";
foreach($interfaces AS $key => $interface) {
	if(array_key_exists('name', $interface)) {
		echo "{ \"name\": \"$interface[name]\", \"unconfigured\": \"$interface[unconfigured]\", \"ipv4_assignment\": \"$interface[ipv4_assignment]\", \"ipv4_address\": \"$interface[ipv4_address]\", \"ipv4_gateway\": \"$interface[ipv4_gateway]\", \"dyn_ipv4_address\": \"$interface[dyn_ipv4_address]\", \"ipv6_assignment\": \"$interface[ipv6_assignment]\", \"ipv6_address\": [ ";
		foreach($interface['ipv6_address'] AS $ipv6_address) {
			echo "{ \"address\": \"$ipv6_address\"}, ";
		}
		echo "], \"ipv6_gateway\": \"$interface[ipv6_gateway]\", \"ipv6_accept_ra\": \"$interface[ipv6_accept_ra]\", \"ipv6_autoconf\": \"$interface[ipv6_autoconf]\", \"bonding_status\": \"$interface[bonding_status]\", \"bonding_master\": \"$interface[bonding_master]\", \"bond_member\": [ ";
		foreach($interface['bond_member'] AS $member) {
			echo "{ \"interface\": \"$member\"}, ";
		}
		echo "], \"bond_parameter\": [ ";
		foreach ($interface['bond_parameter'] AS $key => $value) {
			echo "{ \"$key\": \"$value\"}, ";
		}
		echo "] }";
		if ($i < $len) {
			echo ", ";
		}
		$i++;
	}
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
											<div class="col-md-9">
												<select name="network_interface" id="network_interface" class="form-control">
												<?php
												foreach($interfaces AS $interface) {
													if(array_key_exists('name', $interface)) {
														echo "<option value=\"$interface[name]\"";
														if((isset($_POST['network_interface']) && $_POST['network_interface'] == $interface['name']) || isset($_POST['add_interface_name']) && $_POST['add_interface_name'] == $interface['name']) {
															echo " selected=\"selected\"";
														}
														echo ">$interface[name]</option>\n";
													}
												}
												?>
												</select>
											</div>
										</div>
									</div>
								</div>
								<div id="bonding_warning"></div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv4 Assignment Method</div>
											<div class="col-md-9 radioset">
												<input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-static" value="static"/><label for="ipv4_assignment-static">Static</label>
												<input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-dhcp" value="dhcp"/><label for="ipv4_assignment-dhcp">DHCP</label>
												<input type="radio" class="ipv4_assignment" name="ipv4_assignment" id="ipv4_assignment-unconfigured" value="unconfigured"/><label for="ipv4_assignment-unconfigured">Unconfigured</label>
											</div>
										</div>
									</div>
								</div>
								<div class="element-container static_only">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv4 address/netmask</div>
											<div class="col-md-9">
												<input type="text" id="ipv4_address" name="ipv4_address" class="ipv4_disabled form-control">
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv4 Gateway</div>
											<div class='col-md-9'>
												<input type="text" id="ipv4_gateway" name="ipv4_gateway" class="ipv4_disabled form-control">
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">&nbsp;
										<div class="row form-group">
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv6 Assignment Method</div>
											<div class="col-md-9 radioset">
												<input type="radio" name="ipv6_assignment" id="ipv6_assignment-static" value="static"/><label for="ipv6_assignment-static">Static</label>
												<input type="radio" name="ipv6_assignment" id="ipv6_assignment-dhcp" value="dhcp"/><label for="ipv6_assignment-dhcp">DHCP</label>
												<input type="radio" name="ipv6_assignment" id="ipv6_assignment-unconfigured" value="unconfigured"/><label for="ipv6_assignment-unconfigured">Unconfigured</label>
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv6 address/netmask</div>
											<div class='col-md-9'>
												<input type="text" id="ipv6_address" name="ipv6_address" class="ipv6_disabled form-control">
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv6 Gateway</div>
											<div class='col-md-9'>
												<input type="text" id="ipv6_gateway" name="ipv6_gateway" class="ipv6_disabled form-control">
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv6 Autoconf</div>
											<div class="col-md-9 radioset">
												<input type="radio" name="ipv6_autoconf" id="ipv6_autoconf_on" value="1"/><label for="ipv6_autoconf_on">On</label>
												<input type="radio" name="ipv6_autoconf" id="ipv6_autoconf_off" value="0"/><label for="ipv6_autoconf_off">Off</label>
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="">
										<div class="row form-group">
											<div class="col-md-3">IPv6 Accept Router Advertisement</div>
											<div class="col-md-9 radioset">
												<input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_on" value="1"/><label for="ipv6_accept_ra_on">On</label>
												<input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_forwarding" value="2"/><label for="ipv6_accept_ra_forwarding">On + Forwarding</label>
												<input type="radio" name="ipv6_accept_ra" id="ipv6_accept_ra_off" value="0"/><label for="ipv6_accept_ra_off">off</label>
											</div>
										</div>
									</div>
								</div>
								<div id="bond_section" style="display: none;">
									<br><br>
									<div class="element-container">
										<div class="">
											<div class="row">
												<div class="col-md-12">Bonding Options (the disabled options are not available in the selected bonding mode)</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Mode</div>
												<div class="col-md-9">
													<select name="mode" id="bond_mode" class="form-control">
													<option value="balance-rr">Balace-RR</option>
													<option value="active-backup">Active Backup</option>
													<option value="balance-xor">Balace XOR</option>
													<option value="broadcast">Broadcast</option>
													<option value="802.3ad">802.3ad</option>
													<option value="balance-tlb">Balance TLB</option>
													<option value="balance-alb">Balace ALB</option>
													</select>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Bond member<i class="fa fa-question-circle fpbx-help-icon" data-for="bond_member"></i></div>
												<div class='col-md-9' id="bond_member"></div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="bond_member-help" class="help-block fpbx-help-block">
													<?php echo _("The interfaces that are to be included in the bond (only unconfigured interfaces will be displayed).")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">LACP Rate<i class="fa fa-question-circle fpbx-help-icon" data-for="lacp_rate"></i></div>
												<div class="col-md-9">
													<select name="lacp_rate" id="lacp_rate" class="8023ad form-control">
													<option value="slow">Slow (every 30 seconds)</option>
													<option value="fast">Fast (Every second)</option>
													</select>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="lacp_rate-help" class="help-block fpbx-help-block">
													<?php echo _("Specify the rate in which we’ll ask our link partner to transmit LACPDU packets in 802.3ad mode.")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">MII-Monitor-Interval<i class="fa fa-question-circle fpbx-help-icon" data-for="mii_monitor_interval"></i></div>
												<div class='col-md-9'>
													<input type="text" id="mii_monitor_interval" name="mii_monitor_interval" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="mii_monitor_interval-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the MII link monitoring frequency in milliseconds. This determines how often the link state of each slave is inspected for link failures. A value of zero disables MII link monitoring")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Minimum Links<i class="fa fa-question-circle fpbx-help-icon" data-for="min_links"></i></div>
												<div class='col-md-9'>
													<input type="text" id="min_links" name="min_links" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="min_links-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the minimum number of links that must be active before asserting carrier")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Transmit Hash Policy<i class="fa fa-question-circle fpbx-help-icon" data-for="transmit_hash_policy"></i></div>
												<div class="col-md-9">
													<select name="transmit_hash_policy" id="transmit_hash_policy" class="form-control balance-xor 8023ad balance-tlb">
													<option value="layer2">Layer 2</option>
													<option value="layer2+3">Layer 2 + 3</option>
													<option value="layer3+4">Layer 3 + 4</option>
													<option value="encap2+3">Encap 2 + 3</option>
													<option value="encap3+4">Encap 3 + 4</option>
													</select>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="transmit_hash_policy-help" class="help-block fpbx-help-block">
													<?php echo _("Selects the transmit hash policy to use for slave selection in balance-xor, 802.3ad, and tlb modes")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Aggregation selection mode<i class="fa fa-question-circle fpbx-help-icon" data-for="ad_select"></i></div>
												<div class="col-md-9">
													<select name="ad_select" id="ad_select" class="form-control 8023ad">
													<option value="stable">Stable</option>
													<option value="bandwidth">Bandwidth</option>
													<option value="count">Count</option>
													</select>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="ad_select-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the 802.3ad aggregation selection logic to use.")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">All members active<i class="fa fa-question-circle fpbx-help-icon" data-for="all_members_active"></i></div>
												<div class="col-md-9 radioset">
													<input type="radio" name="all_members_active" id="all_members_active_off" value="false"><label for="all_members_active_off">No</label>
													<input type="radio" name="all_members_active" id="all_members_active_on" value="true"><label for="all_members_active_on">Yes</label>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="all_members_active-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies that duplicate frames (received on inactive ports) should be dropped (No, default option and desirable for most users) or delivered (Yes).")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">ARP Link monitoring intervall<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_interval"></i></div>
												<div class='col-md-9'>
													<input type="text" id="arp_interval" name="arp_interval" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="arp_interval-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the ARP link monitoring frequency in milliseconds")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">ARP IP targets<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_ip_targets"></i></div>
												<div class='col-md-9'>
													<input type="text" id="arp_ip_targets" name="arp_ip_targets" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="arp_ip_targets-help" class="help-block fpbx-help-block">
													<?php echo _("Comma-separated list of IP addresses to which ARP requests are sent to monitor the link status of a port. A maximum of 16 addresses is allowed, and only IPv4 addresses are permitted.")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Method to validate ARP replies<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_validate"></i></div>
												<div class="col-md-9">
													<select name="arp_validate" id="arp_validate" class="form-control">
													<option value="active">Active</option>
													<option value="all">All</option>
													<option value="backup">Backup</option>
													<option value="none">None</option>
													</select>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="arp_validate-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies whether or not ARP probes and replies should be validated in any mode that supports arp monitoring, or whether non-ARP traffic should be filtered (disregarded) for link monitoring purposes (<b>Active:</b> Validation is performed only for the active slave. <b>All:</b> Validation is performed for all slaves. <b>Backup:</b> Validation is performed only for backup slaves. <b>None: </b> No validation or filtering is performed.")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">ARP all targets<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_all_targets"></i></div>
												<div class="col-md-9 radioset">
													<input type="radio" name="arp_all_targets" id="arp_all_targets_any" value="any" class="active-backup"><label for="arp_all_targets_any">Any</label>
													<input type="radio" name="arp_all_targets" id="arp_all_targets_all" value="all" class="active-backup"><label for="arp_all_targets_all">All</label>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="arp_all_targets-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the quantity of arp_ip_targets that must be reachable in order for the ARP monitor to consider a slave as being up (this option is only used when ARP Vallidation is configured")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Up delay<i class="fa fa-question-circle fpbx-help-icon" data-for="up_delay"></i></div>
												<div class='col-md-9'>
													<input type="text" id="up_delay" name="up_delay" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="up_delay-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the time, in milliseconds, to wait before enabling a slave after a link recovery has been detected. This option is only valid for the miimon link monitor. The updelay value should be a multiple of the miimon value; if not, it will be rounded down to the nearest multiple. The default value is 0")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Down delay<i class="fa fa-question-circle fpbx-help-icon" data-for="down_delay"></i></div>
												<div class='col-md-9'>
													<input type="text" id="down_delay" name="down_delay" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="down_delay-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the time, in milliseconds, to wait before disabling a slave after a link failure has been detected. This option is only valid for the miimon link monitor. The downdelay value should be a multiple of the miimon value; if not, it will be rounded down to the nearest multiple. The default value is 0")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Fail-over MAC policy<i class="fa fa-question-circle fpbx-help-icon" data-for="fail_over_mac_policy"></i></div>
												<div class="col-md-9 radioset">
													<input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_active" value="active"/><label for="fail_over_mac_policy_active">Active</label>
													<input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_follow" value="follow"/><label for="fail_over_mac_policy_follow">Follow</label>
													<input type="radio" name="fail_over_mac_policy" id="fail_over_mac_policy_none" value="none"/><label for="fail_over_mac_policy_none">None</label>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="fail_over_mac_policy-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies whether active-backup mode should set all slaves to the same MAC address at enslavement (the traditional behavior), or, when enabled, perform special handling of the bond’s MAC address in accordance with the selected policy (<b>Active:</b> The MAC address of the bond should always be the MAC address of the currently active slave. <b>Follow:</b> Sets the MAC address of the bond to be selected normally (normally the MAC address of the first slave added to the bond). <b>None:</b> Sets the MAC addresses of all slaves to the same address (this is the default).")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Gratuitous-ARP<i class="fa fa-question-circle fpbx-help-icon" data-for="gratuitous_arp"></i></div>
												<div class='col-md-9'>
													<input type="text" id="gratuitous_arp" name="gratuitous_arp" class="active-backup form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="gratuitous_arp-help" class="help-block fpbx-help-block">
													<?php echo _("Specify the number of peer notifications (gratuitous ARPs and unsolicited IPv6 Neighbor Advertisements) to be issued after a failover event (valid range from 0 - 255)")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Packets per slave<i class="fa fa-question-circle fpbx-help-icon" data-for="packets_per_member"></i></div>
												<div class='col-md-9'>
													<input type="text" id="packets_per_member" name="packets_per_member" class="balance-rr form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="packets_per_member-help" class="help-block fpbx-help-block">
													<?php echo _("Specify the number of packets to transmit through a slave before moving to the next one. When set to 0 then a slave is chosen at random (valid range from 0 - 65535")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Reselection policy<i class="fa fa-question-circle fpbx-help-icon" data-for="primary_reselect_policy"></i></div>
												<div class="col-md-9 radioset">
													<input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_always" value="always"/><label for="primary_reselect_policy_always">Always</label>
													<input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_better" value="better"/><label for="primary_reselect_policy_better">Better</label>
													<input type="radio" name="primary_reselect_policy" id="primary_reselect_policy_failure" value="failure"/><label for="primary_reselect_policy_failure">Failure</label>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="primary_reselect_policy-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the reselection policy for the primary slave. This affects how the primary slave is chosen to become the active slave when failure of the active slave or recovery of the primary slave occurs (<b>Always:</b> The primary slave becomes the active slave whenever it comes back up. <b>Better:</b> The primary slave becomes the active slave when it comes back up, if the speed and duplex of the primary slave is better than the speed and duplex of the current active slave. <b>Failure:</b>The primary slave becomes the active slave only if the current active slave fails and the primary slave is up.)")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Resend IGMP<i class="fa fa-question-circle fpbx-help-icon" data-for="resend_igmp"></i></div>
												<div class='col-md-9'>
													<input type="text" id="resend_igmp" name="resend_igmp" class="form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="resend_igmp-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the number of IGMP membership reports to be issued after a failover event. One membership report is issued immediately after the failover, subsequent packets are sent in each 200ms interval (valid range 0 (disable) - 255).")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Intervall of learning packets<i class="fa fa-question-circle fpbx-help-icon" data-for="learn_packet_interval"></i></div>
												<div class='col-md-9'>
													<input type="text" id="learn_packet_interval" name="learn_packet_interval" class="balance-tlb balance-alb form-control">
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="learn_packet_interval-help" class="help-block fpbx-help-block">
													<?php echo _("Specifies the number of seconds between instances where the bonding driver sends learning packets to each slaves peer switch")?>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="">
											<div class="row form-group">
												<div class="col-md-3">Primary port<i class="fa fa-question-circle fpbx-help-icon" data-for="primary"></i></div>
												<div class="col-md-9">
													<select name="primary" id="primary" class="active-backup balance-alb balance-tlb form-control">
													</select>
												</div>
											</div>
										</div>
										<div class="">
											<div class="row">
												<div class="col-md-12">
													<span id="primary-help" class="help-block fpbx-help-block">
													<?php echo _("Specify a device, which will be active while it is available")?>
													</span>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<button type='button' class='btn btn-default pull-right' id='del_interface'><?php echo _("Delete Interface Config"); ?></button>
											<button type='button' class='btn btn-default pull-right' id='add_interface'><?php echo _("Add Interface"); ?></button>
										</div>
									</div>
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

<?php
namespace FreePBX\modules;
use splitbrain\PHPArchive\Tar;
/*
 * Class stub for BMO Module class
 * In _Construct you may remove the database line if you don't use it
 * In getActionbar change extdisplay to align with whatever variable you use to decide if the page is in edit mode.
 *
 */

class Systemadmin extends \FreePBX_Helpers implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function install() {
		$spooldir = $this->FreePBX->Config->get('ASTSPOOLDIR');
		$dir = is_dir("$spooldir/packetcapture") || mkdir("$spooldir/packetcapture", 0755, true);
		$this->addCronJob();
	}

	public function uninstall() {
		$sql = "DROP TABLE IF EXISTS `systemadmin_settings`, `systemadmin_logs`, `systemadmin_packetcapture`";

		try {
			$sth = $this->db->prepare($sql);
			return $sth->execute();

		} catch(PDOException $e) {
			return $e->getMessage();
		}
		$spooldir = $this->FreePBX->Config->get('ASTSPOOLDIR');
		$this->deleteDirectory("$spooldir/packetcapture");
	}


	public function doConfigPageInit($page) {}

	public function chownFreepbx () {
		$files = array(
			array('type' => 'rdir',
			'path' => '/var/spool/asterisk/packetcapture',
			'perms' => 0755)
		);
		return $files;
	}

	public function deleteDirectory($str) {
        if (is_file($str)) {
			return unlink($str);
        }
        elseif (is_dir($str)) {
			$scan = glob(rtrim($str, '/').'/*');
			foreach($scan as $index=>$path) {
				deleteAll($path);
			}
			return @rmdir($str);
        }
	}

	public function addCronJob() {
		$this->removeCronJob();
		$this->FreePBX->Job()->addCommand("systemadmin", "updateCaptureTable", "[ -e /usr/local/freepbx/php/updateCaptureTable.php ] && /usr/local/freepbx/php/updateCaptureTable.php", "0 6 * * *");
		$this->FreePBX->Job()->addCommand("systemadmin", "sendCaptureWaring", "[ -e /usr/local/freepbx/php/sendCaptureWaring.php ] && /usr/local/freepbx/php/sendCaptureWaring.php", "0 7 * * *");
		$this->FreePBX->Job()->addCommand("systemadmin", "sendStorageNotifications", "[ -e /usr/local/freepbx/php/sendStorageNotifications.php ] && /usr/local/freepbx/php/sendStorageNotifications.php", "@hourly");
	}

	public function removeCronJob() {
		$this->FreePBX->Job()->remove("systemadmin", "updateCaptureTable");
		$this->FreePBX->Job()->remove("systemadmin", "sendCaptureWaring");
		$this->FreePBX->Job()->remove("systemadmin", "sendStorageNotifications");
	}

	/*
	 * Write log-entry to systemadmin_logs and return mysql_insert_id
	 * Needed to prevent unprivileged users with shell access to call our setuid-binaries directly
	 * @param module-name
	 * @return id of the inserted entry
	 */
	public function write_log($module = '') {
		if ($module == '') {
			//module should not be empty
			return -1;
		}
		$id = 0;
		$module = preg_replace('/[^a-z]*/', '', $module);
		$username = (isset($_SESSION['AMP_user']->username) ? $_SESSION['AMP_user']->username : 'unknown');
		$timestamp = time();
		$sql = "INSERT INTO systemadmin_logs(username, module, timestamp) VALUES('$username', '$module', '$timestamp')";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$id = $this->db->lastInsertId();
		return $id;
	}

	public function delete_log($id = 0) {
		if ($id == 0) {
			return false;
		}
		$id = preg_replace('/[^0-9]*/', '', $id);
		$sql = "DELETE FROM systemadmin_logs WHERE id='$id'";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		return true;
	}

	//get all network interfaces
	public function getInterfaces() {
		$interfaces = array();
		if ($handle = opendir('/sys/class/net/')) {
				while (false !== ($file = readdir($handle))) {
					if ($file == '.' || $file == '..' || $file == 'lo' || $file == 'bonding_masters') {
						continue;
					}
					$interfaces[$file]['name'] = $file;
					$interfaces[$file]['unconfigured'] = true;
					$interfaces[$file]['ipv4_assignment'] = '';
					$interfaces[$file]['ipv4_address'] = '';
					$interfaces[$file]['ipv4_gateway'] = '';
					$interfaces[$file]['ipv6_assignment'] = '';
					$interfaces[$file]['ipv6_address'] = array();
					$interfaces[$file]['ipv6_gateway'] = '';
					$interfaces[$file]['ipv6_accept_ra'] = '1'; //0 = off, 1 = on, 2 = on + forwarding
					$interfaces[$file]['ipv6_autoconf'] = '0'; //0 = off, 1 = on
					$interfaces[$file]['dyn_ipv4_address'] = "";
					$interfaces[$file]['dyn_ipv6_address'] = "";
					$interfaces[$file]['bonding_status'] = 'none';
					$interfaces[$file]['bonding_master'] = '';
					$interfaces[$file]['bond_member'] = array();
					$interfaces[$file]['bond_parameter'] = array();
					if (is_dir('/proc/net/bonding')) {
						exec("/usr/bin/grep -rl $file /proc/net/bonding", $output_is_bonding_slave, $rc_is_bonding_slave);
						if($rc_is_bonding_slave == 0) {
						   $interfaces[$file]['bonding_status'] = 'slave';
						   $interfaces[$file]['bonding_master'] = basename($output_is_bonding_slave[0]);
						}
					}
					if (file_exists('/sys/class/net/bonding_masters')) {
						exec("/usr/bin/grep -q $file /sys/class/net/bonding_masters", $output_is_bonding_master, $rc_is_bonding_master);
						if ($rc_is_bonding_master == 0) {
							$interfaces[$file]['bonding_status'] = 'master';
						}
					}
				}
				return $interfaces;
			}
		}

	public function get_ifupdown_config ($interface) {
		exec("/usr/bin/grep -rl $interface[name] /etc/network/interfaces.d", $output, $rc);
		$i = 0;
		$last_i = 0;
		$last_inserted = ""; //if the adddress is not in cidr format (--> address and netmask are configured in different lines) we need to know what the last entry was (ipv4 or ipv6?)
		if ($rc == 0) { //file for this interface exists in /etc/network/interfaces.d
			$file = fopen("$output[0]", "r") or die("Unable to open file!");
			while(!feof($file)) {
				$line = trim(fgets($file), " \t\x00\v");
				if (str_contains($line, "iface $interface[name] inet static") || str_contains($line, "iface $interface[name] inet manual") || str_contains($line, "iface $interface[name] inet dhcp") || str_contains($line, "iface $interface[name] inet6 static") || str_contains($line, "iface $interface[name] inet6 manual") || str_contains($line, "iface $interface[name] inet6 dhcp") || str_contains($line, "iface $interface[name] inet6 auto")) {
					$interface['unconfigured'] = false;
					if (preg_match("/iface $interface[name] inet\s(.*)/i", $line, $matches)) {
						$interface['ipv4_assignment'] = $matches[1];
					}
					else if (preg_match("/iface $interface[name] inet6\s(.*)/i", $line, $matches)) {
						$interface['ipv6_assignment'] = $matches[1];
					}
				}
				else if (preg_match("/address\s(.*)/i", $line, $matches)) {
					if (str_contains($matches[1], '/')) { //address is configured in CIDR notation
						$temp = explode("/", $matches[1]);
						if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
							$interface['ipv4_address'] = "$temp[0]/$temp[1]";
						}
						else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
							$interface['ipv6_address'][$i] = "$temp[0]/$temp[1]";
							$last_i = $i;
							$i++;
						}
					}
					else {
						if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
							$interface['ipv4_address'] = $matches[1];
							$last_inserted = "ipv4";
						}
						else if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
							$interface['ipv6_address'][$i] = $matches[1];
							$last_inserted = "ipv6";
							$last_i = $i;
							$i++;
						}
					}
				}
				else if (preg_match("/netmask\s(.*)/i", $line, $matches)) {
					if ($last_inserted == ipv4) {
						if (is_numeric($matches[1]) && $matches[1] <= 32) { //netmask is not in dotted-decimal
							$interface['ipv4_address'] .= "/$matches[1]";
						}
						else { //netmask is in dotted-decimal-format so we need to convert it
							$long = ip2long($matches[1]);
							$base = ip2long('255.255.255.255');
							$temp_netmask = 32-log(($long ^ $base)+1,2);
							$interface['ipv4_address'] .= "/$temp_netmask";
						}
					}
					else {
                         $interface['ipv6_address'][$last_i] .= "/$matches[1]";
					}
				}
				else if (preg_match("/gateway\s(.*)/i", $line, $matches)) {
					if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$interface['ipv4_gateway'] = $matches[1];
					}
					else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$interface['ipv6_gateway'] = $matches[1];
					}
				}
				else if (preg_match("/autoconf\s(.*)/i", $line, $matches)) {
					$interface['ipv6_autoconf'] = $matches[1];
				}
				else if (preg_match("/accept_ra\s(.*)/i", $line, $matches)) {
					$interface['ipv6_accept_ra'] = $matches[1];
				}
				else if (preg_match("/bond-slaves\s(.*)/i", $line, $matches)) {
					$interface['bond_member'] = explode(' ', $matches[1]);
				}
				else if (preg_match("/bond-mode\s(.*)/i", $line, $matches)) {
					if ($matches[1] == "balance-rr" || $matches[1] == "0") {
						$interface['bond_parameter']['mode'] = 'balance-rr';
					}
					else if ($matches[1] == "active-backup" || $matches[1] == "1") {
						$interface['bond_parameter']['mode'] = 'active-backup';
					}
					else if ($matches[1] == "balance-xor" || $matches[1] == "2") {
						$interface['bond_parameter']['mode'] = 'balance-xor';
					}
					else if ($matches[1] == "broadcast" || $matches[1] == "3") {
						$interface['bond_parameter']['mode'] = 'broadcast';
					}
					else if ($matches[1] == "802.3ad" || $matches[1] == "4") {
						$interface['bond_parameter']['mode'] = '802.3ad';
					}
					else if ($matches[1] == "balance-tlbr" || $matches[1] == "5") {
						$interface['bond_parameter']['mode'] = 'balance-tlb';
					}
					else if ($matches[1] == "balance-alb" || $matches[1] == "6") {
						$interface['bond_parameter']['mode'] = 'balance-alb';
					}
				}
				else if (preg_match("/bond-lacp-rate\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['lacp_rate'] = $matches[1];
				}
				elseif (preg_match("/bond-miimon\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['mii_monitor_interval'] = $matches[1];
				}
				elseif (preg_match("/bond-min-links\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['min_links'] = $matches[1];
				}
				elseif (preg_match("/bond-xmit-hash-policy\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['transmit_hash_policy'] = $matches[1];
				}
				elseif (preg_match("/bond-ad-select\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['ad_select'] = $matches[1];
				}
				elseif (preg_match("/bond-all-slaves-active\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['all_members_active'] = $matches[1];
				}
				elseif (preg_match("/bond-arp-interval\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['arp_interval'] = $matches[1];
				}
				elseif (preg_match("/bond-arp-ip-target\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['arp_ip_targets'] = $matches[1];
				}
				elseif (preg_match("/bond-arp-validate\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['arp_validate'] = $matches[1];
				}
				elseif (preg_match("/bond-arp-all-targets\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['arp_all_targets'] = $matches[1];
				}
				elseif (preg_match("/bond-updelay\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['up_delay'] = $matches[1];
				}
				elseif (preg_match("/bond-downdelay\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['down_delay'] = $matches[1];
				}
				elseif (preg_match("/fail-over-mac-policy\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['fail_over_mac_policy'] = $matches[1];
				}
				elseif (preg_match("/bond-num-grat-arp\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['gratuitous_arp'] = $matches[1];
				}
				elseif (preg_match("/bond-packets-per-slave\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['packets_per_member'] = $matches[1];
				}
				elseif (preg_match("/bond-primary-reselect\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['primary_reselect_policy'] = $matches[1];
				}
				elseif (preg_match("/bond-resend-igmp\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['resend_igmp'] = $matches[1];
				}
				elseif (preg_match("/bond-lp-interval\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['learn_packet_interval'] = $matches[1];
				}
				elseif (preg_match("/bond-primary\s(.*)/i", $line, $matches)) {
					$interface['bond_parameter']['primary'] = $matches[1];
				}
			}
			fclose($file);
		}
		else { //no file for this interface exists in /etc/network/interfaces.d
			exec("/usr/bin/grep $interface[name] /etc/network/interfaces", $output, $rc);
			if ($rc == 0) { //interface is configured in /etc/network/interfaces
				$ouputLines = false;
				$file = fopen("/etc/network/interfaces", "r") or die("Unable to open file!");
				while(!feof($file)) {
					$line = trim(fgets($file), " \t\x00\v");
					if (str_contains($line, "allow-hotplug $interface[name]") || str_contains($line, "iface $interface[name]")) {
						$ouputLines = true;
					}
					else if (str_contains($line, "allow-hotplug") || str_contains($line, "iface")) {
						$ouputLines = false;
					}
					if ($ouputLines) {
						if (str_contains($line, "iface $interface[name] inet static") || str_contains($line, "iface $interface[name] inet manual") || str_contains($line, "iface $interface[name] inet dhcp") || str_contains($line, "iface $interface[name] inet6 static") || str_contains($line, "iface $interface[name] inet6 manual") || str_contains($line, "iface $interface[name] inet6 dhcp") || str_contains($line, "iface $interface[name] inet6 auto")) {
							$interface['unconfigured'] = false;
							if (preg_match("/iface $interface[name] inet\s(.*)/i", $line, $matches)) {
								$interface['ipv4_assignment'] = $matches[1];
							}
							else if (preg_match("/iface $interface[name] inet6\s(.*)/i", $line, $matches)) {
								$interface['ipv6_assignment'] = $matches[1];
							}
						}
						else if (preg_match("/address\s(.*)/i", $line, $matches)) {
							if (str_contains($matches[1], '/')) { //address is configured in CIDR notation
								$temp = explode("/", $matches[1]);
								if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
									$interface['ipv4_address'] = "$temp[0]/$temp[1]";
								}
								else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
									$interface['ipv6_address'][$i] = "$temp[0]/$temp[1]";
									$last_i = $i;
									$i++;
								}
							}
							else {
								if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
									$interface['ipv4_address'] = $matches[1];
								}
								else if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
									$interface['ipv6_address'][$i] = $matches[1];
									$last_i = $i;
									$i++;
								}
							}
						}
						else if (preg_match("/netmask\s(.*)/i", $line, $matches)) {
							if (str_contains($matches[1], ".") || (is_numeric($matches[1]) && $matches[1] <= 32)) {
								$interface['ipv4_address'] .= "/$matches[1]";
							}
							else {
								$interface['ipv6_address'][$last_i] .= "/$matches[1]";
							}
						}
						else if (preg_match("/gateway\s(.*)/i", $line, $matches)) {
							if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
								$interface['ipv4_gateway'] = $matches[1];
							}
							else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
								$interface['ipv6_gateway'] = $matches[1];
							}
						}
						else if (preg_match("/autoconf\s(.*)/i", $line, $matches)) {
							$interface['ipv6_autoconf'] = $matches[1];
						}
						else if (preg_match("/accept_ra\s(.*)/i", $line, $matches)) {
							$interface['ipv6_accept_ra'] = $matches[1];
						}
						else if (preg_match("/bond-slaves\s(.*)/i", $line, $matches)) {
							$interface['bond_member'] = explode(' ', $matches[1]);
						}
						else if (preg_match("/bond-mode\s(.*)/i", $line, $matches)) {
							if ($matches[1] == "balance-rr" || $matches[1] == "0") {
								$interface['bond_parameter']['mode'] = 'balance-rr';
							}
							else if ($matches[1] == "active-backup" || $matches[1] == "1") {
								$interface['bond_parameter']['mode'] = 'active-backup';
							}
							else if ($matches[1] == "balance-xor" || $matches[1] == "2") {
								$interface['bond_parameter']['mode'] = 'balance-xor';
							}
							else if ($matches[1] == "broadcast" || $matches[1] == "3") {
								$interface['bond_parameter']['mode'] = 'broadcast';
							}
							else if ($matches[1] == "802.3ad" || $matches[1] == "4") {
								$interface['bond_parameter']['mode'] = '802.3ad';
							}
							else if ($matches[1] == "balance-tlbr" || $matches[1] == "5") {
								$interface['bond_parameter']['mode'] = 'balance-tlb';
							}
							else if ($matches[1] == "balance-alb" || $matches[1] == "6") {
								$interface['bond_parameter']['mode'] = 'balance-alb';
							}
						}
						else if (preg_match("/bond-lacp-rate\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['lacp_rate'] = $matches[1];
						}
						elseif (preg_match("/bond-miimon\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['mii_monitor_interval'] = $matches[1];
						}
						elseif (preg_match("/bond-min-links\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['min_links'] = $matches[1];
						}
						elseif (preg_match("/bond-xmit-hash-policy\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['transmit_hash_policy'] = $matches[1];
						}
						elseif (preg_match("/bond-ad-select\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['ad_select'] = $matches[1];
						}
						elseif (preg_match("/bond-all-slaves-active\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['all_members_active'] = $matches[1];
						}
						elseif (preg_match("/bond-arp-interval\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['arp_interval'] = $matches[1];
						}
						elseif (preg_match("/bond-arp-ip-target\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['arp_ip_targets'] = $matches[1];
						}
						elseif (preg_match("/bond-arp-validate\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['arp_validate'] = $matches[1];
						}
						elseif (preg_match("/bond-arp-all-targets\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['arp_all_targets'] = $matches[1];
						}
						elseif (preg_match("/bond-updelay\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['up_delay'] = $matches[1];
						}
						elseif (preg_match("/bond-downdelay\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['down_delay'] = $matches[1];
						}
						elseif (preg_match("/fail-over-mac-policy\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['fail_over_mac_policy'] = $matches[1];
						}
						elseif (preg_match("/bond-num-grat-arp\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['gratuitous_arp'] = $matches[1];
						}
						elseif (preg_match("/bond-packets-per-slave\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['packets_per_member'] = $matches[1];
						}
						elseif (preg_match("/bond-primary-reselect\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['primary_reselect_policy'] = $matches[1];
						}
						elseif (preg_match("/bond-resend-igmp\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['resend_igmp'] = $matches[1];
						}
						elseif (preg_match("/bond-lp-interval\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['learn_packet_interval'] = $matches[1];
						}
						elseif (preg_match("/bond-primary\s(.*)/i", $line, $matches)) {
							$interface['bond_parameter']['primary'] = $matches[1];
						}
					}
				}
				fclose($file);
			}
		}
		exec("/usr/sbin/ip -4 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_4, $rc_ipv4);
		if ($rc_ipv4 == 0) {
			$interface['dyn_ipv4_address'] = '';
			foreach($output_dyn_4 AS $temp) {
				$temp = explode(" ", $temp);
				$interface['dyn_ipv4_address'] .= $temp[5];
			}
		}
		exec("/usr/sbin/ip -6 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_6, $rc_ipv6);
		if ($rc_ipv6 == 0) {
			$interface['dyn_ipv6_address'] = '';
			foreach($output_dyn_6 AS $temp) {
				$temp = explode(" ", $temp);
				$interface['dyn_ipv6_address'] .= "$temp[5], ";
			}
		}
		return $interface;
	}

	public function get_systemd_networkd_config ($interface) {
		$i = 0;
		exec("/usr/bin/grep -l Name=$interface[name] /etc/systemd/network/*.network", $output, $rc);
		if ($rc == 0) {
			$interface['unconfigured'] = false;
			$file = fopen("$output[0]", "r") or die("Unable to open file!");
			while(!feof($file)) {
				$line = trim(fgets($file), " \t\x00\v");
				if (preg_match("/DHCP=(.*)/i", $line, $matches)) {
					if ($matches[1] == "yes") {
						$interface['ipv4_assignment'] = 'dhcp';
						$interface['ipv6_assignment'] = 'dhcp';
					}
					else if ($matches[1] == "ipv4") {
						$interface['ipv4_assignment'] = 'dhcp';
					}
					else if ($matches[1] == "ipv6") {
						$interface['ipv6_assignment'] = 'dhcp';
					}
				}
				else if (preg_match("/Address=(.*)/i", $line, $matches)) {
					$temp = explode("/", $matches[1]);
					if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$interface['ipv4_address'] = "$temp[0]/$temp[1]";
						$interface['ipv4_assignment'] = 'static';
					}
					else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$interface['ipv6_address'][$i] = "$temp[0]/$temp[1]";
						$interface['ipv6_assignment'] = 'static';
						$i++;
					}
				}
				else if (preg_match("/Gateway=(.*)/i", $line, $matches)) {
					if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$interface['ipv4_gateway'] = $matches[1];
					}
					else if (filter_var($temp[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$interface['ipv6_gateway'] = $matches[1];
					}
				}
				else if (preg_match("/IPv6AcceptRA=(.*)/i", $line, $matches)) {
					if ($matches[1] == "true") {
						$interface['ipv6_accept_ra'] = "1";
					}
					else {
						$interface['ipv6_accept_ra'] = "0";
					}
				}
			}
			fclose($file);
			if ($interface['bonding_status'] == "master") {
				exec("/usr/bin/grep -l Bond=$interface[name] /etc/systemd/network/*.network", $output_bond_member, $rc_bond_member);
				if ($rc_bond_member == 0) {
					foreach($output_bond_member AS $member_file) {
						$output_member_name = "";
						$rc_bond_parameter = 0;
						//get interface name of the bond slaves
						exec("/usr/bin/grep Name $member_file | awk -F'=' {'print $2'}", $output_member_name, $rc_member_name);
						if ($rc_member_name == 0) {
							$temp_member[] = $output_member_name[0];
						}
						//networkd configures the Primary interface not in the parameter-file (see below), but in the .network file of the interface
						exec("/usr/bin/grep PrimarySlave $member_file", $output_bond_primary, $rc_bond_primary);
						if ($rc_bond_primary == 0) {
							$bond_primary = explode('=', $output_bond_primary[0]);
							if ($bond_primary[1] == "true") {
								exec("/usr/bin/grep Name $member_file | awk -F'=' {'print $2'}", $output_primary_name, $rc_primary_name);
								if($rc_primary_name == 0) {
									$interface['bond_parameter']['primary'] = $output_primary_name[0];
								}
							}
						}
					}
					$interface['bond_member'] = $temp_member;
				}
				//Get Bond parameter
				exec("/usr/bin/grep -l Name=$interface[name] /etc/systemd/network/*.netdev", $output_bond_parameter, $rc_bond_parameter);
				if($rc_bond_parameter == 0) {
					$parameter_file = fopen("$output_bond_parameter[0]", "r") or die("Unable to open file!");
					while(!feof($parameter_file)) {
						$parameter_line = trim(fgets($parameter_file), " \t\x00\v");
						if (preg_match("/Mode=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['mode'] = $matches[1];
						}
						else if (preg_match("/LACPTransmitRate=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['lacp_rate'] = $matches[1];
						}
						elseif (preg_match("/MIIMonitorSec=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['mii_monitor_interval'] = $matches[1];
							//this parameter is configured in seconds by networkd and a 's' is added to the value, so we have to remove the 's' and we need to convert the value to milliseconds
							rtrim($interface['bond_parameter']['mii_monitor_interval'], 's');
							$interface['bond_parameter']['mii_monitor_interval'] = (int)$interface['bond_parameter']['mii_monitor_interval'] *1000;
						}
						elseif (preg_match("/MinLinks=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['min_links'] = $matches[1];
						}
						elseif (preg_match("/TransmitHashPolicy=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['transmit_hash_policy'] = $matches[1];
						}
						elseif (preg_match("/AdSelect=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['ad_select'] = $matches[1];
						}
						elseif (preg_match("/AllSlavesActive=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['all_members_active'] = $matches[1];
						}
						elseif (preg_match("/ARPIntervalSec=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['arp_interval'] = $matches[1];
							//this parameter is configured in seconds by networkd and a 's' is added to the value, so we have to remove the 's' and we need to convert the value to milliseconds
							rtrim($interface['bond_parameter']['arp_interval'], 's');
							$interface['bond_parameter']['arp_interval'] = (int)$interface['bond_parameter']['arp_interval'] *1000;
						}
						elseif (preg_match("/ARPIPTargets=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['arp_ip_targets'] = str_replace(' ', ',', $matches[1]);
						}
						elseif (preg_match("/ARPValidate=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['arp_validate'] = $matches[1];
						}
						elseif (preg_match("/ARPAllTargets=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['arp_all_targets'] = $matches[1];
						}
						elseif (preg_match("/UpDelaySec=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['up_delay'] = $matches[1];
							//this parameter is configured in seconds by networkd and a 's' is added to the value, so we have to remove the 's' and we need to convert the value to milliseconds
							rtrim($interface['bond_parameter']['up_delay'], 's');
							$interface['bond_parameter']['up_delay'] = (int)$interface['bond_parameter']['up_delay'] *1000;
						}
						elseif (preg_match("/DownDelaySec=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['down_delay'] = $matches[1];
							//this parameter is configured in seconds by networkd and a 's' is added to the value, so we have to remove the 's' and we need to convert the value to milliseconds
							rtrim($interface['bond_parameter']['down_delay'], 's');
							$interface['bond_parameter']['down_delay'] = (int)$interface['bond_parameter']['down_delay'] *1000;
						}
						elseif (preg_match("/FailOverMACPolicy=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['fail_over_mac_policy'] = $matches[1];
						}
						elseif (preg_match("/GratuitousARP=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['gratuitous_arp'] = $matches[1];
						}
						elseif (preg_match("/PacketsPerSlave=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['packets_per_member'] = $matches[1];
						}
						elseif (preg_match("/PrimaryReselectPolicy=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['primary_reselect_policy'] = $matches[1];
						}
						elseif (preg_match("/ResendIGMP=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['resend_igmp'] = $matches[1];
						}
						elseif (preg_match("/LearnPacketIntervalSec=(.*)/i", $parameter_line, $matches)) {
							$interface['bond_parameter']['learn_packet_interval'] = $matches[1];
							//this parameter is configured in seconds by networkd and a 's' is added to the value, so we have to remove the 's' and we need to convert the value to milliseconds
							rtrim($interface['bond_parameter']['learn_packet_interval'], 's');
							$interface['bond_parameter']['learn_packet_interval'] = (int)$interface['bond_parameter']['learn_packet_interval'] *1000;
						}
					}
					fclose($parameter_file);
				}
			}
		}
		exec("/usr/sbin/ip -4 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_4, $rc_ipv4);
		if ($rc_ipv4 == 0) {
			$interface['dyn_ipv4_address'] = '';
			foreach($output_dyn_4 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv4_address'] .= $ip[0];
			}
		}
		exec("/usr/sbin/ip -6 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_6, $rc_ipv6);
		if ($rc_ipv6 == 0) {
			$interface['dyn_ipv6_address'] = '';
			foreach($output_dyn_6 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv6_address'] .= "$ip[0]<br>\n";
			}
		}
		return $interface;
	}

	public function get_nm_config ($interface) {
        if (is_dir("/proc/sys/net/ipv6/conf/$interface[name]")) {
            $interface['ipv6_accept_ra'] = trim(file_get_contents("/proc/sys/net/ipv6/conf/$interface[name]/accept_ra"));
        }
		exec("/usr/bin/nmcli -t -f all device show $interface[name]", $output, $rc);
		if ($rc == 0) {
			$interface['unconfigured'] = false;
			foreach($output AS $line) {
				if (preg_match("/BOND.SLAVES:(.*)/i", $line, $matches)) {
					$interface['bond_member'] = explode(' ', $matches[1]);
				}
				if (preg_match("/GENERAL.CONNECTION:(.*)/i", $line, $matches)) {
					$interface['nm_connection'] = $matches[1];
					exec("/usr/bin/nmcli -t connection show $matches[1]", $connection, $rc_connection);
					if ($rc_connection == 0) {
						foreach($connection AS $con) {
							if (preg_match("/ipv4.method:(.*)/i", $con, $ipv4_method)) {
								$ipv4_method = explode(":", $ipv4_method[0]);
								if ($ipv4_method[1] == "auto") {
									$interface['ipv4_assignment'] = "dhcp";
								}
								else if ($ipv4_method[1] == "manual") {
									$interface['ipv4_assignment'] = "static";
								}
								else {
									$interface['ipv4_assignment'] = "unconfigured";
								}
							}
							else if (preg_match("/ipv6.method:(.*)/i", $con, $ipv6_method)) {
								$ipv6_method = explode(":", $ipv6_method[0]);
								if ($ipv6_method[1] == "dhcp") {
									$interface['ipv6_assignment'] = "dhcp";
									$interface['ipv6_autoconf'] = '0';
								}
								else if ($ipv6_method[1] == "auto") {
									$interface['ipv6_assignment'] = "dhcp";
									$interface['ipv6_autoconf'] = '1';
								}
								else if ($ipv6_method[1] == "manual") {
									$interface['ipv6_assignment'] = "static";
								}
								else {
									$interface['ipv6_assignment'] = "unconfigured";
								}
							}
							else if (preg_match("/ipv4.addresses:(.*)/i", $con, $ipv4_address)) {
								$ipv4_address = explode(":", $ipv4_address[0]);
								$ipv4_address = explode("/", $ipv4_address[1]);
								if (filter_var($ipv4_address[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
									$interface['ipv4_address'] = "$ipv4_address[0]/$ipv4_address[1]";
								}
							}
							else if (preg_match("/ipv6.addresses:(.*)/i", $con, $ipv6_address)) {
								$ipv6_address = explode(":", $ipv6_address[0], 2);
								$ipv6_address = explode(", ", $ipv6_address[1]);
								$i = 0;
								foreach($ipv6_address AS $entry) {
									$entry = explode("/", $entry);
									if (filter_var($entry[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
										$interface['ipv6_address'][$i] = "$entry[0]/$entry[1]";
										$i++;
									}
								}
							}
							else if (preg_match("/ipv4.gateway:(.*)/i", $con, $ipv4_gateway)) {
								$temp = explode(":", $ipv4_gateway[0]);
								if (filter_var($temp[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
									$interface['ipv4_gateway'] = $temp[1];
								}
							}
							else if (preg_match("/ipv6.gateway:(.*)/i", $con, $ipv6_gateway)) {
								$temp = explode(":", $ipv6_gateway[0], 2);
								if (filter_var($temp[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
									$interface['ipv6_gateway'] = $temp[1];
								}
							}
							else if (preg_match("/bond.options:(.*)/i", $con, $bond_options)) {
								$temp_options = explode(":", $bond_options[0]);
								foreach($temp_options AS $b_option) {
									$options = explode(',', $b_option);
									foreach($options AS $option) {
										if (preg_match("/mode=(.*)/i", $option, $matches_option)) {
											if ($matches_option[1] == "balance-rr" || $matches_option[1] == "0") {
												$interface['bond_parameter']['mode'] = 'balance-rr';
											}
											else if ($matches_option[1] == "active-backup" || $matches_option[1] == "1") {
												$interface['bond_parameter']['mode'] = 'active-backup';
											}
											else if ($matches_option[1] == "balance-xor" || $matches_option[1] == "2") {
												$interface['bond_parameter']['mode'] = 'balance-xor';
											}
											else if ($matches_option[1] == "broadcast" || $matches_option[1] == "3") {
												$interface['bond_parameter']['mode'] = 'broadcast';
											}
											else if ($matches_option[1] == "802.3ad" || $matches_option[1] == "4") {
												$interface['bond_parameter']['mode'] = '802.3ad';
											}
											else if ($matches_option[1] == "balance-tlb" || $matches_option[1] == "5") {
												$interface['bond_parameter']['mode'] = 'balance-tlb';
											}
											else if ($matches_option[1] == "balance-alb" || $matches_option[1] == "6") {
												$interface['bond_parameter']['mode'] = 'balance-alb';
											}
										}
										else if (preg_match("/lacp_rate=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['lacp_rate'] = $matches_option[1];
										}
										elseif (preg_match("/miimon=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['mii_monitor_interval'] = $matches_option[1];
										}
										elseif (preg_match("/min_links=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['min_links'] = $matches_option[1];
										}
										elseif (preg_match("/xmit_hash_policy=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['transmit_hash_policy'] = $matches_option[1];
										}
										elseif (preg_match("/ad_select=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['ad_select'] = $matches_option[1];
										}
										elseif (preg_match("/all_slaves_active=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['all_members_active'] = $matches_option[1];
										}
										elseif (preg_match("/arp_interval=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['arp_interval'] = $matches_option[1];
										}
										elseif (preg_match("/arp_ip_target=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['arp_ip_targets'] = $matches_option[1];
										}
										elseif (preg_match("/arp_validate=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['arp_validate'] = $matches_option[1];
										}
										elseif (preg_match("/arp_all_targets=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['arp_all_targets'] = $matches_option[1];
										}
										elseif (preg_match("/updelay=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['up_delay'] = $matches_option[1];
										}
										elseif (preg_match("/downdelay=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['down_delay'] = $matches_option[1];
										}
										elseif (preg_match("/fail_over_mac=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['fail_over_mac_policy'] = $matches_option[1];
										}
										elseif (preg_match("/num_grat_arp=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['gratuitous_arp'] = $matches_option[1];
										}
										elseif (preg_match("/packets_per_slave=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['packets_per_member'] = $matches_option[1];
										}
										elseif (preg_match("/primary_reselect=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['primary_reselect_policy'] = $matches_option[1];
										}
										elseif (preg_match("/resend_igmp=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['resend_igmp'] = $matches_option[1];
										}
										elseif (preg_match("/lp_interval=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['learn_packet_interval'] = $matches_option[1];
										}
										elseif (preg_match("/primary=(.*)/i", $option, $matches_option)) {
											$interface['bond_parameter']['primary'] = $matches_option[1];
										}
									}
								}
							}
						}
					}
				}
			}
			if ($interface['bonding_status'] == "master" && $interface['bond_member'][0] == "") {
                $name = array();
                //This interface is a bond but we were unable to fetch the slaves. So we need to loop through each connection to find the bond-member and update the bonding-status of the meber (this happens in balance-alb)
                exec("/usr/bin/nmcli -t connection show", $output_loop_connections, $rc_loop_connections);
                if ($rc_loop_connections == 0) {
                    foreach($output_loop_connections AS $loop) {
                        $output_lcon = "";
                        $output_lname = "";
                        $rc_lcon = -1;
                        $rc_lname = -1;
                        $lcon = explode(':', $loop);
                        exec("/usr/bin/nmcli -t connection show $lcon[0] | /usr/bin/grep connection.master:$interface[name]", $output_lcon, $rc_lcon);
                        if ($rc_lcon == 0) {
                            exec("/usr/bin/nmcli -t connection show $lcon[0] | /usr/bin/grep connection.interface-name", $output_lname, $rc_lname);
                            if($rc_lname == 0) {
                                $temp_name = explode(':', $output_lname[0]);
                                $name[] = $temp_name[1];
                            }
                        }
                    }
                }
                $interface['bond_member'] = $name;
            }
		}
		exec("/usr/sbin/ip -4 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_4, $rc_ipv4);
		if ($rc_ipv4 == 0) {
			$interface['dyn_ipv4_address'] = '';
			foreach($output_dyn_4 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv4_address'] .= $ip[0];
			}
		}
		exec("/usr/sbin/ip -6 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_6, $rc_ipv6);
		if ($rc_ipv6 == 0) {
			$interface['dyn_ipv6_address'] = '';
			foreach($output_dyn_6 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv6_address'] .= "$ip[0]<br>\n";
			}
		}
		return $interface;
	}

	public function get_netplan_config ($interface) {
		/*if ($interface['bonding_status'] == "slave") {
			return;
		}*/

		//Netplan configuration files are only accessible by root. We need to fetch them by a program which has setuid-bit enabled
		// the 2>&1 at the end of the next line is required to catch any error messages
		if ($interface['bonding_status'] == "none") {
			$type = "ethernets";
		}
		else {
			$type = "bonds";
		}
		exec("/usr/local/freepbx/bin/get_netplan_config --interface $interface[name] --type $type 2>&1", $temp_interface, $rc);
		if ($rc != 0) {
			$err_msg = "";
			foreach($temp_interface AS $line) {
				$err_msg .= "$line\n";
			}
			throw new \Exception("Can't get neplan config for interface $interface[name]: $err_msg");
		}
		else {
			//Check if interface is unconfigured
			exec("/usr/local/freepbx/bin/get_netplan_config --interface $interface[name] --type $type --check_configured 2>&1", $output, $rc_check);
			if ($rc_check != 0) {
				$err_msg = "";
				foreach($temp_interface AS $line) {
					$err_msg .= "$line\n";
				}
			throw new \Exception("Can't get neplan config for interface $interface[name]: $err_msg");
			}
			if ($output == "0") {
				$interface['unconfigured'] = false;
			}
			$i = 0;
			$interface['ipv6_autoconf'] = "1"; //Netplan has no option to configure autoconf;
			foreach($temp_interface AS $line) {
				if (preg_match("/dhcp4:\s(.*)/i", $line, $matches)) {
					if ($matches[1] == "true") {
						$interface['ipv4_assignment'] = "dhcp";
					}
				}
				else if (preg_match("/dhcp6:\s(.*)/i", $line, $matches)) {
					if ($matches[1] == "true") {
						$interface['ipv6_assignment'] = "dhcp";
					}
				}
				else if (preg_match("/ip_address:\s(.*)/i", $line, $matches)) {
					$temp = explode(", ", $matches[1]);
					foreach($temp AS $temp1) {
						$ip_addr = explode("/", $temp1);
						if (filter_var($ip_addr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
							$interface['ipv4_address'] = "$ip_addr[0]/$ip_addr[1]";
							$interface['ipv4_assignment'] = "static";
						}
						else if (filter_var($ip_addr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
							$interface['ipv6_address'][$i] = "$ip_addr[0]/$ip_addr[1]";
							$interface['ipv6_assignment'] = "static";
							$i++;
						}
					}
				}
				else if (preg_match("/routes:\s(.*)/i", $line, $matches)) {
					if (preg_match("/to:default,\svia:(.*)|to:0.0.0.0,\svia:(.*)|to:::\/0,\svia(.*)/i", $matches[1], $gateway_addr)) {
						$gateway_addr = explode(", ", $gateway_addr[1]);
						if (filter_var($gateway_addr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
							$interface['ipv4_gateway'] = $gateway_addr[0];
						}
						else if (filter_var($gateway_addr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
							$interface['ipv6_gateway'] = $gateway_addr[0];
						}
					}
				}
				else if (preg_match("/accept_ra:\s(.*)/i", $line, $matches)) {
					if ($matches[1] == "false") {
						$interface['ipv6_accept_ra'] = 0;
					}
					else {
						$interface['ipv6_accept_ra'] = 1;
					}
				}
				else if (preg_match("/interfaces:\s(.*)/i", $line, $matches)) {
					$interface['bond_member'] = explode(', ', $matches[1]);
				}
				else if (preg_match("/parameters:\s(.*)/i", $line, $matches)) {
					$parameters = explode('; ', $matches[1]);
					foreach($parameters AS $parameter) {
						$param = explode(':', $parameter);
						if ($param[0] == 'mode') {
							$interface['bond_parameter']['mode'] = $param[1];
						}
						elseif ($param[0] == 'lacp-rate') {
							$interface['bond_parameter']['lacp_rate'] = $param[1];
						}
						elseif ($param[0] == 'mii-monitor-interval') {
							$interface['bond_parameter']['mii_monitor_interval'] = $param[1];
						}
						elseif ($param[0] == 'min-links') {
							$interface['bond_parameter']['min_links'] = $param[1];
						}
						elseif ($param[0] == 'transmit-hash-policy') {
							$interface['bond_parameter']['transmit_hash_policy'] = $param[1];
						}
						elseif ($param[0] == 'ad-select') {
							$interface['bond_parameter']['ad_select'] = $param[1];
						}
						elseif ($param[0] == 'all-members-active') {
							$interface['bond_parameter']['all_members_active'] = $param[1];
						}
						elseif ($param[0] == 'arp-interval') {
							$interface['bond_parameter']['arp_interval'] = $param[1];
						}
						elseif ($param[0] == 'arp-ip-targets') {
							$interface['bond_parameter']['arp_ip_targets'] = str_replace(' -', '', $param[1]);
						}
						elseif ($param[0] == 'arp-validate') {
							$interface['bond_parameter']['arp_validate'] = $param[1];
						}
						elseif ($param[0] == 'arp-all-targets') {
							$interface['bond_parameter']['arp_all_targets'] = $param[1];
						}
						elseif ($param[0] == 'up-delay') {
							$interface['bond_parameter']['up_delay'] = $param[1];
						}
						elseif ($param[0] == 'down-delay') {
							$interface['bond_parameter']['down_delay'] = $param[1];
						}
						elseif ($param[0] == 'fail-over-mac-policy') {
							$interface['bond_parameter']['fail_over_mac_policy'] = $param[1];
						}
						elseif ($param[0] == 'gratuitous-arp') {
							$interface['bond_parameter']['gratuitous_arp'] = $param[1];
						}
						elseif ($param[0] == 'packets-per-member') {
							$interface['bond_parameter']['packets_per_member'] = $param[1];
						}
						elseif ($param[0] == 'primary-reselect-policy') {
							$interface['bond_parameter']['primary_reselect_policy'] = $param[1];
						}
						elseif ($param[0] == 'resend-igmp') {
							$interface['bond_parameter']['resend_igmp'] = $param[1];
						}
						elseif ($param[0] == 'learn-packet-interval') {
							$interface['bond_parameter']['learn_packet_interval'] = $param[1];
						}
						elseif ($param[0] == 'primary') {
							$interface['bond_parameter']['primary'] = $param[1];
						}
					}
				}
			}

		}
		exec("/usr/sbin/ip -4 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_4, $rc_ipv4);
		if ($rc_ipv4 == 0) {
			$interface['dyn_ipv4_address'] = '';
			foreach($output_dyn_4 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv4_address'] .= $ip[0];
			}
		}
		exec("/usr/sbin/ip -6 address show dev $interface[name] | grep inet | grep dynamic", $output_dyn_6, $rc_ipv6);
		if ($rc_ipv6 == 0) {
			$interface['dyn_ipv6_address'] = '';
			foreach($output_dyn_6 AS $temp) {
				$temp = explode(" ", $temp);
				$ip = explode("/", $temp[5]);
				$interface['dyn_ipv6_address'] .= "$ip[0]<br>\n";
			}
		}
		return $interface;
	}

    //check if interface is managed by ifupdown()
    public function check_ifupdown ($interface) {
		$ifupdown = false;
        if (file_exists('/run/network/ifstate')) {
			$file = file_get_contents('/run/network/ifstate');
            if (str_contains($file, $interface['name'])) {
				$ifupdown = true;
            }
            return $ifupdown;
		}
	}

    //check if interface is managed by netplan.io
    public function check_netplan ($interface) {
        $netplan = false;
        if (is_dir('/etc/netplan') && !$this->check_ifupdown ($interface)) {
            $netplan = true;
        }
        return $netplan;
    }

    //check if interface is managed by NetworkManager
    public function check_networkManager ($interface) {
        $nm = false;
        exec("/usr/bin/systemctl status NetworkManager", $output, $rc);
        if($rc == 0 && !$this->check_ifupdown ($interface) && !$this->check_netplan ($interface)) {
            $nm = true;
        }
        return $nm;
    }

    //check if interface is managed by systemd-networkd
    public function check_systemd_networkd ($interface) {
        $systemd = false;
        exec("/usr/bin/systemctl status systemd-networkd", $output, $rc);
        if($rc == 0 && !$this->check_ifupdown ($interface) && !$this->check_netplan ($interface)) {
            $systemd = true;
        }
        return $systemd;
    }

	//This shows the submit buttons
	public function getActionBar($request) {
		$buttons = array();
		switch($_GET['display']) {
			case 'systemadmin':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($_GET['extdisplay'])) {
					unset($buttons['delete']);
				}
			break;
		}
		return $buttons;
	}

	public function showPage($view){
		$view = __DIR__."/views/$view.php";
		if (!file_exists($view)) {
			throw new \Exception("Can't find page $view");
		}
		echo load_view($view);
		return load_view(__DIR__."/views/rnav.php");
	}
	public function ajaxRequest($command, &$setting) {
		switch ($command) {
			case 'powermgmt':
			case 'localdownload':
			case 'localdelete':
			case 'localstop':
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	private function FetchPacketCaptureById($id) {
		$packetcapture = array();
		$sql = "SELECT date FROM systemadmin_packetcapture WHERE id = '$id'";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$packetcapture = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $packetcapture;
	}

	private function getCapturePath ($id) {
		$data = $this->FetchPacketCaptureById($id);
		$date = explode(" ", $data['date']);
		$temp = explode("-", $date[0]);
		$temp1 = explode(":", $date[1]);
		$year = $temp[0];
		$month = $temp[1];
		$day = $temp[2];
		$hour = $temp1[0];
		$min = $temp1[1];
		$sec = $temp1[2];
		$capture = "/var/spool/asterisk/packetcapture/$day-$month-$year"."_$hour-$min-$sec/";
		return $capture;
	}

	private function getCapturePid($id) {
		$pid = -1;
		$capture = $this->getCapturePath($id);
		exec("/usr/bin/ps -ef | /usr/bin/grep $capture | /usr/bin/grep -v grep | /usr/bin/awk -F' ' {'print $2'}", $pid_output, $rc);
		if (array_key_exists(0, $pid_output)) {
			$pid = $pid_output[0];
		}
		return $pid;
	}

	private function preparecapturedownload($id) {
		$capture = $this->getCapturePath($id);
		$dirname = basename($capture);
		$tar = new Tar();
		$tar->create("/tmp/$dirname.tar.gz");
        $tar->addFile("$capture", "$dirname");
		if ($handle = opendir("$capture")) {
			while (false !== ($file = readdir($handle))) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				exec("cp $capture/$file /tmp");
				$tar->addFile("/tmp/$file", "$dirname/$file");
				exec("rm /tmp/$file");
			}
		}
		$tar->close();
		$tarfile = "/tmp/$dirname.tar.gz";
		return $tarfile;
	}

	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'powermgmt':
				switch ($_REQUEST['action']) {
					case 'reboot':
						exec("/usr/local/freepbx/bin/powermgmt reboot 2>&1 ", $output, $rc);
						return $rc;
					break;
					case 'poweroff':
						exec("/usr/local/freepbx/bin/powermgmt shutdown 2>&1 ", $output, $rc);
						return $rc;
					break;
					default:
						return false;
					break;
				}
			break;
			case 'localdownload':
				if (empty($_REQUEST['id']) || !preg_match("/^[0-9]+$/", $_REQUEST['id'])) {
					return false;
				}
				$id = $_REQUEST['id'];
				$path = $this->preparecapturedownload($id);
				header("Content-disposition: attachment; filename=".basename((string) $path));
				header("Content-type: application/octet-stream");
				readfile($path);
				system("rm $path");
				exit;
			break;
			case 'localdelete':
				if (empty($_REQUEST['id']) || !preg_match("/^[0-9]+$/", $_REQUEST['id'])) {
					return false;
				}
				$id = $_REQUEST['id'];
				$pid = $this->getCapturePid($id);
				$path = $this->getCapturePath($id);
				exec("/usr/local/freepbx/bin/packet_capture deletecapture $pid $path 2>&1");
				$sql = "DELETE FROM systemadmin_packetcapture WHERE id = '$id'";
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetch(\PDO::FETCH_ASSOC);
				header('Location: /admin/config.php?display=systemadmin&view=packetcapture&tab=jobs');
				exit;
			break;
			case 'localstop':
				if (empty($_REQUEST['id']) || !preg_match("/^[0-9]+$/", $_REQUEST['id'])) {
					return false;
				}
				$id = $_REQUEST['id'];
				$pid = $this->getCapturePid($id);
				exec("/usr/local/freepbx/bin/packet_capture stopcapture $pid 2>&1");
				$sql = "UPDATE systemadmin_packetcapture SET stopped='yes' WHERE id = '$id'";
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetch(\PDO::FETCH_ASSOC);
				header('Location: /admin/config.php?display=systemadmin&view=packetcapture&tab=jobs');
				exit;
			break;


			default:
				return false;
			break;
		}
	}
}

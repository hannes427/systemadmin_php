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

	//get all network interfaces
	public function getInterfaces() {
		$interfaces = array();
		if ($handle = opendir('/sys/class/net/')) {
				while (false !== ($file = readdir($handle))) {
					if ($file == '.' || $file == '..' || $file == 'lo') {
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
		exec("/usr/bin/grep -l $interface[name] /etc/systemd/network/*.network", $output, $rc);
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
		$interface['ipv6_accept_ra'] = trim(file_get_contents("/proc/sys/net/ipv6/conf/$interface[name]/accept_ra"));
		exec("/usr/bin/nmcli -t device show $interface[name]", $output, $rc);
		if ($rc == 0) {
			$interface['unconfigured'] = false;
			foreach($output AS $line) {
				if(preg_match("/GENERAL.CONNECTION:(.*)/i", $line, $matches)) {
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
										$interface['ipv6_address'][$i] = "$entry[0]/$entry[1]; $entry[1]";
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

	public function get_netplan_config ($interface) {
		//Netplan configuration files are only accessible by root. We need to fetch them by a program which has setuid-bit enabled
		// the 2>&1 at the end of the next line is required to catch any error messages
		exec("/usr/local/freepbx/bin/get_netplan_config --interface $interface[name] 2>&1", $temp_interface, $rc);
		if ($rc != 0) {
			$err_msg = "";
			foreach($temp_interface AS $line) {
				$err_msg .= "$line\n";
			}
			throw new \Exception("Can't get neplan config for interface $interface[name]: $err_msg");
		}
		else {
			//Check if interface is unconfigured
			exec("/usr/local/freepbx/bin/get_netplan_config --interface $interface[name] --check_configured 2>&1", $output, $rc_check);
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

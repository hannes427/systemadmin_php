<?php
if(isset($_POST['set_dns']) && $_POST['set_dns'] == "true") {
  $dns_servers = array();
  $dns_search = array();
  $arguments = "--managed-by $_POST[managed_by]";
  if (array_key_exists('dns_server', $_POST) && $_POST['dns_server'] != "") {
    $dns_servers = explode("\n", $_POST['dns_server']);
  }
  if (array_key_exists('dns_search', $_POST) && $_POST['dns_search'] != "") {
    $dns_search = explode("\n", $_POST['dns_search']);
  }

  //Vallidation
  $error = false;
  $i = 1;
  $len = count($dns_servers);
  if($len != 0) {
    $arguments .= " --dns-server ";
    foreach($dns_servers AS $dns_server) {
      $dns_server = trim($dns_server);
      if (!filter_var($dns_server, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($dns_server, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $error = true;
      }
      else {
        $arguments .= "$dns_server";
        if ($i < $len) {
          $arguments .= ",";
          $i++;
        }
      }
    }
  }
  $j = 1;
  $len1 = count($dns_search);
  if($len1 != 0) {
    $arguments .= " --search ";
    foreach($dns_search AS $search) {
      $search = trim($search);
      if (!preg_match("/^[a-z0-9]+[a-z0-9\.\-_]+[a-z0-9]+/i", $search)) {
        $error = true;
      }
      else {
        $arguments .= "$search";
        if ($j < $len1) {
          $arguments .= ",";
          $j++;
        }
      }
    }
  }

  if (!$error) {
    exec("/usr/local/freepbx/bin/change_dns $arguments 2>&1", $output, $rc);
      if ($rc != 0) {
        $err_msg = "";
        foreach($output AS $line) {
          $err_msg .= "$line\n";
        }
        throw new \Exception("Can't update naetwork config: $err_msg");
      }
    foreach($output AS $line) {
      echo "$line<br>";
    }
    echo "Ja<br><br>$arguments";
  }
  else {
    echo "Nein<br><br>$arguments";
  }

  print_r($_POST);
  echo "<br><br><br>";
}
$nameservers = array();
$search = array();
if (is_link("/etc/resolv.conf")) {
  $resolv_conf = readlink("/etc/resolv.conf");
  if ($resolv_conf == "../run/resolvconf/resolv.conf") {
    $file = file_get_contents("/run/resolvconf/resolv.conf");
    $file = explode("\n", $file);
    foreach($file AS $tempfile) {
      if (preg_match("/^nameserver\s/", $tempfile)) {
        $temp = explode(" ", $tempfile);
        $nameservers[] = $temp[1];
      }
      elseif (preg_match("/^search\s|domain\s/", $tempfile)) {
        $temp = explode(" ", $tempfile);
        foreach($temp AS $t) {
          if ($t != "search" && $t != "domain") {
            $search[] = $t;
          }
        }
      }
    }
  }
  else if ($resolv_conf == "../run/systemd/resolve/stub-resolv.conf") {
    exec("/usr/bin/resolvectl status", $output_dns_server);
    echo "<br>br><br>";
    print_r($output_dns_server);
    echo "<br><br><br>";
    foreach($output_dns_server AS $line) {
      if (preg_match("/DNS Servers:\s(.*)$/", $line, $matches)) {
        $temp_server = explode(" ", $matches[1]);
        foreach($temp_server AS $server) {
          if (!in_array($server, $nameservers)) {
            $nameservers[] = $server;
          }
        }
      }
      else if (preg_match("/DNS Domain:\s(.*)$/", $line, $matches)) {
        $temp_search = explode(" ", $matches[1]);
        foreach($temp_search AS $t) {
          if (!in_array($t, $search)) {
            $search[] = $t;
          }
        }
      }
    }
  }
}
else {
  $file = file_get_contents("/etc/resolv.conf");
  $file = explode("\n", $file);
  foreach($file AS $tempfile) {
    if (preg_match("/^nameserver\s/", $tempfile)) {
      $temp = explode(" ", $tempfile);
      $nameservers[] = $temp[1];
    }
    elseif (preg_match("/^search\s|domain\s/", $tempfile)) {
      $temp = explode(" ", $tempfile);
      foreach($temp AS $t) {
          if ($t != "search" && $t != "domain") {
                  $search[] = $t;
          }
      }
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
    $interfaces[$interface['name']] = $interface;
}

?>
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
			<div class="col-md-3">DNS server</div>
  <div class='col-md-4'><textarea id="dns_server" name="dns_server" rows="5" cols="31" class="form-control"><?php
  $k = 1;
  $len2 = count($nameservers);
  foreach($nameservers AS $nameserver) {
    echo "$nameserver";
    if ($k < $len2) {
      echo "&#13;&#10;";
      $k++;
    }
  }
  ?></textarea></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3">DNS search list</div>
   <div class='col-md-4'><textarea id="dns_search" name="dns_search" rows="5" cols="31" class="form-control"><?php
  $l = 1;
  $len3 = count($search);
  foreach($search AS $search) {
    echo "$search";
    if ($l < $len3) {
      echo "&#13;&#10;";
      $l++;
    }
  }
  ?></textarea></div>
  </div></div></div>
<input type="hidden" name="set_dns" value="true">
<?php
echo "<input type=\"hidden\" name=\"managed_by\" value=\"$managed_by\">\n";
?>
</div>
</div>
</div>
</form>
</div>

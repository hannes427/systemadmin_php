<?php
if(isset($_POST['packetcapture']) && $_POST['packetcapture'] == "true") {
    $arguments = "";
    if ($_POST['interface'] != "all") {
        $arguments .= " -i $_POST[interface]";
        $interface = $_POST['interface'];
    }
    if ($_POST['maxsize'] != "") {
        $arguments .= " -C $_POST[maxsize]";
        $maxsize = $_POST['maxsize'];
    }
    if ($_POST['maxfilecount'] != "") {
        $arguments .= " -W $_POST[maxfilecount]";
        $maxfilecount = $_POST['maxfilecount'];
    }
    if ($_POST['count'] != "") {
        $arguments .= " -c $_POST[count]";
        $count = $_POST['count'];
    }
    if ($_POST['ip_version'] != "all") {
        $arguments .= " --ip_version $_POST[ip_version]";
        $ip_version = $_POST['ip_version'];
    }
    if ($_POST['protocol'] != "all") {
        $arguments .= " --protocol $_POST[protocol]";
        $protocol = $_POST['protocol'];
     }
    if ($_POST['host_address'] != "") {
        $arguments .= " --host_address $_POST[host_address]";
        $host_address = $_POST['host_address'];
    }
    if ($_POST['port'] != "") {
        $arguments .= " --port $_POST[port]";
        $port = $_POST['port'];
    }
    //Vallidation
    $error = false;
    $err = "";
    if(isset($interface) && !preg_match("/^[a-z]+[0-9]{1,3}$/i", $_POST['interface'])) {
        $error = true;
    }
    if(isset($maxsize) && !preg_match("/^[0-9]+$/", $_POST['maxsize'])) {
         $error = true;
    }
    if (isset($maxfilecount) && (!preg_match("/^[0-9]{1,2}$/", $_POST['maxfilecount']) || $_POST['maxsize'] == "")) {
        $error = true;
    }
    if (isset($count) && !preg_match("/^[0-9]+$/", $_POST['count'])) {
        $error = true;
    }
    if (isset($ip_version) && ($_POST['ip_version'] != "ip" && $_POST['ip_version'] != "ip6")) {
        $error = true;
    }
    if (isset($protocol) && ($_POST['protocol'] != "tcp" && $_POST['protocol'] != "udp" && $_POST['protocol'] != "icmp" && $_POST['protocol'] != "arp")) {
        $error = true;
    }
    if (isset($host_address) && !preg_match("/^[a-z0-9.\-_]+$/i", $_POST['host_address'])) {
        $error = true;
    }
    if (isset($port) && !preg_match("/^[0-9]+$/", $_POST['port'])) {
        $error = true;
    }
    if (!$error) {
        $date = date("d-m-Y_H-i-s");
        $dir = is_dir("/var/spool/asterisk/packetcapture/".$date) || mkdir("/var/spool/asterisk/packetcapture/".$date, 0755, true);
        $arguments .= " --path /var/spool/asterisk/packetcapture/$date";
        exec("/usr/local/freepbx/bin/packet_capture startcapture $arguments 2>&1", $output, $rc);
        if ($rc != 0) {
            rmdir("/var/spool/asterisk/packetcapture/".$date);
            $err_msg = "";
            foreach($output AS $line) {
                $err_msg .= "$line\n";
            }
            throw new \Exception("Can't start capture: $err_msg");
        }
        else {
            $date = explode("_", $date);
            $temp = explode("-", $date[0]);
            $temp1 = explode("-", $date[1]);
            $day = $temp[0];
            $month = $temp[1];
            $year = $temp[2];
            $hour = $temp1[0];
            $min = $temp1[1];
            $sec = $temp1[2];
            $dbh = \FreePBX::Database();
            $sql = "INSERT INTO systemadmin_packetcapture (date, stopped) VALUES('$year-$month-$day $hour:$min:$sec', 'no')";
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }
}
$interfaces = FreePBX::Systemadmin()->getInterfaces();
ksort($interfaces);
?>
<form method="post" class="fpbx-submit" id="captureform">
					<div class="display full-border">
						<div class='container-fluid'>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Listen on interface");?></div>
  <div class="col-md-9"><select name="interface" id="interface" class="form-control"><option value="all" selected="selected">All</option>
  <?php
  foreach($interfaces AS $interface) {
    echo "<option value=\"$interface[name]\">$interface[name]</option>\n";
  }
  ?></select></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("IP version");?></div>
  <div class="col-md-9"><select name="ip_version" id="ip_version" class="form-control">
  <option value="all" selected="selected"><?php echo _("All");?></option>
  <option value="ip"><?php echo _("IPv4");?></option>
  <option value="ip6"><?php echo _("IPv6");?></option>
  </select></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Protocol");?></div>
  <div class="col-md-9"><select name="protocol" id="protocol" class="form-control">
  <option value="all" selected="selected"><?php echo _("All");?></option>
  <option value="tcp">TCP</option>
  <option value="udp">UDP</option>
  <option value="icmp">ICMP</option>
  <option value="arp">ARP</option>
  </select></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Host");?></div>
  <div class="col-md-9"><input type="text" id="host_address" name="host_address" class="form-control"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Port");?></div>
  <div class="col-md-9"><input type="text" id="port" name="port" class="form-control"></div>
  </div></div></div>

  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Max file size");?></div>
  <div class="col-md-9"><input type="text" id="maxsize" name="maxsize" class="form-control"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Max file count");?></div>
  <div class="col-md-9"><input type="text" id="maxfilecount" name="maxfilecount" class="form-control"></div>
  </div></div></div>
  <div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Packet Count");?></div>
  <div class="col-md-9"><input type="text" id="count" name="count" class="form-control"></div>
  </div></div></div>
  </div>
</div>
<input type="hidden" name="packetcapture" value="true">
</form>

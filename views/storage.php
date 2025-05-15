<link href="modules/systemadmin/assets/css/storage.css" rel="stylesheet" type="text/css" />
<?php
if (isset($_POST['set_storage']) && $_POST['set_storage'] == "true") {
    $error_monitor = false;
    $json_storage_settings = "";
    $array_storage_settings1 = array();
    $array_storage_settings2 = array();
    $valid_freqency = array("hourly", "daily", "weekly", "monthly");
    if (isset($_POST['storage_threshold_1']) && $_POST['storage_threshold_1'] != "") {
        if (is_numeric($_POST['storage_threshold_1']) && in_array($_POST['storage_email_frequency_1'], $valid_freqency)) {
            $array_storage_settings1 = array("threshold_1" => $_POST['storage_threshold_1'], "email_frequency_1" => $_POST['storage_email_frequency_1']);
        }
    }
    if (isset($_POST['storage_threshold_2']) && $_POST['storage_threshold_2'] != "") {
        if (is_numeric($_POST['storage_threshold_2']) && in_array($_POST['storage_email_frequency_2'], $valid_freqency)) {
            $array_storage_settings2 = array("threshold_2" => $_POST['storage_threshold_2'], "email_frequency_2" => $_POST['storage_email_frequency_2']);
        }
    }
    $array_storage_settings = array_merge($array_storage_settings1, $array_storage_settings2);
    if (count($array_storage_settings) != "0") {
        $json_storage_settings = json_encode($array_storage_settings);
    }
    $json_monitor = "";
    if (isset($_POST['monitor'])) {
        foreach($_POST['monitor'] AS $monitor_device) {
            if (!preg_match("/^\/[a-z0-9\/_\-\.]*$/i", $monitor_device)) {
                $error_monitor = true;
            }
        }
        if (!$error_monitor) {
            $json_monitor = json_encode($_POST['monitor']);
        }
    }
    $dbh = \FreePBX::Database();
    if ($json_monitor != "") {
        $sql = "INSERT INTO systemadmin_settings (`key`, value) VALUES('monitor_devices', '$json_monitor') ON DUPLICATE KEY UPDATE value='$json_monitor'";
    }
    else {
        $sql = "DELETE FROM systemadmin_settings WHERE `key` = 'monitor_devices'";
    }
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    if ($json_storage_settings != "") {
        $sql1 = "INSERT INTO systemadmin_settings (`key`, value) VALUES('storage_settings', '$json_storage_settings') ON DUPLICATE KEY UPDATE value='$json_storage_settings'";
    }
    else {
        $sql1 = "DELETE FROM systemadmin_settings WHERE `key` = 'storage_settings'";
    }
    $stmt1 = $dbh->prepare($sql1);
    $stmt1->execute();
}
$email = systemadminFetchEmail();
if (array_key_exists('storageemail', $email)) {
    $storageemail = $email['storageemail'];
}
else {
    $storageemail = "";
}
$storagesettings = systemadminFetchStorageConfig();
$monitor_disks = array();
$dbh = \FreePBX::Database();
$sql = 'SELECT value FROM systemadmin_settings WHERE `key` = \'monitor_devices\'';
$stmt = $dbh->prepare($sql);
$stmt->execute();
if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
    $monitor_disks = json_decode($result['value'], true);
}
$disks = array();
$raids = array();
$raid_configured = false;
exec("/usr/bin/df -Tm | grep ^/ | awk -v OFS=' ' '{print $1, $2, $3, $4, $6, $7}'", $output_disks, $output_disks_rc);
if ($output_disks_rc == 0) {
    $i = 0;
    foreach($output_disks AS $line) {
        $disk = explode(" ", $line);
        $disks[$i]['device'] = $disk[0];
        $disks[$i]['fstype'] = $disk[1];
        $disks[$i]['size'] = $disk[2];
        $disks[$i]['used_space'] = $disk[3];
        $disks[$i]['percentage'] = $disk[4];
        $disks[$i]['mountpoint'] = $disk[5];
        if (in_array($disk[5], $monitor_disks)) {
            $disks[$i]['checked'] = " checked=\"checked\"";
        }
        else {
            $disks[$i]['checked'] = "";
        }
        $i++;
    }
}
if (is_dir('/dev/md/') && $handle = opendir('/dev/md/')) {
    while (false !== ($file = readdir($handle))) {
        $output_raid = "";
        $output_raid_rc = "";
        if ($file == '.' || $file == '..') {
            continue;
        }
        $raid =  basename(readlink("/dev/md/".$file));
        exec("/usr/local/freepbx/bin/get_raid --device /dev/$raid --raid_type mdadm", $output_raid, $output_raid_rc);
        if ($output_raid_rc == 0) {
            $raid_configured = true;
            $raids[] = json_decode($output_raid[0], true);
        }
    }
}
?>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
			<form method="post" class="fpbx-submit" id="storage">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
<div class="panel panel-default">
<div class="row outer">
<div class="col-md-6">
<h3><?php echo _("RAID");?></h3>
<?php
if (count($raids) == 0) {
    echo _("No Raid devices found.");
}
else {
    foreach($raids AS $raid) {
        echo "<div class=\"panel panel-default\">";
        echo "<div class=\"panel-heading\"><h4><i class=\"fa fa-database\"></i>&nbsp;$raid[device] ($raid[raid_level])";
        if ($raid['state'] == "clean") {
        echo "<span class=\"label label-success\">Clean</span>";
        }
        else if ($raid['state'] == "active") {
            echo "<span class=\"label label-success\">Active</span>";
        }
        else if ($raid['state'] == "clean, resyncing") {
            echo "<span class=\"label label-warning\">Resyncing";
            if (array_key_exists("rebuild_status_percent", $raid)) {
                echo " &nbsp;($raid[rebuild_status_percent] %)";
            }
            echo "</span>";
        }
        else if ($raid['state'] == "clean, degraded, recovering") {
            echo "<span class=\"label label-warning\">Recovering";
            if (array_key_exists("rebuild_status_percent", $raid)) {
                echo " &nbsp;($raid[rebuild_status_percent] %)";
            }
            echo "</span>";
        }
        else if ($raid['state'] == "clean, degraded") {
            echo "<span class=\"label label-danger\">Degraded</span>";
        }
        echo "</h4></div>";
        echo "<div class=\"panel-body\">";
        foreach($raid['device_table'] AS $raid_disk) {
            echo "<i class=\"fa fa-hdd-o\"></i>&nbsp;";
            if ($raid_disk['device'] == "") {
                echo "Unknown&nbsp;<span class=\"label label-danger\">Removed&nbsp;</span>";
            }
            else {
                echo "$raid_disk[device]&nbsp;";
                if ($raid_disk['state'][0] == "faulty") {
                    echo "<span class=\"label label-danger\">Failed&nbsp;</span>";
                }
                else if ($raid_disk['state'][0] == "active") {
                    echo "<span class=\"label label-success\">OK&nbsp;</span>";
                }
                else if ($raid_disk['state'][0] == "spare") {
                    if (array_key_exists(1, $raid_disk['state']) && $raid_disk['state'][1] == "rebuilding") {
                        echo "<span class=\"label label-warning\">Spare, rebuilding&nbsp;";
                    }
                    else {
                        echo "<span class=\"label label-success\">Spare&nbsp;";
                    }
                echo "</span>";
                }
            }
            echo "<br>";
        }
        echo "</div>";
        echo "</div>";
    }
}
    ?>
</div>
<div class="col-md-6">
<div>
<h3><?php echo _("Disks");?></h3>
<div class="display full-border">
<div class="col-md-20">
<?php
$i = 1;
foreach($disks AS $disk) {
    echo "<div class=\"panel panel-default\">";
    echo "<div class=\"panel-heading\"><input type=\"checkbox\" name=\"monitor[]\" value=\"$disk[mountpoint]\" id=\"monitor_$disk[mountpoint]\"$disk[checked]><label for=\"monitor_$disk[mountpoint]\">$disk[mountpoint]</label> ($disk[device], $disk[fstype])<div class=\"pull-right\">$disk[used_space] MB / $disk[size] MB ($disk[percentage])</div></div>";
    echo "<div class=\"panel-body\">";
    echo "<meter id=\"meter$i\" min=\"0\" low=\"0\" max=\"100\" value=\"".substr_replace($disk['percentage'], '', -1)."\">$disk[used_space] MB / $disk[size] MB ($disk[percentage])</meter>";
    echo "</div>";
    echo "</div>";
    $i++;
}
?>
</div></div>
</div></div></div></div>
<div class="element-container">
	<div class="">
		<div class="row form-group">
			<div class="col-md-3"><?php echo _("Storage e-mail address");?></div>
  <div class="col-md-9">
  <?php
  if(array_key_exists('storageemail', $email) && $email['storageemail'] != "") {
      echo "$email[storageemail]";
  }
  else {
      echo _("Warning! No e-mail address set. Please set the storage e-mail address under <a href=\"config.php?display=systemadmin&view=notifications\">Notifications Config</a>");
  }
  ?>
  </div>
  </div>
  </div></div>
<div class="element-container">
		<div class="row">
<div class="col-md-6">
    <div class="row">
    <div class="col-md-3">
    <?php echo _("Storage Threshold 1");?></div>
    <div class="col-md-3">
    <input type="number" name="storage_threshold_1" class="form-control" id="storage_threshold_1" min="1" max="99"
    <?php
    if (array_key_exists("threshold_1", $storagesettings)) {
        echo " value=\"$storagesettings[threshold_1]\"";
    }
    ?>
    >
    </div>
</div>
</div>
<div class="col-md-6">
    <div class="row">
    <div class="col-md-3">
    <?php echo _("E-Mail Frequency 1");?></div>
    <div class="col-md-3">
    <select class="form-control" id="storage_frequency_1" name="storage_email_frequency_1">
    <option value="hourly"
    <?php
    if (array_key_exists("email_frequency_1", $storagesettings) && $storagesettings['email_frequency_1'] == "hourly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Hourly</option>
    <option value="daily"
    <?php
    if (array_key_exists("email_frequency_1", $storagesettings) && $storagesettings['email_frequency_1'] == "daily") {
        echo " selected=\"selected\"";
    }
    ?>
    >Daily</option>
    <option value="weekly"
    <?php
    if (array_key_exists("email_frequency_1", $storagesettings) && $storagesettings['email_frequency_1'] == "weekly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Weekly</option>
    <option value="monthly"
    <?php
    if (array_key_exists("email_frequency_1", $storagesettings) && $storagesettings['email_frequency_1'] == "monthly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Monthly</option>
    </select>
    </div>
</div>
</div>
<div class="col-md-6">
    <div class="row">
    <div class="col-md-3">
    <?php echo _("Storage Threshold 2");?></div>
    <div class="col-md-3">
     <input type="number" name="storage_threshold_2" class="form-control" id="storage_threshold_2" min="1" max="99"
    <?php
    if (array_key_exists("threshold_2", $storagesettings)) {
        echo " value=\"$storagesettings[threshold_2]\"";
    }
    ?>
    >
    </div>
</div>
</div>
<div class="col-md-6">
    <div class="row">
    <div class="col-md-3">
    <?php echo _("E-Mail Frequency 2");?></div>
    <div class="col-md-3">
    <select class="form-control" id="storage_frequency_2" name="storage_email_frequency_2">
    <option value="hourly"
    <?php
    if (array_key_exists("email_frequency_2", $storagesettings) && $storagesettings['email_frequency_2'] == "hourly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Hourly</option>
    <option value="daily"
    <?php
    if (array_key_exists("email_frequency_2", $storagesettings) && $storagesettings['email_frequency_2'] == "daily") {
        echo " selected=\"selected\"";
    }
    ?>
    >Daily</option>
    <option value="weekly"
    <?php
    if (array_key_exists("email_frequency_2", $storagesettings) && $storagesettings['email_frequency_2'] == "weekly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Weekly</option>
    <option value="monthly"
    <?php
    if (array_key_exists("email_frequency_2", $storagesettings) && $storagesettings['email_frequency_2'] == "monthly") {
        echo " selected=\"selected\"";
    }
    ?>
    >Monthly</option>
    </select>
    </div>
</div>
</div>
</div>
</div>
</div>
</div></div>
<input type="hidden" name="set_storage" value="true"></form></div>

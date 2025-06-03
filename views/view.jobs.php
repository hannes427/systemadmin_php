<?php
$captures = systemadminFetchPacketCaptures();
if (count($captures) == 0) {
	echo _("No Captures found. Please start a capture first");
}
else {
	?>
	<table id="captures" class="table table-striped table-bordered table-hover">
	<thead style=""><tr><th class="bs-checkbox " style="width: 36px; " data-field="0"><div class="th-inner "><label><input name="btSelectAll" type="checkbox"><span></span></label></div><div class="fht-cell"></div></th><th style="" data-field="name"><div class="th-inner "><?php echo _("Capture date");?></div><div class="fht-cell"></div></th><th class="col_actions" style="" data-field="id"><div class="th-inner "><?php echo _("Actions");?></div><div class="fht-cell"></div></th></tr></thead><tbody>
	<?php
	$i = 1;
	foreach($captures AS $capture) {
		$date = explode(" ", $capture['date']);
		$temp = explode("-", $date[0]);
		$temp1 = explode(":", $date[1]);
		$year = $temp[0];
		$month = $temp[1];
		$day = $temp[2];
		$hour = $temp1[0];
		$min = $temp1[1];
		$sec = $temp1[2];
		echo "<tr data-index=\"$i\" class=\"\"><td class=\"bs-checkbox \" style=\"width: 36px; \"><label><input data-index=\"$i\" name=\"btSelectItem\" type=\"checkbox\"><span></span></label></td><td>$day.$month.$year $hour:$min:$sec</td><td><a href=\"/admin/api/systemadmin/localdownload?id=$capture[id]\" id=\"localdownload\" name=\"$capture[id]\" class=\"localdownload\"><i class=\"fa fa-download\" style=\"font-size: 1.5em;\"></i></a>&nbsp;<a href=\"/admin/api/systemadmin/localdelete?id=$capture[id]\" id=\"localdelete\" name=\"$capture[id]\" class=\"localdelete\"><i class=\"fa fa-trash-o \" style=\"font-size: 1.5em;\"></i></a>";
		if ($capture['stopped'] == "no") {
			echo "&nbsp;<a href=\"/admin/api/systemadmin/localstop?id=$capture[id]\" id=\"localstop\" name=\"$capture[id]\" class=\"localstop\"><i class=\"fa fa-stop\" style=\"font-size: 1.5em;\"></i></a>";
		}
		echo "</td></tr>";
		$i++;
	}
	?>
	</tbody>
	</table>
<?php
}


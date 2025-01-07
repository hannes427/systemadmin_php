<?php
$file = file_get_contents("/etc/resolv.conf");
$file = explode("\n", $file);
$nameserver = array();
$search = array();
foreach($file AS $tempfile) {
  if (preg_match("/^nameserver\s/", $tempfile)) {
    $temp = explode(" ", $tempfile);
    $nameserver[] = $temp[1];
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

?>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
										<div class="display full-border">
						<div class='container-fluid'>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>Operating System:</div>
  <div class='col-sm-3'><?php print_r($nameserver); ?></div>
  </div>
  <div class='row'>
  <div class='col-sm-offset-6 col-sm-3'>PBX Version:</div>
  <div class='col-sm-3'><?php print_r($search); ?></div>
  </div>
IT WORKS!!!! Generated for Systemadmin
fokbdlkfbfkgb<br><br>fkljhkflkhjflhjflkghjkfl<br><br>
</div>
</div>
</div>
</div>

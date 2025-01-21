<?php
if (!isset($_REQUEST['tab'])) {
	$tab = "startcapture";
} else {
	$tab = $_REQUEST['tab'];
}
$startcapture = "";
$jobs = "";
switch ($tab) {
case 'startcapture':
case 'jobs':
	${$tab} = "active";
	break;
default:
	$startcapture = "active";
}
?>
<script src="modules/systemadmin/assets/js/views/capture.js"></script>
<div class="container-fluid">
<h1>System Admin</h1>
<div class = "display full-border">
<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
<div class="nav-container">
    <ul class="nav nav-tabs list pb-0" role="tablist">
      <li role="presentation" data-name="startcapture">
        <a  class="nav-link <?php echo $startcapture; ?>" href="#startcapture" aria-controls="startcapture" role="tab" data-toggle="tab"><?php echo _("Start new Capture")?> </a>
      </li>
      <li role="presentation" data-name="jobs">
        <a  class="nav-link <?php echo $jobs; ?>" href="#jobs" aria-controls="jobs" role="tab" data-toggle="tab"><?php echo _("Jobs")?> </a>
      </li>
    </ul>
    <div class="tab-content display">

      <div role="tabpanel" id="startcapture" class="tab-pane <?php echo $startcapture; ?>">
        <?php echo load_view(__DIR__."/view.startcapture.php"); ?>
      </div>
      <div role="tabpanel" id="jobs" class="tab-pane <?php echo $jobs; ?>">
        <?php echo load_view(__DIR__."/view.jobs.php"); ?>
      </div>
    </div>
  </div>
</div>
</div>

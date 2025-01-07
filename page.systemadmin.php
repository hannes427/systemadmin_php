<?php
if (!empty($_REQUEST['view'])) {
		$view = $_REQUEST['view'];
	} else {
		$view = "about";
	}
echo FreePBX::Systemadmin()->showPage($view);

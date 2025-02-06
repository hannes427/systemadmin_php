<?php
if (!empty($_REQUEST['view'])) {
		$view = basename($_REQUEST['view']);
	} else {
		$view = "about";
	}
echo FreePBX::Systemadmin()->showPage($view);

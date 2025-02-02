 function rebootWait() {
     console.log("Hallo");
        $.ajax({
            url: '/',
            timeout: 2500
        }).fail(function () {
            setTimeout(rebootWait, 2500);
        }).done(function () {
            $(location).attr('href', '/admin/config.php?display=systemadmin&view=power');
        });
    }

$(document)
.on('click', '#poweroff', function(e) {
    if (!confirm('Are you shure to SHUTDOWN the system? Active calls will be terminated and no new calls will be processed during reboot!\n\n Make shure you can poeron the system remotly or that you have physical access to the system')) {
        e.preventDefault(); //stop submit
    }
 else {
    var req = {
			module: 'systemadmin',
			command: 'powermgmt',
			action: 'poweroff',
		};

        $.ajax({
			url: FreePBX.ajaxurl,
			data: req,
			success: function(data) {
				 $("#shutdownmodal").modal('show');
}
});
}});

$(document)
.on('click', '#reboot', function(e) {
    if (!confirm('Are you shure to reboot the system? Active calls will be terminated and no new calls will be processed during reboot!')) {
        e.preventDefault(); //stop submit
    }
 else {
    var req = {
			module: 'systemadmin',
			command: 'powermgmt',
			action: 'reboot',
		};
        $.ajax({
            type: "POST",
			url: FreePBX.ajaxurl,
			data: req,
			success: function(data) {
                $("#rebootmodal").modal('show');
                 rebootWait();
}
});
}});

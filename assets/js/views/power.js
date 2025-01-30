 function rebootWait() {
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
				BootstrapDialog.show({
                type:BootstrapDialog.TYPE_INFO,
                title: 'Your device is rebooting',
                closable: false,
                message: 'The system is rebooting now, please wait...' +
                    ' <i class="fa fa-cog fa-spin"></i>',
                onshow: function (dialogRef) {
                    setTimeout(rebootWait, 45000);
            }});
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
}
});
}});










/*$(document)
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
			url: FreePBX.ajaxurl,
			data: req,
			});
         BootstrapDialog.show({
            message: 'Hi Apple!'
        });

} } );

BootstrapDialog.show({
            message: 'Hi Apple!'
        });*/
/*$(document)
.on('click', '#reboot', function() {
            BootstrapDialog.show({
                type:BootstrapDialog.TYPE_INFO,
                title: 'Your device is rebooting',
                closable: false,
                message: 'The system is rebooting now, please wait...' +
                    ' <i class="fa fa-cog fa-spin"></i>',
                onshow: function (dialogRef) {
                    ajaxCall('/api/core/system/reboot');
                    setTimeout(rebootWait, 45000);
                },
            });
    });
  $( function() {
    $( "#dialog" ).dialog();
  } );
*/

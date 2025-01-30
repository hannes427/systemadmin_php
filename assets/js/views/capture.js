$(document)
.on('click', '#localdelete', function(e) {
    e.preventDefault(); //stop submit
    var id = ($(this).attr('name'));
    var req = {
        module: 'systemadmin',
        command: 'localdelete',
        id: id,
    };
    $.ajax({
        url: FreePBX.ajaxurl,
        data: req,
        success: function(data) {
            window.location.href = '/admin/config.php?display=systemadmin&view=packetcapture&tab=jobs';
}
})
});

$(document)
.on('click', '#localstop', function(e) {
    e.preventDefault(); //stop submit
    var id = ($(this).attr('name'));
    var req = {
        module: 'systemadmin',
        command: 'localstop',
        id: id,
    };
    $.ajax({
        url: FreePBX.ajaxurl,
        data: req,
        success: function(data) {
            window.location.href = '/admin/config.php?display=systemadmin&view=packetcapture&tab=jobs';
}
})
});

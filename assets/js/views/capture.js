$(document)
.on('click', '#localdownload', function(e) {
    e.preventDefault(); //stop submit
    var id = ($(this).attr('name'));
    console.log("Hallo " + id;
    var req = {
        module: 'systemadmin',
        command: 'localdownload',
        id: id,
    };
    $.ajax({
        url: FreePBX.ajaxurl,
        data: req,
        success: function(data) {
            return true;
}
})
});

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
            return true;
}
})
});

$(document)
.on('click', '#localstop', function(e) {
    e.preventDefault(); //stop submit
    var id = ($(this).attr('name'));
    console.log(id);
    var req = {
        module: 'systemadmin',
        command: 'localstop',
        id: id,
    };
    $.ajax({
        url: FreePBX.ajaxurl,
        data: req,
        success: function(data) {
            return true;
}
})
});

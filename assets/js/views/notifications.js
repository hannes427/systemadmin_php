function ValidateEmail(input) {
    if (input.length == 0) {
        return true;
    }
    var validRegex = /^[a-zA-Z0-9\.!#%&'\*+\-\/=\?\^_`\{\|\}\~]+@[a-zA-Z0-9]+-*[a-zA-Z0-9.][a-zA-Z0-9]+\.\w{2,}$/;
    if (input.match(validRegex)) {
        return true;
    }
    else {
        return false;
    }
}
function validate_form(){
    var error_msg = "";
    var trimmed_from = jQuery.trim($('#from_address').val());
    if (!ValidateEmail(trimmed_from)) {
        var error_msg = error_msg +"Sender e-mail address invalid!\n";
    }
    var trimmed_storage = jQuery.trim($('#storage_address').val());
    if (!ValidateEmail(trimmed_storage)) {
        var error_msg = error_msg +"Storage e-mail address invalid!\n";
    }
    if (error_msg != "") {
        alert(error_msg);
        return false;
    }
    else {
        return true;
    }
};

$(document)
.on('click', '#submit', function(e) {
    var isValid = validate_form();
    if(!isValid) {
        e.stopImmediatePropagation(); //stop submit
    }
});

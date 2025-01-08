$(document).on('change','#email_server-local',function() {
    $(".remote_local").toggle();
});

$(document).on('change','#email_server-remote',function() {
    $(".remote_local").toggle();
});

$(document).on('change','#smtp_use_tls-yes',function() {
    $(".tls_port").show();
});

$(document).on('change','#smtp_use_tls-no',function() {
     $(".tls_port").hide();
});

$(document).on('change','#password',function() {
    $('input[name="password_changed"]').attr("value", "true");
});

$(document).on('change','#smtp_sasl_auth_enable-yes',function(){
    $(".username_password").show();
});

$(document).on('change','#smtp_sasl_auth_enable-no',function(){
    $(".username_password").hide();
});
function ValidateIPaddress4(ipaddress) {
    if (/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])$/.test(ipaddress)) {
        return true;
  }
  return false;
}

/**
 * @param {String} a String
 * @return {Boolean} true if the String is a valid IPv6 address; false otherwise
 */
function ValidateIPaddress6(ipaddress) {
// See https://blogs.msdn.microsoft.com/oldnewthing/20060522-08/?p=31113 and
// https://4sysops.com/archives/ipv6-tutorial-part-4-ipv6-address-syntax/

    if(ipaddress.match(/[g-z]/i)) {
            return false;
    }
    const components = ipaddress.split(":");
    if (components.length < 2 || components.length > 8) {
        return false;
    }
    if (components[0] !== "" || components[1] !== "") {
        // Address does not begin with a zero compression ("::")
        if (!components[0].match(/^[\da-f]{1,4}/i)) {
            // Component must contain 1-4 hex characters
            return false;
        }
    }

    let numberOfZeroCompressions = 0;
    for (let i = 1; i < components.length; ++i) {
        if (components[i] === "") {
            // We're inside a zero compression ("::")
            ++numberOfZeroCompressions;
            if (numberOfZeroCompressions > 1) {
                // Zero compression can only occur once in an address
                return false;
            }
            continue;
        }
        if (!components[i].match(/^[\da-f]{1,4}/i)) {
            // Component must contain 1-4 hex characters
            return false;
        }
    }
return true;
}

function validate_form(){
    var error_msg = "";
    var trimmed_port = jQuery.trim($("#port").val());
    let isnum = /^\d+$/.test(trimmed_port);
    if ($("#smtp_use_tls-yes").prop("checked") == true && (trimmed_port.length == 0 || !isnum)) {
         var error_msg = error_msg +"TLS Encryption is enabled but no valid port was provided!\n";
    }
    var trimmed_username = jQuery.trim($("#username").val());
    var trimmed_password = jQuery.trim($("#password").val());
    if ($("#email_server-remote").prop("checked") == true) {
        var trimmed_username = jQuery.trim($("#username").val());
        var trimmed_password = jQuery.trim($("#password").val());
        var trimmed_mailserver = jQuery.trim($("#mailserver").val());
        let valid_mailserver = /^[a-z0-9]+[a-z0-9-\._]+\.[a-z]{2,}$/.test(trimmed_mailserver);
        is_ipv4 = ValidateIPaddress4(trimmed_mailserver);
        is_ipv6 = ValidateIPaddress6(trimmed_mailserver);
        if ($("#smtp_sasl_auth_enable-yes").prop("checked") == true && (trimmed_username.length == 0 || trimmed_password.length == 0)) {
            var error_msg = error_msg +"Authentication is enabled. Please enter Username and password!\n";
        }
        if (trimmed_mailserver.length == 0 || (!valid_mailserver && !is_ipv4 && !is_ipv6)) {
            var error_msg = error_msg +"Please provide a valid address of the remote server\n";
        }
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

$(document).ready(function(){
    if (setup == "local" || setup == "") {
        $("#email_server-local").attr("checked", "checked");
    }
    else if (setup == "remote") {
        $("#email_server-remote").attr("checked", "checked");
    }
    if (smtp_use_tls == "yes") {
        $("#smtp_use_tls-yes").attr("checked", "checked");
    }
    else {
        $("#smtp_use_tls-no").attr("checked", "checked");
    }
    if(smtp_sasl_auth_enable == "yes") {
        $("#smtp_sasl_auth_enable-yes").attr("checked", "checked");
        $(".username_password").show();
    }
    else {
        $("#smtp_sasl_auth_enable-no").attr("checked", "checked");
    }
});

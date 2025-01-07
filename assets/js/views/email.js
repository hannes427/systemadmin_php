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

$(document).ready(function(){
    if (setup == "local" || setup == "") {
        $("#email_server-local").attr("checked", "checked");
    }
    else if (setup == "remote") {
        $("#email_server-remote").attr("checked", "checked");
    }
    if(smtp_use_tls == "yes") {
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

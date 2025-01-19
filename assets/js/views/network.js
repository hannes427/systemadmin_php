function fetch_config(iface) {
    let result = interface.filter(x=> x.name == iface);
    $.each(result, function(key, val){
         if (val.ipv4_assignment == "static") {
             console.log(iface + " Static");
            $("#ipv4_assignment-static").click();
            $(".ipv4_disabled").prop('disabled', false);
            $("#ipv4_address").val(val.ipv4_address);
        }
        else if (val.ipv4_assignment == "dhcp") {
             console.log(iface + " dhcp");
            $("#ipv4_assignment-dhcp").click();
            $(".ipv4_disabled").prop('disabled', true);
            $("#ipv4_address").val(val.dyn_ipv4_address);
        }
        else {
            $("#ipv4_assignment-unconfigured").click();
             console.log(iface + " unconfigured");
            $(".ipv4_disabled").prop('disabled', true);
            $("#ipv4_address").val("");
        }
        $("#ipv4_gateway").val(val.ipv4_gateway);
        if (val.ipv6_assignment == "static") {
            $("#ipv6_assignment-static").click();
            $(".ipv6_disabled").prop('disabled', false);
        }
        else if (val.ipv6_assignment == "dhcp") {
            $("#ipv6_assignment-dhcp").click();
            $(".ipv6_disabled").prop('disabled', true);
        }
        else {
            $("#ipv6_assignment-unconfigured").click();
            $(".ipv6_disabled").prop('disabled', true);
        }
        document.getElementById("ipv6_address").innerHTML = "";
        $.each(val.ipv6_address, function(k, v){
            $("#ipv6_address").append(v.address + "&#13;&#10;");
        });
        var ta = $('#ipv6_address').val()
        $("#ipv6_gateway").val(val.ipv6_gateway);
        if (val.ipv6_autoconf == "1") {
            $("#ipv6_autoconf_on").click();
        }
        else {
            $("#ipv6_autoconf_off").click();
        }
        if (val.ipv6_accept_ra == "0") {
            $("#ipv6_accept_ra_off").click();
        }
        else if (val.ipv6_accept_ra == "1") {
            $("#ipv6_accept_ra_on").click();
        }
        else {
            $("#ipv6_accept_ra_forwarding").click();
        }
    });
};

$(document).on('click','#network_interface',function(){
    $("#network_interface").off("change");
    console.log($(this).val());
    fetch_config($(this).val());
});

$(document).on('change','#ipv4_assignment-static',function(){
    $(".ipv4_disabled").prop('disabled', false);
});

$(document).on('change','#ipv4_assignment-dhcp',function(){
    $(".ipv4_disabled").prop('disabled', true);
});

$(document).on('change','#ipv4_assignment-unconfigured',function(){
    $(".ipv4_disabled").prop('disabled', true);
});

$(document).on('change','#ipv6_assignment-static',function(){
    $(".ipv6_disabled").prop('disabled', false);
});

$(document).on('change','#ipv6_assignment-dhcp',function(){
    $(".ipv6_disabled").prop('disabled', true);
});

$(document).on('change','#ipv6_assignment-unconfigured',function(){
    $(".ipv6_disabled").prop('disabled', true);
});

function ValidateIPaddress4(temp_ipaddress) {
    var ipaddress = temp_ipaddress.split("/");
    if (ipaddress.length < 2) {
        return false;
    }
    if (/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])$/.test(ipaddress[0])) {
        return true;
  }
  return false;
}

/**
 * @param {String} a String
 * @return {Boolean} true if the String is a valid IPv6 address; false otherwise
 */
function ValidateIPaddress6(ipaddress) {
    var splitted_ipaddress = jQuery.trim(ipaddress);
    splitted_ipaddress = ipaddress.split('\n');
    for (const element of splitted_ipaddress) {
        if (element.length == 0) {
            continue;
        }
        // See https://blogs.msdn.microsoft.com/oldnewthing/20060522-08/?p=31113 and
        // https://4sysops.com/archives/ipv6-tutorial-part-4-ipv6-address-syntax/
        if(!element.match(/\/\d\d|\/1[10][0-9]|\/12[0-8]/)) {
            return false;
        }
        if(element.match(/[g-z]/i)) {
            return false;
        }
        const components = element.split(":");
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
    }
    return true;
};

function validate_form(){
    var error_msg = "";
    var trimmed_ipv4 = jQuery.trim($('#ipv4_address').val());
    if ($('#ipv4_assignment-static').is(':checked')  && (trimmed_ipv4.length == 0 || !ValidateIPaddress4($('#ipv4_address').val()))) {
        var error_msg = error_msg +"IPv4 Assignment Method is set to \"static\". Please provide a valid IPv4 address and netmask!\n";
    }
    var trimmed_ipv6 = jQuery.trim($('#ipv6_address').val());
    if($('#ipv6_assignment-static').is(':checked')  && (trimmed_ipv6.length == 0 || !ValidateIPaddress6($('#ipv6_address').val())))  {
        var error_msg = error_msg +"IPv6 Assignment Method is set to \"static\". Please provide a a valid IPv6 address and netmask!\n";
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
    selected_interface = $('#network_interface').find(":selected").val();
    fetch_config(selected_interface);
});

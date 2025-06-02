function fetch_config(iface) {
    let result = interface.filter(x=> x.name == iface);
    $.each(result, function(key, val){
        if (val.bonding_status == "slave") {
            $("#bonding_warning").html("<span style='color: red;'>Since the interface "+val.name+" is a member of the bond "+val.bonding_master+", it cannot be configured directly. Please select the interface "+val.bonding_master+" to configure</span>");
            $("form").find("input, textarea, button").prop("disabled", true);
        }
        else {
            $("#bonding_warning").html("");
            $("form").find("input, textarea, button").prop("disabled", false);
            if (val.bonding_status == "master") {
                var primary_interface = Object.values(val.bond_parameter.find(param => param.primary) || "");
                if (primary_interface !== "") {
                    $('#primary').val(primary_interface);
                }
                $('#bond_member').empty();
                $.each(interface, function(k, v){
                    //Check if interface is member of this bond
                    var is_member = result.some(function(iface1) {
                        return iface1.bond_member.some(function(member) {
                            return member.interface === v.name;
                        });
                    });
                    if ((v.ipv4_assignment == "" && v.ipv6_assignment == "" && v.bonding_status == "none") || is_member) {
                        var checkbox = $("<input type=\"checkbox\" name=\"bond_member[]\" id=\"bond_member_"+v.name+"\" value=\""+v.name+"\">");
                        var label = $("<label for=\"bond_member_"+v.name+"\">&nbsp;"+v.name+"</label>");
                        $('#bond_member').append(checkbox).append(label).append("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
                    }
                });
                $.each(val.bond_member, function(k, v){
                    $('#bond_member_'+v.interface+'').prop('checked', true);

                    $('#primary').append($('<option></option>').val(v.interface).text(v.interface));
                });
                $('#networkform').append('<input type="hidden" name="selected_bond" id="selected_bond" value="true">');
                $("#bond_section").show();
                $(".active-backup").prop("disabled", true);
                $(".balance-alb").prop("disabled", true);
                $(".balance-rr").prop("disabled", true);
                $(".balance-tlb").prop("disabled", true);
                $(".balance-xor").prop("disabled", true);
                $(".8023ad").prop("disabled", true);
                var mode = Object.values(val.bond_parameter.find(param => param.mode) || "");
                var lacp_rate = Object.values(val.bond_parameter.find(param => param.lacp_rate) || "");
                var mii_monitor_interval = Object.values(val.bond_parameter.find(param => param.mii_monitor_interval) || "");
                var min_links = Object.values(val.bond_parameter.find(param => param.min_links) || "");
                var transmit_hash_policy = Object.values(val.bond_parameter.find(param => param.transmit_hash_policy) || "");
                var ad_select = Object.values(val.bond_parameter.find(param => param.ad_select) || "");
                var all_members_active = Object.values(val.bond_parameter.find(param => param.all_members_active) || "");
                var arp_interval = Object.values(val.bond_parameter.find(param => param.arp_interval) || "");
                var arp_ip_targets = Object.values(val.bond_parameter.find(param => param.arp_ip_targets) || "");
                var arp_validate = Object.values(val.bond_parameter.find(param => param.arp_validate) || "");
                var arp_all_targets = Object.values(val.bond_parameter.find(param => param.arp_all_targets) || "");
                var up_delay = Object.values(val.bond_parameter.find(param => param.up_delay) || "");
                var down_delay = Object.values(val.bond_parameter.find(param => param.down_delay) || "");
                var fail_over_mac_policy = Object.values(val.bond_parameter.find(param => param.fail_over_mac_policy) || "");
                var gratuitous_arp = Object.values(val.bond_parameter.find(param => param.gratuitous_arp) || "");
                var packets_per_member = Object.values(val.bond_parameter.find(param => param.packets_per_member) || "");
                var primary_reselect_policy = Object.values(val.bond_parameter.find(param => param.primary_reselect_policy) || "");
                var resend_igmp = Object.values(val.bond_parameter.find(param => param.resend_igmp) || "");
                var learn_packet_interval = Object.values(val.bond_parameter.find(param => param.learn_packet_interval) || "");
                var primary = Object.values(val.bond_parameter.find(param => param.primary) || "");
                if (mode == "active-backup") {
                    $(".active-backup").prop("disabled", false);
                    $('#bond_mode').val("active-backup");
                }
                else if (mode == "balance-alb") {
                     $(".balance-alb").prop("disabled", false);
                     $('#bond_mode').val("balance-alb");
                }
                 else if (mode == "balance-rr") {
                     $(".balance-rr").prop("disabled", false);
                     $('#bond_mode').val("balance-rr");
                }
                 else if (mode == "balance-tlb") {
                     $(".balance-tlb").prop("disabled", false);
                     $('#bond_mode').val("balance-tlb");
                }
                 else if (mode == "balance-xor") {
                     $(".balance-xor").prop("disabled", false);
                     $('#bond_mode').val("balance-xor");
                }
                 else if (mode == "802.3ad") {
                     $(".8023ad").prop("disabled", false);
                     $('#bond_mode').val("802.3ad");
                }
                else if (mode == "broadcast") {
                    $('#bond_mode').val("broadcast");
                }
                if (lacp_rate == "slow" || lacp_rate == "") {
                    $("#lacp_rate").val("slow");
                }
                else {
                     $("#lacp_rate").val("fast");
                }
                $("#mii_monitor_interval").val(mii_monitor_interval);
                $("#min_links").val(min_links);
                if (transmit_hash_policy == "layer2") {
                    $("#transmit_hash_policy").val("layer2");
                    }
                else if (transmit_hash_policy == "layer2+3") {
                    $("#transmit_hash_policy").val("layer2+3");
                    }
                else if (transmit_hash_policy == "layer3+4") {
                    $("#transmit_hash_policy").val("layer3+4");
                    }
                else if (transmit_hash_policy == "encap2+3") {
                    $("#transmit_hash_policy").val("encap2+3");
                    }
                else if (transmit_hash_policy == "encap3+4") {
                    $("#transmit_hash_policy").val("encap3+4");
                    }

                if (ad_select == "stable" || ad_select == "") {
                    $("#ad_select").val("stable");
                    }
                else if (ad_select == "bandwidth") {
                    $("#ad_select").val("bandwidth");
                    }
                else if (ad_select == "count") {
                    $("#ad_select").val("count");
                    }
                if (all_members_active == "false" || all_members_active == "") {
                    $('#all_members_active_off').prop('checked', true);
                }
                else if (all_members_active == "true") {
                    $('#all_members_active_on').prop('checked', true);
                }
                $("#arp_interval").val(arp_interval);
                $("#arp_ip_targets").val(arp_ip_targets);
                if (arp_validate == "none" || arp_validate == "") {
                    $("#arp_validate").val("none");
                }
                else if (arp_validate == "active") {
                    $("#arp_validate").val("active");
                }
                else if (arp_validate == "all") {
                    $("#arp_validate").val("all");
                }
                else if (arp_validate == "backup") {
                    $("#arp_validate").val("backup");
                }
                if (arp_all_targets == "any" || arp_all_targets == "") {
                    $('#arp_all_targets_any').prop('checked', true);
                }
                else if (arp_all_targets == "all") {
                    $('#arp_all_targets_all').prop('checked', true);
                }
                $("#up_delay").val(up_delay);
                $("#down_delay").val(down_delay);
                if (fail_over_mac_policy == "none" || fail_over_mac_policy == "") {
                    $('#fail_over_mac_policy_none').prop('checked', true);
                }
                else if (fail_over_mac_policy == "follow") {
                    $('#fail_over_mac_policy_follow').prop('checked', true);
                }
                else if (fail_over_mac_policy == "active") {
                    $('#fail_over_mac_policy_active').prop('checked', true);
                }
                $("#gratuitous_arp").val(gratuitous_arp);
                $("#packets_per_member").val(packets_per_member);
                if (primary_reselect_policy == "always" || primary_reselect_policy == "") {
                    $('#primary_reselect_policy_always').prop('checked', true);
                }
                else if (primary_reselect_policy == "better") {
                    $('#primary_reselect_policy_better').prop('checked', true);
                }
                else if (primary_reselect_policy == "failure") {
                    $('#primary_reselect_policy_failure').prop('checked', true);
                }
                $("#resend_igmp").val(resend_igmp);
                $("#learn_packet_interval").val(learn_packet_interval);
                $("#primary").val(primary);
            }
            else {
                $("#bond_section").hide();
                $('input[type="hidden"][name="selected_bond"]').remove();
            }
            if (val.ipv4_assignment == "static") {
                $("#ipv4_assignment-static").click();
                $(".ipv4_disabled").prop('disabled', false);
                $("#ipv4_address").val(val.ipv4_address);
            }
            else if (val.ipv4_assignment == "dhcp") {
                $("#ipv4_assignment-dhcp").click();
                $(".ipv4_disabled").prop('disabled', true);
                $("#ipv4_address").val(val.dyn_ipv4_address);
            }
            else {
                $("#ipv4_assignment-unconfigured").click();
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
            $.each(val.ipv6_address, function(k, v){
                $("#ipv6_address").val(v.address);
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
        }
    });
};

$(document).on('click', "#add_interface", function(e) {
	e.preventDefault();
	$("#custmodal").modal('show');
});

$(document).on('click', "#addinterface_close", function(e) {
	e.preventDefault();
	$('#add_interface_name').text("");
	$("#custmodal").modal('hide');
});

$(document).on('click', '#addinterface_save', function(e) {
    e.preventDefault();
    var isValid = validate_modal();
    if(!isValid) {
        e.stopImmediatePropagation(); //stop submit
    }
    else {
        $('#addinterface').submit();
    }
});

$(document).on('click', "#del_interface", function(e) {
	e.preventDefault();
    var del_interface = $("#network_interface option:selected").text();
    $("#del_interface_name").val(""+del_interface+"");
    for (var i = 0; i < interface.length; i++) {
        if (interface[i].name === del_interface) {
            var type = interface[i].bonding_status;
            break;
        }
    }
    if (type == "master") {
        $("#del_interface_type").val("bond");
    }
    $('#del_interface_name_div').text(''+del_interface+'').css('font-weight', 'bold');
    $('#del_interface_checkbox').text("Are you sure you want to delete the interface? This cannot be undone").css('color', 'red');
	$("#delmodal").modal('show');
});

$(document).on('click', "#delinterface_close", function(e) {
	e.preventDefault();
	$('#del_interface_name_div').text("");
	$("#delmodal").modal('hide');
});

$(document).on('click', "#delinterface_yes", function(e) {
	e.preventDefault();
    if (!$("#del_interface_check").is(':checked')) {
        alert("Please confirm that you are sure by checking the checkbox.");
    }
    else {
        $('#delinterface').submit();
    }
});

$(document).on('click','#network_interface',function(){
    $("#network_interface").off("change");
    fetch_config($(this).val());
});

$(document).on('click','#bond_mode',function(){
    $("#bond_mode").off("change");
    $(".active-backup").prop("disabled", true);
    $(".balance-alb").prop("disabled", true);
    $(".balance-rr").prop("disabled", true);
    $(".balance-tlb").prop("disabled", true);
    $(".balance-xor").prop("disabled", true);
    $(".8023ad").prop("disabled", true);
    if ($(this).val() == "active-backup") {
        $(".active-backup").prop("disabled", false);
    }
    else if ($(this).val() == "balance-alb") {
        $(".balance-alb").prop("disabled", false);
    }
    else if ($(this).val() == "balance-rr") {
        $(".balance-rr").prop("disabled", false);
    }
    else if ($(this).val() == "balance-tlb") {
        $(".balance-tlb").prop("disabled", false);
    }
    else if ($(this).val() == "balance-xor") {
        $(".balance-xor").prop("disabled", false);
    }
    else if ($(this).val() == "802.3ad") {
        $(".8023ad").prop("disabled", false);
    }
});

$(document).on('input', '#add_interface_name', function() {
    var inputValue = $(this).val();
    var found = interface.some(function(iface) {
        return iface.name === inputValue;
    });
    if (found) {
        $('#warning_name').text('Interface "' + inputValue + '" already exists!').css('color', 'red');
    }
    else {
        $('#warning_name').text('');
    }
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

function containsValue(arr, value) {
    for (let i = 0; i < arr.length; i++) {
        for (let j = 0; j < arr[i].length; j++) {
            if (arr[i][j] === value) {
                return true;
            }
        }
    }
    return false;
}

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

function validate_modal(){
    var error_msg = "";
    var trimmed_add_interface_name = jQuery.trim($('#add_interface_name').val());
    if(trimmed_add_interface_name == '') {
        var error_msg = error_msg +"Interface name must not be blank!\n";
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

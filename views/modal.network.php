<div class='modal fade' id='custmodal'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h3 id='mheader' class="mr-auto">Add Interface</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class='modal-body'>
            <form method="post" class="fpbx-submit" id='addinterface'>
                 <div class="element-container">
                    <div class="">
                        <div class="row form-group">
                            <div class="col-md-3">Interface name</div>
                            <div class="col-md-9">
                               <input type="text" name="add_interface_name" id="add_interface_name">
                               <div id="warning_name"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="element-container">
                    <div class="">
                        <div class="row form-group">
                            <div class="col-md-3">Interface type</div>
                            <div class="col-md-9">
                                <select name="interface_type" id="interface_type" class="form-control">
                                <option value="bond">Bond</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="add_bond_section">
                    <div class="element-container">
                        <div class="">
                            <div class="row form-group">
                                <div class="col-md-3">Mode</div>
                                <div class="col-md-9">
                                    <select name="add_if_mode" id="add_if_bond_mode" class="form-control">
                                    <option value="balance-rr">Balace-RR</option>
                                    <option value="active-backup">Active Backup</option>
                                    <option value="balance-xor">Balace XOR</option>
                                    <option value="broadcast">Broadcast</option>
                                    <option value="802.3ad">802.3ad</option>
                                    <option value="balance-tlb">Balance TLB</option>
                                    <option value="balance-alb">Balace ALB</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="element-container">
                        <div class="">
                            <div class="row form-group">
                                <div class="col-md-3">Bond member</div>
                                <div class='col-md-9' id="add_if_bond_member">
                                    <?php
                                    foreach($interfaces AS $interface) {
                                        if (isset($interface['ipv4_assignment']) && $interface['ipv4_assignment'] == "" && isset($interface['ipv6_assignment']) && $interface['ipv6_assignment'] == "" && $interface['bonding_status'] == "none") {
                                            echo "<input type=\"checkbox\" name=\"bond_member[]\" id=\"bond_member_$interface[name]\" value=\"$interface[name]\"><label for=\"bond_member_$interface[name]\">&nbsp;$interface[name]</label>&nbsp;&nbsp;&nbsp;&nbsp;";
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <input type="hidden" name="add_interface_modal" value="true">
            <?php
            echo "<input type=\"hidden\" name=\"add_interface_managed_by\" value=\"$managed_by\">\n";
            ?>
            </form>
            </div>
            <div class='modal-footer'>
                <button type="button" class="btn btn-primary" id='addinterface_save'><?php echo _("Save"); ?></button><button type="button" class="btn btn-primary" id='addinterface_close'><?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

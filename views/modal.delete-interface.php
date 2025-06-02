<div class='modal fade' id='delmodal'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h3 id='mheader' class="mr-auto">Add Interface</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class='modal-body'>
            <form method="post" class="fpbx-submit" id='delinterface'>
                 <div class="element-container">
                    <div class="">
                        <div class="row form-group">
                            <div class="col-md-3">Interface name</div>
                            <div class="col-md-9">
                               <div id="del_interface_name_div"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="element-container">
                    <div class="">
                        <div class="row form-group">
                            <div class="col-md-12">
                                <div id="del_interface_checkbox"></div>
                                <input type="checkbox" name="del_interface_check" id="del_interface_check" value="true">Yes
                            </div>
                        </div>
                    </div>
                </div>
            <input type="hidden" name="del_interface_modal" id="del_interface_modal" value="true">
            <input type="hidden" name="del_interface_name" id="del_interface_name" value="">
            <input type="hidden" name="del_interface_type" id="del_interface_type" value="ethernet">
            <?php
            echo "<input type=\"hidden\" name=\"del_interface_managed_by\" value=\"$managed_by\">\n";
            ?>
            </form>
            </div>
            <div class='modal-footer'>
                <button type="button" class="btn btn-primary" id='delinterface_yes'><?php echo _("Yes"); ?></button><button type="button" class="btn btn-primary" id='delinterface_close'><?php echo _("Cancel"); ?></button>
            </div>
        </div>
    </div>
</div>

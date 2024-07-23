<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content" class="omniva_m-overlay">
    <div class="page-header">
        <div class="container-fluid">
            <h1><img src="view/image/omniva_m/logo.png" alt="Omniva Logo" style="height: 33px;"></h1>
            <ul class="breadcrumb">
                <?php 
                foreach ($breadcrumbs as $breadcrumb) :
                ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php
                endforeach;
                ?>
            </ul>
            <span class="omniva-version">v<?php echo $omniva_m_version; ?> - OMX</span>
        </div>
    </div>

    <!-- Errors / Success -->
    <div class="container-fluid">
        <?php if ($error_warning): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- VERSION CHECK -->
    <?php if ($omniva_m_git_version): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo str_replace('$$omniva_m_new_version$$' , $omniva_m_git_version['version'], $omniva_m_new_version_notify); ?> 
            <a href="<?php echo $omniva_m_git_version['download_url']; ?>" target="_blank" class="btn btn-success"><?php echo $omniva_m_button_download_version; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- DB CHECK -->
    <?php if ($omniva_m_db_check): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $omniva_m_db_fix_notify; ?> 
            <a href="<?php echo $omniva_m_db_fix_url; ?>" class="btn btn-success"><?php echo $omniva_m_button_fix_db; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- XML CHECK -->
    <?php if ($omniva_m_xml_check): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $omniva_m_xml_fix_notify; ?> 
            <a href="<?php echo $omniva_m_xml_fix_url; ?>" class="btn btn-success"><?php echo $omniva_m_button_fix_xml; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <ul class="container-fluid nav nav-tabs">
        <li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $omniva_m_tab_general; ?></a></li>
        <li><a href="#tab-api" data-toggle="tab"><?php echo $omniva_m_tab_api; ?></a></li>
        <li><a href="#tab-sender-info" data-toggle="tab"><?php echo $omniva_m_tab_sender_info; ?></a></li>
        <?php if ($required_settings_set): ?>
            <li><a href="#tab-price" data-toggle="tab"><?php echo $omniva_m_tab_price; ?></a></li>
            <li><a href="#tab-cod" data-toggle="tab"><?php echo $omniva_m_tab_cod; ?></a></li>
            <li><a href="#tab-terminals" data-toggle="tab"><?php echo $omniva_m_tab_terminals; ?></a></li>
            <li><a href="#tab-tracking-email" data-toggle="tab"><?php echo $omniva_m_tab_tracking_email; ?></a></li>
        <?php else: ?>
            <li><div class="alert alert-danger"><?php echo $omniva_m_alert_settings; ?></div></li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <!-- Module Settings -->
        <div class="tab-pane active" id="tab-general">
            <div class="container-fluid">
                <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $omniva_m_title_edit; ?></h3>
                </div>
                <div class="panel-body">
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-omniva_m" class="form-horizontal">
                    <input type="hidden" name="module_settings_update">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-tax-class"><?php echo $omniva_m_label_tax_class; ?></label>
                        <div class="col-sm-10">
                        <select name="omniva_m_tax_class_id" id="input-tax-class" class="form-control">
                            <option value="0"><?php echo $omniva_m_generic_none; ?></option>
                            <?php foreach($tax_classes as $tax_class): ?>
                                <?php if ($tax_class['tax_class_id'] == $omniva_m_tax_class_id): ?>
                                    <option value="<?php echo $tax_class['tax_class_id']; ?>" selected="selected"><?php echo $tax_class['title']; ?></option>
                                <?php else: ?>
                                    <option value="<?php echo $tax_class['tax_class_id']; ?>"><?php echo $tax_class['title']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $omniva_m_label_geo_zone; ?></label>
                        <div class="col-sm-10">
                        <select name="omniva_m_geo_zone_id" id="input-geo-zone" class="form-control">
                            <option value="0"><?php echo $omniva_m_option_all_zones; ?></option>
                            <?php foreach($geo_zones as $geo_zone): ?>
                                <?php if ($geo_zone['geo_zone_id'] == $omniva_m_geo_zone_id): ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                                <?php else: ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $omniva_m_label_status; ?></label>
                        <div class="col-sm-10">
                        <select name="omniva_m_status" id="input-status" class="form-control">
                            <?php if ($omniva_m_status): ?>
                                <option value="1" selected="selected"><?php echo $omniva_m_generic_enabled; ?></option>
                                <option value="0"><?php echo $omniva_m_generic_disabled; ?></option>
                            <?php else: ?>
                                <option value="1"><?php echo $omniva_m_generic_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $omniva_m_generic_disabled; ?></option>
                            <?php endif; ?>
                        </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $omniva_m_label_sort_order; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sort_order" value="<?php echo $omniva_m_sort_order; ?>" id="input-sort-order" class="form-control" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-order-status-registered"><?php echo $omniva_m_label_order_status_registered; ?></label>
                        <div class="col-sm-10">
                            <select type="text" name="omniva_m_order_status_registered" id="input-order-status-registered" class="form-control">
                                <option value="0"><?php echo $omniva_m_generic_none; ?></option>
                                <?php foreach ($order_statuses as $order_status): ?>
                                    <?php if ($order_status['order_status_id'] == $omniva_m_order_status_registered): ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"><?php echo $omniva_m_help_order_status_registered; ?></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-order-status-error"><?php echo $omniva_m_label_order_status_error; ?></label>
                        <div class="col-sm-10">
                            <select type="text" name="omniva_m_order_status_error" id="input-order-status-error" class="form-control">
                                <option value="0"><?php echo $omniva_m_generic_none; ?></option>
                                <?php foreach ($order_statuses as $order_status): ?>
                                    <?php if ($order_status['order_status_id'] == $omniva_m_order_status_error): ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"><?php echo $omniva_m_help_order_status_error; ?></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-disable_cart_weight_check"><?php echo $omniva_m_label_disable_cart_weight_check; ?></label>
                        <div class="col-sm-10">
                            <select name="omniva_m_disable_cart_weight_check" id="input-disable_cart_weight_check" class="form-control">
                                <option value="0"><?php echo $omniva_m_option_no; ?></option>
                                <option value="1" 
                                    <?php if ($omniva_m_disable_cart_weight_check == 1) { echo "selected"; } ?>
                                ><?php echo $omniva_m_option_yes; ?></option>
                            </select>
                            <p class="help-block"><?php echo $omniva_m_help_disable_cart_weight_check; ?></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-use_simple_terminal_check"><?php echo $omniva_m_label_use_simple_terminal_check; ?></label>
                        <div class="col-sm-10">
                            <select name="omniva_m_use_simple_terminal_check" id="input-use_simple_terminal_check" class="form-control">
                                <option value="0"><?php echo $omniva_m_option_no; ?></option>
                                <option value="1" 
                                    <?php if ($omniva_m_use_simple_terminal_check == 1) { echo "selected"; } ?>
                                ><?php echo $omniva_m_option_yes; ?></option>
                            </select>
                            <p class="help-block"><?php echo $omniva_m_help_use_simple_terminal_check; ?></p>
                        </div>
                    </div>
                    </form>
                </div>

                <div class="panel-footer clearfix">
                    <div class="pull-right">
                    <button type="submit" form="form-omniva_m" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                    <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- API Settings -->
        <div class="tab-pane" id="tab-api">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-cogs"></i> <?php echo $omniva_m_title_api_settings; ?></h3>
                    </div>

                    <div class="panel-body">
                        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-omniva_m-api" class="form-horizontal">
                            <input type="hidden" name="api_settings_update">
                            
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-url"><?php echo $omniva_m_label_api_url; ?></label>
                                <div class="col-sm-8">
                                    <input type="text" name="omniva_m_api_url" value="<?php echo $omniva_m_api_url; ?>" placeholder="<?php echo $omniva_m_placeholder_api_url; ?>" id="input-api-url" class="form-control" disabled />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-contract-origin"><?php echo $omniva_m_label_contract_origin; ?></label>
                                <div class="col-sm-8">
                                    <select name="omniva_m_api_contract_origin" id="input-api-contract-origin" class="form-control">
                                        <?php foreach($contract_origins as $key => $contract_origin): ?>
                                            <option value="<?php echo $key; ?>" 
                                                <?php if ($omniva_m_api_contract_origin == $key) { echo "selected"; } ?>
                                            ><?php echo $contract_origin; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group omniva_m-courier-options">
                                <label class="col-sm-4 control-label"><?php echo $omniva_m_label_cod_options; ?></label>
                                <div class="col-sm-8">
                                    <div class="omniva_m-checkboxes">
                                        <?php foreach($courier_options as $key => $courier_name ): ?>
                                        <div class="checkbox">
                                            <input type="checkbox" name="omniva_m_courier_options[]" id="courier-option-<?php echo $key; ?>" value="<?php echo $key; ?>" 
                                                <?php if (in_array($key, $omniva_m_courier_options)): ?>
                                                checked
                                                <?php endif; ?>
                                            >
                                            <label for="courier-option-<?php echo $key; ?>"><?php echo $courier_name; ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="help-block"><?php echo $omniva_m_help_courier_options; ?></p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-user"><?php echo $omniva_m_label_api_user; ?></label>
                                <div class="col-sm-8">
                                    <input type="text" name="omniva_m_api_user" value="<?php echo $omniva_m_api_user; ?>" id="input-api-user" class="form-control" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-pass"><?php echo $omniva_m_label_api_pass; ?></label>
                                <div class="col-sm-8">
                                    <input type="text" name="omniva_m_api_pass" value="<?php echo $omniva_m_api_pass; ?>" id="input-api-pass" class="form-control" />
                                </div>
                            </div>

                            <br/>
                            <!-- options -->
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-sendoff_type"><?php echo $omniva_m_label_api_sendoff_type; ?></label>
                                <div class="col-sm-8">
                                    <select name="omniva_m_api_sendoff_type" id="input-api-sendoff_type" class="form-control">
                                        <?php foreach($sendoff_types as $key => $sendofftype): ?>
                                        <option value="<?php echo $key; ?>" 
                                            <?php if ($omniva_m_api_sendoff_type == $key) { echo "selected"; } ?>
                                        ><?php echo $sendofftype; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-label-print_type"><?php echo $omniva_m_label_api_label_print_type; ?></label>
                                <div class="col-sm-8">
                                    <select name="omniva_m_api_label_print_type" id="input-api-label-print_type" class="form-control">
                                        <?php foreach($label_print_types as $key => $print_type): ?>
                                        <option value="<?php echo $key; ?>" 
                                            <?php if ($omniva_m_api_label_print_type == $key) { echo "selected"; } ?>
                                        ><?php echo $print_type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-add-comment"><?php echo $omniva_m_label_api_add_comment; ?></label>
                                <div class="col-sm-8">
                                    <select name="omniva_m_api_add_comment" id="input-api-add-comment" class="form-control">
                                        <option value="0"><?php echo $omniva_m_option_no; ?></option>
                                        <option value="1" 
                                            <?php if ($omniva_m_api_add_comment == 1) { echo "selected"; } ?>
                                        ><?php echo $omniva_m_option_yes; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="input-api-show-return-code"><?php echo $omniva_m_label_api_show_return_code; ?></label>
                                <div class="col-sm-8">
                                    <select name="omniva_m_api_show_return_code" id="input-api-show-return-code" class="form-control">
                                        <?php foreach($show_return_code_types as $key => $show_status): ?>
                                            <option value="<?php echo $key; ?>"
                                                <?php if ($omniva_m_api_show_return_code == $key) { echo "selected"; } ?>
                                            ><?php echo $show_status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="panel-footer clearfix">
                        <div class="pull-right">
                            <button type="submit" form="form-omniva_m-api" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                            <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sender Settings -->
        <div class="tab-pane" id="tab-sender-info">
            <div class="container-fluid">
                <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $omniva_m_title_sender_settings; ?></h3>
                </div>
                <div class="panel-body">
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-omniva_m-sender" class="form-horizontal">
                    <input type="hidden" name="sender_settings_update">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-name"><?php echo $omniva_m_label_sender_name; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_name" value="<?php echo $omniva_m_sender_name; ?>" id="input-sender-name" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-street"><?php echo $omniva_m_label_sender_street; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_street" value="<?php echo $omniva_m_sender_street; ?>" id="input-sender-street" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-postcode"><?php echo $omniva_m_label_sender_postcode; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_postcode" value="<?php echo $omniva_m_sender_postcode; ?>" id="input-sender-postcode" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-city"><?php echo $omniva_m_label_sender_city; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_city" value="<?php echo $omniva_m_sender_city; ?>" id="input-sender-city" class="form-control" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $omniva_m_label_sender_country; ?></label>
                        <div class="col-sm-10">
                        <select name="omniva_m_sender_country" class="js-select-sender" style="width: 100%">
                            <option value=""></option>
                            <?php foreach($countries as $country): ?>
                            <option value="<?php echo $country['iso_code_2']; ?>" 
                                <?php if ($country['iso_code_2'] == $omniva_m_sender_country) { echo "selected"; } ?>
                            ><?php echo $country['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-phone"><?php echo $omniva_m_label_sender_phone; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_phone" value="<?php echo $omniva_m_sender_phone; ?>" id="input-sender-phone" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sender-email"><?php echo $omniva_m_label_sender_email; ?></label>
                        <div class="col-sm-10">
                        <input type="text" name="omniva_m_sender_email" value="<?php echo $omniva_m_sender_email; ?>" id="input-sender-email" class="form-control" />
                        </div>
                    </div>
                    </form>
                </div>

                <div class="panel-footer clearfix">
                    <div class="pull-right">
                    <button type="submit" form="form-omniva_m-sender" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                    <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
                    </div>
                </div>
                </div>
            </div>

        </div>

        <!-- Price Settings -->
        <div class="tab-pane" id="tab-price">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-money"></i> <?php echo $omniva_m_title_price_settings; ?></h3>
                    </div>
                    <div class="panel-body">
                        <p class="help-block"><?php echo $omniva_m_help_price; ?></p>
                        <div id="price-table" class="form-horizontal">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="input-country"><?php echo $omniva_m_label_price_country; ?></label>
                                        <div class="col-sm-10">
                                            <select name="country" class="js-select2" style="width: 100%" data-placeholder="<?php echo $omniva_m_placeholder_price_country; ?>">
                                                <?php foreach($countries as $country): ?>
                                                <option value="<?php echo $country['iso_code_2']; ?>"><?php echo $country['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="help-block"><?php echo $omniva_m_help_price_country; ?></p>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="input-terminal-price"><?php echo $omniva_m_label_price_terminal; ?></label>
                                        <div class="col-sm-4">
                                            <input type="text" name="terminal_price" value="" id="input-terminal-price" class="form-control" />
                                        </div>

                                        <label class="col-sm-2 control-label" for="input-terminal-price-type"><?php echo $omniva_m_label_price_range_type; ?></label>
                                        <div class="col-sm-4">
                                            <select name="terminal_price_range_type" value="0" id="input-terminal-price-range-type" class="form-control">
                                                <?php foreach($price_range_types as $range_key => $price_range_type): ?>
                                                    <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="input-courier-price"><?php echo $omniva_m_label_price_courier; ?></label>
                                        <div class="col-sm-4">
                                            <input type="text" name="courier_price" value="" id="input-courier-price" class="form-control" />
                                        </div>

                                        <label class="col-sm-2 control-label" for="input-courier-price-range-type"><?php echo $omniva_m_label_price_range_type; ?></label>
                                        <div class="col-sm-4">
                                            <select name="courier_price_range_type" value="0" id="input-courier-price-range-type" class="form-control">
                                                <?php foreach($price_range_types as $range_key => $price_range_type): ?>
                                                    <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group text-center">
                                        <button id="add-price-btn" class="btn btn-default center"><?php echo $omniva_m_button_add_price; ?></button>
                                    </div>
                                </div> <!-- price panel heading -->

                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php echo $omniva_m_label_price_country; ?></th>
                                                <th><?php echo $omniva_m_label_price_terminal; ?></th>
                                                <th><?php echo $omniva_m_label_price_range_type; ?></th>
                                                <th><?php echo $omniva_m_label_price_courier; ?></th>
                                                <th><?php echo $omniva_m_label_price_range_type; ?></th>
                                                <th><?php echo $omniva_m_header_actions; ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="created-prices">
                                            <?php 
                                                $omniva_m_prices_style = "";
                                                if ($omniva_m_prices) {
                                                    $omniva_m_prices_style = "display: none";
                                                }
                                            ?>
                                            <tr id="no-price-notification" style="<?php echo $omniva_m_prices_style; ?>">
                                                <td colspan="6">No prices set</td>
                                            </tr>
                                            <?php foreach($omniva_m_prices as $price): ?>
                                                <tr data-price-row="<?php echo $price['country']; ?>" data-price-data='<?php echo json_encode($price); ?>'>
                                                    <td><?php echo $price['country_name']; ?></td>
                                                    <td><?php echo $price['terminal_price']; ?></td>
                                                    <td><?php echo $price_range_types[$price['terminal_price_range_type']]; ?></td>
                                                    <td><?php echo $price['courier_price']; ?></td>
                                                    <td><?php echo $price_range_types[$price['courier_price_range_type']]; ?></td>
                                                    <td>
                                                        <button data-edit-btn class="btn btn-primary"><i class="fa fa-edit"></i></button>
                                                        <button data-delete-btn class="btn btn-danger"><i class="fa fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div> <!-- price panel body -->
                            </div> <!-- price panel -->
                        </div>
                    </div> <!-- panel body -->
                </div> <!-- panel -->
            </div> <!-- container -->
        </div> <!-- tab-pane -->

        <!-- COD Settings -->
        <div class="tab-pane" id="tab-cod">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-money"></i> <?php echo $omniva_m_title_cod_settings; ?></h3>
                    </div>

                    <div class="panel-body">
                        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-omniva_m-cod" class="form-horizontal">
                            <input type="hidden" name="cod_settings_update">
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-cod-status"><?php echo $omniva_m_label_cod_status; ?></label>
                                <div class="col-sm-10">
                                    <select name="omniva_m_cod_status" id="input-cod-status" class="form-control">
                                        <?php if ($omniva_m_cod_status): ?>
                                        <option value="1" selected="selected"><?php echo $omniva_m_generic_enabled; ?></option>
                                        <option value="0"><?php echo $omniva_m_generic_disabled; ?></option>
                                        <?php else: ?>
                                        <option value="1"><?php echo $omniva_m_generic_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $omniva_m_generic_disabled; ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label"><?php echo $omniva_m_label_cod_options; ?></label>
                                <div class="col-sm-10">
                                    <div class="omniva_m-checkboxes">
                                        <?php foreach($cod_options as $key => $cod_name): ?>
                                        <div class="checkbox">
                                            <input type="checkbox" name="omniva_m_cod_options[]" id="cod-option-<?php echo $key; ?>" value="<?php echo $key; ?>" 
                                                <?php if (in_array($key, $omniva_m_cod_options)) { echo "checked"; } ?>
                                            >
                                            <label for="cod-option-<?php echo $key; ?>"><?php echo $cod_name; ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="help-block"><?php echo $omniva_m_help_cod_options; ?></p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-cod-receiver"><?php echo $omniva_m_label_cod_receiver; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="omniva_m_cod_receiver" value="<?php echo $omniva_m_cod_receiver; ?>" id="input-cod-receiver" class="form-control" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-cod-iban"><?php echo $omniva_m_label_iban; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="omniva_m_cod_iban" value="<?php echo $omniva_m_cod_iban; ?>" id="input-cod-iban" class="form-control" />
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="panel-footer clearfix">
                        <div class="pull-right">
                            <button type="submit" form="form-omniva_m-cod" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                            <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
                        </div>
                    </div>
                </div> <!-- panel -->
            </div>
        </div>

        <!-- Terminals Information -->
        <div class="tab-pane" id="tab-terminals">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-map-marker"></i> <?php echo $omniva_m_title_terminals; ?></h3>
                    </div>

                    <div class="panel-body omniva_m-terminal-info">
                        <div class="row">
                            <div class="col-sm-4 omniva_m-terminal-info-name"><?php echo $omniva_m_label_last_update; ?>:</div>
                            <div class="col-sm-8 bold omniva_m-terminal-last-update"><?php echo $last_update; ?></div>
                        </div>

                        <div class="row omniva_m-terminal-list">
                            <?php if (empty($terminals_info)): ?>
                                <div class="col-sm-12 omniva_m-terminal-info-name"><?php echo $omniva_m_help_terminals_empty; ?></div>
                            <?php endif; ?>
                            <?php foreach($terminals_info as $key => $loc_count): ?>
                                <div class="col-sm-4 omniva_m-terminal-info-name"><?php echo $key; ?>:</div>
                                <div class="col-sm-8 bold"><?php echo $loc_count; ?></div>
                            <?php endforeach; ?>
                        </div>

                        <div class="row">
                            <div class="col-sm-4 omniva_m-terminal-info-name"><?php echo $omniva_m_label_cron_url; ?>:</div>
                            <div class="col-sm-8 bold">
                                <?php if ($cron_url): ?>
                                <a target="_blank" href="<?php echo $cron_url; ?>"><?php echo $cron_url; ?></a>
                                <p class="help-block"><?php echo $omniva_m_help_terminals; ?></p>
                                <?php else: ?>
                                Secret not set
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel-footer clearfix">
                        <div class="pull-right">
                            <button id="update-terminals-btn" data-toggle="tooltip" title="<?php echo $omniva_m_button_update; ?>" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking Email -->
        <div class="tab-pane" id="tab-tracking-email">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-envelope"></i> <?php echo $omniva_m_title_tracking_email; ?></h3>
                    </div>
                    <div class="panel-body">
                        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-omniva_m-tracking-email" class="form-horizontal">
                            <input type="hidden" name="tracking_email_update">
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-tracking-email-status"><?php echo $omniva_m_label_tracking_email_status; ?></label>
                                <div class="col-sm-10">
                                    <select name="omniva_m_tracking_email_status" id="input-tracking-email-status" class="form-control">
                                        <?php if ($omniva_m_tracking_email_status): ?>
                                        <option value="1" selected="selected"><?php echo $omniva_m_generic_enabled; ?></option>
                                        <option value="0"><?php echo $omniva_m_generic_disabled; ?></option>
                                        <?php else: ?>
                                        <option value="1"><?php echo $omniva_m_generic_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $omniva_m_generic_disabled; ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-tracking-url"><?php echo $omniva_m_label_tracking_url; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="omniva_m_tracking_url" value="<?php echo $omniva_m_tracking_url; ?>" id="input-tracking-url" class="form-control" />
                                    <p class="help-block"><?php echo $omniva_m_help_tracking_url; ?></p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-tracking-email-subject"><?php echo $omniva_m_label_tracking_email_subject; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="omniva_m_tracking_email_subject" value="<?php echo $omniva_m_tracking_email_subject; ?>" id="input-tracking-email-subject" class="form-control" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-tracking-email-template"><?php echo $omniva_m_label_tracking_email_template; ?></label>
                                <div class="col-sm-10">
                                    <textarea name="omniva_m_tracking_email_template" id="input-tracking-email-template" class="form-control" rows="20"><?php echo $omniva_m_tracking_email_template; ?></textarea>
                                    <p class="help-block"><?php echo $omniva_m_help_tracking_email_template; ?></p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="panel-footer clearfix">
                        <div class="pull-right">
                            <button type="submit" form="form-omniva_m-tracking-email" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                            <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $omniva_m_generic_btn_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
                        </div>
                    </div>
                </div> <!-- panel -->
            </div>
        </div>

    </div> <!-- End Tab content -->

    <!-- Price EDIT Modal -->
    <div class="edit-price-modal" style="display: none;">
        <div class="panel panel-default col-xs-11 col-md-9 col-lg-7">
            <div class="panel-body form-horizontal">
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-modal-country"><?php echo $omniva_m_label_price_country; ?></label>
                    <div class="col-sm-10">
                        <input type="hidden" name="country" value="">
                        <input name="country_name" type="text" readonly="" value="" id="input-modal-country" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-modal-terminal-price"><?php echo $omniva_m_label_price_terminal; ?></label>
                    <div class="col-sm-4">
                        <input type="text" name="terminal_price" value="" id="input-modal-terminal-price" class="form-control" />
                    </div>

                    <label class="col-sm-2 control-label" for="input-modal-terminal-price-range-type"><?php echo $omniva_m_label_price_range_type; ?></label>
                    <div class="col-sm-4">
                        <select name="terminal_price_range_type" value="0" id="input-modal-terminal-price-range-type" class="form-control">
                            <?php foreach($price_range_types as $range_key => $price_range_type): ?>
                                <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-modal-courier-price"><?php echo $omniva_m_label_price_courier; ?></label>
                    <div class="col-sm-4">
                        <input type="text" name="courier_price" value="" id="input-modal-courier-price" class="form-control" />
                    </div>

                    <label class="col-sm-2 control-label" for="input-modal-courier-price-range-type"><?php echo $omniva_m_label_price_range_type; ?></label>
                    <div class="col-sm-4">
                        <select name="courier_price_range_type" value="0" id="input-modal-courier-price-range-type" class="form-control">
                            <?php foreach($price_range_types as $range_key => $price_range_type): ?>
                                <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group text-center">
                    <button id="save-price-btn" class="btn btn-default center"><?php echo $omniva_m_button_save_price; ?></button>
                    <button id="cancel-price-btn" class="btn btn-default center"><?php echo $omniva_m_generic_btn_cancel; ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="view/javascript/omniva_m/select2.min.css">
<script src="view/javascript/omniva_m/select2.min.js"></script>
<link rel="stylesheet" href="view/javascript/omniva_m/settings.css?20220401">
<script>
  $(document).ready(function() {
    $('.js-select-sender').select2();

    $('.js-select2').select2({
      sorter: data => data.sort((a, b) => a.text.localeCompare(b.text))
    });
  });
  var ajax_url = '<?php echo $ajax_url; ?>';
  var omniva_m_geo_zone_id = '<?php echo $omniva_m_geo_zone_id; ?>';
  var price_range_types = <?php echo json_encode($price_range_types); ?>;
  var omniva_m_current_tab = '#<?php echo $omniva_m_current_tab; ?>';
  const OMNIVA_DATA = {
      contractCourierOptions: <?php echo json_encode($courier_options); ?>,
      contractOrigins: <?php echo json_encode($contract_origins); ?>,
      contractEnableCourieroptions: '<?php echo $contract_enable_courier_services; ?>'
  };
</script>
<script src="view/javascript/omniva_m/settings.js?01"></script>

<?php echo $footer; ?>
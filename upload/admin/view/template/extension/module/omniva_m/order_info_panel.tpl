<link rel="stylesheet" href="view/javascript/omniva_m/settings.css?20220401">

<div id="omniva_m-panel" class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			<i class="fa fa-info-circle"></i>
			<?php echo $omniva_m_panel_title; ?>
		</h3>
	</div>
	<div class="panel-body">
		<ul class="nav nav-tabs">
			<li class="active">
				<a href="#tab-omniva_m-order-info" data-toggle="tab"><?php echo $omniva_m_tab_order_info; ?></a>
			</li>
			<li>
				<a href="#tab-omniva_m-history" data-toggle="tab"><?php echo $omniva_m_tab_history; ?> <span class="badge"><?php echo $omniva_m_order['label_history']['total']; ?></span></a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="tab-omniva_m-order-info">
				<div id="omniva_m-order-info">
                    <?php if ($omniva_m_order['oc_order']): ?>
                    <input type="hidden" name="omniva_m_order_id" value="<?php echo $omniva_m_order['oc_order']['order_id']; ?>">
					<div class="form-horizontal">
                        <?php if ($omniva_m_order['manifest_id'] > 0): ?>
                        <div class="omniva_m_alert alert-info"><?php echo $omniva_m_info_manifest_id . ' ' . $omniva_m_order['manifest_id']; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($omniva_m_order['label_history']['last_barcodes'] && $omniva_m_order['label_history']['last_barcodes'] !== "[]"): ?>
                        <div class="omniva_m_alert alert-info"><?php echo $omniva_m_info_last_barcodes . ' ' . $omniva_m_order['label_history']['last_barcodes']; ?></div>
                        <?php endif; ?>

                        <?php if ($omniva_m_order['label_history']['last_error']): ?>
                        <div class="omniva_m_alert alert-danger"><?php echo $omniva_m_info_last_error . ' ' . $omniva_m_order['label_history']['last_error']; ?></div>
                        <?php endif; ?>

                        <?php if ($omniva_m_order['shipping_types']['terminal'] == $omniva_m_order['shipping_type'] && !$omniva_m_order['terminal_data']): ?>
                        <div class="omniva_m_alert alert-danger"><?php echo $omniva_m_warning_no_terminal; ?></div>
                        <?php endif; ?>

                        <?php if ($omniva_m_order['order_data']): ?>
                        <div class="omniva_m_alert alert-warning"><?php echo $omniva_m_warning_order_data_changed; ?></div>
                        <?php endif; ?>

                        <!-- Multiparcel only for Courier -->
                        <?php if ($omniva_m_order['shipping_types']['courier'] == $omniva_m_order['shipping_type']): ?>
                        <div class="form-group">
							<label class="col-sm-2 control-label" for="input-omniva_m-multiparcel"><?php echo $omniva_m_label_multiparcel; ?></label>
							<div class="col-sm-10">
                                <select name="omniva_m_multiparcel" id="input-omniva_m-multiparcel" class="form-control">
                                    <?php for ($amount = 1; $amount < 6; $amount++): ?>
                                        <option value="<?php echo $amount; ?>" 
                                            <?php if ($amount == $omniva_m_order['multiparcel']) { echo "selected"; } ?>
                                        ><?php echo $amount; ?></option>
                                    <?php endfor; ?>
                                </select>
							</div>
						</div>
                        <?php endif; ?>
                        
                        <!-- Parcel weight -->
                        <div class="form-group">
							<label class="col-sm-2 control-label" for="input-omniva_m-set-weight"><?php echo $omniva_m_label_total_weight; ?></label>
							<div class="col-sm-10">
                                <div class="input-group">
                                    <input type="text" name="omniva_m_set_weight" value="<?php echo $omniva_m_order['set_weight']; ?>" id="input-omniva_m-total-weight" class="form-control" />
                                    <span class="input-group-addon">kg</span>
                                </div>
                                <?php if ($omniva_m_order['shipping_types']['courier'] == $omniva_m_order['shipping_type']): ?>
                                <p class="help-block"><?php echo $omniva_m_help_weight_multiparcel; ?></p>
                                <?php endif; ?>
                                <?php if ($omniva_m_order['terminal_overweight']): ?>
                                <div class="omniva_m_alert alert-warning"><?php echo $omniva_m_warning_overweight; ?> <?php echo $omniva_m_order['terminal_max_weight']; ?>kg</div>
                                <?php endif; ?>
							</div>
						</div>

                        <!-- COD -->
                        <?php if ($omniva_m_order['cod']['order_use'] && !$omniva_m_order['cod']['enabled']): ?>
                        <div class="omniva_m_alert alert-danger"><?php echo $omniva_m_warning_cod_used; ?></div>
                        <?php endif; ?>

                        <?php if ($omniva_m_order['cod']['enabled']): ?>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-omniva_m-cod-use"><?php echo $omniva_m_label_cod_use; ?></label>
                                <div class="col-sm-4">
                                    <select name="omniva_m_cod_use" id="input-omniva_m-cod-use" class="form-control">
                                        <option value="0"><?php echo $omniva_m_option_no; ?></option>
                                        <option value="1" 
                                            <?php if ($omniva_m_order['cod']['use']) { echo 'selected'; } ?>
                                        ><?php echo $omniva_m_option_yes; ?></option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label" for="input-omniva_m-cod-amount"><?php echo $omniva_m_label_cod_amount; ?></label>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <input type="text" name="omniva_m_cod_amount" value="<?php echo $omniva_m_order['cod']['amount']; ?>" id="input-omniva_m-cod-amount" class="form-control" />
                                        <span class="input-group-addon">&euro;</span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($omniva_m_order['cod']['amount'] != $omniva_m_order['cod']['oc_amount']): ?>
                            <div class="omniva_m_alert alert-warning"><?php echo str_replace(['$$cod_amount$$', '$$order_amount$$'], [omniva_m_order['cod']['amount'], omniva_m_order['cod']['oc_amount']], $omniva_m_warning_cod_amount_mismatch); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="omniva_m-panel-buttons-wrapper">
                            <a href="#" class="btn btn-default omniva_m-register-label-btn"><?php echo $omniva_m_btn_register_label; ?></a>
                            <?php if ($omniva_m_order['label_history']['last_barcodes'] && $omniva_m_order['label_history']['last_barcodes'] != "[]"): ?>
                            <a href="#" class="btn btn-default omniva_m-print-label-btn"><?php echo $omniva_m_btn_print_label; ?></a>
                            <?php endif; ?>
                            <a href="#" class="btn btn-default omniva_m-save-data-btn"><?php echo $omniva_m_btn_save_data; ?></a>
                        </div>
					</div>
                    <?php else: ?>
                    <div class="omniva_m_alert omniva_m_alert-danger"><?php echo $omniva_m_error_no_oc_order; ?></div>
                    <?php endif; ?>
				</div>
			</div>
			<div class="tab-pane" id="tab-omniva_m-history">
				<div id="omniva_m-history">
					<div class="table-responsive">
						<table class="table table-bordered">
							<thead>
								<tr>
									<td><?php echo $omniva_m_header_date; ?></td>
									<td><?php echo $omniva_m_header_service_code; ?></td>
									<td><?php echo $omniva_m_header_tracking_numbers; ?></td>
									<td><?php echo $omniva_m_header_actions; ?></td>
								</tr>
							</thead>
							<tbody id="omniva_m-label_history">
                                <?php if (empty($omniva_m_label_history)): ?>
                                <tr>
                                    <td colspan='4'><?php echo $omniva_m_history_empty; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach($omniva_m_label_history as $omniva_m_lh): ?>
                                <tr class="<?php if ($omniva_m_lh['is_error'] == 1) { echo "alert-danger"; } ?>">
                                    <td><?php echo $omniva_m_lh['date_add']; ?></td>
                                    <?php if ($omniva_m_lh['barcodes'] != "[]"): ?>
                                        <td><?php echo $omniva_m_lh['service_code']; ?></td>
                                        <?php if ($omniva_m_lh['is_error'] == 1): ?>
                                            <td colspan="2"><?php echo $omniva_m_lh['barcodes']; ?></td>
                                        <?php else: ?>
                                            <td><?php echo $omniva_m_lh['barcodes']; ?></td>
                                            <td>
                                                <a class="btn btn-default omniva_m-print-history-label-btn"
                                                    href="#" data-history-id="<?php echo $omniva_m_lh['id_label_history']; ?>">Print</a>
                                            </td>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <td colspan="3"><?php echo $omniva_m_lh['service_code']; ?></td>
                                    <?php endif; ?>
                                </tr>                                
                                <?php endforeach; ?>
                            </tbody>
						</table>
					</div>
				</div>
			</div>
            <div id="omniva_m-response-info" class="omniva_m_alert hidden"></div>
		</div>
	</div>
    <div class="omniva_m-panle-overlay hidden">
        <div class="bs5-spinner-border text-warning"></div>
    </div>
</div>
<script>
    const OMNIVA_M_ORDER_DATA = <?php echo json_encode($omniva_m_order); ?>;
    const OMNIVA_M_INFO_PANEL_TRANSLATION = <?php echo json_encode($omniva_m_info_panel_translation); ?>;
</script>
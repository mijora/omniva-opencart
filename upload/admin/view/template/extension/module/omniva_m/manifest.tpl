<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content" class="omniva_m-overlay">
    <div class="page-header">
        <div class="container-fluid">
            <h1><img src="view/image/omniva_m/logo.png" alt="Omniva Logo"></h1>
            <div id="header-action-buttons" class="pull-right">
                <button type="button" data-toggle="tooltip" title="" 
                    onclick="$('#filter-order').toggleClass('hidden-sm hidden-xs');" class="btn btn-default hidden-md hidden-lg" data-original-title="<?php echo $omniva_m_title_filters; ?>">
                    <i class="fa fa-filter"></i>
                </button>
                <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                    data-action="printLabels"
                    data-original-title="<?php echo $omniva_m_tooltip_print_labels; ?>" data-toggle="tooltip"
                >
                    <i class="fa fa-print"></i>
                    <div class="bs5-spinner-border hidden"></div>
                </a>
                <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                    data-action="createManifest"
                    data-original-title="<?php echo $omniva_m_tooltip_create_manifest; ?>" data-toggle="tooltip"
                >
                    <i class="fa fa-file-pdf-o"></i>
                    <div class="bs5-spinner-border hidden"></div>
                </a>
                <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                    data-action="callCourier"
                    data-original-title="<?php echo $omniva_m_tooltip_call_courier; ?>" data-toggle="tooltip"
                >
                    <i class="fa fa-truck"></i>
                    <div class="bs5-spinner-border hidden"></div>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div id="filter-order" class="col-md-3 col-md-push-9 col-sm-12 hidden-sm hidden-xs">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-filter"></i> <?php echo $omniva_m_title_filters; ?></h3>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="control-label" for="input-order-id"><?php echo $omniva_m_label_order_id; ?></label>
                        <input type="text" name="filter_order_id" value="" id="input-order-id" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="input-barcode"><?php echo $omniva_m_label_customer; ?></label>
                        <input type="text" name="filter_customer" value="" id="input-customer" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="input-barcode"><?php echo $omniva_m_label_barcode; ?></label>
                        <input type="text" name="filter_barcode" value="" id="input-barcode" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="input-has-barcode"><?php echo $omniva_m_label_has_barcode; ?></label>
                        <select name="filter_has_barcode" id="input-has-barcode" class="form-control">
                            <option value="0">-</option>
                            <option value="1"><?php echo $omniva_m_option_no; ?></option>
                            <option value="2"><?php echo $omniva_m_option_yes; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="input-has-manifest"><?php echo $omniva_m_label_has_manifest; ?></label>
                        <select name="filter_has_manifest" id="input-has-manifest" class="form-control">
                            <option value="0">-</option>
                            <option value="1"><?php echo $omniva_m_option_no; ?></option>
                            <option value="2"><?php echo $omniva_m_option_yes; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="input-order-status"><?php echo $omniva_m_label_order_status_id; ?></label>
                        <select name="filter_order_status_id" id="input-order-status" class="form-control">
                            <option value="0">-</option>
                            <?php foreach($order_statuses as $order_status): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                            <?php endforeach; ?>            
                        </select>
                    </div>

                    <div class="form-group text-right">
                        <button type="button" id="button-filter" class="btn btn-default"><i class="fa fa-filter"></i> <?php echo $omniva_m_btn_filter; ?></button>
                    </div>
                </div>
            </div>
        </div> <!-- filter end -->

        <div class="col-md-9 col-md-pull-3 col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $omniva_m_title_manifest_orders; ?></h3>
                </div>

                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td style="width: 1px;" class="text-center"><input id="check-all-input" type="checkbox"/></td>
                                    <td class="text-right"><?php echo $omniva_m_column_order_id; ?></td>
                                    <td class="text-left"><?php echo $omniva_m_column_customer; ?></td>
                                    <td class="text-left"><?php echo $omniva_m_column_status; ?></td>
                                    <td class="text-right"><?php echo $omniva_m_column_barcode; ?></td>
                                    <td class="text-right"><?php echo $omniva_m_column_manifest_id; ?></td>
                                    <td class="text-right"><?php echo $omniva_m_column_action; ?></td>
                                </tr>
                            </thead>
                            <tbody id="omniva_m-manifest-orders">
                                <tr>
                                    <td class="text-center" colspan="7"><?php echo $omniva_m_manifest_orders_no_results; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div id="omniva_m_pagination" class="col-sm-12 text-center hidden">
                            <ul class="pagination">
                                <li>
                                    <a href="#" class="omniva_m-btn-previous">&lt;&lt;</a>
                                </li>
                                <li class="active"><span class="omniva_m-current-page">1</span><span>/</span><span class="omniva_m-total-pages">9999</span></li>
                                <li>
                                    <a href="#" class="omniva_m-btn-next">&gt;&gt;</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- results end -->
    </div> <!-- row end -->
</div> <!-- content end -->

<link rel="stylesheet" href="view/javascript/omniva_m/settings.css">
<script>
    const OMNIVA_M_DATA = <?php echo json_encode($omniva_m_data); ?>;
</script>
<script src="view/javascript/omniva_m/manifest.js?20220401"></script>

<?php echo $footer; ?> 
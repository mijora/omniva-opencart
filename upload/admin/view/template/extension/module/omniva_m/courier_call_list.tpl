<link rel="stylesheet" href="view/javascript/omniva_m/settings.css">
<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content" class="omniva_m-overlay" data-courier-calls>
    <div class="page-header">
        <div class="container-fluid">
            <h1><img src="view/image/omniva_m/logo.png" alt="Omniva Logo"></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $omniva_m_title_courier_calls; ?></h3>
                </div>

                <div class="panel-body">
                    <div class="table-responsive">
                        <p class="help-block"><?php echo $omniva_m_help_courier_calls_list; ?></p>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td class="text-right"><?php echo $omniva_m_column_call_id; ?></td>
                                    <td class="text-left"><?php echo $omniva_m_column_timerange; ?></td>
                                    <td class="text-right"><?php echo $omniva_m_column_action; ?></td>
                                </tr>
                            </thead>
                            <tbody id="omniva_m-courier_calls" data-omnivam-table>
                                <?php if ($callTimes): ?>
                                    <?php foreach ($callTimes as $key => $callTime): ?>
                                        <tr>
                                            <td class="text-right"><?php echo $key; ?></td>
                                            <td class="text-left"><?php echo $callTime; ?></td>
                                            <td class="text-right">
                                                <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                                                    data-call-id="<?php echo $key; ?>" data-action="cancelCourierCall"
                                                    data-original-title="<?php echo $omniva_m_courier_call_list_action_tootip_cancel; ?>" data-toggle="tooltip"
                                                >
                                                    <i class="fa fa-ban"></i>
                                                    <div class="bs5-spinner-border hidden"></div>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="text-center" colspan="3"><?php echo $omniva_m_courier_calls_no_results; ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>  <!-- results end -->
    </div> <!-- row end -->
</div> <!-- content end -->

<script>
    const OMNIVA_M_DATA = <?php echo json_encode($omniva_m_data); ?>;
</script>
<!-- using manifest js for simplicity -->
<script src="view/javascript/omniva_m/manifest.js?202311131643"></script>

<?php echo $footer; ?>

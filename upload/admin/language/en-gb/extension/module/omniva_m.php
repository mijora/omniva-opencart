<?php

// Panel title
$_['omniva_m_panel_title'] = 'Omniva';

// Tabs
$_['omniva_m_tab_order_info'] = 'Omniva order information';
$_['omniva_m_tab_history'] = 'Label history';

// Order information
$_['omniva_m_info_manifest_id'] = 'Order added to manifest ID:';
$_['omniva_m_info_last_barcodes'] = 'Latest registered tracking numbers:';
$_['omniva_m_info_last_error'] = 'Last error:';
$_['omniva_m_label_total_weight'] = 'Total weight';
$_['omniva_m_label_multiparcel'] = 'Multiparcel';
$_['omniva_m_label_cod_use'] = 'C.O.D';
$_['omniva_m_label_cod_amount'] = 'Amount';
$_['omniva_m_option_yes'] = 'Yes';
$_['omniva_m_option_no'] = 'No';

// History table
$_['omniva_m_header_date'] = 'Date';
$_['omniva_m_header_service_code'] = 'Service Code';
$_['omniva_m_header_tracking_numbers'] = 'Tracking Number';
$_['omniva_m_header_actions'] = 'Actions';
$_['omniva_m_history_empty'] = 'Order has no label history';

// Panel buttons
$_['omniva_m_btn_register_label'] = 'Register Label';
$_['omniva_m_btn_print_label'] = 'Print Label';
$_['omniva_m_btn_save_data'] = 'Save Order Data';

// Manifest page
$_['omniva_m_title_manifest_orders'] = 'Orders with Omniva shipping';
$_['omniva_m_column_order_id'] = 'Order ID';
$_['omniva_m_column_customer'] = 'Customer';
$_['omniva_m_column_status'] = 'Status';
$_['omniva_m_column_barcode'] = 'Barcode';
$_['omniva_m_column_manifest_id'] = 'Manifest ID';
$_['omniva_m_column_action'] = 'Actions';
$_['omniva_m_manifest_orders_no_results'] = 'No results!';
$_['omniva_m_title_filters'] = 'Filters';
$_['omniva_m_label_order_id'] = 'Order ID';
$_['omniva_m_label_customer'] = 'Customer';
$_['omniva_m_label_barcode'] = 'Barcode';
$_['omniva_m_label_order_status_id'] = 'Order Status';
$_['omniva_m_label_has_barcode'] = 'Has Barcode';
$_['omniva_m_label_has_manifest'] = 'Is In Manifest';
$_['omniva_m_tooltip_print_labels'] = 'Print / Register Labels';
$_['omniva_m_tooltip_create_manifest'] = 'Create Manifest';
$_['omniva_m_tooltip_call_courier'] = 'Call Courier';
$_['omniva_m_btn_filter'] = 'Filter';

// General messages
$_['omniva_m_help_weight_multiparcel'] = 'If multiparcel is used, weight will be divided by multiparcel number.';

$_['omniva_m_error_no_oc_order'] = 'Could not find Opencart Order Information!';
$_['omniva_m_error_no_barcodes_found'] = 'No barcode information received';
$_['omniva_m_error_missing_origin'] = 'Bad contract Origin! Please check your module settings!';
$_['omniva_m_error_nothing_in_manifest'] = 'No order added to manifest. Please inspect selected orders for issues.';

$_['omniva_m_warning_no_terminal'] = 'Could not find terminal, it might have been removed from system!';
$_['omniva_m_warning_overweight'] = 'Warning! Total weight is over terminal limit:';
$_['omniva_m_warning_cod_used'] = 'Warning! COD payment detected, but COD is disabled in module!';
$_['omniva_m_warning_cod_amount_mismatch'] = 'Warning! Set COD amount [ $$cod_amount$$ ] does not match Order amount [ $$order_amount$$ ]!';
$_['omniva_m_warning_order_data_changed'] = 'Warning! You have changed some of bellow information manualy!';

// Translations for js code
$_['omniva_m_js_label_registered'] = 'Label registered. Please wait for page to refresh within 5s';
$_['omniva_m_js_bad_response'] = 'Omniva_m: bad response from server';
$_['omniva_m_js_order_saved'] = 'Changes saved. Please wait for page to refresh within 5s';
$_['omniva_m_js_order_not_saved'] = 'Failed to save order changes';
$_['omniva_m_js_no_data_changes'] = 'No data changes';
$_['omniva_m_js_confirm_new_label'] = 'Order has registered tracking number. Are you sure you want to generate new?';
$_['omniva_m_js_refresh_now_btn'] = 'Refresh now';
$_['omniva_m_js_filter_label_omniva_only'] = 'Show Only Omniva Orders';
$_['omniva_m_js_btn_no'] = 'No';
$_['omniva_m_js_btn_yes'] = 'Yes';
$_['omniva_m_js_option_no'] = 'No';
$_['omniva_m_js_option_yes'] = 'Yes';
$_['omniva_m_js_tooltip_btn_print_register'] = 'Omniva: Register/Print Labels';
$_['omniva_m_js_tooltip_btn_call_courier'] = 'Omniva: Call Courier';
$_['omniva_m_js_tooltip_btn_manifest'] = 'Omniva: Print Manifest';
$_['omniva_m_js_confirm_create_manifest'] = 'Create manifest with orders? Order list:';
$_['omniva_m_js_confirm_call_courier'] = 'Courier will be called to:';
$_['omniva_m_js_confirm_print_labels'] = 'Please be aware that orders without registered label will be registered! Continue?';
$_['omniva_m_js_alert_no_orders'] = 'No Orders with Omniva shipping method selected!';
$_['omniva_m_js_alert_response_error'] = 'There was a problem: ';
$_['omniva_m_js_alert_no_pdf'] = 'No PDF returned';
$_['omniva_m_js_alert_bad_response'] = 'Bad response from server!';
$_['omniva_m_js_notify_courrier_called'] = 'Request for courier pickup sent';
$_['omniva_m_js_notify_courrier_call_failed'] = 'Request for courier pickup failed';
$_['omniva_m_js_no_results'] = 'No results!';

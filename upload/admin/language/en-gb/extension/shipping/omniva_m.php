<?php

//Menu
$_['omniva_m_menu_head'] = 'Omniva';
$_['omniva_m_menu_manifest'] = 'Manifest';
$_['omniva_m_menu_settings'] = 'Settings';
$_['omniva_m_menu_called_couriers'] = 'Active Courier Calls';

// Heading - without prefix as thats what opencart expects
$_['heading_title'] = 'Omniva';

// Breadcrumb
$_['omniva_m_text_extension'] = 'Extensions';

// Module new version notification
$_['omniva_m_new_version_notify'] = 'There is new module version v$$omniva_m_new_version$$!';
$_['omniva_m_button_download_version'] = 'Download';
// DB fix notification
$_['omniva_m_db_fix_notify'] = 'Problems found with DB tables';
$_['omniva_m_button_fix_db'] = 'FIX IT';
// XML fix notification
$_['omniva_m_xml_fix_notify'] = 'Newer version of modification file found for system/omniva_m_base.ocmod.xml';
$_['omniva_m_button_fix_xml'] = 'Update file';
$_['omniva_m_xml_updated'] = 'system/omniva_m_base.ocmod.xml updated. Please refresh modifications now.';

// Generic Options
$_['omniva_m_generic_none'] = '--- None ---';
$_['omniva_m_generic_enabled'] = 'Enabled';
$_['omniva_m_generic_disabled'] = 'Disabled';

// Generic Buttons
$_['omniva_m_generic_btn_save'] = 'Save';
$_['omniva_m_generic_btn_cancel'] = 'Cancel';

// Tabs
$_['omniva_m_tab_api'] = 'API';
$_['omniva_m_tab_general'] = 'General';
$_['omniva_m_tab_sender_info'] = 'Sender';
$_['omniva_m_tab_price'] = 'Price';
$_['omniva_m_tab_cod'] = 'C.O.D';
$_['omniva_m_tab_terminals'] = 'Terminals';
$_['omniva_m_tab_tracking_email'] = 'Tracking E-mail';
$_['omniva_m_tab_advanced'] = 'Advanced Settings';

// API Tab
$_['omniva_m_title_api_settings'] = 'API settings';
$_['omniva_m_placeholder_api_url'] = 'https://edipost.xml.ee';
$_['omniva_m_label_api_url'] = 'Endpoint URL';
$_['omniva_m_label_contract_origin'] = 'Contract Origin';
$_['omniva_m_label_api_user'] = 'Username';
$_['omniva_m_label_api_pass'] = 'Password';
$_['omniva_m_label_api_sendoff_type'] = 'Send parcel by';
$_['omniva_m_label_api_label_print_type'] = 'Print labels';
$_['omniva_m_label_api_add_comment'] = 'Add Order ID as comment to label';
$_['omniva_m_label_api_show_return_code'] = 'Show return code to customers';
$_['omniva_m_option_courier'] = 'Courier';
$_['omniva_m_option_terminal'] = 'Terminal';
$_['omniva_m_option_sorting_center'] = 'Sorting center';
$_['omniva_m_option_label_print_a4'] = 'A4 (4 labels)';
$_['omniva_m_option_label_print_a6'] = 'A6 (1 label)';
$_['omniva_m_option_contract_estonia'] = 'Estonia';
$_['omniva_m_option_contract_other'] = 'Other';
$_['omniva_m_option_courier_estonia'] = 'Estonian courier service';
$_['omniva_m_option_courier_finland'] = 'Finland courier service';
$_['omniva_m_option_no'] = 'No';
$_['omniva_m_option_yes'] = 'Yes';
$_['omniva_m_help_extra_charges_note'] = "Please note that extra charges may apply. For more information, contact your Omniva`s business customer support.";

// General Tab
$_['omniva_m_title_edit'] = 'General settings';
$_['omniva_m_label_tax_class'] = 'Tax class';
$_['omniva_m_label_length_class'] = 'Length class (cm)';
$_['omniva_m_label_weight_class'] = 'Weight class (kg)';
$_['omniva_m_label_geo_zone'] = 'Geo zone';
$_['omniva_m_option_all_zones'] = 'All Zones';
$_['omniva_m_label_status'] = 'Module status';
$_['omniva_m_label_sort_order'] = 'Sort order';
$_['omniva_m_label_order_status_registered'] = 'Order status';
$_['omniva_m_help_order_status_registered'] = 'What order status to set after successful label registration.';
$_['omniva_m_label_order_status_error'] = 'Register error status';
$_['omniva_m_help_order_status_error'] = 'What order status to set after failed label registration.';
$_['omniva_m_label_disable_cart_weight_check'] = 'Disable cart weight check for terminals';
$_['omniva_m_help_disable_cart_weight_check'] = 'If set to YES disables total cart weight check (max 25kg) for terminals';
$_['omniva_m_label_use_simple_terminal_check'] = 'Use simple terminal fit check';
$_['omniva_m_help_use_simple_terminal_check'] = 'Checks if cart fits terminal box dimensions W38 - H39 - L64.';
$_['omniva_m_help_length_class'] = 'Select length class used for centimeters';
$_['omniva_m_help_weight_class'] = 'Select weight class used for kilograms';

// Sender Tab
$_['omniva_m_title_sender_settings'] = 'Sender Information';
$_['omniva_m_label_sender_name'] = 'Name';
$_['omniva_m_label_sender_street'] = 'Street';
$_['omniva_m_label_sender_postcode'] = 'Postcode';
$_['omniva_m_label_sender_city'] = 'City';
$_['omniva_m_label_sender_country'] = 'Country';
$_['omniva_m_label_sender_phone'] = 'Mob. phone';
$_['omniva_m_label_sender_email'] = 'E-mail';

// Price Tab
$_['omniva_m_title_price_settings'] = 'Price Settings';
$_['omniva_m_label_price_country'] = 'Country';
$_['omniva_m_label_price_col'] = 'Price Data';
$_['omniva_m_label_price_terminal'] = 'Terminal price';
$_['omniva_m_label_price_courier'] = 'Courier price';
$_['omniva_m_label_price_premium'] = 'Premium price';
$_['omniva_m_label_price_standard'] = 'Standard price';
$_['omniva_m_label_price_economy'] = 'Economy price';
$_['omniva_m_label_price_range_type'] = 'Range type';
$_['omniva_m_button_add_price'] = 'Add Price';
$_['omniva_m_button_save_price'] = 'Save Price';
$_['omniva_m_placeholder_price_country'] = 'Select country';
$_['omniva_m_header_actions'] = 'Actions';
$_['omniva_m_help_price'] = 'Set -1 (negative price) in price field to disable that option for particular country.';
$_['omniva_m_help_price_country'] = 'Selection is limited to set Geo Zone';
$_['omniva_m_range_type_cart'] = 'Cart Total';
$_['omniva_m_range_type_weight'] = 'Cart Weight';
$_['omniva_m_help_courier_options'] = 'Only for contracts based in Estonia. Please select courier services expected to be used.';

// COD Tab
$_['omniva_m_title_cod_settings'] = 'C.O.D Settings';
$_['omniva_m_label_cod_status'] = 'Status';
$_['omniva_m_label_cod_options'] = 'Payment Options';
$_['omniva_m_label_cod_receiver'] = 'Receiver name';
$_['omniva_m_label_bic'] = 'BIC';
$_['omniva_m_label_iban'] = 'IBAN';
$_['omniva_m_help_cod_options'] = 'Select payment options that are for C.O.D';

// Terminals Tab
$_['omniva_m_title_terminals'] = 'Terminals information';
$_['omniva_m_label_last_update'] = 'Last update';
$_['omniva_m_label_total_terminals'] = 'Total terminals';
$_['omniva_m_label_cron_url'] = 'CRON URL';
$_['omniva_m_button_update'] = 'Update Now';
$_['omniva_m_help_terminals'] = 'Use this link to setup automated terminals update (Cron Job)';
$_['omniva_m_help_terminals_empty'] = 'No terminal data, please update terminal list';

// Tracking Email Tab
$_['omniva_m_title_tracking_email'] = 'Tracking URL e-mail';
$_['omniva_m_label_tracking_email_status'] = 'Status';
$_['omniva_m_label_tracking_url'] = 'Tracking URL';
$_['omniva_m_label_tracking_email_subject'] = 'E-mail subject';
$_['omniva_m_label_tracking_email_template'] = 'E-mail template';
$_['omniva_m_error_tracking_email_disabled'] = 'Tracking URL e-mail is disabled';
$_['omniva_m_help_tracking_url'] = '@ will be replaced with tracking number';
$_['omniva_m_help_tracking_email_template'] = '{{ tracking_url }} - key where to insert tracking URL, to insert just tracking number please use {{ tracking_number }}';

// General Errors
$_['omniva_m_error_permission'] = 'Warning: You do not have permission to modify Omniva module settings!';

// General Messages
$_['omniva_m_msg_setting_saved'] = 'Omniva module settings saved';
$_['omniva_m_alert_settings'] = 'To see other settings please configure API and Sender first';

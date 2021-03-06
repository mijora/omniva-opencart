<?php

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Order;
use Mijora\OmnivaOpencart\Params;

class ModelExtensionModuleOmnivaMOrder extends Model
{
    public function loadOrder($id_order)
    {
        $this->load->model('sale/order');

        $data = [
            'oc_order' => null,
            'shipping_type' => Params::SHIPPING_TYPE_COURIER,
            'terminal_data' => false,
            'terminal_max_weight' => Params::TERMINAL_MAX_WEIGHT,
            'terminal_overweight' => false,
            'shipping_types' => [
                'courier' => Params::SHIPPING_TYPE_COURIER,
                'terminal' => Params::SHIPPING_TYPE_TERMINAL
            ],
            'multiparcel' => 1,
            'cod' => [
                'enabled' => false,
                'use' => 0,
                'amount' => 0,
                'oc_amount' => 0,
                'order_use' => false // identifies if OC order uses COD (works only if settings are correct)
            ],
            'order_data' => false, // any chages that differs from original order information for omniva
            'label_history' => [
                'total' => 0,
                'last_error' => false,
                'last_barcodes' => false
            ],
            'total_weight' => 0.1, // default set as 0.1kg
            'set_weight' => 0.1, // default set as 0.1kg
            'manifest_id' => 0, // default no manifest
        ];

        $oc_order = $this->model_sale_order->getOrder((int) $id_order);

        if (!$oc_order) {
            return $data;
        }

        $data['ajax_url'] = 'index.php?route=extension/module/omniva_m/ajax&' . $this->getUserToken();

        $data['oc_order'] = $oc_order;

        // load override information
        $order_data = new Order((int) $id_order, $this->db, true);

        $data['order_data'] = $order_data->getDataAll();

        unset($data['order_data']['manifest_id']);
        if (empty($data['order_data'])) {
            $data['order_data'] = false;
        }

        $data['manifest_id'] = $order_data->getData('manifest_id');

        if ($order_data->getData('multiparcel') !== null) {
            $data['multiparcel'] = (int) $order_data->getData('multiparcel');
        }

        // add cod information
        $cod_enabled = (bool) $this->config->get(Params::PREFIX . 'cod_status');
        $data['cod']['enabled'] = $cod_enabled;
        $oc_total_amount = round((float) $oc_order['total'] * (float) $oc_order['currency_value'], 2);
        $data['cod']['amount'] = $oc_total_amount;
        $data['cod']['oc_amount'] = $oc_total_amount;

        if ($order_data->getData('cod_amount') !== null) {
            $data['cod']['amount'] = (float) $order_data->getData('cod_amount');
        }

        $cod_options = @json_decode($this->config->get(Params::PREFIX . 'cod_options'), true);
        if (!is_array($cod_options)) {
            $cod_options = [];
        }
        if (in_array($oc_order['payment_code'], $cod_options)) {
            $data['cod']['use'] = 1;
            $data['cod']['order_use'] = true;

            if ($order_data->getData('cod_use') !== null) {
                $data['cod']['use'] = (int) $order_data->getData('cod_use');
            }
        }

        $data['total_weight'] = $this->getOrderWeight($id_order);
        $data['set_weight'] = $data['total_weight'];

        if ($order_data->getData('weight') !== null) {
            $data['set_weight'] = (float) $order_data->getData('weight');
        }

        if (strpos($oc_order['shipping_code'], 'omniva_m.terminal_') === 0) {
            $data['shipping_type'] = Params::SHIPPING_TYPE_TERMINAL;

            $zip = str_replace('omniva_m.terminal_', '', $oc_order['shipping_code']);
            $terminal_data = Helper::getTerminalByZip($oc_order['shipping_iso_code_2'], $zip);
            $data['terminal_data'] = $terminal_data;

            $data['terminal_overweight'] = $data['set_weight'] > Params::TERMINAL_MAX_WEIGHT;
        }

        // data for history tab
        $data['label_history']['total'] = $this->getOrderHistoryTotal($id_order);

        $last_history_record = $this->loadOrderHistory($id_order, 1, 1);

        if ($last_history_record) {
            if ((int) $last_history_record['is_error'] === 1) {
                $data['label_history']['last_error'] = $last_history_record['barcodes'];
            } else {
                $data['label_history']['last_barcodes'] = $last_history_record['barcodes'];
            }
        }

        return $data;
    }

    public function getOrderIdsByManifestId($manifest_id)
    {
        $result = $this->db->query("
            SELECT order_id FROM `" . DB_PREFIX . "omniva_m_order_data`
            WHERE manifest_id = '" . (int) $manifest_id . "'
        ");

        if (!$result->rows) {
            return [];
        }

        $ids = [];
        foreach ($result->rows as $row) {
            $ids[] = $row['order_id'];
        }

        return $ids;
    }

    public function loadManifestOrders($filter)
    {
        $sql = $this->buildManifestQuery($filter);

        return $this->db->query($sql)->rows;
    }

    public function loadManifestOrdersTotal($filter)
    {
        $sql = $this->buildManifestQuery($filter, true);

        $result = $this->db->query($sql);
        return isset($result->row['total_orders']) ? (int) $result->row['total_orders'] : 0;
    }

    public function buildManifestQuery($filter, $count_only = false)
    {
        $sql = "
            SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, 
            (
                SELECT os.name FROM " . DB_PREFIX . "order_status os 
                WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ) AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value,
            o.date_added, o.date_modified, omod.manifest_id, omlh.barcodes, omlh.is_error
        ";

        if ($count_only) {
            $sql = "
                SELECT COUNT(o.order_id) as total_orders
            ";
        }

        $sql .= "
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "omniva_m_order_data` omod ON omod.order_id = o.order_id
            LEFT JOIN `" . DB_PREFIX . "omniva_m_label_history` omlh ON omlh.order_id = o.order_id AND omlh.`id_label_history` IN (
            	SELECT MAX(id_label_history) as latest_history_id 
                FROM `" . DB_PREFIX . "omniva_m_label_history`
                GROUP BY order_id
            )
            WHERE o.shipping_code LIKE 'omniva_m.%'
        ";

        if ((int) $filter['filter_order_status_id'] > 0) {
            $sql .= "
                AND o.order_status_id = '" . $this->db->escape((int) $filter['filter_order_status_id']) . "'
            ";
        } else {
            $sql .= "
                AND o.order_status_id > '0'
            ";
        }

        if ((int) $filter['filter_order_id'] > 0) {
            $sql .= "
                AND o.order_id = '" . $this->db->escape((int) $filter['filter_order_id']) . "'
            ";
        }

        if ((int) $filter['filter_order_id'] > 0) {
            $sql .= "
                AND o.order_id = '" . $this->db->escape((int) $filter['filter_order_id']) . "'
            ";
        }

        if (!empty($filter['filter_customer'])) {
            $sql .= "
                AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($filter['filter_customer']) . "%'
            ";
        }

        if (!empty($filter['filter_barcode'])) {
            $sql .= "
                AND omlh.barcodes LIKE '%" . $this->db->escape((int) $filter['filter_barcode']) . "%'
            ";
        }

        if ((int) $filter['filter_has_barcode'] > 0) {
            switch ((int) $filter['filter_has_barcode']) {
                case 1: // orders with no barcode, including with errors
                    $sql .= "
                        AND (omlh.barcodes = '[]' OR omlh.is_error = '1')
                    ";
                    break;
                case 2: // orders that have barcodes
                    $sql .= "
                        AND (omlh.barcodes <> '[]' AND omlh.is_error = '0')
                    ";
                    break;
            }
        }

        if ((int) $filter['filter_has_manifest'] > 0) {
            switch ((int) $filter['filter_has_manifest']) {
                case 1: // orders with no manifest
                    $sql .= "
                        AND (omod.manifest_id < 1 OR omod.manifest_id IS NULL)
                    ";
                    break;
                case 2: // orders that in manifest
                    $sql .= "
                        AND omod.manifest_id > 0
                    ";
                    break;
            }
        }

        if (!$count_only) {
            $page = $filter['page'];
            $limit = $filter['limit'];

            $offset = ($page - 1) * $limit;

            $sql .= "
            ORDER BY o.order_id DESC
            LIMIT " . $offset . ", " . $limit;
        }

        return $sql;
    }

    public function loadOrderHistory($order_id, $page = 1, $limit = Params::MAX_PER_PAGE_HISTORY)
    {
        $offset = ($page - 1) * $limit;
        $result = $this->db->query("
            SELECT `id_label_history`, `date_add`, `is_error`, `service_code`, `barcodes` 
            FROM " . DB_PREFIX . "omniva_m_label_history
            WHERE order_id = " . (int) $order_id . "
            ORDER BY id_label_history DESC
            LIMIT " . $offset . ", " . $limit . "
        ");

        return ($limit === 1 && $result->rows) ? $result->row : $result->rows;
    }

    public function getBarcodes($order_ids, $history_id = null)
    {
        $barcodes_data = [
            'barcodes' => [],
            'order_ids' => []
        ];

        if (!is_array($order_ids)) {
            return $barcodes_data;
        }

        $order_ids = array_filter(array_map('intval', $order_ids));

        $sql = "
            SELECT `id_label_history`, olh.`order_id`, `date_add`, `is_error`, `service_code`, `barcodes` 
            FROM " . DB_PREFIX . "omniva_m_label_history olh
        ";

        if ((int) $history_id > 0) {
            $sql .= "
                WHERE olh.`order_id` IN ('" . implode("', '", $order_ids) . "') AND olh.`id_label_history` = " . (int) $history_id;
        } else {
            $sql .= "
                INNER JOIN (
                    SELECT order_id, MAX(id_label_history) as latest_history_id 
                    FROM " . DB_PREFIX . "omniva_m_label_history olh
                    GROUP BY order_id
                ) tmp ON olh.`order_id` = tmp.order_id AND olh.`id_label_history` = tmp.latest_history_id
                WHERE olh.order_id IN ('" . implode("', '", $order_ids) . "')
            ";
        }

        $result = $this->db->query($sql);

        if (!$result->rows) {
            return $barcodes_data;
        }

        foreach ($result->rows as $row) {
            if ((int) $row['is_error'] === 1) {
                continue;
            }

            $decoded_barcodes = json_decode($row['barcodes'], true);

            if (empty($decoded_barcodes)) {
                continue;
            }

            // $barcodes = array_merge($barcodes, json_decode($row['barcodes'], true));
            $barcodes_data['barcodes'] = array_merge($barcodes_data['barcodes'], $decoded_barcodes);
            $barcodes_data['order_ids'][] = (int) $row['order_id'];
        }

        return $barcodes_data;
    }

    public function getOrderHistoryTotal($order_id)
    {
        $result = $this->db->query("
            SELECT COUNT(`id_label_history`) AS `total`
            FROM " . DB_PREFIX . "omniva_m_label_history
            WHERE order_id = " . (int) $order_id . "
        ");

        return $result->rows ? $result->row['total'] : 0;
    }

    public function saveLabelHistory($order_data, $barcodes, $service_code, $is_error = false)
    {
        $order_id = $order_data['oc_order']['order_id'];
        $barcodes_empty = empty($barcodes);
        // $barcodes will hold error message in case is_error is true, otherwise array of tracking numbers
        $barcodes_string = $is_error ? $this->db->escape($barcodes) : json_encode($barcodes);

        // update order history
        if (!$is_error && !$barcodes_empty) {
            // notify customer if enabled
            $notified = 0;
            if ($this->config->get('omniva_m_tracking_email_status')) {
                $notified = $this->sendTrackingUrl($order_data, $barcodes) ? 1 : 0;
            }

            $last_status_id = $this->db->query("
                SELECT order_status_id FROM `" . DB_PREFIX . "order_history`
                WHERE order_id = " . (int) $order_id . "
                ORDER BY order_history_id DESC
                LIMIT 1
            ")->row;

            $last_status_id = isset($last_status_id['order_status_id']) ? $last_status_id['order_status_id'] : 0;

            if ($last_status_id > 0) {
                $this->db->query(
                    "
                    INSERT INTO `" . DB_PREFIX . "order_history` 
                    SET `order_id` = '" . (int) $order_id . "', `order_status_id` = '" . (int) $last_status_id . "', `notify` = '" . $notified . "', `comment` = '" . $this->db->escape($barcodes_string) . "', `date_added` = NOW()
                    "
                );
            }
        }

        return $this->db->query("
            INSERT INTO `" . DB_PREFIX . "omniva_m_label_history` (`order_id`, `is_error`, `barcodes`, `service_code`, `date_add`)
            VALUES('" . (int) $order_id . "', '" . (int) $is_error . "', '" . $barcodes_string . "', '" . $service_code . "', NOW()) 
        ");
    }

    /**
     * Sends email to customer with tracking url
     * 
     * @param array|int $order_data - order data array with all required information
     * 
     * @return array - array containing either success or error keys
     */
    public function sendTrackingUrl($order_data, $barcodes)
    {
        $this->load->language('extension/module/omniva_m');

        $is_tracking_email_enabled = (bool) $this->config->get('omniva_m_tracking_email_status');
        $tracking_url_template = $this->config->get('omniva_m_tracking_url');
        $subject = $this->config->get('omniva_m_tracking_email_subject');
        $template = $this->config->get('omniva_m_tracking_email_template');

        // must be enabled and have all fields
        if (
            !$is_tracking_email_enabled ||
            empty($tracking_url_template) || strpos($tracking_url_template, '@') === false ||
            empty($subject) || empty($template)
        ) {
            return false;
        }

        $tracking_url = [];
        foreach ($barcodes as $barcode) {
            $tracking_url[] = str_replace('@', $barcode, $tracking_url_template);
        }

        // initialize opencart mailer
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $mail = new Mail($this->config->get('config_mail_engine'));
        } else { // OC 2.3
            $mail = new Mail();
            $mail->protocol = $this->config->get('config_mail_protocol');
        }

        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

        $mail->setTo($order_data['oc_order']['email']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));

        $key_values = [
            '{{ tracking_url }}' => implode("\n", $tracking_url),
            '{{ tracking_number }}' => implode(', ', $barcodes)
        ];
        $body = str_replace(array_keys($key_values), array_values($key_values), json_decode($template));

        $mail->setText(html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
        $mail->send();

        return true;
    }

    protected function getUserToken()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return 'user_token=' . $this->session->data['user_token'];
        }

        return 'token=' . $this->session->data['token'];
    }

    private function getOrderWeight($order_id)
    {
        $this->load->model('sale/order');

        $this->load->model('catalog/product');

        $weight_class = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "weight_class_description
            WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND unit = 'kg'
        ");

        $kg_class_id = $weight_class->row['weight_class_id'];

        $total_order_weight = 0.0;

        $products = $this->model_sale_order->getOrderProducts($order_id);

        foreach ($products as $product) {
            $option_weight = 0;

            $product_info = $this->model_catalog_product->getProduct($product['product_id']);

            if (!$product_info) {
                continue;
            }

            $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

            foreach ($options as $option) {
                if ($option['type'] = 'file') {
                    continue;
                }

                $product_option_value_info = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

                if (!empty($product_option_value_info['weight'])) {
                    if ($product_option_value_info['weight_prefix'] == '+') {
                        $option_weight += $product_option_value_info['weight'];
                    } elseif ($product_option_value_info['weight_prefix'] == '-') {
                        $option_weight -= $product_option_value_info['weight'];
                    }
                }
            }

            $weight_in_kg = $this->weight->convert(($product_info['weight'] + (float)$option_weight) * $product['quantity'], $product_info['weight_class_id'], $kg_class_id);

            $total_order_weight += (float) $weight_in_kg;
        }

        if ($total_order_weight <= 0) {
            $total_order_weight = Params::DEFAULT_WEIGHT;
        }

        return $total_order_weight;
    }

    public function loadInfoPanelTranslation()
    {
        return $this->getJsTranslations();
    }

    public function getSenderInformation()
    {
        $sender_data_keys = [
            'sender_name', 'sender_street', 'sender_postcode',
            'sender_city', 'sender_country', 'sender_phone', 'sender_email',
        ];
        $sender_data = [];
        foreach ($sender_data_keys as $key) {
            $sender_data[$key] = $this->config->get(Params::PREFIX . $key);
        }

        return "<br>"
            . implode(", ", [
                $sender_data['sender_name'], $sender_data['sender_phone'], $sender_data['sender_email']
            ])
            . ",<br>" . $sender_data['sender_street'] . ", " . $sender_data['sender_city'] . " "
            . $sender_data['sender_postcode'] . ", " . $sender_data['sender_country'];
    }

    public function loadAdminModuleTranslations()
    {
        $this->load->language('extension/module/omniva_m');

        // OC3 automatically loads translations into view with $this->load->language
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return [];
        }

        $data = [];
        // but versions before 3.0 requires manualy loading each string
        $translations = [
            // Panel title
            'panel_title',
            // Tabs
            'tab_order_info', 'tab_history',
            // Order information
            'info_manifest_id', 'info_last_barcodes', 'info_last_error', 'label_total_weight',
            'label_multiparcel', 'label_cod_use', 'label_cod_amount', 'option_yes',
            'option_no',
            // History table
            'header_date', 'header_service_code', 'header_tracking_numbers',
            'header_actions', 'history_empty',
            // Panel buttons
            'btn_register_label', 'btn_print_label', 'btn_save_data',
            // Manifest page
            'title_manifest_orders', 'column_order_id', 'column_customer', 'column_status',
            'column_barcode', 'column_manifest_id', 'column_action', 'manifest_orders_no_results',
            'title_filters', 'label_order_id', 'label_customer', 'label_barcode',
            'label_order_status_id', 'label_has_barcode', 'label_has_manifest', 'tooltip_print_labels',
            'tooltip_create_manifest', 'tooltip_call_courier', 'btn_filter',
            // General messages
            'help_weight_multiparcel', 'error_no_oc_order', 'error_no_barcodes_found', 'error_missing_origin',
            'error_nothing_in_manifest', 'warning_no_terminal', 'warning_overweight', 'warning_cod_used',
            'warning_cod_amount_mismatch', 'warning_order_data_changed'
        ];

        foreach ($translations as $key) {
            $data[Params::PREFIX . $key] = $this->language->get(Params::PREFIX . $key);
        }

        return $data;
    }

    public function loadListJsData()
    {
        return [
            'trans' => $this->getJsTranslations(),
            'call_courier_address' => $this->getSenderInformation(),
            'ajax_url' => 'index.php?route=extension/module/omniva_m/ajax&' . $this->getUserToken(),
            'is_oc3' => version_compare(VERSION, '3.0.0', '>=')
        ];
    }

    public function getJsTranslations()
    {
        $this->load->language('extension/module/omniva_m');

        $strings = [
            'order_saved', 'order_not_saved', 'bad_response', 'label_registered', 'no_data_changes',
            'confirm_new_label', 'refresh_now_btn', 'btn_no', 'btn_yes', 'tooltip_btn_print_register',
            'tooltip_btn_call_courier', 'confirm_call_courier', 'alert_no_orders', 'confirm_print_labels',
            'alert_response_error', 'alert_no_pdf', 'alert_bad_response', 'notify_courrier_called',
            'notify_courrier_call_failed', 'option_yes', 'option_no', 'tooltip_btn_manifest',
            'filter_label_omniva_only', 'filter_label_has_label', 'filter_label_in_manifest',
            'no_results', 'confirm_create_manifest'
        ];

        $translations = [];

        foreach ($strings as $string) {
            $translations[$string] = $this->language->get(Params::PREFIX . 'js_' . $string);
        }

        return $translations;
    }
}

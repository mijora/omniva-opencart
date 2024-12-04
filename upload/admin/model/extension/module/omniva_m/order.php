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
            'multi_type' => 'multiparcel',
            'cod' => [
                'enabled' => false,
                'use' => 0,
                'amount' => 0,
                'oc_amount' => 0,
                'order_use' => false // identifies if OC order uses COD (works only if settings are correct)
            ],
            'order_data' => false, // any chages that differs from original order information for omniva
            'add_services' => Helper::getAdditionalServicesList(), // additional services list to use for generating packages
            'label_history' => [
                'total' => 0,
                'last_error' => false,
                'last_barcodes' => false
            ],
            'total_weight' => 0.1, // default set as 0.1kg
            'set_weight' => 0.1, // default set as 0.1kg
            'manifest_id' => 0, // default no manifest
            'shipping_code' => '',
            'is_international' => false,
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

        $data['shipping_code'] = Helper::getShippingCode($oc_order['shipping_code']);
        $data['is_international'] = Helper::isInternational($data['shipping_code']);

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

        $cod_options = @json_decode((string) $this->config->get(Params::PREFIX . 'cod_options'), true);
        if (!is_array($cod_options)) {
            $cod_options = [];
        }
        if (in_array($oc_order['payment_code'], $cod_options)) {
            $data['cod']['use'] = $cod_enabled ? 1 : 0;
            $data['cod']['order_use'] = true;

            if ($order_data->getData('cod_use') !== null) {
                $data['cod']['use'] = $cod_enabled ? (int) $order_data->getData('cod_use') : 0;
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

        // determine multi_type
        $data['multi_type'] = Helper::getMultiType($data['shipping_type'], $data['cod']['use']);

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

            //$decoded_barcodes = json_decode((string) $row['barcodes'], true);
            $backward_barcodes_string = str_replace([' ','[',']','"'], '', $row['barcodes']);

            if (empty($backward_barcodes_string)) {
                continue;
            }

            $decoded_barcodes = explode(',', $backward_barcodes_string);

            if (empty($decoded_barcodes)) {
                continue;
            }

            // $barcodes = array_merge($barcodes, json_decode((string) $row['barcodes'], true));
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
        $barcodes_string = $is_error ? $this->db->escape($barcodes) : implode(', ', $barcodes);

        // update order history if $barcodes is not empty which means history change comming from registration
        if (!$barcodes_empty) {
            // if not error try sending tracking email if enabled
            $notified = 0;

            $last_status_id = $this->db->query("
                SELECT order_status_id FROM `" . DB_PREFIX . "order_history`
                WHERE order_id = " . (int) $order_id . "
                ORDER BY order_history_id DESC
                LIMIT 1
            ")->row;

            $last_status_id = isset($last_status_id['order_status_id']) ? (int) $last_status_id['order_status_id'] : 0;
            $status_id = $last_status_id;

            if (!$is_error) {
                if ($this->config->get('omniva_m_tracking_email_status')) {
                    $notified = $this->sendTrackingUrl($order_data, $barcodes) ? 1 : 0;
                }

                $success_status_id = $this->config->get(Params::PREFIX . 'order_status_registered');
                if ($success_status_id) {
                    $status_id = (int) $success_status_id;
                }
            } else { // if error
                $error_status_id = $this->config->get(Params::PREFIX . 'order_status_error');
                if ($error_status_id) {
                    $status_id = (int) $error_status_id;
                }
            }

            if ($status_id > 0) {
                $query = $this->db->query(
                    "
                    SELECT COUNT(*) AS total 
                    FROM `" . DB_PREFIX . "order_status` 
                    WHERE `order_status_id` = '" . (int) $status_id . "'
                    "
                );
                if ($query->row['total'] > 0) {
                    $this->db->query(
                        "
                        INSERT INTO `" . DB_PREFIX . "order_history` 
                        SET `order_id` = '" . (int) $order_id . "', `order_status_id` = '" . (int) $status_id . "', `notify` = '" . $notified . "', `comment` = '" . $this->db->escape($barcodes_string) . "', `date_added` = NOW()
                        "
                    );
                    if ($status_id !== $last_status_id) {
                        $this->db->query(
                            "
                            UPDATE `" . DB_PREFIX . "order` 
                            SET `order_status_id` = '" . (int) $status_id . "' 
                            WHERE `order_id` = '" . (int) $order_id . "'
                            "
                        );
                    }
                }
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
            WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND unit IN ('kg', 'кг')
            LIMIT 1
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
        $omniva_m_translations = $this->load->language('extension/module/omniva_m');

        // OC3 automatically loads translations into view with $this->load->language
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return [];
        }

        return $omniva_m_translations;
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

    /**
     * Get specificly marked JS strings from translation. JS string is identified by key starting with omniva_m_js_
     * 
     * @return array
     */
    public function getJsTranslations()
    {
        $omniva_m_translations = $this->load->language('extension/module/omniva_m');

        $translations = [];

        $needle = Params::PREFIX . 'js_';
        $substr_offset = strlen($needle);
        foreach ($omniva_m_translations as $key => $string) {
            if (strpos($key, $needle) !== 0) {
                continue;
            }

            $translations[substr($key, $substr_offset)] = $string;
        }

        return $translations;
    }

    public function loadOrderInfoPanelData($order_id)
    {
        Helper::sendPowerBi($this->db, $this->config);

        $order_data = $this->loadOrder($order_id);

        return [
            'omniva_m_order' => $order_data,
            'omniva_m_label_history' => $this->loadOrderHistory($order_id),
            'omniva_m_info_panel_translation' => $this->loadInfoPanelTranslation(),
        ];
    }
}

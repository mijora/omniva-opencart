<?php

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Params;
use Mijora\OmnivaOpencart\Price;

class ControllerExtensionShippingOmnivaM extends Controller
{
    private $error = array();

    private $tabs = [
        'general', 'api', 'sender-info', 'price', 'cod', 'terminals',
        'tracking-email', 'advanced'
    ];

    public function install()
    {
        $sql_array = [
            "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_m_price` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `country_code` varchar(2) DEFAULT NULL,
                `price_data` text,
                PRIMARY KEY (`id`),
                UNIQUE KEY `country_code` (`country_code`)
            ) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;
            ",
            "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_m_label_history` (
                `id_label_history` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `order_id` int(11) unsigned DEFAULT NULL,
                `is_error` tinyint(1) DEFAULT NULL,
                `barcodes` text,
                `service_code` text,
                `date_add` datetime DEFAULT NULL,
                PRIMARY KEY (`id_label_history`),
                KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
            ",
            "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_m_order_data` (
                `order_id` int(11) unsigned NOT NULL,
                `data` text,
                `manifest_id` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`order_id`),
                KEY `manifest_id` (`manifest_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
        ];

        foreach ($sql_array as $sql) {
            $this->db->query($sql);
        }

        // save default settings
        $this->saveSettings([
            Params::PREFIX . 'api_url' => 'https://edixml.post.ee',
            Params::PREFIX . 'api_add_comment' => 1,
            Params::PREFIX . 'tracking_url' => 'https://www.omniva.lt/verslo/siuntos_sekimas?barcode=@',
        ]);
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('omniva_m');

        // delete price table
        $sql_array = [
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_m_price`",
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_m_label_history`",
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_m_order_data`",
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_m_courier_call`", // created by CourierCall class
        ];

        foreach ($sql_array as $sql) {
            $this->db->query($sql);
        }

        // remove modification file
        Helper::removeModificationXml();
    }

    public function index()
    {
        Helper::sendPowerBi($this->db, $this->config);

        $omniva_m_translations = $this->load->language('extension/shipping/omniva_m');

        $this->document->setTitle($this->language->get('heading_title'));

        // $this->load->model('setting/setting');

        $extension_home = 'extension';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $extension_home = 'marketplace';
        }

        if (isset($this->request->get['fixdb']) && $this->validate()) {
            $this->fixDb();
            $this->response->redirect($this->url->link('extension/shipping/omniva_m', $this->getUserToken(), true));
        }

        if (isset($this->request->get['fixxml']) && $this->validate()) {
            Helper::copyModificationXml();
            $this->session->data['success'] = $this->language->get(Params::PREFIX . 'xml_updated');
            $this->response->redirect($this->url->link($extension_home . '/modification', $this->getUserToken(), true));
        }

        $current_tab = 'tab-general';

        if (isset($this->request->get['tab']) && in_array($this->request->get['tab'], $this->tabs)) {
            $current_tab = 'tab-' . $this->request->get['tab'];
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->prepPostData();

            if (isset($this->request->post['api_settings_update'])) {
                // we need unescaped password post
                $this->request->post[Params::PREFIX . 'api_pass'] = $_POST[Params::PREFIX . 'api_pass'];
                unset($this->request->post['api_settings_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get('omniva_m_msg_setting_saved');
                $current_tab = 'api';
            }

            if (isset($this->request->post['module_settings_update'])) {
                unset($this->request->post['module_settings_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get('omniva_m_msg_setting_saved');
                $current_tab = 'general';
            }

            if (isset($this->request->post['sender_settings_update'])) {
                unset($this->request->post['sender_settings_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get('omniva_m_msg_setting_saved');
                $current_tab = 'sender-info';
            }

            if (isset($this->request->post['cod_settings_update'])) {
                unset($this->request->post['cod_settings_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get('omniva_m_msg_setting_saved');
                $current_tab = 'cod';
            }

            if (isset($this->request->post['tracking_email_update'])) {
                unset($this->request->post['tracking_email_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get('omniva_m_msg_setting_saved');
                $current_tab = 'tracking-email';
            }

            $this->response->redirect($this->url->link('extension/shipping/omniva_m', $this->getUserToken() . '&tab=' . $current_tab, true));
        }

        $data[Params::PREFIX . 'version'] = Params::VERSION;
        $data['heading_title'] = $this->language->get('heading_title');

        $data['omniva_m_current_tab'] = $current_tab;

        // OC3 automatically loads translations into view with $this->load->language, but versions before it requires manual
        if (version_compare(VERSION, '3.0.0', '<')) {
            $data = array_merge($data, $omniva_m_translations);
        }

        $data['success'] = '';
        $data['error_warning'] = '';

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->getUserToken(), true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get(Params::PREFIX . 'text_extension'),
            'href' => $this->url->link($extension_home . '/extension', $this->getUserToken() . '&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/omniva_m', $this->getUserToken(), true)
        );

        $data['action'] = $this->url->link('extension/shipping/omniva_m', $this->getUserToken(), true);

        $data['cancel'] = $this->url->link($extension_home . '/extension', $this->getUserToken() . '&type=shipping', true);

        $data['cod_options'] = $this->loadPaymentOptions();

        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->getCountries();
        
        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['ajax_url'] = 'index.php?route=extension/shipping/omniva_m/ajax&' . $this->getUserToken();

        // opencart 3 expects status and sort_order begin with shipping_ 
        $setting_prefix = '';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $setting_prefix = 'shipping_';
        }

        $oc_settings = [
            'status', 'sort_order'
        ];

        foreach ($oc_settings as $value) {
            if (isset($this->request->post[$setting_prefix . Params::PREFIX . $value])) {
                $data[Params::PREFIX . $value] = $this->request->post[$setting_prefix . Params::PREFIX . $value];
                continue;
            }

            $data[Params::PREFIX . $value] = $this->config->get($setting_prefix . Params::PREFIX . $value);
        }

        // Load saved settings or values from post request
        $module_settings = [
            // general tab
            'tax_class_id', 'geo_zone_id', 'order_status_registered', 'order_status_error', 'disable_cart_weight_check',
            'use_simple_terminal_check',
            // api tab
            'api_user', 'api_pass', 'api_url', 'api_sendoff_type', 'api_label_print_type',
            'api_add_comment', 'api_contract_origin', 'api_show_return_code',
            // sender-info tab
            'sender_name', 'sender_street', 'sender_postcode',
            'sender_city', 'sender_country', 'sender_phone', 'sender_email',
            // COD tab
            'cod_status', 'cod_receiver', /* 'cod_bic', */ 'cod_iban',
            // Terminal tab
            'last_updated',
            // Tracking email tab
            'tracking_email_status', 'tracking_url', 'tracking_email_subject',
        ];

        foreach ($module_settings as $key) {
            if (isset($this->request->post[Params::PREFIX . $key])) {
                $data[Params::PREFIX . $key] = $this->request->post[Params::PREFIX . $key];
                continue;
            }

            $data[Params::PREFIX . $key] = $this->config->get(Params::PREFIX . $key);
        }

        // contract origins
        $data['contract_origins'] = [
            Params::CONTRACT_ORIGIN_OTHER => $this->language->get(Params::PREFIX . 'option_contract_other'),
            Params::CONTRACT_ORIGIN_ESTONIA => $this->language->get(Params::PREFIX . 'option_contract_estonia'),
        ];

        $data['contract_enable_courier_services'] = Params::CONTRACT_ORIGIN_ESTONIA;

        $data['courier_options'] = [
            Params::SERVICE_COURIER_ESTONIA => $this->language->get(Params::PREFIX . 'option_courier_estonia'),
            Params::SERVICE_COURIER_FINLAND => $this->language->get(Params::PREFIX . 'option_courier_finland')
        ];

        // sendoff types
        $data['sendoff_types'] = [
            Params::SENDOFF_TYPE_COURIER => $this->language->get(Params::PREFIX . 'option_courier'),
            Params::SENDOFF_TYPE_TERMINAL => $this->language->get(Params::PREFIX . 'option_terminal'),
            Params::SENDOFF_TYPE_SORTING_CENTER => $this->language->get(Params::PREFIX . 'option_sorting_center'),
        ];

        // label print types
        $data['label_print_types'] = [
            Params::LABEL_PRINT_TYPE_A4 => $this->language->get(Params::PREFIX . 'option_label_print_a4'),
            Params::LABEL_PRINT_TYPE_A6 => $this->language->get(Params::PREFIX . 'option_label_print_a6'),
        ];

        // return code display types
        $data['show_return_code_types'] = [
            Params::SHOW_RETURN_ALL => $this->language->get(Params::PREFIX . 'option_addto_sms_email'),
            Params::SHOW_RETURN_SMS => $this->language->get(Params::PREFIX . 'option_addto_sms'),
            Params::SHOW_RETURN_EMAIL => $this->language->get(Params::PREFIX . 'option_addto_email'),
            Params::SHOW_RETURN_DONT => $this->language->get(Params::PREFIX . 'option_addto_dont'),
        ];

        // special cases (that need json_decode)
        if (isset($this->request->post[Params::PREFIX . 'cod_options'])) {
            $data[Params::PREFIX . 'cod_options'] = json_decode($this->request->post[Params::PREFIX . 'cod_options']);
        } else {
            $data[Params::PREFIX . 'cod_options'] = json_decode((string) $this->config->get(Params::PREFIX . 'cod_options'));
        }

        if (!$data[Params::PREFIX . 'cod_options']) {
            $data[Params::PREFIX . 'cod_options'] = [];
        }

        if (isset($this->request->post[Params::PREFIX . 'courier_options'])) {
            $data[Params::PREFIX . 'courier_options'] = json_decode($this->request->post[Params::PREFIX . 'courier_options']);
        } else {
            $data[Params::PREFIX . 'courier_options'] = json_decode((string) $this->config->get(Params::PREFIX . 'courier_options'));
        }

        if (!$data[Params::PREFIX . 'courier_options']) {
            $data[Params::PREFIX . 'courier_options'] = [];
        }

        if (isset($this->request->post[Params::PREFIX . 'tracking_email_template'])) {
            $data[Params::PREFIX . 'tracking_email_template'] = json_decode($this->request->post[Params::PREFIX . 'tracking_email_template']);
        } else {
            $data[Params::PREFIX . 'tracking_email_template'] = json_decode((string) $this->config->get(Params::PREFIX . 'tracking_email_template'));
            if (empty($data[Params::PREFIX . 'tracking_email_template'])) {
                $data[Params::PREFIX . 'tracking_email_template'] = Helper::getDefaultTrackingEmailTemplate();
            }
        }

        $data[Params::PREFIX . 'prices'] = array_map(
            function ($price) {
                return json_decode((string) $price['price_data'], true);
            },
            Price::getPrices($this->db) //$this->getPrices()
        );

        $data['price_range_types'] = [
            Price::RANGE_TYPE_CART_PRICE => $this->language->get(Params::PREFIX . 'range_type_cart'),
            Price::RANGE_TYPE_WEIGHT => $this->language->get(Params::PREFIX . 'range_type_weight')
        ];

        $data['terminals_info'] = Helper::getTerminalsInformation();
        $data['last_update'] = $this->config->get(Params::PREFIX . 'last_update');
        $data['last_update'] = $data['last_update'] == null ? 'Never updated' : date('Y-m-d H:i:s', $data['last_update']);
        $data['cron_url'] = $this->getCronUrl();

        $version_check = @json_decode((string) $this->config->get(Params::PREFIX . 'version_check_data'), true);
        if (empty($version_check) || Helper::isTimeToCheckVersion($version_check['timestamp'])) {
            $git_version = Helper::hasGitUpdate();
            $version_check = [
                'timestamp' => time(),
                'git_version' => $git_version
            ];
            $this->saveSettings([
                Params::PREFIX . 'version_check_data' => json_encode($version_check)
            ]);
        }

        $data[Params::PREFIX . 'git_version'] = $version_check['git_version'];

        //check if we still need to show notification
        if ($version_check['git_version'] !== false && !Helper::isModuleVersionNewer($version_check['git_version']['version'])) {
            $data[Params::PREFIX . 'git_version'] = false;
        }

        $data[Params::PREFIX . 'db_check'] = Helper::checkDbTables($this->db);
        $data[Params::PREFIX . 'db_fix_url'] = $this->url->link('extension/shipping/omniva_m', $this->getUserToken() . '&fixdb', true);

        $data[Params::PREFIX . 'xml_check'] = Helper::isModificationNewer();
        $data[Params::PREFIX . 'xml_fix_url'] = $this->url->link('extension/shipping/omniva_m', $this->getUserToken() . '&fixxml', true);

        $data['required_settings_set'] = $this->isRequiredSettingsSet($data);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/omniva_m', $data));
    }

    protected function isRequiredSettingsSet($data)
    {
        $required = [
            // api tab
            'api_user', 'api_pass',
            // sender-info tab
            'sender_name', 'sender_street', 'sender_postcode',
            'sender_city', 'sender_country', 'sender_phone', 'sender_email'
        ];

        foreach ($required as $key) {
            if (!isset($data[Params::PREFIX . $key]) || empty($data[Params::PREFIX . $key])) {
                return false;
            }
        }

        return true;
    }

    protected function getUserToken()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return 'user_token=' . $this->session->data['user_token'];
        }

        return 'token=' . $this->session->data['token'];
    }


    protected function fixDb()
    {
        $db_check = Helper::checkDbTables($this->db);
        if (!$db_check) {
            return; // nothing to fix
        }

        foreach ($db_check as $table => $fix) {
            $this->db->query($fix);
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/omniva_m')) {
            $this->error['warning'] = $this->language->get(Params::PREFIX . 'error_permission');
            return false; // skip the rest
        }

        return !$this->error;
    }

    protected function getPrices()
    {
        $result = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `code` = 'omniva_m' AND `key` LIKE 'omniva_m_price_%'");
        return $result->rows;
    }

    protected function getCronUrl()
    {
        $secret = $this->config->get(Params::PREFIX . 'cron_secret');
        if (!$secret) { // first time create a secret
            $secret = uniqid();
            $this->saveSettings(array(Params::PREFIX . 'cron_secret' => $secret));
        }

        return HTTPS_CATALOG . 'index.php?route=extension/module/omniva_m/ajax&action=terminalUpdate&secret=' . $secret;
    }

    protected function saveSettings($data)
    {
        Helper::saveSettings($this->db, $data);
    }

    protected function loadPaymentOptions()
    {
        $result = array();

        if (version_compare(VERSION, '3.0.0', '>=')) {
            $this->load->model('setting/extension');
            $payments = $this->model_setting_extension->getInstalled('payment');
        } else {
            $this->load->model('extension/extension');
            $payments = $this->model_extension_extension->getInstalled('payment');
        }

        foreach ($payments as $payment) {
            $this->load->language('extension/payment/' . $payment);
            $result[$payment] = $this->language->get('heading_title');
        }

        return $result;
    }

    /**
     * Converts certain settings that comes as array into string
     */
    protected function prepPostData()
    {
        // when no checkboxes is selected post doesnt send it, make sure settings is updated correctly
        if (isset($this->request->post['cod_settings_update'])) {
            $post_cod_options = [];
            if (isset($this->request->post[Params::PREFIX . 'cod_options'])) {
                $post_cod_options = $this->request->post[Params::PREFIX . 'cod_options'];
            }
            $this->request->post[Params::PREFIX . 'cod_options'] = json_encode($post_cod_options);
        }

        // when no checkboxes is selected post doesnt send it, make sure settings is updated correctly
        if (isset($this->request->post['api_settings_update'])) {
            $post_courier_options = [];
            if (isset($this->request->post[Params::PREFIX . 'courier_options'])) {
                $post_courier_options = $this->request->post[Params::PREFIX . 'courier_options'];
            }
            $this->request->post[Params::PREFIX . 'courier_options'] = json_encode($post_courier_options);
        }

        // we want to json_encode email template for better storage into settings
        if (isset($this->request->post[Params::PREFIX . 'tracking_email_template'])) {
            $this->request->post[Params::PREFIX . 'tracking_email_template'] = json_encode($this->request->post[Params::PREFIX . 'tracking_email_template']);
        }

        // // Opencart 3 expects status to be shipping_omniva_m_status
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'status'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'status'] = $this->request->post[Params::PREFIX . 'status'];
            unset($this->request->post[Params::PREFIX . 'status']);
        }

        // Opencart 3 expects sort_order to be shipping_omniva_m_sort_order
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'sort_order'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'sort_order'] = $this->request->post[Params::PREFIX . 'sort_order'];
            unset($this->request->post[Params::PREFIX . 'sort_order']);
        }
    }

    protected function hasAccess()
    {
        // if (!$this->user->hasPermission('modify', 'sale/order')) {
        //     $this->error['warning'] = $this->language->get('error_permission');
        // }

        // return !$this->error;
    }

    public function ajax()
    {
        $this->load->language('extension/shipping/omniva_m');
        if (!$this->validate()) {
            echo json_encode($this->error);
            exit();
        }
        $restricted = json_encode(['warning' => 'Restricted']);
        switch ($_GET['action']) {
            case 'getCountries':
                $this->getCountries();
                break;
            case 'savePrice':
                $this->savePrice();
                break;
            case 'deletePrice':
                $this->deletePrice();
                break;
            case 'terminalUpdate':
                $data = Helper::ajaxUpdateTerminals($this->db);
                header('Content-Type: application/json');
                echo json_encode(['data' => $data]);
                exit();

            default:
                die($restricted);
                break;
        }
    }

    protected function getCountries()
    {
        $geo_zone_id = 0;

        // check if needs countries assigned to specific geo zone or all of them
        if (isset($this->request->post['geo_zone_id'])) {
            $geo_zone_id = (int) $this->request->post['geo_zone_id'];
        }

        $price = new Price($this->db);

        echo json_encode(['data' => $price->getCountries($geo_zone_id)]);
        exit();
    }

    protected function savePrice()
    {
        if (!isset($this->request->post['country'])) {
            echo json_encode(array('error' => 'Bad request'));
            exit();
        }

        $price = new Price($this->db);

        $result = $price->savePrice($this->request->post);

        echo json_encode(['data' => $result]);
        exit();
    }

    protected function deletePrice()
    {
        if (!isset($this->request->post['country']) || strlen($this->request->post['country']) > 3) {
            echo json_encode(array('error' => 'Bad request'));
            exit();
        }

        $country_code = $this->request->post['country'];

        $data = $this->request->post;

        $price = new Price($this->db);

        $price->deletePrice($country_code);

        echo json_encode(['data' => $data]);
        exit();
    }
}

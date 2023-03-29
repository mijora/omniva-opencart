<?php

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

use Mijora\Omniva\Shipment\CallCourier;
use Mijora\Omniva\Shipment\Package\AdditionalService;
use Mijora\Omniva\Shipment\Package\Address;
use Mijora\Omniva\Shipment\Package\Contact;
use Mijora\Omniva\Shipment\Package\Measures;
use Mijora\Omniva\Shipment\Package\Cod;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Shipment;
use Mijora\Omniva\Shipment\ShipmentHeader;
use Mijora\Omniva\Shipment\Label;
use Mijora\Omniva\Shipment\Manifest;
use Mijora\Omniva\Shipment\Order as ApiOrder;
use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Params;
use Mijora\OmnivaOpencart\Order;

class ControllerExtensionModuleOmnivaM extends Controller
{
    public function ajax()
    {
        if (!isset($_GET['action'])) {
            $_GET['action'] = 'default';
        }

        switch ($_GET['action']) {
            case 'saveOrderData':
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->saveOrderData()]);
                exit();
                break;
            case 'printLabel':
                $id_order = (int) (isset($this->request->get['order_id']) ? $this->request->get['order_id'] : 0);
                $history_id = (int) (isset($this->request->post['history_id']) ? $this->request->post['history_id'] : 0);
                $order_ids = [$id_order];

                if ($id_order === 0) {
                    $order_ids = isset($this->request->post['order_ids']) ? $this->request->post['order_ids'] : [];
                }

                header('Content-Type: application/json');
                if ($history_id > 0) {
                    echo json_encode(['data' => $this->printHistoryLabel($order_ids, $history_id)]);
                    exit();
                }
                echo json_encode(['data' => $this->printLabel($order_ids)]);
                exit();
                break;
            case 'createManifest':
                $order_ids = isset($this->request->post['order_ids']) ? $this->request->post['order_ids'] : [];
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->createManifest($order_ids)]);
                exit();
                break;
            case 'printManifest':
                $manifest_id = isset($this->request->post['manifest_id']) ? $this->request->post['manifest_id'] : 0;
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->printManifest($manifest_id)]);
                exit();
                break;
            case 'registerLabel':
                $id_order = (int) (isset($this->request->post['order_id']) ? $this->request->post['order_id'] : 0);
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->registerLabel($id_order)]);
                exit();
                break;
            case 'callCourier':
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->callCourier()]);
                exit();
                break;
            case 'getManifestOrders':
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->getManifestOrders()]);
                exit();
                break;
            default:
                break;
        }

        echo json_encode(['data' => ['error' => 'Restricted']]);
        exit();
    }

    private function saveOrderData()
    {
        $id_order = (int) (isset($this->request->post['order_id']) ? $this->request->post['order_id'] : 0);

        $order_data = new Order($id_order, $this->db);

        if (isset($this->request->post['multiparcel'])) {
            $multiparcel = (int) $this->request->post['multiparcel'];
            if ($multiparcel > 1) {
                $order_data->setMultiparcel($multiparcel);
            }
        }

        if (isset($this->request->post['weight'])) {
            $weight = (float) $this->request->post['weight'];
            if ($weight > 0) {
                $order_data->setWeight($weight);
            }
        }

        if (isset($this->request->post['cod_use'])) {
            $cod_use = (int) $this->request->post['cod_use'];
            $order_data->setCodUse($cod_use);
        }

        if (isset($this->request->post['cod_amount'])) {
            $cod_amount = (float) $this->request->post['cod_amount'];
            $order_data->setCodAmount($cod_amount);
        }

        if ($order_data->save()) {
            $this->saveLabelHistory($id_order, [], 'Order data changed');
            return true;
        }

        return false;
    }

    private function callCourier()
    {
        $this->load->language('extension/module/omniva_m');

        $username = $this->config->get(Params::PREFIX . 'api_user');
        $password = $this->config->get(Params::PREFIX . 'api_pass');
        $origin = $this->config->get(Params::PREFIX . 'api_contract_origin');

        if (!in_array($origin, Params::CONTRACT_AVAILABLE_ORIGINS)) {
            return [
                'error' => $this->language->get(Params::PREFIX . 'error_missing_origin')
            ];
        }

        $sender_contact = $this->getSenderContact();

        $destination_country = $sender_contact->getAddress()->getCountry();
        if ($origin === Params::CONTRACT_ORIGIN_ESTONIA) {
            $destination_country = 'estonia'; // api expects this way for CI service, finland for CE service, anything else for QH
        }

        $call = new CallCourier();
        $call->setDestinationCountry($destination_country);
        $call->setAuth($username, $password);
        $call->setSender($sender_contact);

        try {
            return $call->callCourier();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getSenderContact()
    {
        $sender_name = $this->config->get(Params::PREFIX . 'sender_name');
        $sender_mobile = $this->config->get(Params::PREFIX . 'sender_phone');
        $sender_street = $this->config->get(Params::PREFIX . 'sender_street');
        $sender_postcode = $this->config->get(Params::PREFIX . 'sender_postcode');
        $sender_city = $this->config->get(Params::PREFIX . 'sender_city');
        $sender_country = $this->config->get(Params::PREFIX . 'sender_country');

        $sender_address = (new Address())
            ->setCountry($sender_country)
            ->setPostcode($sender_postcode)
            ->setDeliverypoint($sender_city)
            ->setStreet($sender_street);

        // Sender contact data
        return (new Contact())
            ->setAddress($sender_address)
            ->setMobile($sender_mobile)
            ->setPersonName($sender_name);
    }

    private function registerLabel($id_order)
    {
        $this->load->language('extension/module/omniva_m');
        $this->load->model('extension/module/omniva_m/order');

        $order_data = $this->model_extension_module_omniva_m_order->loadOrder((int) $id_order);

        // remove order from manifest if it was in one
        if ((int) $order_data['manifest_id'] > 0) {
            Order::updateManifestId($id_order, 0, $this->db);
        }

        $username = $this->config->get(Params::PREFIX . 'api_user');
        $password = $this->config->get(Params::PREFIX . 'api_pass');

        $sendoff_type = (int) $this->config->get(Params::PREFIX . 'api_sendoff_type');
        $add_comment = (int) $this->config->get(Params::PREFIX . 'api_add_comment');
        $contract_origin = (int) $this->config->get(Params::PREFIX . 'api_contract_origin');
        $courier_options = json_decode($this->config->get(Params::PREFIX . 'courier_options'));
        if (!is_array($courier_options)) {
            $courier_options = [];
        }
        $service_code = Helper::decideServiceCode(
            $sendoff_type,
            $order_data['shipping_type'],
            $contract_origin,
            $courier_options,
            $order_data['oc_order']['shipping_iso_code_2']
        );

        $offload_code = null;
        if ($order_data['shipping_type'] === Params::SHIPPING_TYPE_TERMINAL) {
            $offload_code = str_ireplace('omniva_m.terminal_', '', $order_data['oc_order']['shipping_code']);
        }

        $receiver_name = $order_data['oc_order']['shipping_firstname'] . ' ' . $order_data['oc_order']['shipping_lastname'];
        $receiver_mobile = $order_data['oc_order']['telephone'];
        $receiver_street = $order_data['oc_order']['shipping_address_1'] . ', ' . $order_data['oc_order']['shipping_address_2'];
        $receiver_postcode = $order_data['oc_order']['shipping_postcode'];
        $receiver_city = $order_data['oc_order']['shipping_city'];
        $receiver_country = $order_data['oc_order']['shipping_iso_code_2'];
        $receiver_email = $order_data['oc_order']['email'];

        $cod_receiver = $this->config->get(Params::PREFIX . 'cod_receiver');
        $cod_iban = trim(str_replace(' ', '', $this->config->get(Params::PREFIX . 'cod_iban')));

        $additional_services = [];
        if ($order_data['shipping_type'] === Params::SHIPPING_TYPE_TERMINAL) {
            if (!empty($receiver_email)) {
                $additional_services[] = 'SF'; // notify by email
            }
            $additional_services[] = 'ST'; // mandatory notify by sms
        }

        if ($add_comment) {
            $comment = 'Order ID: ' . (int) $id_order;
        }

        $cod = null;
        if ($order_data['cod']['enabled'] && $order_data['cod']['use']) {
            $additional_services[] = 'BP';
            $cod = (new Cod())
                ->setAmount((float) $order_data['cod']['amount'])
                ->setBankAccount($cod_iban)
                ->setReceiverName($cod_receiver)
                ->setReferenceNumber(Helper::calculateCodReference((int) $id_order));
        }

        $weight = $order_data['set_weight'];

        if ($order_data['multiparcel'] > 1) {
            $weight = round($weight / $order_data['multiparcel'], 3);
        }

        try {
            $shipmentHeader = new ShipmentHeader();
            $shipmentHeader
                ->setSenderCd($username) // same as partnerId
                ->setFileId(date('Ymdhis'));


            $measures = (new Measures())
                ->setWeight($weight);

            $receiver_address = (new Address())
                ->setCountry($receiver_country)
                ->setPostcode($receiver_postcode)
                ->setDeliverypoint($receiver_city)
                ->setStreet($receiver_street);

            if ($offload_code) {
                $receiver_address->setOffloadPostcode($offload_code);
            } elseif (in_array(strtoupper($service_code), Package::ZIP_NOT_REQUIRED_SERVICES)) {
                $receiver_address->setOffloadPostcode($receiver_postcode);
            }

            $senderContact = $this->getSenderContact();

            // Receiver contact data
            $receiverContact = (new Contact())
                ->setAddress($receiver_address)
                ->setEmail($receiver_email)
                ->setMobile($receiver_mobile)
                ->setPersonName($receiver_name);

            $services_to_register = [];
            if (!empty($additional_services)) {
                foreach ($additional_services as $add_service_code) {
                    $services_to_register[] = (new AdditionalService())->setServiceCode($add_service_code);
                }
            }

            // create packages
            $packages = [];
            for ($i = 0; $i < $order_data['multiparcel']; $i++) {
                $package = (new Package())
                    ->setId($id_order)
                    ->setService($service_code)
                    ->setMeasures($measures)
                    ->setReceiverContact($receiverContact)
                    ->setSenderContact($senderContact);

                if (!empty($services_to_register)) {
                    $package->setAdditionalServices($services_to_register);
                }

                if ($cod) {
                    $package->setCod($cod);
                }

                $packages[] = $package;
            }

            // Build Shipment object
            $shipment = new Shipment();
            if ($add_comment) {
                $shipment->setComment($comment);
            }

            $shipment->setShipmentHeader($shipmentHeader);

            $shipment->setPackages($packages);

            //set auth data
            $shipment->setAuth($username, $password);

            $result = $shipment->registerShipment();
            if (isset($result['barcodes'])) {
                $this->saveLabelHistory($order_data, $result['barcodes'], $this->formatServicesString($service_code, $additional_services));

                $barcodes_string = implode(', ', $result['barcodes']);

                return [
                    'data' => "Received barcodes: " . $barcodes_string,
                    'barcodes' => $result['barcodes']
                ];
            }
        } catch (\Exception $e) {
            $this->saveLabelHistory($order_data, $e->getMessage(), $this->formatServicesString($service_code, $additional_services), true);
            return ['error' => $e->getMessage()];
        }

        $this->saveLabelHistory($order_data, 'Omniva API responded without tracking numbers!', $this->formatServicesString($service_code, $additional_services), true);
        return ['error' => 'Omniva API responded without tracking numbers!'];
    }

    private function formatServicesString($service, $services)
    {
        return $service . (!empty($services) ? ' + ' . implode(', ', $services) : '');
    }

    private function saveLabelHistory($order_data, $barcodes, $service_code, $is_error = false)
    {
        $this->load->model('extension/module/omniva_m/order');

        if (!is_array($order_data)) {
            $order_data = $this->model_extension_module_omniva_m_order->loadOrder((int) $order_data);
        }

        return $this->model_extension_module_omniva_m_order->saveLabelHistory(
            $order_data,
            $barcodes,
            $service_code,
            $is_error
        );
    }

    private function printLabel($order_ids, $register_missing = true)
    {
        $this->load->language('extension/module/omniva_m');
        $this->load->model('extension/module/omniva_m/order');

        $barcodes_data = $this->model_extension_module_omniva_m_order->getBarcodes($order_ids);

        $missing = array_diff($order_ids, $barcodes_data['order_ids']);

        $register_errors = [];
        foreach ($missing as $missing_id) {
            $register_result = $this->registerLabel($missing_id);

            if (isset($register_result['error'])) {
                $register_errors[] = [
                    'order_id' => $missing_id,
                    'error' => $register_result['error']
                ];
                continue;
            }

            $barcodes_data['order_ids'][] = (int) $missing_id;
            $barcodes_data['barcodes'] = array_merge($barcodes_data['barcodes'], $register_result['barcodes']);
        }

        $result = $this->getLabelPdf($barcodes_data);

        $result['register_errors'] = $register_errors;

        return $result;
    }

    private function printHistoryLabel($order_id, $history_id)
    {
        $this->load->language('extension/module/omniva_m');
        $this->load->model('extension/module/omniva_m/order');

        $barcodes_data = $this->model_extension_module_omniva_m_order->getBarcodes($order_id, $history_id);

        return $this->getLabelPdf($barcodes_data);
    }

    private function getLabelPdf($barcodes_data)
    {
        if (empty($barcodes_data['barcodes'])) {
            return [
                'error' => $this->language->get(Params::PREFIX . 'error_no_barcodes_found')
            ];
        }

        $username = $this->config->get(Params::PREFIX . 'api_user');
        $password = $this->config->get(Params::PREFIX . 'api_pass');
        $print_type = (int) $this->config->get(Params::PREFIX . 'api_label_print_type');

        try {
            $label = new Label();
            $label->setAuth($username, $password);

            $pdf = $label->downloadLabels($barcodes_data['barcodes'], ($print_type === Params::LABEL_PRINT_TYPE_A4), 'S');
        } catch (\Exception $th) {
            return [
                'error' => $th->getMessage()
            ];
        }

        return [
            'pdf' => base64_encode($pdf),
            'order_ids' => $barcodes_data['order_ids']
        ];
    }

    private function printManifest($manifest_id)
    {
        $this->load->language('extension/module/omniva_m');
        $this->load->model('extension/module/omniva_m/order');

        $manifest = new Manifest();
        $manifest->setSender($this->getSenderContact());

        $order_ids = $this->model_extension_module_omniva_m_order->getOrderIdsByManifestId((int) $manifest_id);

        if ((int) $manifest_id === 0 || empty($order_ids)) {
            return [
                'error' => $this->language->get(Params::PREFIX . 'error_nothing_in_manifest'),
            ];
        }

        $skipped_orders = [];
        foreach ($order_ids as $order_id) {
            $order_data = $this->model_extension_module_omniva_m_order->loadOrder((int) $order_id);

            // skip orders wihtout barcodes (means not registered or with errors)
            if (empty($order_data['label_history']['last_barcodes'])) {
                $skipped_orders[] = $order_id;
                continue;
            }

            $barcodes = @json_decode($order_data['label_history']['last_barcodes'], true);

            if (!is_array($barcodes) || empty($barcodes)) {
                $skipped_orders[] = $order_id;
                continue;
            }

            $parcel_weight = round($order_data['set_weight'] / count($barcodes), 3);
            $receiver_address = Helper::getFormatedAddresFromOcOrderData($order_data['oc_order']);
            if ($order_data['shipping_type'] === Params::SHIPPING_TYPE_TERMINAL) {
                $receiver_address = Helper::getFormatedTerminalAddress($order_data['terminal_data'], true);
            }

            foreach ($barcodes as $barcode) {
                $order = new ApiOrder();
                $order->setTracking($barcode);
                $order->setQuantity(1);
                $order->setWeight($parcel_weight);
                $order->setReceiver($receiver_address);

                $manifest->addOrder($order);
            }
        }

        $pdf = $manifest->downloadManifest('S', 'omniva_manifest');

        return [
            'pdf' => base64_encode($pdf),
            'skipped_order_ids' => $skipped_orders
        ];
    }

    private function createManifest($order_ids)
    {
        $this->load->language('extension/module/omniva_m');
        $this->load->model('extension/module/omniva_m/order');

        $manifest = new Manifest();
        $manifest->setSender($this->getSenderContact());

        $manifest_id = Order::getNextManifestId($this->db);
        $manifest_empty = true;
        $skipped_orders = [];
        foreach ($order_ids as $order_id) {
            $order_data = $this->model_extension_module_omniva_m_order->loadOrder((int) $order_id);

            // skip orders wihtout barcodes (means not registered or with errors)
            if (empty($order_data['label_history']['last_barcodes']) || (int) $order_data['manifest_id'] > 0) {
                $skipped_orders[] = $order_id;
                continue;
            }

            $barcodes = @json_decode($order_data['label_history']['last_barcodes'], true);

            if (!is_array($barcodes) || empty($barcodes)) {
                $skipped_orders[] = $order_id;
                continue;
            }

            $manifest_empty = false;

            $parcel_weight = round($order_data['set_weight'] / count($barcodes), 3);
            $receiver_address = Helper::getFormatedAddresFromOcOrderData($order_data['oc_order']);
            if ($order_data['shipping_type'] === Params::SHIPPING_TYPE_TERMINAL) {
                $receiver_address = Helper::getFormatedTerminalAddress($order_data['terminal_data'], true);
            }

            // mark order as having manifest
            Order::updateManifestId($order_id, $manifest_id, $this->db);

            foreach ($barcodes as $barcode) {
                $order = new ApiOrder();
                $order->setTracking($barcode);
                $order->setQuantity(1);
                $order->setWeight($parcel_weight);
                $order->setReceiver($receiver_address);

                $manifest->addOrder($order);
            }
        }

        if ($manifest_empty) {
            return [
                'error' => $this->language->get(Params::PREFIX . 'error_nothing_in_manifest'),
                'skipped_order_ids' => $skipped_orders
            ];
        }

        $pdf = $manifest->downloadManifest('S', 'omniva_manifest');

        return [
            'pdf' => base64_encode($pdf),
            'skipped_order_ids' => $skipped_orders
        ];
    }

    private function getManifestOrders()
    {
        $this->load->model('extension/module/omniva_m/order');

        $limit_per_page = $this->config->get('config_limit_admin');

        // default filter values
        $filter = [
            'page' => 1,
            'limit' => $limit_per_page,
            'filter_order_id' => null,
            'filter_customer' => null,
            'filter_barcode' => null,
            'filter_order_status_id' => null,
            'filter_has_barcode' => null,
            'filter_has_manifest' => null,
        ];

        if (isset($this->request->post['page'])) {
            $filter['page'] = (int) $this->request->post['page'];
            if ($filter['page'] < 1) {
                $filter['page'] = 1;
            }
        }

        if (isset($this->request->post['filter_order_id'])) {
            $filter['filter_order_id'] = (int) $this->request->post['filter_order_id'];
            if ($filter['filter_order_id'] < 1) {
                $filter['filter_order_id'] = null;
            }
        }

        if (isset($this->request->post['filter_customer']) && !empty($this->request->post['filter_customer'])) {
            $filter['filter_customer'] = $this->request->post['filter_customer'];
        }

        if (isset($this->request->post['filter_barcode']) && !empty($this->request->post['filter_barcode'])) {
            $filter['filter_barcode'] = $this->request->post['filter_barcode'];
        }

        if (isset($this->request->post['filter_order_status_id'])) {
            $filter['filter_order_status_id'] = (int) $this->request->post['filter_order_status_id'];
            if ($filter['filter_order_status_id'] < 1) {
                $filter['filter_order_status_id'] = null;
            }
        }

        if (isset($this->request->post['filter_has_barcode'])) {
            $filter['filter_has_barcode'] = (int) $this->request->post['filter_has_barcode'];
            if ($filter['filter_has_barcode'] < 1) {
                $filter['filter_has_barcode'] = null;
            }
        }

        if (isset($this->request->post['filter_has_manifest'])) {
            $filter['filter_has_manifest'] = (int) $this->request->post['filter_has_manifest'];
            if ($filter['filter_has_manifest'] < 1) {
                $filter['filter_has_manifest'] = null;
            }
        }

        $total_orders = $this->model_extension_module_omniva_m_order->loadManifestOrdersTotal($filter);

        return [
            'orders' => $this->model_extension_module_omniva_m_order->loadManifestOrders($filter),
            'total_orders' => $total_orders,
            'current_page' => $filter['page'],
            'total_pages' => ceil($total_orders / $limit_per_page),
            'applied_filters' => $filter,
        ];
    }

    public function manifest()
    {
        $this->load->language('extension/module/omniva_m');

        $data = [];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('extension/module/omniva_m/order');

        $omniva_m_translation = $this->model_extension_module_omniva_m_order->loadAdminModuleTranslations();
        $data = array_merge($data, $omniva_m_translation);

        $data['omniva_m_data'] = [
            'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true),
            'ajax_url' => 'index.php?route=extension/module/omniva_m/ajax&' . $this->getUserToken(),
            'call_courier_address' => $this->model_extension_module_omniva_m_order->getSenderInformation(),
            'trans' => $this->model_extension_module_omniva_m_order->getJsTranslations(),
        ];

        $this->response->setOutput($this->load->view('extension/module/omniva_m/manifest', $data));
    }

    protected function getUserToken()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return 'user_token=' . $this->session->data['user_token'];
        }

        return 'token=' . $this->session->data['token'];
    }
}

<?php

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

use Mijora\Omniva\ServicePackageHelper\PackageItem;
use Mijora\Omniva\ServicePackageHelper\ServicePackageHelper;
use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Params;
use Mijora\OmnivaOpencart\Price;
use Mijora\BoxCalculator\Elements\Item as BoxCalcItem;
use Mijora\BoxCalculator\CalculateBox;

class ModelExtensionShippingOmnivaM extends Model
{
    private function getTextTitle($country = '', $terminal = false)
    {
        if ($country == 'FI' && $terminal) {
            return $this->language->get('text_title_mh');
        }

        return $this->language->get('text_title');
    }

    private function getTextPrefix($country = '', $terminal = false)
    {
        if ($country == 'FI' && $terminal) {
            return $this->language->get('text_prefix_mh');
        }

        return $this->language->get('text_prefix');
    }

    public function getQuote($address)
    {
        Helper::sendPowerBi($this->db, $this->config);

        $this->load->language('extension/shipping/omniva_m');

        $setting_prefix = '';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $setting_prefix = 'shipping_';
        }

        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
            WHERE geo_zone_id = '" . (int) $this->config->get(Params::PREFIX . 'geo_zone_id') . "' 
                AND country_id = '" . (int) $address['country_id'] . "' 
                AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')
        ");

        if (!$this->config->get(Params::PREFIX . 'geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        // check if there are prices set, else disable
        $price_data = Price::getPriceData($this->db, $address['iso_code_2']);
        if (!$price_data) {
            $status = false;
        }

        $method_data = array();

        // if disabled or wrong geo zone etc, return empty array (no options)
        if (!$status) {
            return $method_data;
        }

        // Add shipping options
        $tax_class_id = $this->config->get(Params::PREFIX . 'tax_class_id');

        //determine cost
        $price_data = json_decode((string) $price_data, true);

        $quote_data = array();

        $services = $this->determineServicesCost($price_data);

        foreach ($services as $service_id => $service_cost) {
            if ($service_cost < 0) {
                continue;
            }

            $quote_data[$service_id] = array(
                'code'         => 'omniva_m.' . $service_id,
                'title'        => $this->getTextPrefix($address['iso_code_2']) . $this->language->get('text_' . $service_id),
                'cost'         => $service_cost,
                'tax_class_id' => $tax_class_id,
                'text'         => $this->currency->format(
                    $this->tax->calculate(
                        $service_cost,
                        $tax_class_id,
                        $this->config->get('config_tax')
                    ),
                    $this->session->data['currency']
                )
            );
        }

        $courier_cost = (float) $this->calculateCost(
            $price_data['courier_price'],
            (int) $price_data['courier_price_range_type'],
            Params::SHIPPING_TYPE_COURIER
        );

        if ($courier_cost >= 0) {
            $quote_data['courier'] = array(
                'code'         => 'omniva_m.courier',
                'title'        => $this->getTextPrefix($address['iso_code_2']) . $this->language->get('text_courier'),
                'cost'         => $courier_cost,
                'tax_class_id' => $tax_class_id,
                'text'         => $this->currency->format(
                    $this->tax->calculate(
                        $courier_cost,
                        $tax_class_id,
                        $this->config->get('config_tax')
                    ),
                    $this->session->data['currency']
                )
            );
        }

        $terminal_cost = $this->determineTerminalCost($price_data, $address['iso_code_2']);

        if ($terminal_cost >= 0) {
            $terminals = Helper::loadTerminalListByCountry($address['iso_code_2']);

            foreach ($terminals as $terminal) {
                $key = 'terminal_' . $terminal['ZIP'];
                $quote_data[$key] = array(
                    'code'         => 'omniva_m.' . $key,
                    'title'        => $this->getTextPrefix($address['iso_code_2'])
                        . Helper::getFormatedTerminalAddress($terminal),
                    'cost'         => $terminal_cost,
                    'tax_class_id' => $tax_class_id,
                    'text'         => $this->currency->format(
                        $this->tax->calculate(
                            $terminal_cost,
                            $tax_class_id,
                            $this->config->get('config_tax')
                        ),
                        $this->session->data['currency']
                    )
                );
            }
        }

        // if neither courier nor terminal options available return empty array
        if (empty($quote_data)) {
            return $method_data;
        }

        $method_data = array(
            'code'       => 'omniva_m',
            'title'      => $this->getTextTitle($address['iso_code_2']),
            'quote'      => $quote_data,
            'sort_order' => $this->config->get($setting_prefix . Params::PREFIX . 'sort_order'),
            'error'      => false
        );

        return $method_data;
    }

    protected function determineServicesCost($price_data, $delivery_country = null)
    {
        $items = $this->getFormatedCartItems();
        if (!$items) {
            return [];
        }

        $packageItems = [];
        foreach ($items as $item) {
            // items has kg and cm, we need meters
            $packageItems[] = new PackageItem($item['weight'], $item['length'] / 100, $item['width'] / 100, $item['height'] / 100);
        }

        $services = ServicePackageHelper::getAvailablePackages($price_data['country'], $packageItems);

        $result = [];
        foreach ($services as $service) {
            $service_prefix = strtolower($service);

            $cost = isset($price_data[$service_prefix . '_price']) ? $price_data[$service_prefix . '_price'] : '';
            $range_type = isset($price_data[$service_prefix . '_price_range_type']) ? (int) $price_data[$service_prefix . '_price_range_type'] : 0;
            
            $result[$service_prefix] = (float) $this->calculateCost(
                $cost,
                $range_type,
                Params::SHIPPING_TYPE_COURIER
            );
        }

        return $result;
    }

    protected function determineTerminalCost($price_data, $delivery_country = null)
    {
        $contract_origin = (int) $this->config->get(Params::PREFIX . 'api_contract_origin');
        $sender_country_iso = $this->config->get(Params::PREFIX . 'sender_country');

        // disable terminals for FINLAND if sender is set to LT
        // if ($delivery_country === 'FI' && $sender_country_iso === 'LT') {
        //     return -1;
        // }

        $terminal_cost = (float) $this->calculateCost(
            $price_data['terminal_price'],
            (int) $price_data['terminal_price_range_type'],
            Params::SHIPPING_TYPE_TERMINAL
        );

        $use_simple_terminal_check = (bool) $this->config->get(Params::PREFIX . 'use_simple_terminal_check');
        if ($use_simple_terminal_check && !$this->isTerminalAllowed()) {
            return -1; // disable terminals
        }

        return $terminal_cost;
    }

    /**
     * Determines if cost setting has weight:price formating and extracts cost by cart weight. 
     * In case of incorrect formating will return -1.
     * If no format identifier (:) found in string will return original $cost_ranges.
     * 
     * @param string|float $cost_ranges price setting, can be in weight:price range formating (string)
     * 
     * @return string|float Extracted cost from format according to cart weight.
     */
    protected function getCostByWeight($cost_ranges, $cart_weight)
    {
        $cost = -1;
        $ranges = explode(';', $cost_ranges);
        if (!is_array($ranges)) {
            return $cost;
        }

        foreach ($ranges as $range) {
            $weight_cost = explode(':', trim($range));
            // check it is valid weight cost pair, skip otherwise
            if (!is_array($weight_cost) || count($weight_cost) != 2) {
                continue;
            }

            // if cart weight is higher than set weight use this ranges cost
            // formating is assumed to go from lowest to highest weight
            // and cost will be the last lower or equal to cart weight
            if ((float) trim($weight_cost[0]) <= $cart_weight) {
                $cost = (float) trim($weight_cost[1]);
            }
        }

        return $cost;
    }

    protected function getCartWeightInKg()
    {
        // Get cart weight
        $total_kg = $this->cart->getWeight();
        $kg_weight_class_id = (int) $this->config->get(Params::PREFIX . 'weight_class_id');
        $weight_class_id = (int) $this->config->get('config_weight_class_id');

        if ($kg_weight_class_id === $weight_class_id) {
            return (float) $total_kg;
        }

        return $kg_weight_class_id
                ? (float) $this->weight->convert($total_kg, $weight_class_id, $kg_weight_class_id)
                : (float) $total_kg;
    }

    protected function getCostByCartTotal($cost_ranges)
    {
        $cost = -1;
        $ranges = explode(';', $cost_ranges);
        if (!is_array($ranges)) {
            return $cost;
        }

        $cart_price = $this->cart->getTotal();
        $cart_price = $this->currency->format($cart_price, $this->session->data['currency'], false, false);

        foreach ($ranges as $range) {
            $cart_cost = explode(':', trim($range));
            // check it is valid weight cost pair, skip otherwise
            if (!is_array($cart_cost) || count($cart_cost) != 2) {
                continue;
            }

            // if cart price is higher than set price use this range cost
            // formating is assumed to go from lowest to highest cart price
            // and cost will be the last lower or equal to cart price
            if ((float) trim($cart_cost[0]) <= $cart_price) {
                $cost = (float) trim($cart_cost[1]);
            }
        }

        return $cost;
    }

    protected function calculateCost($cost, $range_type, $shipping_type)
    {
        // empty values assumed as disabled
        if ($cost === '') {
            return -1;
        }

        if ($shipping_type === Params::SHIPPING_TYPE_TERMINAL || $range_type === Price::RANGE_TYPE_WEIGHT) {
            $cart_weight = $this->getCartWeightInKg();
        }

        // disable terminal option if total cart weight is above allowed and this check is not disabled
        $disable_cart_weight_check = (bool) $this->config->get(Params::PREFIX . 'disable_cart_weight_check');
        if ($shipping_type === Params::SHIPPING_TYPE_TERMINAL && !$disable_cart_weight_check && $cart_weight > Params::TERMINAL_MAX_WEIGHT) {
            return -1;
        }

        // Check if $cost_ranges is in cart_total:price ; cart_total:price format
        if (!Price::isPriceRangeFormat($cost)) {
            return $cost; // not formated return as is
        }

        if ($range_type === Price::RANGE_TYPE_WEIGHT) {
            $cost = $this->getCostByWeight($cost, $cart_weight);
        }

        if ($range_type === Price::RANGE_TYPE_CART_PRICE) {
            $cost = $this->getCostByCartTotal($cost);
        }

        return $cost;
    }

    public function isTerminalAllowed()
    {
        $cart_items = $this->getFormatedCartItems();
        if ( ! $cart_items ) {
            return false;
        }

        $items_list = array();
        foreach ( $cart_items as $item ) {
            for ( $i = 0; $i < $item['quantity']; $i++ ) {
                $items_list[] = new BoxCalcItem($item['width'], $item['height'], $item['length']);
            }
        }

        $box_calculator = new CalculateBox($items_list);
        $box_calculator->setBoxWallThickness(0);
        $box_calculator->setMaxBoxSize(64, 38, 39);
        $box_calculator->enableDebug(true);
        $box_calculator->setMethod('Heuristic3D');

        $box_size = $box_calculator->findBoxSizeUntilMaxSize();

        return ($box_size) ? true : false;
    }

    public function getLengthClassId()
    {
        $weight_sql = $this->db->query("
            SELECT length_class_id FROM `" . DB_PREFIX . "length_class_description` WHERE `unit` = 'cm' LIMIT 1
        ");

        if (!$weight_sql->rows) {
            return null;
        }

        return (int) $weight_sql->row['length_class_id'];
    }

    public function getFormatedCartItems()
    {
        $cm_length_class_id = $this->config->get(Params::PREFIX . 'length_class_id');
        $kg_weight_class_id = $this->config->get(Params::PREFIX . 'weight_class_id');

        if (!$cm_length_class_id) {
            $cm_length_class_id = $this->getLengthClassId();
        }

        $cart_items = [];
        foreach ($this->cart->getProducts() as $product) {
            // we interested only in shippable items
            if ((int) $product['shipping'] !== 1) {
                continue;
            }

            $width  = $cm_length_class_id
                ? (float) $this->length->convert($product['width'],  $product['length_class_id'], $cm_length_class_id)
                : (float) $product['width'];
            $length = $cm_length_class_id
                ? (float) $this->length->convert($product['length'], $product['length_class_id'], $cm_length_class_id)
                : (float) $product['length'];
            $height = $cm_length_class_id
                ? (float) $this->length->convert($product['height'], $product['length_class_id'], $cm_length_class_id)
                : (float) $product['height'];
            $weight = $kg_weight_class_id
                ? (float) $this->weight->convert($product['weight'], $product['weight_class_id'], $kg_weight_class_id)
                : (float) $product['weight'];

            $cart_items[] = [
                'width' => $width,
                'length' => $length,
                'height' => $height,
                'weight' => $weight,
                'quantity' => (int) $product['quantity'],
            ];
        }

        return $cart_items;
    }
}

<?php

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Params;
use Mijora\OmnivaOpencart\Price;

class ModelExtensionShippingOmnivaM extends Model
{
    public function getQuote($address)
    {
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
        $price_data = json_decode($price_data, true);

        $quote_data = array();

        $courier_cost = (float) $this->calculateCost(
            $price_data['courier_price'],
            (int) $price_data['courier_price_range_type'],
            Params::SHIPPING_TYPE_COURIER
        );

        if ($courier_cost >= 0) {
            $quote_data['courier'] = array(
                'code'         => 'omniva_m.courier',
                'title'        => $this->language->get('text_prefix') . $this->language->get('text_courier'),
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


        $terminal_cost = (float) $this->calculateCost(
            $price_data['terminal_price'],
            (int) $price_data['terminal_price_range_type'],
            Params::SHIPPING_TYPE_TERMINAL
        );

        if ($terminal_cost >= 0) {
            $terminals = Helper::loadTerminalListByCountry($address['iso_code_2']);

            foreach ($terminals as $terminal) {
                $key = 'terminal_' . $terminal['ZIP'];
                $quote_data[$key] = array(
                    'code'         => 'omniva_m.' . $key,
                    'title'        => $this->language->get('text_prefix')
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
            'title'      => $this->language->get('text_title'),
            'quote'      => $quote_data,
            'sort_order' => $this->config->get($setting_prefix . Params::PREFIX . 'sort_order'),
            'error'      => false
        );

        return $method_data;
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
        // Make sure its in kg (we do not support imperial units, so assume weight is in metric units)
        $weight_class_id = $this->config->get('config_weight_class_id');
        $unit = $this->db->query("
            SELECT unit FROM `" . DB_PREFIX . "weight_class_description` wcd 
            WHERE (weight_class_id = " . $weight_class_id . ") 
                AND language_id = '" . (int) $this->config->get('config_language_id') . "'
        ");

        if ($unit->row['unit'] == 'g') { // if default in grams means cart weight will be in grams as well
            $total_kg /= 1000;
        }

        return (float) $total_kg;
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

        // disable terminal option if total cart weight is above allowed
        if ($shipping_type === Params::SHIPPING_TYPE_TERMINAL && $cart_weight > Params::TERMINAL_MAX_WEIGHT) {
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
}

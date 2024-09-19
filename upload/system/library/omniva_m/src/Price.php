<?php

namespace Mijora\OmnivaOpencart;

class Price
{
    const RANGE_TYPE_CART_PRICE = 0;

    const RANGE_TYPE_WEIGHT = 1;

    const RANGE_TYPE = [
        self::RANGE_TYPE_CART_PRICE,
        self::RANGE_TYPE_WEIGHT
    ];

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getCountries($geo_zone_id = false)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "country c";

        if ((int) $geo_zone_id > 0) {
            $sql .= "
                LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z ON z.country_id = c.country_id 
                WHERE z.geo_zone_id = '" . (int) $geo_zone_id . "' 
                GROUP BY c.country_id
            ";
        }

        $result = $this->db->query($sql);

        return $result->rows;
    }

    public static function getPriceData($db, $country_code)
    {
        $result = $db->query("
            SELECT price_data FROM " . DB_PREFIX . "omniva_m_price WHERE country_code = '" . $country_code . "'
        ");

        return !$result->rows ? false : $result->row['price_data'];
    }

    public static function getPrices($db)
    {
        $result = $db->query("
            SELECT price_data FROM " . DB_PREFIX . "omniva_m_price
        ");

        return $result->rows;
    }

    public function savePrice($data, $store_id = 0)
    {
        if (isset($data['courier_price'])) {
            $data['courier_price'] = self::cleanPriceRangeData($data['courier_price']);
        }

        if (isset($data['terminal_price'])) {
            $data['terminal_price'] = self::cleanPriceRangeData($data['terminal_price']);
        }

        $json_data = json_encode($data);

        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "omniva_m_price` (country_code, price_data) 
            VALUES('" . $this->db->escape($data['country']) . "', '" . $json_data . "') 
            ON DUPLICATE KEY UPDATE price_data='" . $json_data . "'
        ");

        return $data;
    }

    public function deletePrice($country_code)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "omniva_m_price` WHERE `country_code` = '" . $this->db->escape($country_code) . "'");
    }

    public static function isPriceRangeFormat($range_string)
    {
        // Check if $cost_ranges is in cart_total:price ; cart_total:price format
        return strpos($range_string, ':') === false ? false : true;
    }

    public static function cleanPriceRangeData($range_string)
    {
        // if not range format return trimmed string
        if (empty($range_string) || !self::isPriceRangeFormat($range_string)) {
            return trim($range_string);
        }

        $ranges = explode(';', $range_string);

        // in case explode returns false - should never happen
        if (!is_array($ranges)) {
            return '';
        }

        $result = [];

        foreach ($ranges as $range_data) {
            // explode into range and cost parts
            $range_data_array = explode(':', trim($range_data));

            // resulting range data array must be array with 2 elements [range, cost]
            if (!is_array($range_data_array) || count($range_data_array) != 2) {
                continue;
            }

            // if either of two values is empty skip it
            if (trim($range_data_array[0]) === '' || trim($range_data_array[1]) === '') {
                continue;
            }

            $range = trim($range_data_array[0]);
            $cost = (float) trim($range_data_array[1]);

            // store data into array using range as key
            $result[$range] = $cost;
        }

        // sort by keys from lowest to highest
        uksort($result, function ($a, $b) {
            return (float) $a - (float) $b;
        });

        // merge everything back into string
        $result_string = implode(' ; ', array_map(
            function ($key, $value) {
                return "$key:$value";
            },
            array_keys($result),
            array_values($result)
        ));

        return $result_string;
    }

    /**
     * Parses price string, returned prices array from range has range key as array key
     * 
     * @param string $price_string 
     * @param bool $return_array should returned result be array even if bot a price range given
     * 
     * @return float|float[] If not range string will return value, if range then array of prices, -1 if invalid range
     */
    public static function parsePriceString($price_string, $return_array = false)
    {
        if (!self::isPriceRangeFormat($price_string)) {
            $price_string = $price_string === '' ? -1.0 : $price_string;
            return $return_array ? [(float) $price_string] : (float) $price_string;
        }

        $ranges = explode(';', $price_string);
        if (!is_array($ranges)) {
            return $return_array ? [-1.0] : -1.0;
        }

        $result = [];
        foreach ($ranges as $range) {
            $parts = explode(':', trim($range));
            // check it is valid weight cost pair, skip otherwise
            if (!is_array($parts) || count($parts) != 2) {
                continue;
            }
            
            $result[$parts[0]] = (float) trim($parts[1]);
        }

        return $result ? $result : ($return_array ? [-1.0] : -1.0);
    }
}

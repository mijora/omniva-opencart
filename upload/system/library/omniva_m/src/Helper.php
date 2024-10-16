<?php

namespace Mijora\OmnivaOpencart;

use DateTime;
use DateTimeZone;
use Mijora\Omniva\PowerBi\OmnivaPowerBi;
use Mijora\Omniva\ServicePackageHelper\ServicePackageHelper;
use Mijora\Omniva\Shipment\AdditionalService\DeliveryToAnAdultService;
use Mijora\Omniva\Shipment\AdditionalService\FragileService;
use Mijora\Omniva\Shipment\Package\ServicePackage;

class Helper
{
    public static function saveSettings($db, $data)
    {
        foreach ($data as $key => $value) {
            $query = $db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE `code` = 'omniva_m' AND `key` = '" . $db->escape($key) . "'");
            if ($query->num_rows) {
                $id = $query->row['setting_id'];
                $db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $db->escape($value) . "', serialized = '0' WHERE `setting_id` = '$id'");
            } else {
                $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', `code` = 'omniva_m', `key` = '$key', `value` = '" . $db->escape($value) . "'");
            }
        }
    }

    public static function getDefaultTrackingEmailTemplate()
    {
        return @file_get_contents(Params::DIR_EMAIL_TEMPLATES . Params::DEFAULT_TRACKING_EMAIL_TEMPLATE);
    }

    public static function getModificationXmlVersion($file)
    {
        if (!is_file($file)) {
            return null;
        }

        $xml = file_get_contents($file);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXml($xml);

        $version = $dom->getElementsByTagName('version')->item(0)->nodeValue;

        return $version;
    }

    public static function getModificationSourceFilename()
    {
        return Params::BASE_MOD_XML_SOURCE_DIR . self::getModificationXmlDirByVersion() . Params::BASE_MOD_XML;
    }

    public static function isModificationNewer()
    {
        return version_compare(
            self::getModificationXmlVersion(self::getModificationSourceFilename()),
            self::getModificationXmlVersion(Params::BASE_MOD_XML_SYSTEM),
            '>'
        );
    }

    public static function getModificationXmlDirByVersion()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return Params::MOD_SOURCE_DIR_OC_3_0;
        }

        if (version_compare(VERSION, '2.3.0', '>=')) {
            return Params::MOD_SOURCE_DIR_OC_2_3;
        }

        // by default return latest version modifications dir
        return Params::MOD_SOURCE_DIR_OC_3_0;
    }

    public static function copyModificationXml()
    {
        self::removeModificationXml();

        copy(self::getModificationSourceFilename(), Params::BASE_MOD_XML_SYSTEM);
    }

    public static function removeModificationXml()
    {
        if (is_file(Params::BASE_MOD_XML_SYSTEM)) {
            @unlink(Params::BASE_MOD_XML_SYSTEM);
        }
    }

    /**
     * @param Object $db Opencart DB object
     * 
     * @return array Array with tablenames as keys and queries to run as values
     */
    public static function checkDbTables($db)
    {
        $result = array();

        // OC3 has too small default type for session (terminals takes a lot of space)
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $session_table = $db->query("DESCRIBE `" . DB_PREFIX . "session`")->rows;
            foreach ($session_table as $col) {
                if (strtolower($col['Field']) != 'data') {
                    continue;
                }
                if (strtolower($col['Type']) == 'text') {
                    // needs to be MEDIUMTEXT or LONGTEXT
                    $result['session'] = "
                        ALTER TABLE `" . DB_PREFIX . "session` 
                        MODIFY `data` MEDIUMTEXT;
                    ";
                }
                break;
            }
        }

        return $result;
    }

    public static function ajaxUpdateTerminals($db)
    {
        $terminal_list_data = self::updateTerminals();

        if (empty($terminal_list_data) || $terminal_list_data === null) {
            return ['error' => 'Failed to update terminal list'];
        }

        $saved = time();

        self::saveSettings($db, [
            Params::PREFIX . 'last_update' => $saved
        ]);

        return [
            'updated' => date('Y-m-d H:i:s', $saved),
            'terminalList' => $terminal_list_data
        ];
    }

    /**
     * Load terminal array from json file on omniva server
     * 
     * @return array|null returns terminal array or null (json_decode on failure also returns null)
     */
    public static function downloadTerminalsJson()
    {
        $terminals = file_get_contents(Params::LOCATIONS_URL);
        if (! $terminals) {
            $terminals = self::getContentsViaCurl(Params::LOCATIONS_URL);
        }
        return @json_decode((string) $terminals, true);
    }

    public static function getContentsViaCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function updateTerminals()
    {
        $terminals_array = self::downloadTerminalsJson();

        if (empty($terminals_array)) {
            return null;
        }

        // store original data file
        file_put_contents(Params::TERMINAL_LIST_JSON_FILE, json_encode($terminals_array));

        $terminals_sorted = [];

        // Sort terminal list by country and filter out post offices if not allowed
        foreach ($terminals_array as $terminal) {
            // if we do not allow postoffice as selection remove TYPE=1 terminals
            if (!Params::ALLOW_POSTOFFICE && (int) $terminal['TYPE'] === 1) {
                continue;
            }

            // check coordinates is valid
            if ((float) $terminal['X_COORDINATE'] <= 0 || (float) $terminal['Y_COORDINATE'] <= 0) {
                continue;
            }

            $country = $terminal['A0_NAME'];
            if (!isset($terminals_sorted[$country])) {
                $terminals_sorted[$country] = [];
            }

            $terminals_sorted[$country][$terminal['ZIP']] = $terminal;
        }

        // save sorted data into files for easier access later.
        // TODO: possible rework to use database table instead of file storage
        $terminal_list_data = [];
        foreach ($terminals_sorted as $key => $value) {
            $terminal_list_data[] = [
                'country' => $key,
                'total' => count($value)
            ];

            file_put_contents(Params::TERMINAL_LIST_JSON_FILE . '_' . $key, json_encode($value));
        }

        return $terminal_list_data;
    }

    public static function getTerminalsInformation()
    {
        $file_list = glob(Params::TERMINAL_LIST_JSON_FILE . '_*');

        if ($file_list === false) {
            return [];
        }

        $terminal_list_data = [];
        foreach ($file_list as $file) {
            $country = explode(Params::TERMINAL_LIST_JSON_FILE . '_', $file)[1] ?? null;

            if ($country === null) {
                continue;
            }

            $terminals_array = json_decode((string) file_get_contents($file), true);

            $terminal_list_data[$country] = count($terminals_array);
        }

        return $terminal_list_data;
    }

    public static function loadTerminalListByCountry($country_code)
    {
        if (empty($country_code)) {
            return [];
        }

        $file = Params::TERMINAL_LIST_JSON_FILE . '_' . $country_code;
        if (!is_file($file)) {
            return [];
        }

        $terminals_array = json_decode((string) file_get_contents($file), true);

        return empty($terminals_array) ? [] : $terminals_array;
    }

    public static function getTerminalByZip($country_code, $zip)
    {
        if (empty($country_code) || empty($zip)) {
            return [];
        }

        $terminal_list = self::loadTerminalListByCountry($country_code);

        return isset($terminal_list[$zip]) ? $terminal_list[$zip] : [];
    }

    public static function getFormatedTerminalAddress($terminal, $with_country = false)
    {
        return $terminal['NAME']
            . " [ "
            . $terminal['A5_NAME'] . " "
            . $terminal['A7_NAME'] . ($with_country ? ', ' . $terminal['ZIP'] . ' ' . $terminal['A0_NAME'] : '')
            . " ]";
    }

    public static function getFormatedAddresFromOcOrderData($oc_order_data)
    {
        $name = $oc_order_data['shipping_firstname'] . ' ' . $oc_order_data['shipping_lastname'];
        $mobile = $oc_order_data['telephone'];
        $street = $oc_order_data['shipping_address_1'];
        if (!empty($oc_order_data['shipping_address_2'])) {
            $street .= ', ' . $oc_order_data['shipping_address_2'];
        }
        $postcode = $oc_order_data['shipping_postcode'];
        $city = $oc_order_data['shipping_city'];
        $country = $oc_order_data['shipping_iso_code_2'];
        $email = $oc_order_data['email'];

        return $name . ", " . $street . ", " . $city . " " . $postcode . ", " . $country;
    }

    public static function decideServiceCode(
        $sendoff_type,
        $receive_type,
        $contract_origin,
        $courier_options,
        $deliver_country_iso_code,
        $sender_country_iso_code
    ) {
        if ($receive_type === Params::SHIPPING_TYPE_COURIER) {
            $courrier_service_code = 'QH'; // default service code

            if ($contract_origin === Params::CONTRACT_ORIGIN_ESTONIA) {
                $courrier_service_code = 'CI'; // default for estonia origin

                // courrier plus if estonian service enabled
                if (in_array(Params::SERVICE_COURIER_ESTONIA, $courier_options)) {
                    $courrier_service_code = 'LX';
                }

                // if delivery to finland and finland service enabled
                if ($deliver_country_iso_code === 'FI' && in_array(Params::SERVICE_COURIER_FINLAND, $courier_options)) {
                    $courrier_service_code = 'QB';
                }
            }

            switch ($sendoff_type) {
                case Params::SENDOFF_TYPE_COURIER:
                    return $courrier_service_code;
                case Params::SENDOFF_TYPE_TERMINAL:
                    return 'PK';
                case Params::SENDOFF_TYPE_SORTING_CENTER:
                    return 'QL';
                default:
                    return null;
            }
        }

        if ($receive_type === Params::SHIPPING_TYPE_TERMINAL) {
            // prevent sending to finland parcel machine if sender is from LT
            // if ($deliver_country_iso_code === 'FI' && $sender_country_iso_code === 'LT') {
            //     return null;
            // }

            // Finland parcel machines are Matkahuolto, and requires CD service
            if ($deliver_country_iso_code === 'FI') {
                return 'CD';
            }

            switch ($sendoff_type) {
                case Params::SENDOFF_TYPE_COURIER:
                    return 'PU';
                case Params::SENDOFF_TYPE_TERMINAL:
                    return 'PA';
                case Params::SENDOFF_TYPE_SORTING_CENTER:
                    return 'PP';
                default:
                    return null;
            }
        }

        return null;
    }


    /**
     * @param mixed should be Order ID
     * 
     * @return string returns string with attached control number
     */
    public static function calculateCodReference($string)
    {
        // makesure its at least 2 symbols
        $order_number = str_pad($string, 2, '0', STR_PAD_LEFT);
        $kaal = array(7, 3, 1);
        $sl = $st = strlen($order_number);

        $total = 0;
        while ($sl > 0 and substr($order_number, --$sl, 1) >= '0') {
            $total += substr($order_number, ($st - 1) - $sl, 1) * $kaal[($sl % 3)];
        }
        $kontrollnr = ((ceil(($total / 10)) * 10) - $total);

        return $order_number . $kontrollnr;
    }

    public static function hasGitUpdate()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => Params::GIT_VERSION_CHECK,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'OMNIVA_M_VERSION_CHECK_v1.0',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $version_data = @json_decode((string) $response, true);

        if (empty($version_data)) {
            return false;
        }

        $git_version = isset($version_data['tag_name']) ? $version_data['tag_name'] : null;

        if ($git_version === null) {
            return false;
        }

        $git_version = str_ireplace('v', '', $git_version);

        if (!self::isModuleVersionNewer($git_version)) {
            return false;
        }

        return [
            'version' => $git_version,
            'download_url' => isset($version_data['assets'][0]['browser_download_url'])
                ? $version_data['assets'][0]['browser_download_url']
                : Params::GIT_URL
        ];
    }

    public static function isModuleVersionNewer($git_version)
    {
        return version_compare($git_version, Params::VERSION, '>');
    }

    public static function isTimeToCheckVersion($timestamp)
    {
        return time() > (int) $timestamp + (Params::GIT_CHECK_EVERY_HOURS * 60 * 60);
    }

    public static function parseBarcodeStringToArray($string)
    {
        if (strlen($string) === 0) {
            return [];
        }

        // backwards compatibility, barcodes used to be stored as json encoded arrays
        if ($string[0] === '[') {
            $array = @json_decode($string, true);
            return is_array($array) ? $array : [];
        }

        $array = explode(',', $string);

        return array_map('trim', $array);
    }

    public static function isValidTimeString($string)
    {
        return preg_match('/^[0-9]{2}:[0-9]{2}$/', $string);
    }

    public static function isValidCourierCallId($call_id)
    {
        return preg_match('/^[0-9A-Z]{1,50}$/i', $call_id);
    }

    public static function getAdditionalServicesList()
    {
        return [
            'consolidate' => [
                FragileService::CODE => FragileService::PARAMS_LIST,
            ],
            'multiparcel' => [
                FragileService::CODE => FragileService::PARAMS_LIST,
                DeliveryToAnAdultService::CODE => DeliveryToAnAdultService::PARAMS_LIST,
            ],
        ];
    }

    public static function getOmxServiceObj($code)
    {
        switch ($code) {
            case FragileService::CODE:
                return new FragileService();
                break;
            case DeliveryToAnAdultService::CODE:
                return new DeliveryToAnAdultService();
                break;

            default:
                return null;
        }

        return null;
    }

    public static function getMultiType($shipping_type, $is_cod)
    {
        if ($shipping_type === Params::SHIPPING_TYPE_COURIER && $is_cod) {
            return 'consolidate';
        }

        // all other cases are multiparcel
        return 'multiparcel';
    }

    public static function convertUtcTimeToLocal($time, $output_format = 'Y-m-d H:i:s', $local_timezone = null)
    {
        $utc_timezone = new DateTimeZone('UTC');
        $datetime = new DateTime(
            (is_numeric($time) ? 'now' : $time),
            $utc_timezone
        );

        // given time is numeric means its a timestamp and not date time string
        if (is_numeric($time)) {
            $datetime->setTimestamp($time);
        }

        try {
            $timezone = $local_timezone ? new DateTimeZone($local_timezone) : new DateTimeZone(date_default_timezone_get());
        } catch (\Throwable $th) {
            $timezone = new DateTimeZone(date_default_timezone_get());
        }

        $datetime->setTimezone($timezone);

        return $datetime->format($output_format);
    }

    public static function sendPowerBi($db, $config)
    {
        try {
            // if PowerBi isnt setup in api-lib yet, do not trigger info send
            if (!OmnivaPowerBi::ENDPOINT) {
                return;
            }

            $username = $config->get(Params::PREFIX . 'api_user');
            $password = $config->get(Params::PREFIX . 'api_pass');

            if (!$username || !$password) {
                return;
            }

            $last_timestamp = $config->get(Params::PREFIX . 'powerbi_timestamp');
            if (!$last_timestamp) {
                $last_timestamp = '1990-01-01 00:00:00';
            }

            $datetime = new DateTime($last_timestamp);
            $current_datetime = new DateTime();
            $days_since_last_send = $datetime->diff($current_datetime)->format("%a");

            $last_check = $config->get(Params::PREFIX . 'powerbi_check');
            // send once per 30d if failed try to send again no more than once per 24h
            if ((int) $days_since_last_send < 30 || ($last_check && ((int) $last_check + (24 * 60 * 60)) > time())) {
                return;
            }

            $query = $db->query("SELECT COUNT(*) as totalToTerminal FROM `" . DB_PREFIX . "order` WHERE `shipping_code` LIKE 'omniva_m.terminal_%' AND date_added >= '" . $last_timestamp . "'");
            $totalTerminal = $query->num_rows ? $query->row['totalToTerminal'] : 0;

            $query = $db->query("SELECT COUNT(*) as totalToCourier FROM `" . DB_PREFIX . "order` WHERE `shipping_code` LIKE 'omniva_m.courier%' AND date_added >= '" . $last_timestamp . "'");
            $totalCourier = $query->num_rows ? $query->row['totalToCourier'] : 0;

            $opb = (new OmnivaPowerBi($username, true))
                ->setPlatform('OpenCart v' . VERSION)
                ->setPluginVersion(Params::VERSION)
                ->setSenderCountry($config->get(Params::PREFIX . 'sender_country'))
                ->setSenderName($config->get(Params::PREFIX . 'sender_name'))
                ->setOrderCountCourier($totalCourier)
                ->setOrderCountTerminal($totalTerminal)
                ->setDateTimeStamp($last_timestamp);

            $priceData = Price::getPrices(($db));

            if (is_array($priceData)) {
                foreach ($priceData as $priceCountry) {
                    $data = json_decode($priceCountry['price_data'], true);

                    // terminal price
                    $price_array = Price::parsePriceString($data['terminal_price'], true);
                    $price_array = array_filter($price_array, function ($item) {
                        return $item >= 0.0;
                    });
                    if ($price_array) {
                        $opb->setTerminalPrice($data['country'], min($price_array), max($price_array));
                    }

                    // courier price
                    $price_array = Price::parsePriceString($data['courier_price'], true);
                    $price_array = array_filter($price_array, function ($item) {
                        return $item >= 0.0;
                    });
                    if ($price_array) {
                        $opb->setCourierPrice($data['country'], min($price_array), max($price_array));
                    }
                }
            }

            $settings = [
                Params::PREFIX . 'powerbi_check' => time()
            ];

            if ($opb->send()) {
                $settings[Params::PREFIX . 'powerbi_timestamp'] = date('Y-m-d H:i:s');
            }

            self::saveSettings($db, $settings);
        } catch (\Throwable $th) {
            // silence is golden
        }
    }

    public static function getShippingCode($oc_shipping_code)
    {
        return explode('.', $oc_shipping_code)[1];
    }

    public static function isInternational($code)
    {
        // Since servicePackage is required only for international shipments we can assume this is premium/standard/economy if we get code back
        $code = ServicePackageHelper::getServicePackageCode($code);

        return $code !== null;
    }
}

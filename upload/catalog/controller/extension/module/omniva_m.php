<?php

use Mijora\OmnivaOpencart\Helper;
use Mijora\OmnivaOpencart\Params;

require_once(DIR_SYSTEM . 'library/omniva_m/vendor/autoload.php');

class ControllerExtensionModuleOmnivaM extends Controller
{
    public function ajax()
    {
        if (!isset($this->request->get['action'])) {
            exit();
        }

        switch ($this->request->get['action']) {
            case 'getTerminals':
                $terminal_list = $this->getTerminals();

                header('Content-Type: application/json');
                echo json_encode(['data' => $terminal_list]);
                exit();
            case 'getCheckoutSettings':
                header('Content-Type: application/json');
                echo json_encode(['data' => $this->getCheckoutSettings()]);
                exit();
            case 'getFrontTrans':
                $translation = $this->getFrontTrans();

                header('Content-Type: application/json');
                echo json_encode(['data' => $translation]);
                exit();
            case 'terminalUpdate':
                $secret = $this->config->get(Params::PREFIX . 'cron_secret');
                if (isset($this->request->get['secret']) && $secret && $secret === $this->request->get['secret']) {
                    $data = Helper::ajaxUpdateTerminals($this->db);
                    header('Content-Type: application/json');
                    echo json_encode(['data' => $data]);
                    exit();
                }

                header('HTTP/1.0 403 Forbidden');
                exit();
            default:
                exit();
        }
    }

    private function getCheckoutSettings()
    {
    	// Opencart 3 expects status to be shipping_omniva_m_status
    	$oc_prefix = '';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $oc_prefix = 'shipping_';
        }
        
        $omniva_m_status = $this->config->get($oc_prefix . Params::PREFIX . 'status');

        $data = [
        	'omniva_m_status' => (bool) $omniva_m_status
        ];

		if ($omniva_m_status) {
			$data['omniva_m_country_code'] = isset($this->session->data['shipping_address']['iso_code_2']) ? $this->session->data['shipping_address']['iso_code_2'] : 'LT';

			$this->load->language('extension/module/omniva_m');

			$data['omniva_m_ajax_url'] = $this->url->link('extension/module/omniva_m/ajax', '', true);

			$data['omniva_m_js_translation'] = $this->getFrontTrans(true);
		}
		
		return $data;
    }

    private function getTerminals()
    {
        $country_code = isset($this->request->get['country_code']) ? $this->request->get['country_code'] : '';

        if (empty($country_code) || strlen($country_code) > 3) {
            return [];
        }

        $terminal_list = Helper::loadTerminalListByCountry($country_code);

        // [
        //     "Title",
        //     "Latitude",
        //     "Longitude",
        //     "ID/Postal Code",
        //     "City",
        //     "Address",
        //     "Description"
        // ];

        $configured_list = [];

        foreach ($terminal_list as $key => $terminal) {
            $configured_list[] = [
                $terminal['NAME'] . " [ " . $terminal['A5_NAME'] . " " . $terminal['A7_NAME'] . " ]",
                $terminal['Y_COORDINATE'],
                $terminal['X_COORDINATE'],
                $terminal['ZIP'],
                $terminal['A1_NAME'],
                $terminal['A2_NAME'],
                $terminal['comment_lit']
            ];
        }

        return $configured_list;
    }

    private function getFrontTrans($remove_prefix = false)
    {
        $all_translations = $this->load->language('extension/module/omniva_m');

		$result = [];

		foreach ($all_translations as $key => $string) {
			if (strpos($key, 'omniva_m_') === FALSE) {
				continue;
			}

			$result[($remove_prefix ? str_replace('omniva_m_', '', $key) : $key)] = $string;
		}
		
		return $result;

        //return array_filter($all_translations, function($item) {
        //    return strpos($item, 'omniva_m_') !== FALSE;
        //}, ARRAY_FILTER_USE_KEY);
    }
}

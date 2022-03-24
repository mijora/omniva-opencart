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
}

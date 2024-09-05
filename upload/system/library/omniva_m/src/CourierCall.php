<?php

namespace Mijora\OmnivaOpencart;

use Mijora\Omniva\Shipment\CallCourier;

class CourierCall
{
    const TABLE_SQL = "
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_m_courier_call` (
            `call_id` varchar(100) NOT NULL,
            `date_from` datetime DEFAULT NULL,
            `date_to` datetime DEFAULT NULL,
            `canceled` tinyint(4) DEFAULT '0',
            PRIMARY KEY (`call_id`),
            KEY `oc_omniva_m_courier_call_date_from_IDX` (`date_from`,`date_to`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    public static function checkTable($db)
    {
        $db->query(self::TABLE_SQL);
    }

    public static function saveCourierCall($db, CallCourier $response)
    {
        // prevent saving invalid response
        if (!$response->getResponseCallNumber()) {
            return;
        }

        self::checkTable($db);

        $date_start = str_ireplace('T', ' ', (string) $response->getResponseTimeStart());
        $date_end = str_ireplace('T', ' ', (string) $response->getResponseTimeEnd());

        $db->query("
            INSERT INTO `" . DB_PREFIX . "omniva_m_courier_call` 
            SET `call_id` = '" . $db->escape($response->getResponseCallNumber()) . "', 
                `date_from` = '" . $db->escape($date_start) . "', 
                `date_to` = '" . $db->escape($date_end) . "'
        ");
    }

    public static function cancelCall($db, $call_id)
    {
        if (!$call_id) {
            return;
        }

        self::checkTable($db);

        $db->query("
            UPDATE `" . DB_PREFIX . "omniva_m_courier_call` 
            SET `canceled` = 1
            WHERE `call_id` = '" . $db->escape($call_id) . "'
        ");
    }

    public static function getActiveCalls($db, $config)
    {
        self::checkTable($db);

        $now = gmdate('Y-m-d H:i:s');

        $result = $db->query("
            SELECT * FROM `" . DB_PREFIX . "omniva_m_courier_call`
            WHERE `canceled` = '0' AND date_to >= '" . $now . "'
        ");

        if (!$result->rows) {
            return [];
        }

        $timezone = $config->get('config_timezone');
        if (!$timezone) {
            $timezone = date_default_timezone_get();
        }

        $timezone = 'UTC';

        $list = [];
        foreach ($result->rows as $row) {
            $list[$row['call_id']] = Helper::convertUtcTimeToLocal($row['date_from'], 'Y-m-d H:i', $timezone) 
                . ' - ' . Helper::convertUtcTimeToLocal($row['date_to'], 'Y-m-d H:i', $timezone);
        }

        return $list;
    } 
}

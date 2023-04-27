<?php

namespace Mijora\OmnivaOpencart;

class Order
{
    private $db = null;

    private $id_order = 0;

    private $data = [];

    public function __construct($id_order, $db, $load_data = false)
    {
        $this->db = $db;
        $this->id_order = (int) $id_order;

        if ($load_data) {
            $this->data = $this->loadOrderDataStatic($id_order, $this->db);
        }
    }

    public function isDataEmpty()
    {
        return empty($this->data);
    }

    public function getData($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function getDataAll()
    {
        return $this->data;
    }

    public function setWeight($weight)
    {
        if ($weight === null && isset($this->data['weight'])) {
            unset($this->data['weight']);
            return $this;
        }

        $this->data['weight'] = (float) $weight;
        return $this;
    }

    public function setCodUse($cod_use)
    {
        if ($cod_use === null && isset($this->data['cod_use'])) {
            unset($this->data['cod_use']);
            return $this;
        }

        $this->data['cod_use'] = (float) $cod_use;
        return $this;
    }

    public function setCodAmount($cod_amount)
    {
        if ($cod_amount === null && isset($this->data['cod_amount'])) {
            unset($this->data['cod_amount']);
            return $this;
        }

        $this->data['cod_amount'] = (float) $cod_amount;
        return $this;
    }

    public function setMultiparcel($multiparcel = null)
    {
        if ($multiparcel === null && isset($this->data['multiparcel'])) {
            unset($this->data['multiparcel']);
            return $this;
        }

        $this->data['multiparcel'] = (int) $multiparcel;
        return $this;
    }

    public function setManifestId($manifest_id)
    {
        $this->data['manifest_id'] = (int) $manifest_id;
        return $this;
    }

    public function save()
    {
        return self::saveOrderDataStatic($this->id_order, $this->data, $this->db);
    }

    public static function loadOrderDataStatic($id_order, $db)
    {
        if ((int) $id_order <= 0) {
            return [];
        }

        $result = $db->query("
            SELECT `data`, `manifest_id` FROM `" . DB_PREFIX . "omniva_m_order_data` WHERE `order_id` = '" . (int) $id_order . "
            LIMIT 1'
        ");

        if (!$result->rows) {
            return [];
        }

        $data = json_decode((string) $result->row['data'], true);
        $data['manifest_id'] = $result->row['manifest_id'];

        return $data; //!$result->rows ? [] : json_decode($result->row['data'], true);
    }

    public static function saveOrderDataStatic($id_order, $data, $db)
    {
        if ((int) $id_order <= 0) {
            return false;
        }

        $manifest_id = isset($data['manifest_id']) ? (int) $data['manifest_id'] : null;
        if ($manifest_id === 0) {
            $manifest_id = null;
        }

        unset($data['manifest_id']);
        $json_data = json_encode($data);

        $result = $db->query("
            INSERT INTO `" . DB_PREFIX . "omniva_m_order_data` (`order_id`, `data`, `manifest_id`) 
            VALUES('" . (int) $id_order . "', '" . $json_data . "', '" . $manifest_id . "')
            ON DUPLICATE KEY UPDATE `data`='" . $json_data . "', `manifest_id` = '" . $manifest_id . "'
        ");

        return $result;
    }

    public static function updateManifestId($order_id, $manifest_id, $db)
    {
        if ((int) $order_id <= 0) {
            return false;
        }

        return $db->query("
            INSERT INTO `" . DB_PREFIX . "omniva_m_order_data` (`order_id`, `manifest_id`) 
            VALUES('" . (int) $order_id . "', '" . $manifest_id . "')
            ON DUPLICATE KEY UPDATE `manifest_id` = '" . $manifest_id . "'
        ");
    }

    public static function getNextManifestId($db)
    {
        $result = $db->query("
            SELECT MAX(manifest_id) as current_manifest_id FROM `" . DB_PREFIX . "omniva_m_order_data` LIMIT 1;
        ");

        if (!$result->rows) {
            return 1;
        }

        return (int) $result->row['current_manifest_id'] + 1;
    }
}

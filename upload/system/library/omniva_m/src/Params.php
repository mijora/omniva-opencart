<?php

namespace Mijora\OmnivaOpencart;

class Params
{
    const VERSION = '2.2.8';

    const PREFIX = 'omniva_m_';

    const LOCATIONS_URL = 'https://www.omniva.ee/locationsfull.json';

    const GIT_VERSION_CHECK = 'https://api.github.com/repos/mijora/omniva-opencart/releases/latest';
    const GIT_URL = 'https://github.com/mijora/omniva-opencart/releases/latest';
    const GIT_CHECK_EVERY_HOURS = 24; // how often to check git for version. Default 24h

    const TERMINAL_MAX_WEIGHT = 25; // max allowed terminal weight in kg

    const COURIER_CALL_HOUR_START = 8; // hour from wich couriers can be called
    const COURIER_CALL_HOUR_END = 19; // hour until wich (not included) couriers can be called

    const DEFAULT_WEIGHT = 1; // by default use 1kg

    const DIR_MAIN = DIR_SYSTEM . 'library/omniva_m/';

    const DIR_EMAIL_TEMPLATES = self::DIR_MAIN . 'email_templates/';

    const DEFAULT_TRACKING_EMAIL_TEMPLATE = 'tracking_email.twig';

    const TERMINAL_LIST_JSON_FILE = DIR_DOWNLOAD . self::PREFIX . 'terminals.json';

    const BASE_MOD_XML = 'omniva_m_base.ocmod.xml';
    const BASE_MOD_XML_SOURCE_DIR = self::DIR_MAIN . 'ocmod/'; // should have subfolders based on oc version
    const BASE_MOD_XML_SYSTEM = DIR_SYSTEM . self::BASE_MOD_XML;
    
    const MOD_SOURCE_DIR_OC_3_0 = '3_0/';
    const MOD_SOURCE_DIR_OC_2_3 = '2_3/';

    const ALLOW_POSTOFFICE = false;

    const SHIPPING_TYPE_COURIER = 1;
    const SHIPPING_TYPE_TERMINAL = 2;

    const SENDOFF_TYPE_COURIER = 1;
    const SENDOFF_TYPE_TERMINAL = 2;
    const SENDOFF_TYPE_SORTING_CENTER = 3;

    const LABEL_PRINT_TYPE_A4 = 1;
    const LABEL_PRINT_TYPE_A6 = 2;

    const MAX_PER_PAGE_HISTORY = 25;

    const CONTRACT_ORIGIN_OTHER = 1;
    const CONTRACT_ORIGIN_ESTONIA = 2;

    const CONTRACT_AVAILABLE_ORIGINS = [
        self::CONTRACT_ORIGIN_OTHER,
        self::CONTRACT_ORIGIN_ESTONIA
    ];

    const SERVICE_COURIER_FINLAND = 1;
    const SERVICE_COURIER_ESTONIA = 2;

}

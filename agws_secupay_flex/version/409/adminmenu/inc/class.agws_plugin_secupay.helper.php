<?php
/**
 * secupay Payment Module
 *
 * Copyright (c) 2018 secupay AG
 *
 * @category  Payment
 * @author    secupay AG
 * @copyright 2018, secupay AG
 *
 * Description:
 * JTL Plugin for integration of secupay AG payment services
 */

/**
 * AGWS helper class for Plugins
 *
 */

/**
 * Class agwsPluginAdminHelper
 */

if (!class_exists('agwsPluginHelperSecupay')) {
    /**
     * Class agwsPluginHelperSecupay
     */
    class agwsPluginHelperSecupay
    {
        /**
         * @var null|agwsPluginHelperSecupay
         */
        private static $_instance = null;

        /**
         * @var null|bool
         */
        private static $_isShop4 = null;

        /**
         * @var null|NiceDB
         */
        private $db = null;

        /**
         * @var null|Plugin
         */
        private $plugin = null;


        /**
         * constructor
         *
         * @param Plugin $oPlugin
         */
        public function __construct(Plugin $oPlugin)
        {
            $this->plugin = $oPlugin;
            //get database instance - do not do this, use Shop::DB()/$GLOBALS['DB'] instead
            if (self::isShop4()) {
                $this->db = Shop::DB();
            } else {
                $this->db = $GLOBALS['DB'];
            }
        }

        /**
         * singleton getter
         *
         * @param Plugin $oPlugin
         *
         * @return agwsPluginHelperSecupay
         */
        public static function getInstance(Plugin $oPlugin)
        {
            return (self::$_instance === null) ? new self($oPlugin) : self::$_instance;
        }


        /**
         * check if there is a current shop version installed
         *
         * @return bool
         */
        public static function isShop4()
        {
            if (self::$_isShop4 === null) {
                //cache the actual value as class variable
                self::$_isShop4 = version_compare(JTL_VERSION, 400, '>=') && class_exists('Shop');
            }

            return self::$_isShop4;
        }

        /**
         * @param     $cString
         * @param int $nSuche
         *
         * @return mixed
         */
        public static function filter__XSS($cString, $nSuche = 0)
        {
            if (self::$_isShop4 === true) {
                return StringHandler::filterXSS($cString, $nSuche);
            } else {
                return filterXSS($cString, $nSuche);
            }
        }

        /**
         * @param bool $bForceSSL
         *
         * @return mixed
         */
        public static function gibShop__URL($bForceSSL = false)
        {
            if (self::$_isShop4 === true) {
                return Shop::getURL($bForceSSL);
            } else {
                return gibShopURL($bForceSSL);
            }
        }

        /**
         * @return mixed
         */
        public static function gibSeiten__Typ()
        {
            if (self::$_isShop4 === true) {
                return Shop::getPageType();
            } else {
                return gibSeitenTyp();
            }
        }

        /**
         * @param $sprachValue
         * @param $sprachSektion
         *
         * @return mixed
         */
        public static function gib__Wert($sprachValue, $sprachSektion)
        {
            if (self::$_isShop4 === true) {
                return Shop::Lang()
                           ->get($sprachValue, $sprachSektion);
            } else {
                return $GLOBALS['oSprache']->gibWert($sprachValue, $sprachSektion);
            }
        }
    }
}

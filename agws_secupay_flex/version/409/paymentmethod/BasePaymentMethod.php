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

include_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php");
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.ZahlungsLog.php");
require_once(PFAD_ROOT . PFAD_INCLUDES . "bestellabschluss_inc.php");
require_once(PFAD_ROOT . PFAD_SMARTY . "Smarty.class.php");
require_once $oPlugin->cAdminmenuPfad . 'inc/class.agws_plugin_secupay.helper.php';

/**
 * Class BasePaymentMethod
 */
class BasePaymentMethod extends PaymentMethod
{
    /**
     * @var agwsPluginHelperSecupay
     */
    protected $secupayHelper;
    /**
     * agws_secupay_flex_ls constructor.
     *
     * @param     $moduleID
     * @param int $nAgainCheckout
     */
    public function __construct($moduleID, $nAgainCheckout = 0)
    {
        $this->moduleID = $moduleID;
        $this->loadSettings();
        $this->init($nAgainCheckout);
        $this->oPlugin       = Plugin::getPluginById('agws_secupay_flex');
        $this->secupayHelper = agwsPluginHelperSecupay::getInstance($this->oPlugin);
    }
    /**
     * @param int $nAgainCheckout
     */
    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);
    }
    /**
     * @return mixed
     */
    public function agws_secupay_flex_getApikey()
    {
        return $this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_vertragsid'];
    }

    /**
     * @return string
     */
    public function agws_secupay_flex_getTitle()
    {
        switch ($_SESSION['Kunde']->cAnrede) {
            case "m":
                $agws_secupay_flex_anrede = $this->secupayHelper->gib__Wert('salutationM', 'global');
                break;

            case "w":
                $agws_secupay_flex_anrede = $this->secupayHelper->gib__Wert('salutationW', 'global');
                break;
        }

        return $agws_secupay_flex_anrede;
    }
    /**
     * @return string
     */
    public function agws_secupay_flex_getCurrency()
    {
        $agws_secupay_flex_waehrung = "EUR";

        return $agws_secupay_flex_waehrung;
    }
    /**
     * @param string $action
     *
     * @return string
     */
    public function agws_secupay_flex_getCurlLink($action = '')
    {
        $agws_secupay_flex_curlopt_url = "";

        switch ($this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_apiurl']) {
            case "1": //Live
                $agws_secupay_flex_curlopt_url = 'https://api.secupay.ag/payment/' . $action;
                break;

            case "2": //Dist
                $agws_secupay_flex_curlopt_url = 'https://api-dist.secupay-ag.de/payment/' . $action;
                break;
        }

        return $agws_secupay_flex_curlopt_url;
    }
    /**
     * @param $order
     *
     * @return array
     */
    public function agws_secupay_flex_getBasket($order)
    {
        $order_pos_arr            = $order->Positionen;
        $agws_secupay_flex_basket = [];
        $ctr                      = 0;

        foreach ($order_pos_arr as $order_pos) {
            if ($order_pos->nPosTyp == 1) {
                $agws_secupay_flex_basket[$ctr]                 = new StdClass();
                $agws_secupay_flex_basket[$ctr]->article_number = utf8_encode($order_pos->Artikel->cArtNr);
                $agws_secupay_flex_basket[$ctr]->name           = utf8_encode($order_pos->Artikel->cName);
                $agws_secupay_flex_basket[$ctr]->model          = utf8_encode($order_pos->Artikel->cHAN);
                $agws_secupay_flex_basket[$ctr]->ean            = utf8_encode($order_pos->Artikel->cBarcode);
                $agws_secupay_flex_basket[$ctr]->quantity       = utf8_encode($order_pos->nAnzahl);
                $agws_secupay_flex_basket[$ctr]->price          = utf8_encode(
                    round(($order_pos->fPreis + ($order_pos->fPreis / 100 * $order_pos->fMwSt)), 2) * 100
                );
                $agws_secupay_flex_basket[$ctr]->total          = utf8_encode(
                    round(
                        round(
                            ($order_pos->fPreis + ($order_pos->fPreis / 100 * $order_pos->fMwSt)) * $order_pos->nAnzahl,
                            4
                        ) * 100
                    )
                );
                $agws_secupay_flex_basket[$ctr]->tax            = utf8_encode($order_pos->fMwSt);

                $ctr++;
            }
        }

        return $agws_secupay_flex_basket;
    }
    /**
     * @return stdClass
     */
    public function agws_secupay_flex_getUserfields()
    {
        $agws_secupay_flex_userfields = new stdClass();

        if ($this->agws_secupay_flex_getLanguage() == "de_DE") {
            $agws_secupay_flex_userfields->userfield_1 = utf8_encode('Bestellung vom ' . date("d.m.Y"));
            $agws_secupay_flex_userfields->userfield_2 = utf8_encode('bei ' . $this->agws_secupay_flex_getShopname());
            $agws_secupay_flex_userfields->userfield_3 = utf8_encode('');
        } else {
            $agws_secupay_flex_userfields->userfield_1 = utf8_encode('Order from ' . date("Y-m-d"));
            $agws_secupay_flex_userfields->userfield_2 = utf8_encode('by ' . $this->agws_secupay_flex_getShopname());
            $agws_secupay_flex_userfields->userfield_3 = utf8_encode('');
        }

        return $agws_secupay_flex_userfields;
    }
    /**
     * @return string
     */
    public function agws_secupay_flex_getLanguage()
    {
        switch ($_SESSION['cISOSprache']) {
            case "ger":
                $agws_secupay_flex_sprache = "de_DE";
                break;

            default:
                $agws_secupay_flex_sprache = "en_US";
                break;
        }

        return $agws_secupay_flex_sprache;
    }
    /**
     * @return string
     */
    public function agws_secupay_flex_getShopname()
    {
        global $Einstellungen;

        if (strlen($this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_shopname']) > 1) {
            $agws_secupay_flex_shop_name = substr(
                $this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_shopname'],
                0,
                48
            );
        } else {
            $agws_secupay_flex_shop_name = substr($Einstellungen['global']['global_shopname'], 0, 48);
        }

        return $agws_secupay_flex_shop_name;
    }
    /**
     * @return string
     */
    public function agws_secupay_flex_getPurpose()
    {
        if ($this->agws_secupay_flex_getLanguage() == "de_DE") {
            $agws_secupay_flex_purpose = utf8_encode(
                'Bestellung vom ' . date("d.m.Y") . ' bei ' . $this->agws_secupay_flex_getShopname()
            );
        } else {
            $agws_secupay_flex_purpose = utf8_encode(
                'Order from ' . date("Y-m-d") . ' by ' . $this->agws_secupay_flex_getShopname()
            );
        }

        return $agws_secupay_flex_purpose;
    }
    /**
     * @param $order
     *
     * @return stdClass
     */
    public function agws_secupay_flex_getDeliveryAddress($order)
    {
        $agws_secupay_flex_deliveryaddress = new stdClass();

        $agws_secupay_flex_deliveryaddress->firstname   = utf8_encode(
            html_entity_decode($order->Lieferadresse->cVorname, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->lastname    = utf8_encode(
            html_entity_decode($order->Lieferadresse->cNachname, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->company     = utf8_encode(
            html_entity_decode($order->Lieferadresse->cFirma, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->street      = utf8_encode(
            html_entity_decode($order->Lieferadresse->cStrasse, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->housenumber = utf8_encode(
            html_entity_decode($order->Lieferadresse->cHausnummer, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->zip         = utf8_encode(
            html_entity_decode($order->Lieferadresse->cPLZ, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->city        = utf8_encode(
            html_entity_decode($order->Lieferadresse->cOrt, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );
        $agws_secupay_flex_deliveryaddress->country     = utf8_encode(
            html_entity_decode($order->Lieferadresse->cLand, ENT_COMPAT | ENT_HTML401, "ISO8859-1")
        );

        return $agws_secupay_flex_deliveryaddress;
    }
    /**
     * @param $strlen_data
     *
     * @return array
     */
    public function agws_secupay_flex_getHttpHeader($strlen_data)
    {
        $agws_secupay_flex_httpheader = [
            'Accept-Language: ' . $this->agws_secupay_flex_getLanguage(),
            'Accept: application/json',
            'Content-type: application/json; charset=utf-8;',
            'User-Agent: ' . $this->agws_secupay_flex_getUserAgent(),
            'Content-Length: ' . $strlen_data
        ];

        return $agws_secupay_flex_httpheader;
    }
    /**
     * @return string
     */
    public function agws_secupay_flex_getUserAgent()
    {
        $this->secupayHelper->isShop4() ? $agws_secupay_flex_useragent = 'JTL4-client V' . $this->oPlugin->nVersion
            : $agws_secupay_flex_useragent = 'JTL3-client V' . $this->oPlugin->nVersion;

        return $agws_secupay_flex_useragent;
    }
    /**
     * @param string $hash
     *
     * @return string
     */
    public function getNotificationURL($hash)
    {
        $shop_notify = ($this->secupayHelper->isShop4()) ? 'secupay_notify4.php?' : 'secupay_notify3.php?';
        $key         = ($this->duringCheckout) ? 'sh' : 'ph';

        return $this->oPlugin->cFrontendPfadURLSSL . $shop_notify . $key . '=' . $hash;
    }

    /**
     * @param array  $order
     * @param string $agws_secupay_flex_paymentHash
     * @param        $args (currently not used)
     *
     * @return bool
     */
    public function finalizeOrder($order, $agws_secupay_flex_paymentHash, $args)
    {
        return $this->verifyNotification($order, $agws_secupay_flex_paymentHash, $args);
    }
}

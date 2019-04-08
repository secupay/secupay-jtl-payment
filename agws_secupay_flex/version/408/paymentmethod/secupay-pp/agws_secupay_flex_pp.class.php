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

include_once(PFAD_ROOT.PFAD_INCLUDES_MODULES.'PaymentMethod.class.php');
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php");
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.ZahlungsLog.php");
require_once(PFAD_ROOT . PFAD_INCLUDES . "bestellabschluss_inc.php");
require_once(PFAD_ROOT . PFAD_SMARTY . "Smarty.class.php");
require_once $oPlugin->cAdminmenuPfad . 'inc/class.agws_plugin_secupay.helper.php';

/**
 * Class agws_secupay_flex_pp
 */
class agws_secupay_flex_pp extends PaymentMethod
{
    /**
     * agws_secupay_flex_ls constructor.
     * @param $moduleID
     * @param int $nAgainCheckout
     */
    public function __construct($moduleID, $nAgainCheckout = 0)
    {
        $this->moduleID = $moduleID;
        $this->loadSettings();
        $this->init($nAgainCheckout);
        $this->oPlugin = Plugin::getPluginById('agws_secupay_flex');
        $this->secupayHelper = agwsPluginHelperSecupay::getInstance($this->oPlugin);
    }

    /**
     * @param int $nAgainCheckout
     */
    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);
        $this->name = 'agws-secupay-pp';
        $this->caption = 'agws-secupay-pp';
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
    public function agws_secupay_flex_getShopname()
    {
        global $Einstellungen;

        if (strlen($this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_shopname']) > 1) {
            $agws_secupay_flex_shop_name = substr($this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_shopname'], 0, 48);
        } else {
            $agws_secupay_flex_shop_name = substr($Einstellungen['global']['global_shopname'], 0, 48);
        }

        return $agws_secupay_flex_shop_name;
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
    public function agws_secupay_flex_getCurrency()
    {
        $agws_secupay_flex_waehrung = "EUR";
    
        return $agws_secupay_flex_waehrung;
    }

    /**
     * @param string $action
     * @return string
     */
    public function agws_secupay_flex_getCurlLink($action='')
    {
        $agws_secupay_flex_curlopt_url ="";
            
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
     * @return string
     */
    public function agws_secupay_flex_getUserAgent()
    {
        ($this->secupayHelper->isShop4()) ?
            $agws_secupay_flex_useragent = 'JTL4-client V'.$this->oPlugin->nVersion:
            $agws_secupay_flex_useragent = 'JTL3-client V'.$this->oPlugin->nVersion;

        return $agws_secupay_flex_useragent;
    }
    
    /**
     * @param $order
     * @return array
     */
    public function agws_secupay_flex_getBasket($order)
    {
        $order_pos_arr = $order->Positionen;
        $agws_secupay_flex_basket = array();
        $ctr = 0;
        
        foreach ($order_pos_arr as $order_pos) {
            if ($order_pos->nPosTyp==1) {
                $agws_secupay_flex_basket[$ctr] = new StdClass;
                $agws_secupay_flex_basket[$ctr]->article_number = utf8_encode($order_pos->Artikel->cArtNr);
                $agws_secupay_flex_basket[$ctr]->name = utf8_encode($order_pos->Artikel->cName);
                $agws_secupay_flex_basket[$ctr]->model = utf8_encode($order_pos->Artikel->cHAN);
                $agws_secupay_flex_basket[$ctr]->ean = utf8_encode($order_pos->Artikel->cBarcode);
                $agws_secupay_flex_basket[$ctr]->quantity = utf8_encode($order_pos->nAnzahl);
                $agws_secupay_flex_basket[$ctr]->price = utf8_encode(round(($order_pos->fPreis + ($order_pos->fPreis / 100 * $order_pos->fMwSt)), 2) * 100);
                $agws_secupay_flex_basket[$ctr]->total = utf8_encode(round(round(($order_pos->fPreis + ($order_pos->fPreis / 100 * $order_pos->fMwSt)) * $order_pos->nAnzahl, 4)  * 100));
                $agws_secupay_flex_basket[$ctr]->tax = utf8_encode($order_pos->fMwSt);
                
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
        $agws_secupay_flex_userfields = new stdClass;
        
        if ($this->agws_secupay_flex_getLanguage() == "de_DE") {
            $agws_secupay_flex_userfields->userfield_1 = utf8_encode('Bestellung vom '.date("d.m.Y"));
            $agws_secupay_flex_userfields->userfield_2 = utf8_encode('bei '. $this->agws_secupay_flex_getShopname());
            $agws_secupay_flex_userfields->userfield_3 = utf8_encode('');
        } else {
            $agws_secupay_flex_userfields->userfield_1 = utf8_encode('Order from '.date("Y-m-d"));
            $agws_secupay_flex_userfields->userfield_2 = utf8_encode('by '. $this->agws_secupay_flex_getShopname());
            $agws_secupay_flex_userfields->userfield_3 = utf8_encode('');
        }
        
        return $agws_secupay_flex_userfields;
    }

    /**
     * @return string
     */
    public function agws_secupay_flex_getPurpose()
    {
        if ($this->agws_secupay_flex_getLanguage() == "de_DE") {
            $agws_secupay_flex_purpose = utf8_encode('Bestellung vom '.date("d.m.Y").' bei '. $this->agws_secupay_flex_getShopname());
        } else {
            $agws_secupay_flex_purpose = utf8_encode('Order from '.date("Y-m-d").' by '. $this->agws_secupay_flex_getShopname());
        }
        
        return $agws_secupay_flex_purpose;
    }

    /**
     * @param $order
     * @return stdClass
     */
    public function agws_secupay_flex_getDeliveryAddress($order)
    {
        $agws_secupay_flex_deliveryaddress = new stdClass;
        
        $agws_secupay_flex_deliveryaddress->firstname = utf8_encode(html_entity_decode($order->Lieferadresse->cVorname, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->lastname = utf8_encode(html_entity_decode($order->Lieferadresse->cNachname, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->company = utf8_encode(html_entity_decode($order->Lieferadresse->cFirma, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->street = utf8_encode(html_entity_decode($order->Lieferadresse->cStrasse, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->housenumber = utf8_encode(html_entity_decode($order->Lieferadresse->cHausnummer, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->zip = utf8_encode(html_entity_decode($order->Lieferadresse->cPLZ, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->city = utf8_encode(html_entity_decode($order->Lieferadresse->cOrt, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        $agws_secupay_flex_deliveryaddress->country = utf8_encode(html_entity_decode($order->Lieferadresse->cLand, ENT_COMPAT | ENT_HTML401, "ISO8859-1"));
        
        return $agws_secupay_flex_deliveryaddress;
    }

    /**
     * @param $strlen_data
     * @return array
     */
    public function agws_secupay_flex_getHttpHeader($strlen_data)
    {
        $agws_secupay_flex_httpheader = array(
            'Accept-Language: '.$this->agws_secupay_flex_getLanguage(),
            'Accept: application/json',
            'Content-type: application/json; charset=utf-8;',
            'User-Agent: '.$this->agws_secupay_flex_getUserAgent(),
            'Content-Length: ' . $strlen_data
       );
       
        return $agws_secupay_flex_httpheader;
    }
    
    /**
     * @param $agws_linkaction
     * @param $agws_httpheader
     * @param null $agws_data
     * @return mixed|string
     */
    public function agws_secupay_flex_getCurlContent($agws_linkaction, $agws_httpheader, $agws_data = null)
    {
        $this->secupayHelper->isShop4()
            ? $agws_url_shop = Shop::getURL()
            : $agws_url_shop = URL_SHOP;

        $agws_secupay_flex_curlcontent = null;

        if (function_exists('curl_init')) {
            $agws_secupay_flex_ch = curl_init();
            curl_setopt($agws_secupay_flex_ch, CURLOPT_URL, $this->agws_secupay_flex_getCurlLink($agws_linkaction));
            curl_setopt($agws_secupay_flex_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_HTTPHEADER, $agws_httpheader);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_REFERER, $agws_url_shop);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_POST, true);
            curl_setopt($agws_secupay_flex_ch, CURLOPT_POSTFIELDS, $agws_data);

            $agws_secupay_flex_curlcontent = curl_exec($agws_secupay_flex_ch);
            
            $info = curl_getinfo($agws_secupay_flex_ch);

            $agws_secupay_flex_logtext =
                'secupay_flex - PP - function getCurlContent <br />
						Ergebnis cURL <br />' . print_r($info, true).' <br/>
						======================';
            Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_DEBUG);

            curl_close($agws_secupay_flex_ch);
        }
        return $agws_secupay_flex_curlcontent;
    }
    
    /**
     * @param null $agws_logbase
     * @param null $agws_logstatus
     * @param null $agws_loglevel
     * @param null $agws_function
     * @param null $agws_data
     * @param null $agws_json
     * @param null $agws_curl
     * @param null $agws_misc
     * @return bool
     */
    public function agws_secupay_flex_doLoggingCurl($agws_logbase=null, $agws_logstatus=null, $agws_loglevel=null, $agws_function=null, $agws_data=null, $agws_json=null, $agws_misc=null)
    {
        $agws_secupay_flex_logtext =
        'secupay_flex - PP - function '.$agws_function.' <br />'.
        $agws_logbase.'-'.$agws_logstatus.' <br />
		gesendete Daten: <br />'.
        print_r($agws_data, true).' <br/>
		---------------------- <br />
		Antwort secupay: <br />'.
        print_r($agws_json, true).' <br/>
		---------------------- <br />
		sonstige Daten: <br />'.
        print_r($agws_misc, true).' <br/>
		======================<br />';
                
        Jtllog::writeLog($agws_secupay_flex_logtext, $agws_loglevel);
        ZahlungsLog::add($this->cModulId, 'Autorisierung erfolgreich ('.$agws_json->status.')', $agws_secupay_flex_logtext, $agws_loglevel);
        
        return true;
    }
    
    /**
     * @param $kBestellung
     */
    public function agws_secupay_flex_sendConfirmationMail($kBestellung, $agws_secupay_flex_pp_due_text, $agws_secupay_flex_recipient_legal, $agws_secupay_flex_accountowner, $agws_secupay_flex_accountnumber, $agws_secupay_flex_bankcode, $agws_secupay_flex_bankname, $agws_secupay_flex_iban, $agws_secupay_flex_bic, $agws_secupay_flex_purpose, $agws_secupay_flex_payment_link, $agws_secupay_flex_payment_qr_image_url)
    {
        $oOrder = new Bestellung($kBestellung);
        $oOrder->fuelleBestellung(0);

        $oCustomer = new Kunde($oOrder->kKunde);
        
        $oSecupay = new stdClass();
        $oSecupay->recipient_legal = $agws_secupay_flex_recipient_legal;
        $oSecupay->accountowner = $agws_secupay_flex_accountowner;
        $oSecupay->ktonr = $agws_secupay_flex_accountnumber;
        $oSecupay->blz = $agws_secupay_flex_bankcode;
        $oSecupay->bank = $agws_secupay_flex_bankname;
        $oSecupay->iban = $agws_secupay_flex_iban;
        $oSecupay->bic = $agws_secupay_flex_bic;
        $oSecupay->zweck = $agws_secupay_flex_purpose;
        $oSecupay->urllink = $agws_secupay_flex_payment_link;
        $oSecupay->faelligkeit = $agws_secupay_flex_pp_due_text;
        
        $oMail = new StdClass;
        $oMail->tkunde = $oCustomer;
        $oMail->tbestellung = $oOrder;
        $oMail->tsecupay = $oSecupay;
        
        $nType = "kPlugin_".$this->oPlugin->kPlugin."_agwssecupayflex";
        
        sendeMail($nType, $oMail);
    }

    /**
     * @param string $hash
     * @param $url_method
     * @return string
     */
    public function getNotificationURL($hash)
    {
        $shop_notify = ($this->secupayHelper->isShop4())?'secupay_notify4.php?':'secupay_notify3.php?';
        $key = ($this->duringCheckout) ? 'sh' : 'ph';

        return $this->oPlugin->cFrontendPfadURLSSL . $shop_notify . $key . '=' . $hash;
    }

    /**
     * @param Bestellung $order
     */
    public function preparePaymentProcess($order)
    {
        if ($this->secupayHelper->isShop4()) {
            $smarty = Shop::Smarty();
        } else {
            global $smarty;
        }

        $agws_secupay_flex_paymentHash = $this->generateHash($order);
        $agws_secupay_flex_amount = round((round($order->fGesamtsumme, 2)*100));
        $agws_secupay_flex_customer = $_SESSION['Kunde'];
        $agws_secupay_flex_failURL = $this->secupayHelper->gibShop__URL() . '/bestellvorgang.php?editZahlungsart=1&agws_url=fail';
        
        $agws_secupay_flex_daten = [
            'data'=> [
                'apikey' => $this->agws_secupay_flex_getApikey(),
                'payment_type' => 'prepay',
                'demo'=> $this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_bmodus'],
                'url_success' => $this->getNotificationURL($agws_secupay_flex_paymentHash).'&agws_sp_url=succ',
                'url_failure' => $agws_secupay_flex_failURL,
                'url_push' => $this->getNotificationURL($agws_secupay_flex_paymentHash).'&agws_sp_url=push',
                'language' => $this->agws_secupay_flex_getLanguage(),
                'shop'=> ($this->secupayHelper->isShop4())?'JTL-Shop4':'JTL-Shop3',
                'shopversion' => JTL_VERSION,
                'modulversion' => $this->oPlugin->nVersion,
                'title' => utf8_encode(html_entity_decode($this->agws_secupay_flex_getTitle())),
                'firstname' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cVorname, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'lastname' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cNachname, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'street' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cStrasse, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'housenumber' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cHausnummer, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'zip' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cPLZ, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'city' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cOrt, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'country' => utf8_encode(html_entity_decode($agws_secupay_flex_customer->cLand, ENT_COMPAT | ENT_HTML401, "ISO8859-1")),
                'telephone' => $agws_secupay_flex_customer->cTel,
                'dob_value' => $agws_secupay_flex_customer->dGeburtstag,
                'email' => $agws_secupay_flex_customer->cMail,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'amount' => $agws_secupay_flex_amount,
                'currency' => $this->agws_secupay_flex_getCurrency(),
                'purpose' => $this->agws_secupay_flex_getPurpose(),
                'basket' => json_encode($this->agws_secupay_flex_getBasket($order)),
                'userfields' => json_encode($this->agws_secupay_flex_getUserfields()),
                'delivery_address' => $this->agws_secupay_flex_getDeliveryAddress($order),
                'order_id' =>'',
                'note' =>'',
                'module_config'=>$this->oPlugin->oPluginEinstellungAssoc_arr,
                'apiversion' => '2.3.14'
            ]
        ];
        
        $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);
        $agws_secupay_flex_http_header = $this->agws_secupay_flex_getHttpHeader(strlen($agws_secupay_flex_data));
        $agws_secupay_flex_antwort = $this->agws_secupay_flex_getCurlContent('init', $agws_secupay_flex_http_header, $agws_secupay_flex_data);
        $agws_secupay_flex_antwort_json = json_decode($agws_secupay_flex_antwort);

        if ($agws_secupay_flex_antwort_json->status == "ok" && isset($agws_secupay_flex_antwort_json->data->iframe_url) && isset($agws_secupay_flex_antwort_json->data->hash)) {
            //Logging
            $this->agws_secupay_flex_doLoggingCurl('Autorisierung', 'erfolgreich', JTLLOG_LEVEL_NOTICE, 'preparePaymentProcess', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);

            $agws_secupay_flex_iframe_tmp = $agws_secupay_flex_antwort_json->data->iframe_url;
            $_SESSION['agws_secupay_flex_hash_tmp'] = $agws_secupay_flex_antwort_json->data->hash;
        
            //direkte Statusabfrage zur Verifizierung
            $agws_secupay_flex_daten = [
                'data'=> [
                    'apikey' => $this->agws_secupay_flex_getApikey(),
                    'hash' => $agws_secupay_flex_antwort_json->data->hash
                ]
            ];

            $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);
            $agws_secupay_flex_http_header = $this->agws_secupay_flex_getHttpHeader(strlen($agws_secupay_flex_data));
            $agws_secupay_flex_antwort = $this->agws_secupay_flex_getCurlContent('status', $agws_secupay_flex_http_header, $agws_secupay_flex_data);
            $agws_secupay_flex_antwort_json = json_decode($agws_secupay_flex_antwort);
           
            if ($agws_secupay_flex_antwort_json->status == "ok" && isset($agws_secupay_flex_antwort_json->data->hash) && $agws_secupay_flex_antwort_json->data->hash == $_SESSION['agws_secupay_flex_hash_tmp']) {
                //Logging + smarty
                $this->agws_secupay_flex_doLoggingCurl('Status', 'erfolgreich', JTLLOG_LEVEL_NOTICE, 'preparePaymentProcess', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);
                $_SESSION['agws_secupay_flex_order_comment'] = $_POST['kommentar'];
                $smarty->assign('agws_secupay_flex_iframe', $agws_secupay_flex_iframe_tmp);
            } else {
                //Logging + Redirect
                $this->agws_secupay_flex_doLoggingCurl('Status', 'fehlerhaft', JTLLOG_LEVEL_ERROR, 'preparePaymentProcess', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);
                header("location: bestellvorgang.php?editZahlungsart=1&AGWS_SECUPAY_ZA=PP&AGWS_SECUPAY_ERRORCODE=".$agws_secupay_flex_antwort_json->status);
            }
        } else {
            //Logging + Redirect
            $this->agws_secupay_flex_doLoggingCurl('Autorisierung', 'fehlerhaft', JTLLOG_LEVEL_ERROR, 'preparePaymentProcess', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);
            header("location: bestellvorgang.php?editZahlungsart=1&AGWS_SECUPAY_ZA=PP&AGWS_SECUPAY_ERRORCODE=".$agws_secupay_flex_antwort_json->status);
        }
    }
    /**
     * @param array $order
     * @param string $agws_secupay_flex_paymentHash
     * @param $args
     */
    public function handleNotification($order, $agws_secupay_flex_paymentHash, $args)
    {
        if ($this->secupayHelper->isShop4()) {
            $smarty = Shop::Smarty();
        } else {
            $smarty = new Smarty;
            $smarty->caching = 0;
            $smarty->compile_dir = PFAD_ROOT.PFAD_COMPILEDIR;
        }

        if (isset($_REQUEST['sh']) && isset($_REQUEST['agws_sp_url']) && $_REQUEST['agws_sp_url']=='succ') {
            if ($this->verifyNotification($order, $agws_secupay_flex_paymentHash, $args)) {
                $agws_secupay_flex_Kommentar_Bestellung = "";
                $agws_secupay_flex_Kommentar_abwLA = "";
                
                if (isset($_SESSION['agws_secupay_flex_order_comment'])) {
                    $agws_secupay_flex_Kommentar_Bestellung = $_SESSION['agws_secupay_flex_order_comment'];
                }
                    
                if ($_SESSION['agws_secupay_flex_abwLA_flag'] == 1) {
                    $smarty->assign('agws_secupay_flex_abwLA_titel', $this->oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_abwLA_titel']);
                    $smarty->assign('agws_secupay_flex_abwLA_text', $this->oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_abwLA_text']);
                    $agws_secupay_flex_Kommentar_abwLA = $smarty->fetch($this->oPlugin->cFrontendPfad . 'template/agws_secupay_flex_abwLA_text.tpl');
                }
                
                $agws_secupay_flex_Kommentar = $agws_secupay_flex_Kommentar_Bestellung;
                $agws_secupay_flex_Kommentar .= $agws_secupay_flex_Kommentar_abwLA;

                unset($_SESSION['agws_secupay_flex_order_comment']);
                unset($_SESSION['agws_secupay_flex_abwLA_session']);

                $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "UPDATE tbestellung SET cAbgeholt='Y' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    )
                    : $GLOBALS["DB"]->executeQuery(
                    "UPDATE tbestellung SET cAbgeholt='Y' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    );

                $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery("UPDATE tbestellung SET cKommentar = '" . $agws_secupay_flex_Kommentar . "' WHERE kBestellung='".$order->kBestellung."'", 4)
                    : $GLOBALS['DB']->executeQuery("UPDATE tbestellung SET cKommentar = '" . $agws_secupay_flex_Kommentar . "' WHERE kBestellung='".$order->kBestellung."'", 4);

                $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "INSERT INTO xplugin_agws_secupay_flex_tsyslog
					    (kBestellung, cHash, dSuccDat, cSecupayZA) VALUES
					    ('".Shop::DB()->realEscape($order->kBestellung)."', '".Shop::DB()->realEscape($_REQUEST['hash'])."',NOW(),'prepay')",
                        10
                    )
                    : $GLOBALS["DB"]->executeQuery(
                    "INSERT INTO xplugin_agws_secupay_flex_tsyslog
					(kBestellung, cHash, dSuccDat, cSecupayZA) VALUES
					('".$GLOBALS["DB"]->realEscape($order->kBestellung)."', '".$GLOBALS["DB"]->realEscape($_REQUEST['hash'])."', NOW(),'prepay')",
                        10
                    );

                header("Location: " . $this->getReturnURL($order));
                exit();
            }
        }

        if (isset($_REQUEST['sh']) && isset($_REQUEST['agws_sp_url']) && $_REQUEST['agws_sp_url']=='push') {
            //direkte Statusabfrage Verifizierung/TACode/Amount
            $agws_secupay_flex_daten = [
                'data'=>[
                    'apikey' => $this->agws_secupay_flex_getApikey(),
                    'hash' => $_REQUEST['hash']
                ]
            ];

            $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);
            $agws_secupay_flex_http_header = $this->agws_secupay_flex_getHttpHeader(strlen($agws_secupay_flex_data));
            $agws_secupay_flex_antwort = $this->agws_secupay_flex_getCurlContent('status', $agws_secupay_flex_http_header, $agws_secupay_flex_data);
            $agws_secupay_flex_antwort_json = json_decode($agws_secupay_flex_antwort);
        
            if ($this->verifyNotification($order, $agws_secupay_flex_paymentHash, $args, $agws_secupay_flex_antwort_json)) {
                $agws_secupay_flex_bank_zweck = $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->purpose;
                $agws_secupay_flex_bank_url = $agws_secupay_flex_antwort_json->data->opt->payment_link;
                $agws_secupay_flex_bank_qrimg = $agws_secupay_flex_antwort_json->data->opt->payment_qr_image_url;

                switch ((int)$this->oPlugin->oPluginEinstellungAssoc_arr[$this->cModulId . "_agws_secupay_flex_pp_due_design"]) {
                    case 0:
                        //kein Fälligkeitshinweis
                        $agws_secupay_flex_pp_due_text = "";
                        break;
                        
                    case 1:
                        //Fällig X Tage nach Lieferung
                        $agws_secupay_flex_pp_due_text = $this->oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_prepay_due_text_days'];
                        $agws_secupay_flex_pp_due_days = $this->oPlugin->oPluginEinstellungAssoc_arr[$this->cModulId . "_agws_secupay_flex_due_days_pp"];
                        $agws_secupay_flex_pp_due_text = str_replace('|t|', $agws_secupay_flex_pp_due_days, $agws_secupay_flex_pp_due_text);
                        break;
                        
                    case 2:
                        //Fälligkeitsdatum berechnet
                        $agws_secupay_flex_pp_due_text = $this->oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_prepay_due_text'];
                        break;
                }

                $smarty->assign('agws_secupay_flex_bank_ktonr', $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->accountnumber);
                $smarty->assign('agws_secupay_flex_bank_blz', $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bankcode);
                $smarty->assign('agws_secupay_flex_bank_bank', utf8_decode($agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bankname));
                $smarty->assign('agws_secupay_flex_bank_iban', $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->iban);
                $smarty->assign('agws_secupay_flex_bank_bic', $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bic);
                $smarty->assign('agws_secupay_flex_pp_due_design', $this->oPlugin->oPluginEinstellungAssoc_arr[$this->cModulId . "_agws_secupay_flex_pp_due_design"]);
                $smarty->assign('agws_secupay_flex_pp_due_text', $agws_secupay_flex_pp_due_text);
                $smarty->assign('agws_secupay_flex_bank_zweck', $agws_secupay_flex_bank_zweck);
                $smarty->assign('agws_secupay_flex_bank_url', $agws_secupay_flex_bank_url);
                $smarty->assign('agws_secupay_flex_bank_recipient_legal', utf8_decode($agws_secupay_flex_antwort_json->data->opt->recipient_legal));
                $smarty->assign('agws_secupay_flex_bank_accountowner', $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->accountowner);

                $agws_secupay_flex_Kommentar_BankVerbindung = $smarty->fetch($this->oPlugin->cFrontendPfad . 'template/agws_secupay_flex_bank_text_'.$_SESSION['cISOSprache'].'.tpl');

                $this->secupayHelper->isShop4()
                    ? $oResTmp = Shop::DB()->executeQuery(
                    "SELECT cKommentar FROM tbestellung WHERE kBestellung='".$order->kBestellung."'",
                        8
                    )
                    : $oResTmp = $GLOBALS['DB']->executeQuery(
                    "SELECT cKommentar FROM tbestellung WHERE kBestellung='".$order->kBestellung."'",
                        8
                    );

                $agws_secupay_flex_Kommentar = $oResTmp['cKommentar'];
                $agws_secupay_flex_Kommentar .= $agws_secupay_flex_Kommentar_BankVerbindung;
                if ($this->oPlugin->oPluginEinstellungAssoc_arr[$this->cModulId . "_agws_secupay_flex_pp_comment"] == 2) {
                    $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "UPDATE tbestellung SET cPUIZahlungsdaten = '" . $agws_secupay_flex_Kommentar_BankVerbindung . "' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    )
                    : $GLOBALS['DB']->executeQuery(
                    "UPDATE tbestellung SET cPUIZahlungsdaten = '" . $agws_secupay_flex_Kommentar_BankVerbindung . "' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    );
                } else {
                    $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "UPDATE tbestellung SET cKommentar = '" . $agws_secupay_flex_Kommentar . "' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    )
                    : $GLOBALS['DB']->executeQuery(
                    "UPDATE tbestellung SET cKommentar = '" . $agws_secupay_flex_Kommentar . "' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    );
                }
                $x = $this->oPlugin->oPluginZahlungsmethodeAssoc_arr[$this->cModulId]->oZahlungsmethodeSprache_arr;
                foreach ($x as $ZASprache2Name) {
                    if ($ZASprache2Name->cISOSprache == $_SESSION['cISOSprache']) {
                        $agws_secupay_flex_name = $ZASprache2Name->cName;
                    }
                }
                if ($_REQUEST['payment_status'] == 'accepted') {
                    $this->name = $agws_secupay_flex_name;
                    $this->setOrderStatusToPaid($order);
                    $this->agws_secupay_flex_sendConfirmationMail($order->kBestellung, $agws_secupay_flex_pp_due_text, utf8_decode($agws_secupay_flex_antwort_json->data->opt->recipient_legal), $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->accountowner, $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->accountnumber, $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bankcode, utf8_decode($agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bankname), $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->iban, $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->bic, $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->purpose, $agws_secupay_flex_antwort_json->data->opt->payment_link, $agws_secupay_flex_antwort_json->data->opt->payment_qr_image_url);
                    $incomingPayment = new StdClass;
                    $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
                    $incomingPayment->cISO = $order->Waehrung->cISO;
                    $incomingPayment->cHinweis = $agws_secupay_flex_antwort_json->data->opt->transfer_payment_data->purpose;
                    $this->addIncomingPayment($order, $incomingPayment);
                    $this->updateNotificationID($order->kBestellung, $agws_secupay_flex_paymentHash);
                }
                $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "UPDATE tbestellung SET cAbgeholt='N' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    )
                    : $GLOBALS["DB"]->executeQuery(
                    "UPDATE tbestellung SET cAbgeholt='N' WHERE kBestellung='".$order->kBestellung."'",
                        4
                    );
                $this->secupayHelper->isShop4()
                    ? Shop::DB()->executeQuery(
                    "UPDATE xplugin_agws_secupay_flex_tsyslog SET cHash='".Shop::DB()->realEscape($_REQUEST['hash'])."',cTACode='".Shop::DB()->realEscape($agws_secupay_flex_antwort_json->data->trans_id)."',kAmountSecupay='".Shop::DB()->realEscape($agws_secupay_flex_antwort_json->data->amount)."',dPushDat=now() WHERE kBestellung='".$order->kBestellung."'",
                        4
                    )
                    : $GLOBALS["DB"]->executeQuery(
                    "UPDATE xplugin_agws_secupay_flex_tsyslog SET cHash='".$GLOBALS["DB"]->realEscape($_REQUEST['hash'])."',cTACode='".$GLOBALS["DB"]->realEscape($agws_secupay_flex_antwort_json->data->trans_id)."',kAmountSecupay='".$GLOBALS["DB"]->realEscape($agws_secupay_flex_antwort_json->data->amount)."',dPushDat=now() WHERE kBestellung='".$order->kBestellung."'",
                        4
                    );

                $agws_secupay_flex_ackreq = 'ack=Approved&' . http_build_query($_POST);

                $agws_secupay_flex_logtext =
                'secupay_flex - PP - function handleNotification <br />
				ackreq erfolgreich <br />
				ackreq: '. print_r($agws_secupay_flex_ackreq, true).' <br/>
				======================';
                Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_DEBUG);
                
                die($agws_secupay_flex_ackreq);
            } else {
                $agws_secupay_flex_ackreq = 'ack=Disapproved&error=Verifikation_fehlerhaft_oder_Multi_Push&' . http_build_query($_POST);

                $agws_secupay_flex_logtext =
                'secupay_flex - PP - function handleNotification <br />
				ackreq nicht erfolgreich <br />
				ackreq: '. print_r($agws_secupay_flex_ackreq, true).' <br/>
				======================';
                Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                
                die($agws_secupay_flex_ackreq);
            }
        }
    }
    /**
     * @param array $order
     * @param string $agws_secupay_flex_paymentHash
     * @param $args (currently not used)
     * @param string $agws_secupay_flex_antwort_json
     * @return bool
     */
    public function verifyNotification($order, $agws_secupay_flex_paymentHash, $args, $agws_secupay_flex_antwort_json="")
    {
        switch ($_REQUEST['agws_sp_url']) {
            case "succ":
                $agws_secupay_flex_daten = [
                    'data'=> [
                        'apikey' => $this->oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_vertragsid'],
                        'hash' => $_SESSION['agws_secupay_flex_hash_tmp']
                    ]
                ];

                $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);
                $agws_secupay_flex_http_header = $this->agws_secupay_flex_getHttpHeader(strlen($agws_secupay_flex_data));

                $status = false;
                $count = 0;
                do {
                    $agws_secupay_flex_antwort = $this->agws_secupay_flex_getCurlContent('status', $agws_secupay_flex_http_header, $agws_secupay_flex_data);
                    $agws_secupay_flex_antwort_json = json_decode($agws_secupay_flex_antwort);
                    $status = in_array($agws_secupay_flex_antwort_json->data->status, array('proceed','scored'));
                    Jtllog::writeLog(print_r($agws_secupay_flex_antwort_json, true), JTLLOG_LEVEL_DEBUG);
                    Jtllog::writeLog('Count: ' .$count, JTLLOG_LEVEL_DEBUG);
                    if ($status == false) {
                        sleep(1);
                    }
                    $count++;
                } while ($status==false && $count < 15);
                if ($status== true && (($_REQUEST['sh'] == $agws_secupay_flex_paymentHash) || ($_REQUEST['sh'] == "_".$agws_secupay_flex_paymentHash))) {
                    $agws_secupay_flex_logtext =
                        'secupay_flex - PP - function verifyNotification <br />
						hash-Vergleich erfolgreich(0) <br />
						empfangener Hash-Wert: ' . print_r($_REQUEST['sh'], true).' <br/>
						----------------------
						gesendeter Hash-Wert: ' . print_r($agws_secupay_flex_paymentHash, true).' <br/>
						======================';
                    Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);
                    ZahlungsLog::add($this->cModulId, 'hash-Vergleich erfolgreich(0)', $agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);
                    
                    return true;
                } else {
                    $agws_secupay_flex_logtext =
                        'secupay_flex - PP - function verifyNotification <br />
						hash-Vergleich fehlerhaft(1) <br />
						empfangener Hash-Wert: ' . print_r($_REQUEST['sh'], true).' <br/>
						----------------------<br>
						gesendeter Hash-Wert: ' . print_r($agws_secupay_flex_paymentHash, true).' <br/>
						======================';
                    Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                    ZahlungsLog::add($this->cModulId, 'hash-Vergleich fehlerhaft(1)', $agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                    
                    return false;
                }

                break;
            
            case "push":
                $x = parse_url($this->agws_secupay_flex_getCurlLink());
                $x1 = parse_url($_SERVER['HTTP_REFERER']);
                 Jtllog::writeLog($_REQUEST['hash'].'STATUS'.$_REQUEST['payment_status'], JTLLOG_LEVEL_NOTICE);
                if (($_REQUEST['sh'] == $agws_secupay_flex_paymentHash) || ($_REQUEST['sh'] == "_".$agws_secupay_flex_paymentHash)
                  && ($_REQUEST['apikey'] == $this->agws_secupay_flex_getApikey())
                  && ($_REQUEST['hash'] == $_SESSION['agws_secupay_flex_hash_tmp'])
                  && ($_REQUEST['payment_status'] == 'accepted')
                  && ($x['scheme'] == $x1['scheme']) && ($x['host'] == $x1['host'])) {
                    Jtllog::writeLog($_REQUEST['hash'].' PUSH'.$_REQUEST['payment_status'], JTLLOG_LEVEL_NOTICE);
                    //Check ob Zahlung bereits gemeldet wurde / Multi-Push-Problem von secupay
                    $this->secupayHelper->isShop4()
                        ? $oNotifyDate = Shop::DB()->executeQuery(
                        "SELECT dNotify FROM tzahlungsession WHERE kBestellung = " . intval($order->kBestellung),
                            8
                        )
                        : $oNotifyDate = $GLOBALS["DB"]->executeQuery(
                        "SELECT dNotify FROM tzahlungsession WHERE kBestellung = " . intval($order->kBestellung),
                            8
                        );
                    Jtllog::writeLog($_REQUEST['hash'].' PUSH '. print_r($oNotifyDate, true), JTLLOG_LEVEL_NOTICE);
                    if (count($oNotifyDate)>0 && !empty($oNotifyDate['dNotify'])) {
                        $agws_secupay_flex_logtext =
                            'secupay_flex - PP - function verifyNotification <br />
							mehrfache Pushmitteilung erhalten: '. print_r($oNotifyDate, true).' <br />
							======================';

                        Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);
                        ZahlungsLog::add($this->cModulId, 'mehrfache Pushmitteilung erhalten', $agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);

                        return false;
                    }

                    if ($agws_secupay_flex_antwort_json->status == "ok"
                     && isset($agws_secupay_flex_antwort_json->data->hash)
                     && $agws_secupay_flex_antwort_json->data->hash == $_REQUEST['hash']
                     && round($agws_secupay_flex_antwort_json->data->amount) == round((round($order->fGesamtsumme, 2)*100))) {
                        $agws_secupay_flex_antwort_json->status == "ok"
                           ? $status_flag = "Status OK"
                           : $status_flag = "Status FAIL";

                        isset($agws_secupay_flex_antwort_json->data->hash) && $agws_secupay_flex_antwort_json->data->hash == $_REQUEST['hash']
                           ? $hash_flag = "hash OK"
                           : $hash_flag = "hash FAIL";

                        round($agws_secupay_flex_antwort_json->data->amount) == round((round($order->fGesamtsumme, 2)*100))
                           ? $sum_flag = "Gesamtsumme OK"
                           : $sum_flag = "Gesamtsumme FAIL";

                        $sum_diff = round($agws_secupay_flex_antwort_json->data->amount) - round((round($order->fGesamtsumme, 2)*100)) ;

                        $agws_secupay_flex_logtext =
                            'secupay_flex - PP - function verifyNotification <br />
							multiVar-Vergleich erfolgreich(0) <br />
							empfangener Request: ' . print_r($_REQUEST, true).' <br/>
							----------------------<br>
							empfangener json-Antwort: ' . print_r($agws_secupay_flex_antwort_json, true).' <br/>
							----------------------<br>
							vergleich paymentHash: ' . print_r($agws_secupay_flex_paymentHash, true).' <br/>
							----------------------<br>
							vergleich api-key: ' . print_r($this->agws_secupay_flex_getApikey(), true).' <br/>
							----------------------<br>
							vergleich secupayHash: ' . print_r($_SESSION['agws_secupay_flex_hash_tmp'], true).' <br/>
							----------------------<br>
							vergleich parseurlAPI: ' . print_r($x, true).' <br/>
							----------------------<br>
							vergleich parseurlREF: ' . print_r($x1, true).' <br/>
							----------------------<br>
							vergleich fGesamtsumme: ' . print_r(round((round($order->fGesamtsumme, 2)*100)), true).' <br/>
							----------------------<br>
							Status: ' . print_r($status_flag, true).' <br/>
							----------------------<br>
							Hash: ' . print_r($hash_flag, true).' <br/>
							----------------------<br>
							Gesamtsumme: ' . print_r($sum_flag, true).' <br/>
							----------------------<br>
							Gesamtsumme-Diff: ' . print_r($sum_diff, true).' <br/>
							======================';
                            
                        Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);
                        ZahlungsLog::add($this->cModulId, 'multiVar-Vergleich erfolgreich(0)', $agws_secupay_flex_logtext, JTLLOG_LEVEL_NOTICE);
                        
                        return true;
                    } else {
                        $agws_secupay_flex_antwort_json->status != "ok"
                           ? $status_flag = "Status FAIL"
                           : $status_flag = "Status OK";

                        isset($agws_secupay_flex_antwort_json->data->hash) && $agws_secupay_flex_antwort_json->data->hash != $_REQUEST['hash']
                           ? $hash_flag = "hash FAIL"
                           : $hash_flag = "hash OK";

                        round($agws_secupay_flex_antwort_json->data->amount) != round((round($order->fGesamtsumme, 2)*100))
                           ? $sum_flag = "Gesamtsumme FAIL"
                           : $sum_flag = "Gesamtsumme OK";

                        $sum_diff = round($agws_secupay_flex_antwort_json->data->amount) - round((round($order->fGesamtsumme, 2)*100)) ;
                            
                        $agws_secupay_flex_logtext =
                            'secupay_flex - PP - function verifyNotification <br />
							multiVar-Vergleich fehlerhaft(0) <br />
							empfangener Request: ' . print_r($_REQUEST, true).' <br/>
							----------------------<br>
							empfangener json-Antwort: ' . print_r($agws_secupay_flex_antwort_json, true).' <br/>
							----------------------<br>
							vergleich paymentHash: ' . print_r($agws_secupay_flex_paymentHash, true).' <br/>
							----------------------<br>
							vergleich api-key: ' . print_r($this->agws_secupay_flex_getApikey(), true).' <br/>
							----------------------<br>
							vergleich secupayHash: ' . print_r($_SESSION['agws_secupay_flex_hash_tmp'], true).' <br/>
							----------------------<br>
							vergleich parseurlAPI: ' . print_r($x, true).' <br/>
							----------------------<br>
							vergleich parseurlREF: ' . print_r($x1, true).' <br/>
							----------------------<br>
							vergleich fGesamtsumme: ' . print_r(round((round($order->fGesamtsumme, 2)*100)), true).' <br/>
							----------------------<br>
							Status: ' . print_r($status_flag, true).' <br/>
							----------------------<br>
							Hash: ' . print_r($hash_flag, true).' <br/>
							----------------------<br>
							Gesamtsumme: ' . print_r($sum_flag, true).' <br/>
							----------------------<br>
							Gesamtsumme-Diff: ' . print_r($sum_diff, true).' <br/>
							======================';
                            
                        Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                        ZahlungsLog::add($this->cModulId, 'multiVar-Vergleich fehlerhaft(0)', $agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                        
                        return false;
                    }
                } else {
                    $agws_secupay_flex_logtext =
                        'secupay_flex - PP - function verifyNotification <br />
						multiVar-Vergleich fehlerhaft(1) <br />
						empfangener Request: ' . print_r($_REQUEST, true).' <br/>
						----------------------<br>
						vergleich paymentHash: ' . print_r($agws_secupay_flex_paymentHash, true).' <br/>
						----------------------<br>
						vergleich api-key: ' . print_r($this->agws_secupay_flex_getApikey(), true).' <br/>
						----------------------<br>
						vergleich secupayHash: ' . print_r($_SESSION['agws_secupay_flex_hash_tmp'], true).' <br/>
						----------------------<br>
						vergleich parseurlAPI: ' . print_r($x, true).' <br/>
						----------------------<br>
						vergleich parseurlREF: ' . print_r($x1, true).' <br/>
						======================';
                    Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                    ZahlungsLog::add($this->cModulId, 'multiVar-Vergleich fehlerhaft(1)', $agws_secupay_flex_logtext, JTLLOG_LEVEL_ERROR);
                
                    return false;
                }

            break;
        }

        return false;
    }

    /**
     * @param $order
     * @param $agws_secupay_flex_paymentHash
     * @param $args
     * @return bool
     */
    public function finalizeOrder($order, $agws_secupay_flex_paymentHash, $args)
    {
        return $this->verifyNotification($order, $agws_secupay_flex_paymentHash, $args);
    }
    /**
     * @param $customer
     * @param $cart
     * @return bool
     */
    public function isValid($customer, $cart)
    {
        //vefuegbare Zahlungsarten
        if ($this->getSetting('min_bestellungen') > 0) {
            if (isset($customer->kKunde) && $customer->kKunde > 0) {
                $res = Shop::DB()->query(
                    "
                  SELECT count(*) AS cnt 
                      FROM tbestellung 
                      WHERE kKunde = " . (int) $customer->kKunde . " 
                          AND (
                                cStatus = '" . BESTELLUNG_STATUS_BEZAHLT . "' 
                                OR cStatus = '" . BESTELLUNG_STATUS_VERSANDT .
                    "')",
                    1
                );
                $count = (int)$res->cnt;
                if ($count < $this->getSetting('min_bestellungen')) {
                    ZahlungsLog::add(
                        $this->moduleID,
                        'Bestellanzahl ' . $count . ' ist kleiner als der Mindestanzahl von ' .
                        $this->getSetting('min_bestellungen'),
                        null,
                        LOGLEVEL_NOTICE
                    );

                    return false;
                }
            } else {
                ZahlungsLog::add($this->moduleID, "Es ist kein kKunde vorhanden", null, LOGLEVEL_NOTICE);

                return false;
            }
        }

        if ($this->getSetting('min') > 0 && $cart->gibGesamtsummeWaren(1) <= $this->getSetting('min')) {
            ZahlungsLog::add(
                $this->moduleID,
                'Bestellwert ' . $cart->gibGesamtsummeWaren(1) .
                ' ist kleiner als der Mindestbestellwert von ' . $this->getSetting('min'),
                null,
                LOGLEVEL_NOTICE
            );

            return false;
        }

        if ($this->getSetting('max') > 0 && $cart->gibGesamtsummeWaren(1) >= $this->getSetting('max')) {
            ZahlungsLog::add(
                $this->moduleID,
                'Bestellwert ' . $cart->gibGesamtsummeWaren(1) .
                ' ist groesser als der maximale Bestellwert von ' . $this->getSetting('max'),
                null,
                LOGLEVEL_NOTICE
            );

            return false;
        }

        if (!$this->isValidIntern($customer, $cart)) {
            return false;
        }

        $agws_secupay_flex_daten = [
            'data' => [
                'apikey' => $this->agws_secupay_flex_getApikey(),
            ]
        ];
            
        $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);
        $agws_secupay_flex_http_header = $this->agws_secupay_flex_getHttpHeader(strlen($agws_secupay_flex_data));
        $agws_secupay_flex_antwort = $this->agws_secupay_flex_getCurlContent('gettypes', $agws_secupay_flex_http_header, $agws_secupay_flex_data);
        $agws_secupay_flex_antwort_json = json_decode($agws_secupay_flex_antwort);
           
        if ($agws_secupay_flex_antwort_json->status == "ok" && in_array("prepay", $agws_secupay_flex_antwort_json->data)) {
            $this->agws_secupay_flex_doLoggingCurl('Statusabfrage', 'erfolgreich', JTLLOG_LEVEL_NOTICE, 'isValid', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);
        } else {
            $this->agws_secupay_flex_doLoggingCurl('Statusabfrage', 'fehlerhaft', JTLLOG_LEVEL_ERROR, 'isValid', "header: ".print_r($agws_secupay_flex_http_header, 1)."<br><br>".$agws_secupay_flex_data, $agws_secupay_flex_antwort_json, $agws_secupay_flex_antwort);
            return false;
        }
        return true;
    }
}

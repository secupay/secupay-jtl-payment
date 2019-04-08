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

require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php");
require_once $oPlugin->cAdminmenuPfad . 'inc/class.agws_plugin_secupay.helper.php';

$helper = agwsPluginHelperSecupay::getInstance($oPlugin);

if ($helper->isShop4()) {
    $smarty = Shop::Smarty();
} else {
    global $smarty;
}

if ($helper->gibSeiten__Typ() == PAGE_BESTELLVORGANG) {
    if (!is_null($_SESSION['Bestellung']->kLieferadresse) && $_SESSION['Bestellung']->kLieferadresse != '0' && $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 1) {
        $smarty->assign('agws_secupay_flex_abwLA_titel', $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_abwLA_titel']);
        $smarty->assign('agws_secupay_flex_abwLA_text', $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_abwLA_text']);
        $agws_secupay_flex_abwLA = $smarty->fetch($oPlugin->cFrontendPfad . 'template/agws_secupay_flex_abwLA_html.tpl');
        $_SESSION['agws_secupay_flex_abwLA_flag'] = 1;
    } else {
        $agws_secupay_flex_abwLA = "";
        $_SESSION['agws_secupay_flex_abwLA_flag'] = 0;
    }

    switch ($oPlugin->nCalledHook) {
        case HOOK_SMARTY_OUTPUTFILTER:  //Hook140

            //wir kümmern uns um die Seite - Zahlungsart
            if (($smarty->get_template_vars('step')=="Zahlung") && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 1 || $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 2)) {
                //$agws_secupay_flex_cModulId_arr nur für Lastschrift und Rechnungskauf, da bei Kreditkarte abweichende LA zulässig
                $agws_secupay_flex_cModulId_arr = array("kPlugin_" . $oPlugin->kPlugin . "_secupaycredit", "kPlugin_" . $oPlugin->kPlugin . "_secupaydebit", "kPlugin_" . $oPlugin->kPlugin . "_secupayinvoice" . $oPlugin->kPlugin . "_secupayprepay" . $oPlugin->kPlugin . "_secupaysofort");
            
                for ($i=0;$i<count($oPlugin->oPluginZahlungsmethode_arr);$i++) {
                    if (($_SESSION['Bestellung']->kLieferadresse != 0) && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 2) && in_array($oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId, $agws_secupay_flex_cModulId_arr)) {
                        pq('#' .$oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId.'')->addClass('hidden');
                    } elseif (($_SESSION['Bestellung']->kLieferadresse != 0) && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 1)  && in_array($oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId, $agws_secupay_flex_cModulId_arr)) {
                        pq('#' .$oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId)->append($agws_secupay_flex_abwLA);
                    }
                }
            }
            break;
   
        case HOOK_BESTELLVORGANG_PAGE:  //Hook19
            if ($_GET['AGWS_SECUPAY_ERRORMSG']) {
                $smarty->assign('cFehler', "<small>".$_GET['AGWS_SECUPAY_ERRORMSG']." (".$_GET['AGWS_SECUPAY_ERRORCODE'].")</small>");
            }
            break;
        default:
            break;
    }
} elseif (($helper->gibSeiten__Typ() == PAGE_UNBEKANNT && isset($_GET['uid']) && $_GET['uid'] != "") || ($helper->gibSeiten__Typ() == PAGE_BESTELLSTATUS)) {
    switch ($oPlugin->nCalledHook) {
        case HOOK_SMARTY_OUTPUTFILTER:  //Hook140
            $agws_secupay_flex_status_ordercomplete = $smarty->fetch($oPlugin->cFrontendPfad . 'template/agws_secupay_flex_status_ordercomplete.tpl');
            $agws_secupay_flex_cssselektor1 = $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_cssselektor'];
            $agws_secupay_flex_pqmethode1 = $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_pqmethode'];
            
            pq($agws_secupay_flex_cssselektor1)->$agws_secupay_flex_pqmethode1($agws_secupay_flex_status_ordercomplete);

            $agws_secupay_flex_oBestellung = $smarty->get_template_vars('Bestellung');
            $agws_secupay_flex_cModulId_arr=array("kPlugin_".$oPlugin->kPlugin."_secupaycredit", "kPlugin_".$oPlugin->kPlugin."_secupaydebit", "kPlugin_".$oPlugin->kPlugin."_secupayinvoice");
            
            if (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum == "0000-00-00")) {
                $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_pending'];
                if ($helper->isShop4()) {
                    $cTmp = '<div class="panel-body">'.$agws_secupay_flex_pqText.'</div>';
                    pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                    pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
                } else {
                    $cTmp = '<fieldset class="resize" style="height: 30px;"><legend>' . $helper->gib__Wert('paymentOptions', 'global') . ': ' . $agws_secupay_flex_oBestellung->cZahlungsartName . '</legend><span>' . $agws_secupay_flex_pqText . '</span></fieldset>';
                    pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                    pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
                }
            } elseif (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum != "0000-00-00")) {
                $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_accept'];
                if ($helper->isShop4()) {
                    $cTmp = '<div class="panel-body">'.$agws_secupay_flex_pqText.'</div>';
                    pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                    pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
                } else {
                    $cTmp = '<fieldset class="resize" style="height: 30px;"><legend>'.$helper->gib__Wert('paymentOptions', 'global').': '.$agws_secupay_flex_oBestellung->cZahlungsartName.'</legend><span>'.$agws_secupay_flex_pqText.'</span></fieldset>';
                    pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                    pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
                }
            }

            break;
        default:
            break;
    }
} elseif ($helper->gibSeiten__Typ() == PAGE_MEINKONTO) {
    $smarty_step = $smarty->get_template_vars('step');
    
    if ($smarty_step == "bestellung") {
        switch ($oPlugin->nCalledHook) {
            case HOOK_SMARTY_OUTPUTFILTER:  //Hook140
                $agws_secupay_flex_oBestellung = $smarty->get_template_vars('Bestellung');
                $agws_secupay_flex_cModulId_arr=array("kPlugin_".$oPlugin->kPlugin."_secupaycredit", "kPlugin_".$oPlugin->kPlugin."_secupaydebit", "kPlugin_".$oPlugin->kPlugin."_secupayinvoice", "kPlugin_".$oPlugin->kPlugin."_secupayprepay", "kPlugin_".$oPlugin->kPlugin."_secupaysofort");
                if (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum == "0000-00-00")) {
                    $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_pending'];
                    if ($helper->isShop4()) {
                        $cTmp = '<div class="panel-body">'.$agws_secupay_flex_pqText.'</div>';
                        pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                        pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
                    } else {
                        $cTmp = '<fieldset class="resize" style="height: 50px;"><legend>'.$helper->gib__Wert('paymentOptions', 'global').': '.$agws_secupay_flex_oBestellung->cZahlungsartName.'</legend><span>'.$agws_secupay_flex_pqText.'</span></fieldset>';
                        pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                        pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
                    }
                } elseif (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum != "0000-00-00")) {
                    $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_accept'];
                    if ($helper->isShop4()) {
                        $cTmp = '<div class="panel-body">'.$agws_secupay_flex_pqText.'</div>';
                        pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                        pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
                    } else {
                        $cTmp = '<fieldset class="resize" style="height: 50px;"><legend>'.$helper->gib__Wert('paymentOptions', 'global').': '.$agws_secupay_flex_oBestellung->cZahlungsartName.'</legend><span>'.$agws_secupay_flex_pqText.'</span></fieldset>';
                        pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                        pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
                    }
                }
                break;
            default:
                break;
        }
    }
}

if ($oPlugin->nCalledHook == HOOK_BESTELLUNGEN_XML_BEARBEITESET) {
    $oBestellungVersand = null;

    $sql="SELECT tbestellung.kBestellung, tbestellung.cTracking,tbestellung.cLogistiker,tbestellung.dVersandDatum,tbestellung.cStatus,tbestellung.cBestellNr, xplugin_agws_secupay_flex_tsyslog.kLogId, xplugin_agws_secupay_flex_tsyslog.dVersandDat, xplugin_agws_secupay_flex_tsyslog.cHash, xplugin_agws_secupay_flex_tsyslog.cSecupayZA
			FROM tbestellung
			JOIN xplugin_agws_secupay_flex_tsyslog ON tbestellung.kBestellung = xplugin_agws_secupay_flex_tsyslog.kBestellung
			WHERE tbestellung.kBestellung = '" . $args_arr['oBestellung']->kBestellung . "'";
    ($helper->isShop4()) ?
        $oBestellungVersand = Shop::DB()->executeQuery($sql, 1):
        $oBestellungVersand = $GLOBALS['DB']->executeQuery($sql, 1);

    if (!is_null($oBestellungVersand) && $oBestellungVersand->cStatus == BESTELLUNG_STATUS_VERSANDT && $oBestellungVersand->dVersandDat == "0000-00-00 00:00:00") {
        Jtllog::writeLog("secupay: Versand für secupay-Bestellung (".$oBestellungVersand->kBestellung.")", JTLLOG_LEVEL_NOTICE);

        if ($oBestellungVersand->cSecupayZA===null || $oBestellungVersand->cSecupayZA == "invoice" || $oBestellungVersand->cSecupayZA == "debit" || $oBestellungVersand->cSecupayZA == "creditcard" || $oBestellungVersand->cSecupayZA == "prepay" || $oBestellungVersand->cSecupayZA == "sofort") {
            Jtllog::writeLog("secupay: Versand für Rechnungskauf - löse capture aus", JTLLOG_LEVEL_NOTICE);

            $agws_secupay_flex_curlcontent = "";

            if (function_exists('curl_init')) {
                ($helper->isShop4()) ?
                    $agws_secupay_flex_useragent = 'JTL4-client V'.$oPlugin->nVersion:
                    $agws_secupay_flex_useragent = 'JTL3-client V'.$oPlugin->nVersion;

                $agws_secupay_flex_daten = array(
                    'data' => array(
                        'apikey' => $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_vertragsid'],
                        'hash'  => $oBestellungVersand->cHash,
                        'tracking' => array(
                        'provider' => $oBestellungVersand->cLogistiker,
                        'number' => $oBestellungVersand->cTracking),
                        'invoice_number' => $oBestellungVersand->cBestellNr
                    )
                );
                if ($oBestellungVersand->cSecupayZA == "invoice") {
                    $agws_secupay_flex_CURL_URL = "https://api.secupay.ag/payment/capture/";
                } else {
                    $agws_secupay_flex_CURL_URL = "https://api.secupay.ag/payment/adddata/";
                }
                
                $agws_secupay_flex_data = json_encode($agws_secupay_flex_daten);

                $agws_secupay_flex_ch = curl_init();
                curl_setopt($agws_secupay_flex_ch, CURLOPT_URL, $agws_secupay_flex_CURL_URL);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_HTTPHEADER, array(
                    'Accept-Language: de_DE',
                    'Accept: application/json',
                    'Content-type: application/json; charset=utf-8;',
                    'User-Agent: ' . $agws_secupay_flex_useragent,
                    'Content-Length: ' . strlen($agws_secupay_flex_data)));
                curl_setopt($agws_secupay_flex_ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_REFERER, URL_SHOP);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_POST, true);
                curl_setopt($agws_secupay_flex_ch, CURLOPT_POSTFIELDS, $agws_secupay_flex_data);

                $agws_secupay_flex_curlcontent = curl_exec($agws_secupay_flex_ch);
                $agws_secupay_flex_antwort = json_decode($agws_secupay_flex_curlcontent);

                $info = curl_getinfo($agws_secupay_flex_ch);

                $agws_secupay_flex_logtext =
                    'secupay_flex - hook - setzeVersand <br />
						Ergebnis cURL <br />' . print_r($info, true).' <br/>
						======================';
                Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_DEBUG);

                curl_close($agws_secupay_flex_ch);

                Jtllog::writeLog("secupay: capture-Antwort: ".print_r($agws_secupay_flex_antwort, 1), JTLLOG_LEVEL_NOTICE);
            }
        }

        Jtllog::writeLog("secupay: Update Log-Datensatz", JTLLOG_LEVEL_NOTICE);

        $queryResultInsert = new stdClass();
        $queryResultInsert->dVersandDat = "now()";
        ($helper->isShop4()) ?
                $queryResult = Shop::DB()->updateRow("xplugin_agws_secupay_flex_tsyslog", "kLogId", $oBestellungVersand->kLogId, $queryResultInsert):
                $queryResult = $GLOBALS["DB"]->updateRow("xplugin_agws_secupay_flex_tsyslog", "kLogId", $oBestellungVersand->kLogId, $queryResultInsert);
    }
}

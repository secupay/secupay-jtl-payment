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
    //wir kümmern uns um die Seite - Zahlungsart
    if (($smarty->get_template_vars('step') == "Zahlung") && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 1 || $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 2)) {
        $agws_secupay_flex_cModulId_arr = array("kPlugin_" . $oPlugin->kPlugin . "_secupaycredit", "kPlugin_" . $oPlugin->kPlugin . "_secupaydebit", "kPlugin_" . $oPlugin->kPlugin . "_secupayinvoice");
        for ($i = 0; $i < count($oPlugin->oPluginZahlungsmethode_arr); $i++) {
            if (($_SESSION['Bestellung']->kLieferadresse != 0) && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 2) && in_array($oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId, $agws_secupay_flex_cModulId_arr)) {
                pq('#' . $oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId . '')->addClass('hidden');
            } elseif (($_SESSION['Bestellung']->kLieferadresse != 0) && ($oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_hinweisflag'] == 1) && in_array($oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId, $agws_secupay_flex_cModulId_arr)) {
                pq('#' . $oPlugin->oPluginZahlungsmethode_arr[$i]->cModulId)->append($agws_secupay_flex_abwLA);
            }
        }
    }
} elseif (($helper->gibSeiten__Typ() == PAGE_UNBEKANNT && isset($_GET['uid']) && $_GET['uid'] != "") || ($helper->gibSeiten__Typ() == PAGE_BESTELLSTATUS)) {
    $agws_secupay_flex_status_ordercomplete = $smarty->fetch($oPlugin->cFrontendPfad . 'template/agws_secupay_flex_status_ordercomplete.tpl');
    $agws_secupay_flex_cssselektor1 = $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_cssselektor'];
    $agws_secupay_flex_pqmethode1 = $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_pqmethode'];

    pq($agws_secupay_flex_cssselektor1)->$agws_secupay_flex_pqmethode1($agws_secupay_flex_status_ordercomplete);

    $agws_secupay_flex_oBestellung = $smarty->get_template_vars('Bestellung');
    $agws_secupay_flex_cModulId_arr = array("kPlugin_" . $oPlugin->kPlugin . "_secupaycredit", "kPlugin_" . $oPlugin->kPlugin . "_secupaydebit", "kPlugin_" . $oPlugin->kPlugin . "_secupayinvoice","kPlugin_"  . $oPlugin->kPlugin . "_secupaysofort","kPlugin_"  . $oPlugin->kPlugin . "_secupayprepay");

    if (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum == "0000-00-00")) {
        $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_pending'];
        if ($helper->isShop4()) {
            $cTmp = '<div class="panel-body">' . $agws_secupay_flex_pqText . '</div>';
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
            $cTmp = '<div class="panel-body">' . $agws_secupay_flex_pqText . '</div>';
            pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
            pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
        } else {
            $cTmp = '<fieldset class="resize" style="height: 30px;"><legend>' . $helper->gib__Wert('paymentOptions', 'global') . ': ' . $agws_secupay_flex_oBestellung->cZahlungsartName . '</legend><span>' . $agws_secupay_flex_pqText . '</span></fieldset>';
            pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
            pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
        }
    }
} elseif ($helper->gibSeiten__Typ() == PAGE_MEINKONTO) {
    $smarty_step = $smarty->get_template_vars('step');

    if ($smarty_step == "bestellung") {
        $agws_secupay_flex_oBestellung = $smarty->get_template_vars('Bestellung');
        $agws_secupay_flex_cModulId_arr = array("kPlugin_" . $oPlugin->kPlugin . "_secupaycredit", "kPlugin_" . $oPlugin->kPlugin . "_secupaydebit", "kPlugin_" . $oPlugin->kPlugin . "_secupayinvoice" . $oPlugin->kPlugin . "_secupayprepay" . $oPlugin->kPlugin . "_secupaysofort");
        if (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum == "0000-00-00")) {
            $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_pending'];
            if ($helper->isShop4()) {
                $cTmp = '<div class="panel-body">' . $agws_secupay_flex_pqText . '</div>';
                pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
            } else {
                $cTmp = '<fieldset class="resize" style="height: 50px;"><legend>' . $helper->gib__Wert('paymentOptions', 'global') . ': ' . $agws_secupay_flex_oBestellung->cZahlungsartName . '</legend><span>' . $agws_secupay_flex_pqText . '</span></fieldset>';
                pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
            }
        } elseif (in_array($agws_secupay_flex_oBestellung->Zahlungsart->cModulId, $agws_secupay_flex_cModulId_arr) && ($agws_secupay_flex_oBestellung->dBezahltDatum != "0000-00-00")) {
            $agws_secupay_flex_pqText = $oPlugin->oPluginSprachvariableAssoc_arr['agws_secupay_flex_loc_global_auth_accept'];
            if ($helper->isShop4()) {
                $cTmp = '<div class="panel-body">' . $agws_secupay_flex_pqText . '</div>';
                pq('div#content>div:eq(3)>div:eq(0)>div>div:eq(1))')->remove();
                pq('div#content>div:eq(3)>div:eq(0)>div')->append($cTmp);
            } else {
                $cTmp = '<fieldset class="resize" style="height: 50px;"><legend>' . $helper->gib__Wert('paymentOptions', 'global') . ': ' . $agws_secupay_flex_oBestellung->cZahlungsartName . '</legend><span>' . $agws_secupay_flex_pqText . '</span></fieldset>';
                pq('div.container:eq(1) ul li:eq(1) div fieldset')->remove();
                pq('div.container:eq(1) ul li:eq(1) div')->append($cTmp);
            }
        }
    }
}

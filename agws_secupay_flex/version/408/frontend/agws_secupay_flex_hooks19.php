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

    if ($_GET['AGWS_SECUPAY_ERRORMSG']) {
        $smarty->assign('cFehler', "<small>" . $_GET['AGWS_SECUPAY_ERRORMSG'] . " (" . $_GET['AGWS_SECUPAY_ERRORCODE'] . ")</small>");
    }
}

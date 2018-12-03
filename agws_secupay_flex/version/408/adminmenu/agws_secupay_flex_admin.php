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

global $oPlugin;

require_once $oPlugin->cAdminmenuPfad . 'inc/class.agws_plugin_secupay.helper.php';

$helper = agwsPluginHelperSecupay::getInstance($oPlugin);

if ($helper->isShop4()) {
    $smarty = Shop::Smarty();
} else {
    global $smarty;
}

$smarty->assign("agws_secupay_flex_infobox_link", "https://connect.secupay.ag");
$smarty->assign("agws_secupay_flex_apikey", $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_vertragsid']);
$smarty->assign("agws_secupay_flex_shophersteller", "JTL");
$smarty->assign("agws_secupay_flex_shopversion", JTL_VERSION);
$smarty->assign("agws_secupay_flex_modulversion", $oPlugin->nVersion);
$smarty->assign("agws_secupay_flex_connect_link", "https://secuoffice.com");

print($smarty->fetch($oPlugin->cAdminmenuPfad . "template/agws_secupay_flex_admin.tpl"));

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
require_once(PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . "blaetternavi.php");
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Bestellung.php");
require_once($oPlugin->cAdminmenuPfad . "inc/agws_secupay_flex_overview_inc.php");

$oPlugin = Plugin::getPluginById('agws_secupay_flex');
$helper  = agwsPluginHelperSecupay::getInstance($oPlugin);

if ($helper->isShop4()) {
    $smarty = Shop::Smarty();
} else {
    global $smarty;
}

$sql = "SELECT kBestellung FROM xplugin_agws_secupay_flex_tsyslog";
($helper->isShop4() === true) ? $agws_secupay_flex_over1 = Shop::DB()
                                                               ->executeQuery($sql, 2)
    : $agws_secupay_flex_over1 = $GLOBALS['DB']->executeQuery($sql, 2);


foreach ($agws_secupay_flex_over1 as $obj) {
    $cSecZA .= $obj->kBestellung . ",";
}

$cHinweis    = "";
$cFehler     = "";
$cStep       = "bestellungen_uebersicht";
$cSuchFilter = "";


// BlätterNavi Getter / Setter + SQL
$nAnzahlProSeite   = 15;
$oBlaetterNaviConf = baueBlaetterNaviGetterSetter(1, $nAnzahlProSeite);

// ###############
// Getter & Setter
// ###############

// Bestellung Wawi Abholung zurücksetzen
if (verifyGPCDataInteger('zuruecksetzen') == 1) {
    switch (setzeAbgeholtZurueck($_POST['kBestellung'])) {
        case -1:    // Alles O.K.
            $cHinweis = "Ihr markierten Bestellungen wurden erfolgreich zur&uuml;ckgesetzt.";
            break;
        case 1:        // Array mit Keys nicht vorhanden oder leer
            $cFehler = "Fehler: Bitte markieren Sie mindestens eine Bestellung.";
            break;
        default:
            break;
    }
} // Bestellnummer gesucht
elseif (verifyGPCDataInteger('Suche') == 1) {
    $cSuche = $helper->filter__XSS(verifyGPDataString('cSuche'));

    if (strlen($cSuche) > 0) {
        $cSuchFilter = $cSuche;
    } else {
        $cFehler = "Fehler: Bitte geben Sie eine Bestellnummer ein.";
    }
}

// #####
// Steps
// #####

// Übersicht
if ($cStep == "bestellungen_uebersicht") {
    // Baue Blätternavigation
    $oBlaetterNaviUebersicht = baueBlaetterNavi(
        $oBlaetterNaviConf->nAktuelleSeite1,
        gibAnzahlBestellungen($cSuchFilter, substr($cSecZA, 0, -1)),
        $nAnzahlProSeite
    );
    $smarty->assign("oBlaetterNaviUebersicht", $oBlaetterNaviUebersicht);
    $smarty->assign(
        "oBestellung_arr",
        gibBestellungsUebersicht($oBlaetterNaviConf->cSQL1, $cSuchFilter, substr($cSecZA, 0, -1))
    );
}

// Error / Notice
$smarty->assign('cHinweis', $cHinweis);
$smarty->assign('cFehler', $cFehler);
$smarty->assign('cStep', $cStep);
$smarty->assign('cAdminmenuPfad', $oPlugin->cAdminmenuPfad);

if (strlen($cSuchFilter) > 0) {
    $smarty->assign("cSuche", $cSuchFilter);
}

$smarty->assign('cSecupayURL', 'https://api.secupay.ag/payment/');
$smarty->assign('cSecupayAPIKey', $oPlugin->oPluginEinstellungAssoc_arr['agws_secupay_flex_global_vertragsid']);

print($smarty->fetch($oPlugin->cAdminmenuPfad . "template/agws_secupay_flex_overview.tpl"));

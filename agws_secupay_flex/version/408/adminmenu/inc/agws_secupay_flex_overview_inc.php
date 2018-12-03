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

function gibBestellungsUebersicht($cLimitSQL, $cSuchFilter, $cSecZA)
{
    $oPlugin = Plugin::getPluginById('agws_secupay_flex');
    $helper = agwsPluginHelperSecupay::getInstance($oPlugin);

    $oBestellung_arr = array();

    //$cSuchFilterSQL = " WHERE kZahlungsart IN(" . $cSecZA. ")";
    $cSuchFilterSQL = " WHERE kBestellung IN(" . $cSecZA. ")";
    if (strlen($cSuchFilter)) {
        $cSuchFilterSQL .= " AND cBestellNr LIKE '%" . $cSuchFilter . "%'";
    }
    
    $sql = "SELECT kBestellung FROM tbestellung" . $cSuchFilterSQL . " ORDER BY dErstellt DESC" . $cLimitSQL;
    ($helper->isShop4()) ?
            $oBestellungToday_arr = Shop::DB()->executeQuery($sql, 2):
            $oBestellungToday_arr = $GLOBALS['DB']->executeQuery($sql, 2);
                                                        
    if (is_array($oBestellungToday_arr) && count($oBestellungToday_arr) > 0) {
        foreach ($oBestellungToday_arr as $oBestellungToday) {
            if (isset($oBestellungToday->kBestellung) && $oBestellungToday->kBestellung > 0) {
                $oBestellung = new Bestellung($oBestellungToday->kBestellung);
                $oBestellung->fuelleBestellung(1, 0, false);
                
                $sql = "SELECT * FROM xplugin_agws_secupay_flex_tsyslog WHERE kBestellung = ".$oBestellungToday->kBestellung." ORDER BY kLogId DESC";
                ($helper->isShop4()) ?
                        $oBestellungSecLog_arr = Shop::DB()->executeQuery($sql, 8):
                        $oBestellungSecLog_arr = $GLOBALS['DB']->executeQuery("$sql", 8);
                
                $oBestellung->agws_seclog_cHash = $oBestellungSecLog_arr['cHash'];
                $oBestellung->agws_seclog_cTACode = $oBestellungSecLog_arr['cTACode'];
                $oBestellung->agws_seclog_kAmountSecupay = $oBestellungSecLog_arr['kAmountSecupay'] / 100;
                $oBestellung->agws_seclog_dSuccDat = $oBestellungSecLog_arr['dSuccDat'];
                $oBestellung->agws_seclog_dPushDat = $oBestellungSecLog_arr['dPushDat'];
                $oBestellung->agws_seclog_dVersandDat = $oBestellungSecLog_arr['dVersandDat'];
                $oBestellung->agws_seclog_cSecupayZA  = $oBestellungSecLog_arr['cSecupayZA'];
                
                $oBestellung_arr[] = $oBestellung;
            }
        }
    }
    
    return $oBestellung_arr;
}

/**
 * @param $cSuchFilter
 * @param $cSecZA
 * @return int
 */
function gibAnzahlBestellungen($cSuchFilter, $cSecZA)
{
    $oPlugin = Plugin::getPluginById('agws_secupay_flex');
    $helper = agwsPluginHelperSecupay::getInstance($oPlugin);

    //$cSuchFilterSQL = " WHERE kZahlungsart IN(" . $cSecZA. ")";
    $cSuchFilterSQL = " WHERE kBestellung IN(" . $cSecZA. ")";
    if (strlen($cSuchFilter)) {
        $cSuchFilterSQL .= " AND cBestellNr LIKE '%" . $cSuchFilter . "%'";
    }
    
    $sql= "SELECT count(*) AS nAnzahl FROM tbestellung" . $cSuchFilterSQL;
    ($helper->isShop4()) ?
            $oBestellung = Shop::DB()->executeQuery($sql, 1):
            $oBestellung = $GLOBALS['DB']->executeQuery($sql, 1);
                                                    
    if (isset($oBestellung->nAnzahl) && $oBestellung->nAnzahl > 0) {
        return intval($oBestellung->nAnzahl);
    }
        
    return 0;
}

/**
 * @param $kBestellung_arr
 * @return int
 */
function setzeAbgeholtZurueck($kBestellung_arr)
{
    $oPlugin = Plugin::getPluginById('agws_secupay_flex');
    $helper = agwsPluginHelperSecupay::getInstance($oPlugin);

    if (is_array($kBestellung_arr) && count($kBestellung_arr) > 0) {
        // Kunden cAbgeholt zurücksetzen
        $sql = "SELECT kKunde FROM tbestellung WHERE kBestellung IN(" . implode(",", $kBestellung_arr) . ") AND cAbgeholt = 'Y'";
        ($helper->isShop4()) ?
                $oKunde_arr = Shop::DB()->executeQuery($sql, 2):
                $oKunde_arr = $GLOBALS['DB']->executeQuery($sql, 2);
                                                        
        if (is_array($oKunde_arr) && count($oKunde_arr) > 0) {
            $kKunde_arr = array();
            foreach ($oKunde_arr as $oKunde) {
                if (!in_array($oKunde->kKunde, $kKunde_arr)) {
                    $kKunde_arr[] = $oKunde->kKunde;
                }
            }
            
            $sql = "UPDATE tkunde SET cAbgeholt = 'N' WHERE kKunde IN(" . implode(",", $kKunde_arr) . ")";
            ($helper->isShop4()) ?
                    Shop::DB()->executeQuery($sql, 3):
                    $GLOBALS['DB']->executeQuery($sql, 3);
        }
    
        // Bestellungen cAbgeholt zurücksetzen
        $sql = "UPDATE tbestellung SET cAbgeholt = 'N' WHERE kBestellung IN(" . implode(",", $kBestellung_arr) . ") AND cAbgeholt = 'Y'";
        ($helper->isShop4()) ?
                Shop::DB()->executeQuery($sql, 3):
                $GLOBALS['DB']->executeQuery($sql, 3);

        return -1;
    }
    
    return 1; // Array mit Keys nicht vorhanden oder leer
}

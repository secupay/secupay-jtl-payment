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

require_once '../../../../../../includes/globalinclude.php';
require_once PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php';
require_once PFAD_ROOT . PFAD_INCLUDES . 'sprachfunktionen.php';
require_once PFAD_ROOT . PFAD_INCLUDES . 'mailTools.php';

// Debug
/**
 *
 */
define('NO_MODE', 0); // 1 = An / 0 = Aus
/**
 *
 */
define('NO_PFAD', PFAD_ROOT . 'jtllogs/notify.log');

$Sprache             = $GLOBALS["DB"]->executeQuery("SELECT cISO FROM tsprache WHERE cShopStandard='Y'", 1);
$Einstellungen       = getEinstellungen(array(CONF_GLOBAL, CONF_KUNDEN, CONF_KAUFABWICKLUNG, CONF_ZAHLUNGSARTEN));
$cEditZahlungHinweis = '';

/*** Session Hash ***/
$cPh = verifyGPDataString('ph');
$cSh = verifyGPDataString('sh');

// Hook
executeHook(HOOK_NOTIFY_HASHPARAMETER_DEFINITION);

if (strlen(verifyGPDataString('ph')) == 0 && strlen(verifyGPDataString('externalBDRID')) > 0) {
    $cPh = verifyGPDataString('externalBDRID');
    if ($cPh[0] == "_") {
        $cPh = "";
        $cSh = verifyGPDataString('externalBDRID');
    }
}

if (strlen($cSh) > 0) {
    Jtllog::writeLog('Notify SH: ' . print_r($_REQUEST, 1), JTLLOG_LEVEL_DEBUG, false, 'Notify');

    if (NO_MODE === 1) {
        writeLog(NO_PFAD, 'Session Hash: ' . $cSh, 1);
    }
    // Load from Session Hash / Session Hash starts with "_"
    $sessionHash    = substr(StringHandler::htmlentities(filterXSS($cSh)), 1);
    $paymentSession = $GLOBALS["DB"]->executeQuery(
        "SELECT cSID, kBestellung FROM tzahlungsession WHERE cZahlungsID='" . $sessionHash . "'",
        1
    );

    if ($paymentSession === false) {
        Jtllog::writeLog(
            'Session Hash: ' . $cSh . ' ergab keine Bestellung aus tzahlungsession',
            JTLLOG_LEVEL_ERROR,
            false,
            'Notify'
        );

        die();
    }

    Jtllog::writeLog(
        'Session Hash: ' . $cSh . ' ergab tzahlungsession ' . print_r($paymentSession, true),
        JTLLOG_LEVEL_DEBUG,
        false,
        'Notify'
    );

    if (!isset($_SESSION['Zahlungsart']) && !isset($paymentSession->kBestellung)) {
        Jtllog::writeLog(
            'Session Hash: ' . $cSh . ' ergab keine Zahlungsart nach Laden der Session ' . print_r(
                $paymentSession,
                true
            ),
            JTLLOG_LEVEL_ERROR,
            false,
            'Notify'
        );

        die();
    }

    $_COOKIE["JTLSHOP"] = $paymentSession->cSID;
    $session            = Session::getInstance();

    require_once PFAD_ROOT . PFAD_INCLUDES . 'bestellabschluss_inc.php';

    Jtllog::writeLog(
        'Session Hash: ' . $cSh . ' ergab cModulId aus Session: ' . ((isset($_SESSION['Zahlungsart']->cModulId))
            ? $_SESSION['Zahlungsart']->cModulId : '---'),
        JTLLOG_LEVEL_DEBUG,
        false,
        'Notify'
    );

    if (!$paymentSession->kBestellung && $_REQUEST['payment_status'] != 'denied') {
        // Generate fake Order and ask PaymentMethod if order should be finalized
        $order = fakeBestellung();
        include_once PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php';
        $paymentMethod = (isset($_SESSION['Zahlungsart']->cModulId)) ? PaymentMethod::create(
            $_SESSION['Zahlungsart']->cModulId
        ) : null;
        if (isset($paymentMethod)) {
            Jtllog::writeLog(
                'Session Hash: ' . $cSh . ' ergab Methode: ' . print_r($paymentMethod, true),
                JTLLOG_LEVEL_DEBUG,
                false,
                'Notify'
            );
            Jtllog::writeLog(
                'Abgelehte Bestellung PUSH.' . print_r($_REQUEST, true),
                JTLLOG_LEVEL_DEBUG,
                false,
                'Notify'
            );
            $kPlugin = gibkPluginAuscModulId($_SESSION['Zahlungsart']->cModulId);
            if ($kPlugin > 0) {
                $oPlugin            = new Plugin($kPlugin);
                $GLOBALS['oPlugin'] = $oPlugin;
            }

            if ($paymentMethod->finalizeOrder($order, $sessionHash, $_REQUEST)) {
                Jtllog::writeLog(
                    'Session Hash: ' . $cSh . ' ergab finalizeOrder passed',
                    JTLLOG_LEVEL_DEBUG,
                    false,
                    'Notify'
                );

                $order = finalisiereBestellung();
                raeumeSessionAufNachBestellung();

                if ($order->kBestellung > 0) {
                    Jtllog::writeLog('tzahlungsession aktualisiert.', JTLLOG_LEVEL_DEBUG, false, 'Notify');

                    $GLOBALS['DB']->executeQuery(
                        "UPDATE tzahlungsession
                            SET nBezahlt = 1, dZeitBezahlt=now(), kBestellung = " . $order->kBestellung . "
                            WHERE cZahlungsID = '" . $sessionHash . "'",
                        3
                    );
                    $paymentMethod->handleNotification($order, '_' . $sessionHash, $_REQUEST);
                    if ($paymentMethod->redirectOnPaymentSuccess() === true) {
                        header('Location: ' . $paymentMethod->getReturnURL($order));
                        exit();
                    }
                }
            } elseif ($_REQUEST['agws_sp_url'] == 'push') {
                Jtllog::writeLog('Abgelehte Bestellung PUSH.', JTLLOG_LEVEL_DEBUG, false, 'Notify');
                if ($_REQUEST['payment_status'] == 'denied') {
                    $agws_secupay_flex_ackreq = 'ack=disapproved&' . http_build_query($_POST);

                    $agws_secupay_flex_logtext = 'secupay_flex - function handleNotification <br />
                    ackreq erfolgreich abgehlehter PUSH<br />
                    ackreq: ' . print_r($agws_secupay_flex_ackreq, true) . ' <br/>
                    ======================';
                    Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_DEBUG);

                    die($agws_secupay_flex_ackreq);
                }
            } else {
                Jtllog::writeLog(
                    'finalizeOrder failed -> zurueck zur Zahlungsauswahl.',
                    JTLLOG_LEVEL_DEBUG,
                    false,
                    'Notify'
                );

                if (strlen($cEditZahlungHinweis) > 0) {
                    header(
                        'Location: ' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&nHinweis='
                        . $cEditZahlungHinweis . '&' . SID
                    );
                } else {
                    header('Location: ' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID);
                }
            }
        }
    } elseif ($_REQUEST['agws_sp_url'] == 'push' && $_REQUEST['payment_status'] == 'denied') {
        $agws_secupay_flex_ackreq = 'ack=approved&' . http_build_query($_POST);

        $agws_secupay_flex_logtext = 'secupay_flex - function handleNotification <br />
                    ackreq erfolgreich abgehlehter PUSH<br />
                    ackreq: ' . print_r($agws_secupay_flex_ackreq, true) . ' <br/>
                    ======================';
        Jtllog::writeLog($agws_secupay_flex_logtext, JTLLOG_LEVEL_DEBUG);

        die($agws_secupay_flex_ackreq);
    } else {
        $order = new Bestellung($paymentSession->kBestellung);
        $order->fuelleBestellung(0);
        include_once PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php';
        Jtllog::writeLog(
            'Session Hash ' . $cSh . ' hat kBestellung. Modul ' . $order->Zahlungsart->cModulId . ' wird aufgerufen',
            JTLLOG_LEVEL_DEBUG,
            false,
            'Notify'
        );

        $paymentMethod = PaymentMethod::create($order->Zahlungsart->cModulId);
        $paymentMethod->handleNotification($order, '_' . $sessionHash, $_REQUEST);
        if ($paymentMethod->redirectOnPaymentSuccess() === true) {
            header('Location: ' . $paymentMethod->getReturnURL($order));
            exit();
        }
    }

    die();
}

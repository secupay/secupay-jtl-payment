---------

{if $agws_secupay_flex_rg_due_design >= "1"}{$agws_secupay_flex_rg_due_text}{/if}

Der Rechnungsbetrag wurde an {$agws_secupay_flex_bank_recipient_legal} abgetreten.
Eine Zahlung mit schuldbefreiender Wirkung ist nur auf folgendes Konto möglich:

Empfänger: {$agws_secupay_flex_bank_accountowner}
Kontonummer: {$agws_secupay_flex_bank_ktonr}, BLZ: {$agws_secupay_flex_bank_blz}
IBAN: {$agws_secupay_flex_bank_iban}, BIC: {$agws_secupay_flex_bank_bic},

Bank: {$agws_secupay_flex_bank_bank}

secupay-Transaktion/Verwendungszweck: {$agws_secupay_flex_bank_zweck}
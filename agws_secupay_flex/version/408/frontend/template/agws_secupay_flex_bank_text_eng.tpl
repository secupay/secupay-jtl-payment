---------

{if $agws_secupay_flex_rg_due_design >= "1"}{$agws_secupay_flex_rg_due_text}{/if}

The invoice amount was assigned to  {$agws_secupay_flex_bank_recipient_legal}.
Payment with the effect of discharging the debt is only effective to the following bank account:

Beneficiary: {$agws_secupay_flex_bank_accountowner}
Account number: {$agws_secupay_flex_bank_ktonr}, BLZ: {$agws_secupay_flex_bank_blz}
IBAN: {$agws_secupay_flex_bank_iban}, BIC: {$agws_secupay_flex_bank_bic},

Bank: {$agws_secupay_flex_bank_bank}

secupay-transaction/Reference: {$agws_secupay_flex_bank_zweck}
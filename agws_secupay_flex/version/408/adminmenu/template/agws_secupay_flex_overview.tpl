{config_load file="$lang.conf" section="bestellungen"}


<script type="text/javascript" src="templates/js/checkAllMSG.js"></script>

{if $cStep == "bestellungen_uebersicht"}
    {include file=$cAdminmenuPfad|cat:"template/tpl_inc/agws_secupay_flex_overview_inc.tpl"}
{/if}
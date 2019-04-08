<script type="text/javascript">
function popup_capture (url) {ldelim}
 fenster = window.open(url, "secupayVersand", "width=680,height=450,resizable=yes");
 fenster.focus();
 return false;
{rdelim}
</script>


<div id="content">
	 {if isset($cHinweis) && $cHinweis|count_characters > 0}
		  <p class="box_success">{$cHinweis}</p>
	 {/if}
	 {if isset($cFehler) && $cFehler|count_characters > 0}			
		  <p class="box_error">{$cFehler}</p>
	 {/if}

	 {if $oBestellung_arr|@count > 0 && $oBestellung_arr}
		  
		  <div class=" block clearall">
				<div class="left">
					 {if $oBlaetterNaviUebersicht->nAktiv == 1}
						  <div class="pages tright">
								<span class="pageinfo">{#page#}: <strong>{$oBlaetterNaviUebersicht->nVon}</strong> - {$oBlaetterNaviUebersicht->nBis} {#from#} {$oBlaetterNaviUebersicht->nAnzahl}</span>
								<a class="back" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard&s1={$oBlaetterNaviUebersicht->nVoherige}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">&laquo;</a>
								{if $oBlaetterNaviUebersicht->nAnfang != 0}<a href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard&s1={$oBlaetterNaviUebersicht->nAnfang}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$oBlaetterNaviUebersicht->nAnfang}</a> ... {/if}
									 {foreach name=blaetternavi from=$oBlaetterNaviUebersicht->nBlaetterAnzahl_arr item=Blatt}
										  <a class="page {if $oBlaetterNaviUebersicht->nAktuelleSeite == $Blatt}active{/if}" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard&s1={$Blatt}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$Blatt}</a>
									 {/foreach}
								
								{if $oBlaetterNaviUebersicht->nEnde != 0}
									 ... <a class="page" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard&s1={$oBlaetterNaviUebersicht->nEnde}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$oBlaetterNaviUebersicht->nEnde}</a>
								{/if}
								<a class="next" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard&s1={$oBlaetterNaviUebersicht->nNaechste}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">&raquo;</a>
						  </div>
					 {/if}
				</div>
				<div class="right">
                <form name="bestellungen" method="post" action="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard">
                     <input type="hidden" name="{$session_name}" value="{$session_id}" />
                     <input type="hidden" name="Suche" value="1" />
					 <label for="orderSearch">{#orderSearchItem#}:</label>
					 <input name="cSuche" type="text" value="{$cSuche}" id="orderSearch" />
					 <button name="submitSuche" type="submit" class="button blue btn btn-primary">{#orderSearchBTN#}</button>
                </form>
				</div>
		  </div>
	 
		  <div class="category">{#order#}</div>
		  
		  <form name="bestellungen" method="post" action="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard">
				<input type="hidden" name="{$session_name}" value="{$session_id}" />
				<input type="hidden" name="zuruecksetzen" value="1" />
				{if isset($cSuche) && $cSuche|count_characters > 0}
					 <input type="hidden" name="cSuche" value="{$cSuche}" />
				{/if}
			  <div class="table-responsive">
				<table class="list table">
					 <thead>
						  <tr>
								<th></th>
								<th class="tleft">Best.-Nr</th>
								<th class="tleft">{#orderCostumer#}</th>
								<th class="tleft">{#orderPaymentName#}</th>
								<th>Abgeholt</th>                        
								<th>Gesamtsumme</th>
								<th class="tright">{#orderDate#}</th>
								<th class="tright">Zahldatum</th>
								<th class="tright">Status</th>
								<th class="tright">Hash</th>
								<th class="tright">TACode</th>
								<th class="tright">Betrag</th>
								<th class="tright">Success</th>
                                                                       <th class="tright">Push</th>
								<th class="tright">Versand</th>
						  </tr>
					 </thead>
					 <tbody>
						  {foreach name=bestellungen from=$oBestellung_arr item=oBestellung}
								<tr class="tab_bg{$smarty.foreach.bestellungen.iteration%2}">
									 <td class="check">{if $oBestellung->cAbgeholt == "Y" && $oBestellung->cZahlungsartName != 'Amazon Payment'}<input type="checkbox" name="kBestellung[]" value="{$oBestellung->kBestellung}" />{/if}</td>
									 <td>{$oBestellung->cBestellNr}</td>
									 <td>{if $oBestellung->oKunde->cVorname || $oBestellung->oKunde->cNachname || $oBestellung->oKunde->cFirma}{$oBestellung->oKunde->cVorname} {$oBestellung->oKunde->cNachname}{if isset($oBestellung->oKunde->cFirma) && $oBestellung->oKunde->cFirma|count_characters > 0} ({$oBestellung->oKunde->cFirma}){/if}{else}{#noAccount#}{/if}</td>
									 <!-- <td>{if $oBestellung->Zahlungsart->cName == "secupay-debit"}Lastschrift{elseif $oBestellung->Zahlungsart->cName == "secupay-credit"}Kreditkarte{elseif $oBestellung->Zahlungsart->cName == "secupay-invoice"}Rechnung{else}unbekannt{/if}</td> -->
									<td>{if $oBestellung->agws_seclog_cSecupayZA == "debit"}Lastschrift{elseif $oBestellung->agws_seclog_cSecupayZA == "creditcard"}Kreditkarte{elseif $oBestellung->agws_seclog_cSecupayZA == "invoice"}Rechnung{elseif $oBestellung->agws_seclog_cSecupayZA == "prepay"}Vorkasse{elseif $oBestellung->agws_seclog_cSecupayZA == "sofort"}SOFORT-&Uuml;berweisung{else}unbekannt{/if}</td>
									 <td class="tcenter">{if $oBestellung->cAbgeholt == "Y" AND $oBestellung->Status == "offen"}wartend{elseif $oBestellung->cAbgeholt == "Y" AND $oBestellung->Status != "offen"}{#yes#}{else}{#no#}{/if}</td>                        
									 <td class="tcenter">{$oBestellung->WarensummeLocalized[0]}</td>
									 <td class="tright">{$oBestellung->dErstelldatum_de}</td>
									 <td class="tright">{$oBestellung->dBezahldatum_de}</td>
									 <td class="tright">{if $oBestellung->Status == "bezahlt"}autorisiert{else}{$oBestellung->Status}{/if}</td>
									 <td class="tright">{$oBestellung->agws_seclog_cHash}</td>
									 <td class="tright">{$oBestellung->agws_seclog_cTACode}</td>
									 <td class="tright">{$oBestellung->agws_seclog_kAmountSecupay|string_format:"%.2f"}&nbsp;&euro;</td>
									 <td class="tright">{if $oBestellung->agws_seclog_dSuccDat|substr:0:10 == "0000-00-00"}-{else}{$oBestellung->agws_seclog_dSuccDat|date_format:"%d.%m.%Y %H:%M:%S"}{/if}</td>
									 <td class="tright">{if $oBestellung->agws_seclog_dPushDat|substr:0:10 == "0000-00-00"}-{else}{$oBestellung->agws_seclog_dPushDat|date_format:"%d.%m.%Y %H:%M:%S"}{/if}</td>
									<!-- td class="tright">{if $oBestellung->Zahlungsart->cName == "secupay-invoice"}<input type="button" onclick="return popup_capture('{$cSecupayURL}{$oBestellung->agws_seclog_cHash}/capture/{$cSecupayAPIKey}');" value="Versand">{/if}</td -->
									<td class="tright"> {if ($oBestellung->Zahlungsart->cName == "secupay-invoice" || $oBestellung->Zahlungsart->cName == "Rechnung") AND $oBestellung->cAbgeholt == "Y" AND $oBestellung->Status != "offen"}
															{if $oBestellung->agws_seclog_dVersandDat|substr:0:10 == "0000-00-00"}
																<input type="button" onclick="return popup_capture('{$cSecupayURL}{$oBestellung->agws_seclog_cHash}/capture/{$cSecupayAPIKey}');" value="Versand">
															{else}
																<span style="color:#00C000">{$oBestellung->agws_seclog_dVersandDat|date_format:"%d.%m.%Y %H:%M:%S"}</span>
															{/if}
														{else}
															{if $oBestellung->agws_seclog_dVersandDat|substr:0:10 == "0000-00-00"}
																<span style="color:darkgrey;"><small>nicht versendet</small></span>
															{else}
																<span style="color:darkgrey;">{$oBestellung->agws_seclog_dVersandDat|date_format:"%d.%m.%Y %H:%M:%S"}</span>
															{/if}
														{/if}</td>
                                    <!-- td class="tright">{if $oBestellung->Zahlungsart->cName == "secupay-invoice"}{if $oBestellung->agws_seclog_dVersandDat|substr:0:10 == "0000-00-00"}<span style="color:#FF0000">offen</span>{else}<span style="color:#00C000">{$oBestellung->agws_seclog_dVersandDat|date_format:"%d.%m.%Y %H:%M:%S"}</span>{/if}{else}<span style="color:#0000FF">entf&auml;llt</span>{/if}</td -->
								</tr>
						  {/foreach}
					 </tbody>
					 <tfoot>
						  <tr>
							 <td class="check"><input name="ALLMSGS" id="ALLMSGS" type="checkbox" onclick="AllMessages(this.form);" /></td>
								<td colspan="14"><label for="ALLMSGS">Alle ausw&auml;hlen</label></td>
						 </tr>
					 </tfoot>
				</table>
				  </div>
				<div class="save_wrapper">
					 <button name="zuruecksetzenBTN" type="submit" class="button orange">WaWi-Freigabe</button>
					 <a href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard" type="submit" class="button blue btn btn-primary right">Anzeige aktualisieren</a>
				</div>
		  </form>
	 {else}
		  <div class=" block clearall">
				<div class="left"> </div>
				<div class="right">
                <form name="bestellungen" method="post" action="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard">
                     <input type="hidden" name="{$session_name}" value="{$session_id}" />
                     <input type="hidden" name="Suche" value="1" />
					 <label for="orderSearch">{#orderSearchItem#}:</label>
					 <input name="cSuche" type="text" value="{$cSuche}" id="orderSearch" />
					 <button name="submitSuche" type="submit" class="button blue btn btn-primary">{#orderSearchBTN#}</button>
                </form>
				</div>
		  </div>
	 
		  <div class="category">{#order#}</div>
		  <div>Keine Daten vorhanden
					 <a href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Dashboard" type="submit" class="button blue btn btn-primary right">Anzeige aktualisieren</a>
		</div>
	  {/if}
</div>
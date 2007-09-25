{*
template incluso.
Visualizza il form di una Sezione.
*}
{php}$vs = &$this->get_template_vars() ;{/php}

<script type="text/javascript">
{literal}

var langs = {
{/literal}
	{foreach name=i from=$conf->langOptions key=lang item=label}
	"{$lang}":	"{$label}" {if !($smarty.foreach.i.last)},{/if} 
	{/foreach}
{literal}

} ;

var validate = null ;

$(document).ready(function(){

	validateFrm = $("#updateform").validate({
		debug:false,
		errorLabelContainer: $("#errorForm"),
		errorClass: "errorFieldForm", 
		rules: {
			"data[title]"		: "required",
			"data[destination]" : "required"
		},
		messages: {
			"data[title]"		: "Il titolo &egrave; obbligatorio",
			"data[destination]" : "Seleziona dove inserire la sezione",
		}	
	});	
	
	$("#updateform//input[@name=cancella]").bind("click", function() {
		if(!confirm("Attenzione!!! operazione potenzialmente dannosa.\nSicuro di voler continuare?")) {
			return false ;
		}
		
		document.location = "{/literal}{$html->url('deleteSection/')}{$section.id}{literal}" ;
	}) ;
	
	
	$("#updateform").bind("submit", function() {
		// se ci sono stati errori, stampa un messaggio
		if(validateFrm.errorList.length) {
			alert(validateFrm.errorList[0].message) ;
		}
	}) ;

	// Aggiunta traduzioni linguistiche dei campi
	$("#cmdTranslateTitle").addTranslateField('title', langs) ;

});

{/literal}
</script>	

<div id="containerPage">
		{formHelper fnc="create" args="'area', array('id' => 'updateform', 'action' => 'saveSection', 'type' => 'POST', 'enctype' => 'multipart/form-data')"}
		<input  type="hidden" name="data[id]" value="{$section.id|default:''}" >
		<div class="FormPageHeader">
			<h1>{$section.title|default:"nuova sezione"}</h1>
	
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<a id="openAllBlockLabel" style="display:block" href="javascript:showAllBlockPage(1)"><b>&#155; </b>apri tutti i dettagli</a>
						<a id="closeAllBlockLabel" href="javascript:hideAllBlockPage()"><b>&#155; </b>chiudi tutti i dettagli</a>
					</td>
					<td style="padding-left:40px;" nowrap>
						{formHelper fnc="submit" args="' salva ', array('name' => 'save', 'class' => 'submit', 'div' => false)"}
						<input type="button" name="cancella" class="submit" value="cancella" />
					</td>
					<td style="padding-left:40px">
						&nbsp;
					</td>
				</tr>
			</table>
		</div>
		<div class="blockForm" id="errorForm"></div>

		<h2 class="showHideBlockButton">Propriet&agrave;</h2>
		<div class="blockForm" id="proprieta">
			<b>status</b>:
			{html_radios name="status" options=$conf->statusOptions selected=$section.status|default:$conf->status separator=" "}
			<hr>
				<table class="tableForm" border="0">
					<tr id="">
						<td class="label">Lingua:</td>
						<td>
							<select name="lang">
								{html_options options=$conf->langOptions selected=$section.lang|default:$conf->lang}
							</select>							
						</td>
						<td>
						&nbsp;
						</td>
					</tr>

					<tr id="Title_TR_{$section.lang|default:$conf->lang}">
						<td class="label">Titolo:</td>
						<td>
							<input  class="{literal}{required:true}{/literal}" id="titleInput"  type="text" name="data[title]" value="{$section.title|default:''|escape:'html'|escape:'quotes'}" >&nbsp;
						</td>
						{if ($section)}					
						<td>
							<input class="cmdField" id="cmdTranslateTitle" type="button" value="lang ...">
						</td>
						{/if}
					</tr>
					{if (isset($section.LangText.title))}
					{foreach name=i from=$section.LangText.title key=lang item=text}
						<tr id="">
							<td class="label"></td>
							<td>
								<input type='hidden' value='title' name="data[LangText][{$smarty.foreach.i.iteration}][name]">
								<input type="text" name="data[LangText][{$smarty.foreach.i.iteration}][txt]" value="{$text|escape:'html'|escape:'quotes'}" >&nbsp;
							</td>
							<td>
								<select name="data[LangText][{$smarty.foreach.i.iteration}][lang]">
								{html_options options=$conf->langOptions selected=$lang}
								</select>
								&nbsp;&nbsp;
								<input type="button" name="delete" value=" x " onclick=" $('../..', this).remove() ; ">
							</td>
					</tr>
					{/foreach}
					{/if}
				</table>
				{if ($section)}
				<hr>
				<table class="tableForm" border="0">
					<tr>
						<td class="label">Alias:</td>
						<td>{$section.nickname}</td>
					</tr>
					<tr>
						<td class="label">Creata il:</td>
						<td>{$section.created|date_format:$conf->date_format}</td>
					</tr>
					<tr>
						<td class="label">Da:</td>
						<td>{$section.UserCreated.userid|default:""}</td>
					</tr>
					<tr>
						<td class="label">Ultima modifica:</td>
						<td>{$section.modified|date_format:$conf->date_format}</td>
					</tr>
					<tr>
						<td class="label">Da:</td>
						<td>{$section.UserModified.userid|default:""}</td>
					</tr>
					<tr>
						<td class="label">IP:</td>
						<td>{$section.IP_created}</td>
					</tr>
				</table>
			{/if}
		</div>
		
		<h2 class="showHideBlockButton">Dove inserire o spostare la sezione</h2>
		<div class="blockForm" id="dove">
					<div id="treecontrol">
						<a href="#">Chiudi tutti</a>
						<a href="#">Espandi tutto</a>
					</div>
					{$beTree->tree("tree", $tree)}
					
			<!-- textarea id="debug" cols="120" rows="60"></textarea -->
		</div>

		<h2 class="showHideBlockButton">Propriet&agrave; Custom</h2>
		<div class="blockForm" id="proprietaCustom">
			{include file="../pages/form_custom_properties.tpl" el=$section}
		</div>

		<h2 class="showHideBlockButton">Permessi</h2>
		<div class="blockForm" id="permessi">
		{include file="../pages/form_permissions.tpl" el=$section recursion=true}
		</div>

		</form>			
	</div>


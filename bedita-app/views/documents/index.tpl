{*
Template Documents.
*}
{php}
$vs = &$this->get_template_vars() ;
//pr($vs["html"]->params);
//exit;
{/php}
</head>
<body>
	<div id="header">
		{include file="head.tpl"}
	</div>

<table border="0" cellspacing="0" cellpadding="0" class="mainTable">
	<tr>
		<td>
		{* Comandi a SX  *}	
		{include file="_incl_menu.tpl" sez="indice" firstContent=$Documents.items[0]}
		</td>	
		<td>
		{* BEGIN -- Main Content *}
		{if ($session->check('Message.flash'))}{$session->flash()}{/if}

		{include file="toolbarList.tpl" sez="menuCentro" toolbar=$Documents.toolbar dim=$html->params.url.dim}
		
		<div class="gest_menuLeft" style="float:left;">
		{include file="areeGruppiTree.tpl" Groups=$Sections}
		</div>
		
		{include file="contentsList.tpl" Lists=$Documents}
		
		<br><br>
		<input type="button" onClick="document.location ='./frmAdd'" value="aggiungi nuovo documento" style="margin:10px;">
		
		{include file="toolbarList.tpl" sez="menuCentro" toolbar=$Documents.toolbar dim=$html->params.dim}
		
		{* END -- Main Content *}
		</td>	
	</tr>
</table>


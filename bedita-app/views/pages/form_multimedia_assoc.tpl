<!-- start upload block-->
<script type="text/javascript">
<!--
{literal}
function addItemsToParent() {
	var itemsIds = new Array() ;
	$(":checkbox").each(function() {
		try {
			if(this.checked && this.name == 'chk_bedita_item') { itemsIds[itemsIds.length] = $(this).attr("value") ;}
		} catch(e) {
		}
	}) ;
	for(i=0;i<itemsIds.length;i++) {
		$("#tr_"+itemsIds[i]).remove();
	}

	{/literal}{$relation}CommitUploadById(itemsIds, '{$relation}'){literal};
}

function loadMultimediaAssoc(urlSearch, showAll) {
	$("#loading").show();
	$("#ajaxSubcontainer").load(urlSearch, function() {
		$("#loading").hide();
		if (showAll) 
			$("#searchMultimediaShowAll").show();
		else
			$("#searchMultimediaShowAll").hide();
	});
}

$(document).ready(function(){
	$(".selItems").bind("click", function(){
		var check = $("input:checkbox",$(this).parent().parent()).get(0).checked ;
		$("input:checkbox",$(this).parent().parent()).get(0).checked = !check ;
	}) ;
	/* select/unselect each item's checkbox */
	$(".selectAll").bind("click", function(e) {
		var status = this.checked;
		$(".itemCheck").each(function() { this.checked = status; });
	}) ;
	/* select/unselect main checkbox if all item's checkboxes are checked */
	$(".itemCheck").bind("click", function(e) {
		var status = true;
		$(".itemCheck").each(function() { if (!this.checked) return status = false;});
		$(".selectAll").each(function() { this.checked = status;});
	}) ;
	
	$("#searchMultimedia").bind("click", function() {
		var textToSearch = $(this).prev().val();
		loadMultimediaAssoc(
			"{/literal}{$html->url("/streams/searchStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{literal}" + textToSearch,
			true
		);
	});
	$("#searchMultimediaText").focus(function() {
		$(this).val("");
	});
	$("#searchMultimediaShowAll").click(function() {
		loadMultimediaAssoc(
			"{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{literal}",
			false
		);
	});
	
	{/literal}
	{if $toolbar|default:""}
	{literal}
		$("#streamPagList").tablesorter({
			headers: {  
				0: {sorter: false},
				2: {sorter: false}
			}
		});
		 
		$("#streamNextPage").click(function() {
			urlReq = "{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{$toolbar.next}/{$toolbar.dim}{literal}";
			loadMultimediaAssoc(urlReq,	false);
		});
		$("#streamPrevPage").click(function() {
			urlReq = "{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{$toolbar.prev}/{$toolbar.dim}{literal}";
			loadMultimediaAssoc(urlReq,	false);
		});
		$("#streamFirstPage").click(function() {
			urlReq = "{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{$toolbar.first}/{$toolbar.dim}{literal}";
			loadMultimediaAssoc(urlReq,	false);
		});
		$("#streamLastPage").click(function() {
			urlReq = "{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{$toolbar.last}/{$toolbar.dim}{literal}";
			loadMultimediaAssoc(urlReq,	false);
		});
		$("#streamPagDim").change(function() {
			urlReq = "{/literal}{$html->url("/streams/showStreams")}/{$object_id|default:'0'}/{$collection|default:'0'}/{$toolbar.first}/{literal}" + $(this).val();
			loadMultimediaAssoc(urlReq,	false);
		});
	{/literal}
	{/if}
	{literal}
});
//-->
{/literal}
</script>

<div id="formMultimediaAssoc">
	<fieldset>
		<div>
			<input type="text" id="searchMultimediaText" name="searchMultimediaItems" value="{$streamSearched|default:'search'}"/>
			<input id="searchMultimedia" type="button" value="{t}Search{/t}"/>
			<input type="button" id="searchMultimediaShowAll" value="{t}Show all{/t}" style="display: none;" />
		</div>
		{if !empty($items)}
			{if $toolbar|default:""}
				<p class="toolbar">
	
					{t}Items{/t}: {$toolbar.size} | {t}page{/t} {$toolbar.page} {t}of{/t} {$toolbar.pages} &nbsp;
					
					{if $toolbar.prev > 0}
						<span><a href="javascript: void(0);" id="streamFirstPage" title="{t}first page{/t}">|<</a></span>
						<span><a href="javascript: void(0);" id="streamPrevPage" title="{t}previous page{/t}"><</a></span>
					{else}
						<span>|<</span>
						<span><</span>
					{/if}
					&nbsp;
					{if $toolbar.page != $toolbar.pages}
						<span><a href="javascript: void(0);" id="streamNextPage" title="{t}next page{/t}">></a></span>
						<span><a href="javascript: void(0);" id="streamLastPage" title="{t}last page{/t}">>|</a></span>
					{else}
						<span>></span>
						<span>>|</span>
					{/if}		
					{t}Dimensions{/t}: 
					<select name="streamPagDim" id="streamPagDim">
						<option value="1"{if $toolbar.dim == 1} selected="selected"{/if}>1</option>
						<option value="5"{if $toolbar.dim == 5} selected="selected"{/if}>5</option>
						<option value="10"{if $toolbar.dim == 10} selected="selected"{/if}>10</option>
						<option value="20"{if $toolbar.dim == 20} selected="selected"{/if}>20</option>
						<option value="50"{if $toolbar.dim == 50} selected="selected"{/if}>50</option>
						<option value="100"{if $toolbar.dim == 100} selected="selected"{/if}>100</option>
					</select>
				</p>
			{/if}
			<input type="button" onclick="javascript:addItemsToParent();" value=" (+) {t}Add selected items{/t}"/>
			<table class="indexList" id="streamPagList" style="clear: left;">
			<thead>
			<tr>
				<th><input type="checkbox" class="selectAll" id="selectAll"/><label for="selectAll"> {t}(Un)Select All{/t}</label></th>
				<th>id</th>
				<th>{t}Thumb{/t}</th>
				<th>{t}Title{/t}</th>
				<th>{t}File name{/t}</th>
				<th>{t}File size{/t}</th>
				<th>{t}Language{/t}</th>
			</tr>
			</thead>

			<tbody>
			{foreach from=$items item='mobj' key='mkey'}
			<tr class="rowList" id="tr_{$mobj.id}">
				<td><input type="checkbox" value="{$mobj.id}" name="chk_bedita_item" class="itemCheck"/></td>
				<td><a class="selItems" href="javascript:void(0);">{$mobj.id}</a></td>
				<td>
				{assign var="thumbWidth" 		value = 30}
				{assign var="thumbHeight" 		value = 30}
				{assign var="filePath"			value = $mobj.path}
				{assign var="mediaPath"         value = $conf->mediaRoot}
				{assign_concat var="mediaCacheBaseURL"	0=$conf->mediaUrl  1="/" 2=$conf->imgCache 3="/"}
				{assign_concat var="mediaCachePATH"		0=$conf->mediaRoot 1=$conf->DS 2=$conf->imgCache 3=$conf->DS}

				{if strtolower($mobj.ObjectType.name) == "image"}
					{thumb 
						width			= $thumbWidth
						height			= $thumbHeight
						file			= $mediaPath$filePath
						cache			= $mediaCacheBaseURL
						cachePATH		= $mediaCachePATH
					}
				{elseif ($mobj.provider|default:false)}
					{assign_associative var="attributes" style="width:30px;heigth:30px;"}
					<div><a href="{$filePath}" target="_blank">{$mediaProvider->thumbnail($mobj, $attributes) }</a></div>
				{else}
					<div><a href="{$conf->mediaUrl}{$filePath}" target="_blank"><img src="{$session->webroot}img/mime/{$mobj.type}.gif" /></a></div>
				{/if}
				</td>
				<td>{$mobj.title|default:""}</td>
				<td>{$mobj.name|default:""}</td>
				<td>{$mobj.size|default:""}</td>
				<td>{$mobj.lang}</td>
			</tr>
			{/foreach}
			</tbody>
			</table>
			<input type="button" onclick="javascript:addItemsToParent();" value=" (+) {t}Add selected items{/t}"/>
		{else}
			{t}No {$itemType} item found{/t}
		{/if}
	</fieldset>
	
</div>
<!-- end upload block -->
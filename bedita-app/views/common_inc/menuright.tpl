{*
Template incluso.
Menu a DX
*}

<script type="text/javascript">
var urlLoadNote = "{$html->url('/pages/loadNote')}";
var urlDelNote = "{$html->url('/pages/deleteNote')}";
var comunicationErrorMsg = "{t}Communication error{/t}";
var confirmDelNoteMsg = "{t}Are you sure that you want to delete the note?{/t}";

{literal}
$(document).ready( function (){
	$("#editornotes").prev(".tab").BEtabsopen();

	var optionsNoteForm = {
		beforeSubmit: function() {$("#noteloader").show();},
		success: showNoteResponse,
		dataType: "json",
		resetForm: true,
		error: function() {
			alert(comunicationErrorMsg);
			$("#noteloader").hide();
		}
	}; 
	$("#saveNote").ajaxForm(optionsNoteForm);
	
	$("#listNote").find("input[@name=deletenote]").click(function() {
		refreshNoteList($(this));
	});
});	

function showNoteResponse(data) {
	if (data.errorMsg) {
		alert(data.errorMsg);
		$("#noteloader").hide();
	} else {
		var emptyDiv = "<div><\/div>";
		$(emptyDiv).load(urlLoadNote, data, function() {
			$("#listNote").prepend(this);
			$("#noteloader").hide();
			$(this).find("input[@name=deletenote]").click(function() {
				refreshNoteList($(this));
			});
		});
	}
}

function refreshNoteList(delButton) {
	var div = delButton.parents("div:first");
	var postdata = {id: delButton.attr("rel")};
	if (confirm(confirmDelNoteMsg)) {
		$.ajax({
			type: "POST",
			url: urlDelNote,
			data: postdata,
			dataType: "json",
			beforeSend: function() {$("#noteloader").show();},
			success: function(data){
				if (data.errorMsg) {
					alert(data.errorMsg);
					$("#noteloader").hide();
				} else {
					$("#noteloader").hide();
					div.remove();
				}
			},
			error: function() {
				alert(comunicationErrorMsg);
				$("#noteloader").hide();
			}
		});
	}
}
{/literal}
</script>


<div class="quartacolonna">	
	
	<div class="tab"><h2>{t}Editors Notes{/t}</h2></div>
<!-- old notes 
	<div id="editornotes" style="margin-top:-10px; padding:10px; background-color:white;">
	{strip}
		<label>{t}editor notes{/t}:</label>
		<textarea name="data[note]" class="autogrowarea editornotes">
		  {$object.note|default:''}
		</textarea>
	{/strip}
	</div>
 end old notes -->
 
	<div id="editornotes" style="margin-top:-10px; padding:10px; background-color:white;">
	{*dump var=$object.EditorNote|@array_reverse*}
	{strip}

		<table class="ultracondensed" style="width:100%">
		<tr>
			<td class="author">you</td>
			<td class="date">now</td>
			<td><img src="{$html->webroot}img/iconNotes.gif" alt="notes" /></td>
		</tr>
		</table>
		<form id="saveNote" action="{$html->url('/pages/saveNote')}" method="post">
		<input type="hidden" name="data[object_id]" value="{$object.id}"/>
		<textarea id="notetext" name="data[description]" class="autogrowarea editornotes"></textarea>
		<input type="submit" style="margin-bottom:10px; margin-top:5px" value="{t}send{/t}" />
		</form>
		
		<div class="loader" id="noteloader" style="clear:both">&nbsp;</div>
	
		<div id="listNote">
		{if (!empty($object.EditorNote))}
			{foreach from=$object.EditorNote|@array_reverse item="note"}
				{include file="../common_inc/single_note.tpl"}
			{/foreach}
		{/if}
		</div>
	
	{/strip}
	 
	{bedev}
	{include file="../common_inc/BEiconstest.tpl}	
	{/bedev}
	
	</div>
</div>



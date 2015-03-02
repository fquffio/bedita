{*
https://dev.channelweb.it/bedita/ticket/156
https://dev.channelweb.it/bedita/ticket/157
*}

{$permModes = $conf->objectPermissions.modes}
{$permLevels = $conf->objectPermissions.levels|@array_flip}

<script type="text/javascript">
var urlLoad = '{$html->url('/pages/loadUsersGroupsAjax')}',
    permissionLoaded = false,
    permissions = {
        'modes': [{foreach $permModes as $mode}'{$mode}'{if !$mode@last}, {/if}{/foreach}],
        'levels': { {foreach $permLevels as $val => $desc}{$val}: '{$desc}'{if !$desc@last}, {/if}{/foreach} },
    };

$(document).ready(function(){

    var $permTab = $('#permissionsTab'),
        $permTbl = $('#frmCustomPermissions'),
        $permFrm = $('#selCustomPermissions');

    $permTab.click(function() {
        if (!permissionLoaded) {
            loadUserGroupAjax(urlLoad);
        }
    });
    if ($permTab.find('h2').hasClass('open')) {
        loadUserGroupAjax(urlLoad);
    }

    $("#cmdAddGroupPerm").click(function() {
        var groupName = $permFrm.find('[data-name="group"]').val(),
            swtch = 'group',
            index = $permTbl.find('tbody tr:last').attr('id');

        // Find next available index.
        if (typeof index === 'undefined') {
            index = 0;
        } else {
            index = parseInt(index.split('_')[1]);
        }
        index++;
        var inputName = 'data[Permission][' + index + ']';

        // Permission level selection.
        var $select = $('<select>');
        for (var level in permissions.levels) {
            $('<option>').val(level).text(permissions.levels[level]).appendTo($select);
        }

        // Build output.
        var $tr = $('<tr>').attr('id', 'permTR_' + index);
        $('<td>').text(groupName)
            .append($('<input>').attr('type', 'hidden').attr('name', inputName + '[name]').val(groupName))
            .append($('<input>').attr('type', 'hidden').attr('name', inputName + '[switch]').val(swtch))
            .appendTo($tr);
        for (var i in permissions.modes) {
            $('<td>')
                .append(
                    $select.clone().attr('name', inputName + '[flag][' + permissions.modes[i] + ']')
                )
                .appendTo($tr);
        }
        $('<td>')
            .append($('<input>').attr('type', 'checkbox').attr('name', inputName + '[flag][noinherit]').val(1))
            .appendTo($tr);
        $('<td>').css('text-align', 'right')
            .append($('<input>').attr('type', 'button').attr('name', 'deletePerms').val(' x '))
            .appendTo($tr);

        $permTbl.find('tbody').append($tr);
        $tr.find('select').select2();
    });

    $permTbl.on('click', 'input[type="button"]', function() {
        $(this).closest('tr').remove();
    });
});

function loadUserGroupAjax(url) {
	$("#loaderug").show();
	$("#inputAddPermGroup").load(url, { itype: 'group' }, function() {
		$("#loaderug").hide();
		permissionLoaded = true;
	});
}
</script>

{if empty($el)}{$el = $object|default:[]}{/if}
{$relcount = $el.Permission|@count|default:0}
<div class="tab" id="permissionsTab">
    <h2 {if !$relcount}class="empty"{/if}>
        {t}Permissions{/t}{if $relcount} &nbsp; <span class="relnumb">{$relcount}</span>{/if}

    </h2>
</div>

<fieldset id="permissions">
<div class="loader" id="loaderug"></div>

<table class="indexlist" border="0" id="frmCustomPermissions">
	<thead>
		<tr>
			<th>{t}name{/t}</th>
{foreach $permModes as $mode}
			<th>{$mode}</th>
{/foreach}
            <th>{t}stop inheritance{/t}</th>
			<th></th>
		</tr>
	</thead>

{if !empty($el.Permission)}

{foreach $el.Permission as $perm}
{$i = $perm@iteration}
        <tr id="permTR_{$i}">
            <td>
                {$perm.name}
                <input type="hidden" name="data[Permission][{$i}][name]" value="{$perm.name|escape:'quotes'}"/>
                <input type="hidden" name="data[Permission][{$i}][switch]" value="{$perm.switch|escape:'quotes'}"/>
            </td>
{foreach $permModes as $mode}
            <td><select name="data[Permission][{$i}][flag][{$mode}]" readonly="readonly">{html_options options=$permLevels selected=$perm.parsedFlag.$mode}</select></td>
{/foreach}
            <td><input type="checkbox" name="data[Permission][{$i}][flag][noinherit]" value="1" readonly="readonly" {if $perm.parsedFlag.noinherit}checked="checked" {/if}/></td>
            <td style="text-align: right">
                <input type="hidden" name="data[Permission][{$i}][flag]" value="{$perm.flag}"/>
                <input type="button" name="deletePerms" value=" x "/>
            </td>
        </tr>
{/foreach}
{else}
    <tr class="trick">
        <td colspan="3">{t}No permission set{/t}</td>
    </tr>
{/if}
</table>

<table class="" border="0" style="margin-top: 20px" id="selCustomPermissions">
    <tr id="addPermGroupTR" class="ignore">
        <td style="white-space:nowrap">
            <label>{t}add group{/t}: <select data-placeholder="{t}select a group{/t}" id="inputAddPermGroup" name="name" data-name="group"></select></label>
        </td>

        <td style="text-align: right"><input type="button" id="cmdAddGroupPerm" value=" {t}add{/t} "/></td>
    </tr>
</table>
</fieldset>

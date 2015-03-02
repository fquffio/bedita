{*
https://dev.channelweb.it/bedita/ticket/156
https://dev.channelweb.it/bedita/ticket/157
*}

{$permModes = $conf->objectPermissions.modes}
{$permLevels = $conf->objectPermissions.levels}

<script type="text/javascript">
var urlLoad = '{$html->url('/pages/loadUsersGroupsAjax')}',
    permissionLoaded = false,
    permissions = {
        'modes': [{foreach $permModes as $mode}'{$mode}'{if !$mode@last}, {/if}{/foreach}],
        'levels': { {foreach $permLevels as $desc => $val}{$val}: '{$desc}'{if !$val@last}, {/if}{/foreach} },
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

        // Build flag and label.
        var label = [],
            flag = {};
        for (var i in permissions.modes) {
            var mode = permissions.modes[i],
                val = $permFrm.find('[data-name="' + mode + '"]').val();

            flag[mode] = val;
            label.push(mode + ': ' + permissions.levels[val]);
        }
        var noinherit = $permFrm.find('[data-name="noinherit"]').prop('checked') ? 1 : 0;
        flag['noinherit'] = noinherit;
        label.push('stop inheritance: ' + noinherit);

        // Build output.
        var $tr = $('<tr>').attr('id', 'permTR_' + index);
        $('<td>').text(groupName).appendTo($tr);
        $('<td>').text(label.join('; ')).appendTo($tr);
        $('<td>').css('text-align', 'right')
            .append($('<input>').attr('type', 'hidden').attr('name', inputName + '[name]').val(groupName))
            .append($('<input>').attr('type', 'hidden').attr('name', inputName + '[switch]').val(swtch))
            .append(function () {
                var res = [];
                for (var mode in flag) {
                    res.push($('<input>').attr('type', 'hidden').attr('name', inputName + '[flag][' + mode + ']').val(flag[mode]));
                }
                return res;
            })
            .append($('<input>').attr('type', 'button').attr('name', 'deletePerms').val(' x '))
            .appendTo($tr);

        $permTbl.find('tbody').append($tr);
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
			<th>{t}permission{/t}</th>
			<th></th>
		</tr>
	</thead>

{if !empty($el.Permission)}

{foreach $el.Permission as $perm}
{$i = $perm@iteration}
		<tr id="permTR_{$i}">
			<td>{$perm.name}</td>
			<td>
                {$perm.flag}
			{*{assign var="objPermReverse" value=$conf->objectPermissions|@array_flip}
			{t}{$objPermReverse[$perm.flag]}{/t}*}
			</td>
			<td style="text-align: right">
				<input type="hidden" name="data[Permission][{$i}][flag]" value="{$perm.flag}"/>
				<input type="hidden" name="data[Permission][{$i}][switch]" value="{$perm.switch|escape:'quotes'}"/>
				<input type="hidden" name="data[Permission][{$i}][name]" value="{$perm.name|escape:'quotes'}"/>
				<input type="button" name="deletePerms" value=" x "/>
			</td>
		</tr>
{/foreach}
{else}
    <tr class="trick">
        <td></td>
        <td></td>
        <td></td>
    </tr>
{/if}
</table>

<table class="" border="0" style="margin-top: 20px" id="selCustomPermissions">
    <tr id="addPermGroupTR" class="ignore">
        <td style="white-space:nowrap">
            <label>{t}add group{/t}: <select data-placeholder="{t}select a group{/t}" id="inputAddPermGroup" name="name" data-name="group"></select></label>
        </td>

{foreach $permModes as $mode}
        <td>
            <label>
                {t}{$mode}{/t}:
                <select data-placeholder="{t}select a permission type{/t}" id="selectGroupPermission-{$mode}" name="flag[{$mode}]" data-name="{$mode}">{html_options options=$permLevels|@array_flip}</select>
            </label>
        </td>
{/foreach}

        <td style="white-space:nowrap">
            <label>{t}stop inheritance{/t}: <input type="checkbox" id="selectGroupPermission-noinherit" name="flag[noinherit]" data-name="noinherit" /></label>
        </td>

        <td style="text-align: right"><input type="button" id="cmdAddGroupPerm" value=" {t}add{/t} "/></td>
    </tr>
</table>
</fieldset>

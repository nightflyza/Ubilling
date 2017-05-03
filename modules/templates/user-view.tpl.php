<?php $user = &$tpldata['userdata']?>
<table cellspacing="1" cellpadding="2" border="0" class="blackborder" style="width: 100%;">
<tr>
    <th><?=__('Username')?></td>
    <td class="row3"><?=$user['username']?></td>
</tr>
<tr>
    <th><?=__('Nickname')?></td>
    <td class="row3"><?=$user['nickname']?></td>
</tr>
<tr>
    <th><?=__('E-mail')?></td>
    <td class="row3"><?=(!$user['hideemail']) ? ('<a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a>') :  __('This field is hidden')?></td>
</tr>
<?php foreach ($tpldata['fields'] as $field_id => $field_name) { 
	if(!empty($user[$field_id])){ ?>
<tr>
    <th><?=__(@$field_name)?></td>
    <td class="row3"><?=rcms_parse_text($user[$field_id], false, false, false, false)?></td>
</tr>
<?php }} ?>
<?php if (!pm_disabled()) print '<tr><th>&nbsp;</th><td class="row3"><a href="?module=pm&for='.$user['username'].'">'.__('Send private message').'</a></td></tr>'; ?>
</table>
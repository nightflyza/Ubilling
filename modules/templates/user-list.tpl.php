<?php
if(!empty($system->config['perpage'])) {
    $pages = ceil(sizeof($tpldata)/$system->config['perpage']);
    if(!empty($_GET['page']) && ((int) $_GET['page']) > 0) $page = ((int) $_GET['page'])-1; else $page = 0;
    $start = $page * $system->config['perpage'];
    $total = $system->config['perpage'];
} else {
    $pages = 1;
    $page = 0;
    $start = 0;
    $total = sizeof($tpldata);
}
$keys = array_keys($tpldata);
?>
<div align="right"><?=rcms_pagination(sizeof($tpldata), $system->config['perpage'], $page+1, '?module=user.list')?></div>
<table cellspacing="1" cellpadding="2" border="0" class="blackborder" style="width: 100%;">
<tr>
    <th width="50%"><?=__('Nickname')?></th>
    <th width="50%"><?=__('E-mail')?></th>
</tr>
<?php
$i=1; for ($c = $start; $c < $total+$start; $c++){
    if(!empty($keys[$c])){
        $user = &$tpldata[$keys[$c]];
        if(!empty($user)) {?>
<tr>
    <td class="row<?=$i?>"><?=user_create_link($user['username'], $user['nickname'])?></td>
    <td class="row<?=$i?>"><?=(!$user['hideemail']) ? ('<a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a>') : __('This field is hidden')?></td>
</tr>
<?php $i++; if($i>3) $i=1; }}}?>
</table>
<div align="left"><?=rcms_pagination(sizeof($tpldata), $system->config['perpage'], $page+1, '?module=user.list')?></div>

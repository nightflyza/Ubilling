<?php if(!LOGGED_IN) {?>
<form method="post" action="">
<input type="hidden" name="password_request" value="1" />
<table cellpadding="2" cellspacing="1" style="width: 100%;">
<tr>
    <td class="row1"><?=__('Username')?></td>
    <td class="row1"><input type="text" name="name" style="width: 90%; text-align:left;" /></td>
</tr>
<tr>
    <td class="row1"><?=__('E-mail')?></td>
    <td class="row1"><input type="text" name="email" style="width: 90%; text-align:left;" /></td>
</tr>
<tr>
    <td class="row2" colspan="2"><input type="submit" value="<?=__('Send new password')?>" /></td>
</tr>
</table>
</form>
<?php } ?>
<form method="post" action="">
<?php if(!LOGGED_IN) {?>
<input type="hidden" name="login_form" value="1" />
<script language="javasript" type="text/javascript">
function showhide(obj){
    if(obj == 'none') return 'inline';
    else return 'none';
}
</script>
<table cellpadding="2" cellspacing="1" style="width: 100%;">
<tr>
    <td class="row2" colspan="2"><a href="#" onclick="document.getElementById('tablelogin').style.display = showhide(document.getElementById('tablelogin').style.display)"><?=__('Log in')?></a></td>
</tr>
</table>
<table cellpadding="2" cellspacing="1" id="tablelogin" style="display: none; width: 100%;">
<tr>
    <td class="row1"><?=__('Username')?>:</td>
    <td class="row1" style="width: 100%;"><input type="text" name="username" style="text-align: left; width: 95%;" /></td>
</tr>
<tr>
    <td class="row1"><?=__('Password')?>:</td>
    <td class="row1" style="width: 100%;"><input type="password" name="password" style="text-align: left; width: 95%;" /></td>
</tr>
<tr>
    <td class="row1" colspan="2">
        <input type="checkbox" name="remember" id="remember" value="1" />
        <label for="remember"><?=__('Remember me')?></label>
    </td>
</tr>
<tr>
    <td class="row2" colspan="2"><input type="submit" value="<?=__('Log in')?>" /></td>
</tr>
</table>
</form>
<form method="post" action="">
<table cellpadding="2" cellspacing="1" style="width: 100%;">
<tr>
    <td class="row2" colspan="2"><a href="#" onclick="document.getElementById('ident').style.display = showhide(document.getElementById('ident').style.display)"><?=__('Identify myself')?></a></td>
</tr>
</table>
<table cellpadding="2" cellspacing="1" id="ident" style="display: none; width: 100%;">
<tr>
    <td class="row1"><?=__('Nickname')?>:</td>
    <td class="row1" style="width: 100%;"><input type="text" name="gst_nick" style="text-align:left; width: 95%;" value="<?=$system->user['nickname']?>"/></td>
</tr>
<tr>
    <td class="row2" colspan="2"><input type="submit" value="<?=__('Submit')?>" /></td>
</tr>
</table>
<table cellpadding="2" cellspacing="1" style="width: 100%;">
<tr>
    <td class="row2" colspan="2"><a href="?module=user.profile&amp;act=password_request"><?=__('I forgot my password')?></a></td>
</tr>
<tr>
    <td class="row2" colspan="2"><a href="?module=user.profile&amp;act=register"><?=__('Register')?></a></td>
</tr>
<?php } else {?>
<input type="hidden" name="logout_form" value="1">
<table cellpadding="2" cellspacing="1" style="width: 100%;">
<?php if($system->checkForRight('-any-')) { ?>
<tr>
    <td class="row3">
        <a href="./admin.php"><?=__('Administration')?></a>
    </td>
</tr>
<?php }?>
<?php if(!empty($system->modules['main']['articles.post'])) { ?>
<tr>
    <td class="row3">
        <a href="?module=articles.post"><?=__('Post article')?></a>
    </td>
</tr>
<?php }?>
<tr>
    <td class="row2">
        <a href="?module=user.profile"><?=__('My profile')?></a>
    </td>
</tr>
<?php if(LOGGED_IN ) { ?>
<tr>
    <td class="row3">
      
    </td>
</tr>
<?php }?>
<tr>
    <td class="row2">
        <input type="submit" value="<?=__('Log out')?>" />
    </td>
</tr>
<?php }?>
</table>
</form>
<hr />
<?php if(!empty($system->config['allowchskin'])){?>
<form name="skin_select" method="post" action="">
    <?=user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100%', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?>
</form>
<?php }?>
<?php if(!empty($system->config['allowchlang'])){?>
<form name="lang_select" method="post" action="">
    <?=user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100%', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?>
</form>
<?php }?>
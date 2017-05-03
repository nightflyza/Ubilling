<form method="post" action="">
<input type="hidden" name="<?=$tpldata['mode']?>" value="1" />
<table cellspacing="0" cellpadding="2" border="0" style="width: 100%;">
<tr>
    <td width="50%" align="right"><?=__('Username')?>:<?php if($tpldata['mode'] != 'profile_form'){?><br /><small><?=__('Will be used only for login. Please use only latinic letters and digits')?></small><?php } ?></td>
    <td width="50%" align="left"><?php if($tpldata['mode'] != 'profile_form'){?> <input type="text" name="username" value="" /><?php } else {?><?=$tpldata['values']['username']?><?php }?></td>
</tr>

<?php if($tpldata['mode'] == 'profile_form'){?>
<tr>
    <td width="50%" align="right"><?=__('Access level')?>:</td>
    <td width="50%" align="left"><?=(int)@$tpldata['values']['accesslevel']?></td>
</tr>
<tr>
 	<td width="50%" align="right" valign=top><?=__('Your current avatar').':'?></td>
 	<td><?=show_avatar($tpldata['values']['username'])?><br><a href="?module=avatar.control"><?=__('Avatar control')?></a></td>
</tr>
<tr>
    <td width="50%" align="right"><?=__('Current password') . ':<br /><small>' . __('To change profile data you must enter your current password.') . '</small'?></td>
    <td width="50%" align="left"><input type="password" name="current_password" /></td>
</tr>
<?php }?>
<?php if($tpldata['mode'] !== 'registration_form' || empty($system->config['regconf'])){?>
<tr>
    <td width="50%" align="right"><?=($tpldata['mode'] == 'profile_form') ? __('New password') . ':<br /><small>' . __('if you do not want change password you must leave this field empty') . '</small>' : __('Password')?></td>
    <td width="50%" align="left"><input type="password" name="password" /></td>
</tr>
<tr>
    <td width="50%" align="right"><?=__('Confirm password')?>:</td>
    <td width="50%" align="left"><input type="password" name="confirmation" /></td>
</tr>
<?php }?>
<tr>
    <td width="50%" align="right"><?=__('Nickname')?>:</td>
    <td width="50%" align="left"><input type="text" name="nickname" value="<?=@$tpldata['values']['nickname']?>" /></td>
</tr>
<tr>
    <td width="50%" align="right"><?=__('E-mail')?>:<br /><small><?=__('Please enter valid e-mail') . '.' . (($tpldata['mode'] == 'registration_form' && !empty($system->config['regconf'])) ? __('This e-mail will be used to send your password to access your account, for password recovery and for important announcements.') : __('This e-mail will be used for password recovery and for important announcements.'))?></small></td>
    <td width="50%" align="left"><input type="text" name="email" value="<?=@$tpldata['values']['email']?>" /></td>
</tr>
<tr>
    <td width="50%" align="right"><?=__('Hide e-mail from other users')?>:</td>
    <td width="50%" align="left"><input type="checkbox" name="userdata[hideemail]" value="1" <?=((!isset($tpldata['values']['hideemail'])) ? 'checked="checked"' : (@$tpldata['values']['hideemail']) ? 'checked="checked"' : '')?> /></td>
</tr>
<tr>
    <td width="50%" align="right"><?=__('Time zone')?>:</td>
    <td width="50%" align="left"><?=user_tz_select(@$tpldata['values']['tz'], 'userdata[tz]')?></td>
</tr>
<?php foreach ($tpldata['fields'] as $field_id => $field_name) { ?>
<tr>
    <td width="50%" align="right"><?=$field_name?>:</td>
    <td width="50%" align="left"><input type="text" name="userdata[<?=$field_id?>]"  value="<?=@$tpldata['values'][$field_id]?>" /></td>
 </tr>
<?php } ?>
<?php if (isset($_GET['act'])) { ?>
<tr>
<td width="50%" align="right">
    <?php 
    $rand=rand(0,666); // Acckaya Sotona haZ you n00b :P
    ?>
    <img src="captcha.php?ident=<?=$rand;?>">
    </td>
    <td width="50%" align="left">
    	<input type="text" size="5" name="captcheckout" value="">
    <input type="hidden" name="antispam" value="<?=$rand;?>">
    </td> 
</tr>
<?php } ?>
<tr>
	<td colspan=2 align=center>
		<input type="submit" value="<?=__('Submit')?>" />
	</td>
</tr>
</table>
</form>
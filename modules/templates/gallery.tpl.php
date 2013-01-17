
<hr />
<? if(!empty($tpldata['keywords'])){ ?>
<?=__('Keywords')?>:&nbsp; <?
 foreach ($tpldata['keywords'] as $keyword) { ?>
<a href="?module=gallery&keyword=<?=$keyword?>"><?=$keyword?></a> 
<?}
 }?>
<hr />

<div style="text-align: right"><?=$tpldata['pagination']?></div>

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<?php
$cols = 3;
$i = 0;
$total = count($tpldata['images']);
foreach ($tpldata['images'] as $image => $data){
	if($i == 0) echo '<tr>';
	$cs = 1;
	if($total == 1 && $i < $cols) $cs = $cols - $i;
?>
<td width="33%" style="text-align: center;" colspan="<?=$cs?>">
	<a href="<?=$tpldata['linkdata']?>&amp;id=<?=$image?>"><?=$data['thumbnail']?><br />
	<?=$data['title']?></a><br />
	(<?=$data['size'] . '/' . $data['type']?>)<br />
	<?=__('Comments') . ' - ' . $data['comments']?>
</td>
<?php
	if($i == $cols-1) echo '</tr>';
	$i++;
	if($i == $cols) $i = 0;
	$total--;
}?>
</table>
<hr />
<? if(!empty($tpldata['types']) || !empty($tpldata['sizes'])){ ?>
<form action="" method="get">
<input type="hidden" name="module" value="gallery" />
<? }?>

<? if(!empty($tpldata['sizes'])){ ?>
<select name="size">
<option value=""><?=__('All sizes')?></option>
<? foreach ($tpldata['sizes'] as $size) {?>
<option value="<?=$size?>" <? if(!empty($_GET['size']) && $_GET['size'] === $size) echo  ' selected';?>><?=$size?></option>
<? }?>
</select>
<? }?>

<? if(!empty($tpldata['types'])){ ?>
<select name="type">
<option value=""><?=__('All types')?></option>
<? foreach ($tpldata['types'] as $type) {?>
<option value="<?=$type?>" <? if(!empty($_GET['type']) && $_GET['type'] === $type) echo  ' selected';?>><?=$type?></option>
<? }?>
</select>
<? }?>

<? if(!empty($tpldata['keywords'])){ ?>
<select name="keyword">
<option value=""><?=__('All images')?></option>
<? foreach ($tpldata['keywords'] as $keyword) {?>
<option value="<?=$keyword?>" <? if(!empty($_GET['keyword']) && $_GET['keyword'] === $keyword) echo  ' selected';?>><?=$keyword?></option>
<? }?>
</select>
<? }?>

<? if(!empty($tpldata['types']) || !empty($tpldata['sizes'])){ ?>
<input type="submit" name="" value="<?=__('Submit')?>" />
</form>
<? }?>

<div style="text-align: left; clear: both"><?=$tpldata['pagination']?></div>

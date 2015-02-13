<?$dta = explode('.', $tpldata['textarea'])?>
<script language="javascript" type="text/javascript">
<!--
// Define the bbCode tags
bbcode = new Array();
bblast = new Array();
bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[img]','[/img]','[url]','[/url]','[hidden]','[/hidden]');
imageTag = false;
//-->
</script>
<script language="javascript" type="text/javascript" src="<?=RCMS_ROOT_PATH?>/modules/jsc/editor.js"></script>
<table cellspacing="0" cellpadding="0" border="0" align="center">
<tr align="center" valign="middle">
    <td>
        <input type="button" accesskey="b" name="addbbcode0" value="B" style="font-weight:bold;" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 0, '<?=$tpldata['textarea']?>')"/>
        <input type="button" accesskey="i" name="addbbcode2" value="i" style="font-style:italic;" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 2, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="u" name="addbbcode4" value="u" style="text-decoration: underline;" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 4, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="q" name="addbbcode6" value="<?=__('Quote')?>" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 6, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="Q" name="addbbcode6" onmouseover='checkselection()' value="<?=__('Quote selection')?>" onclick="addquote(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'])" />
        <input type="button" accesskey="c" name="addbbcode8" value="<?=__('Code')?>" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 8, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="h" name="addbbcode14" value="<?=__('Hidden')?>" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 14, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="p" name="addbbcode10" value="<?=__('Image')?>" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 10, '<?=$tpldata['textarea']?>')" />
        <input type="button" accesskey="w" name="addbbcode12" value="URL" style="text-decoration: underline;" onclick="bbstyle(document.forms['<?=$dta[0]?>'].elements['<?=$dta[1]?>'], 12, '<?=$tpldata['textarea']?>')" />
    </td>
</tr>
<tr align="center" valign="middle">
<td>
<? show_smiles($dta); ?> 
</td>
</tr>
</table>

<?php


$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

if ($us_config['SP_ENABLED']) {
    $spurl=$us_config['SP_URL'];
      $template=  la_tag('div', false, '', 'style="clear: both;"').  la_tag('div',true);
      $template.=la_tag('center',false);
      $template.='
<script type="text/javascript" src="'.$spurl.'speedtest/swfobject.js?v=2.2"></script>
	  <div id="mini-demo">
		  Speedtest.net Mini requires at least version 8 of Flash. Please <a href="http://get.adobe.com/flashplayer/">update your client</a>.
	  </div><!--/mini-demo-->
	<script type="text/javascript">
	  var flashvars = {
			upload_extension: "php"
		};
		var params = {
			wmode: "transparent",
			quality: "high",
			menu: "false",
			allowScriptAccess: "always"
		};
		var attributes = {};
		swfobject.embedSWF("'.$spurl.'speedtest.swf?v=2.1.8", "mini-demo", "350", "200", "9.0.0", "'.$spurl.'speedtest/expressInstall.swf", flashvars, params, attributes);
	</script>
<!-- END SPEED TEST - DO NOT ALTER ABOVE -->

          ';
      $template.=la_tag('center',true);
      $notice=  la_delimiter().__('The test may not be accurate and is dependent on the type and configuration of client software. The results of tests can influence the type of browser settings firewall, flash player, active anti-virus scanning of HTTP traffic function, active downloads, etc');
      show_window(__('Speed test'),$template.$notice);

} else {
     show_window(__('Sorry'),__('Unfortunately speedtest is now disabled'));
}

?>

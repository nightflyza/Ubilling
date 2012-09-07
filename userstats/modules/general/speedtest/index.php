<?php


$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

if ($us_config['SP_ENABLED']) {
    $spurl=$us_config['SP_URL'];
      $template='
         <div style="clear: both;"></div>
         <center>
         
        <!-- BEGIN SPEED TEST - DO NOT ALTER BELOW-->
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
        

        </center>
          ';
      
      show_window(__('Speed test'),$template);

} else {
     show_window(__('Sorry'),__('Unfortunately speedtest is now disabled'));
}

?>

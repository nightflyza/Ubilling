<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php 
include("modules/engine/api.mysql.php");
include("modules/engine/api.signup.php");
?>

<title><?=lang("ISP_NAME")?> - <?=lang("L_SIGREQ")?></title>

<link href="style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="css/coda-slider.css" type="text/css" charset="utf-8" />
<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.22.custom.css" rel="stylesheet" />

<script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
<script src="js/jquery.scrollTo-1.3.3.js" type="text/javascript"></script>
<script src="js/jquery.localscroll-1.2.5.js" type="text/javascript" charset="utf-8"></script>
<script src="js/jquery.serialScroll-1.2.1.js" type="text/javascript" charset="utf-8"></script>
<script src="js/coda-slider.js" type="text/javascript" charset="utf-8"></script>
<script src="js/jquery.easing.1.3.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.22.custom.min.js"></script>

<script type="text/javascript">
	$(function(){
	  var availableStreets = [<?php sn_StreetsArray()?>];
	 
	  $("#street").autocomplete({
	    source: availableStreets
	  });
	});
	</script>


</head>
<body>
<div id="templatemo_wrapper">
	<div id="templatemo_top">
    	<ul id="social_box">
                 
        </ul>
    </div>
    
  <div id="slider"> 
	<div id="header">
            
            	<div id="sitetite">
                	<h1><a href="<?=lang("ISP_URL")?>" target="_parent"><img src="<?=lang("ISP_LOGO")?>" alt="<?=lang("ISP_NAME")?>" /></a></h1>
            	</div> 
            
                <ul class="navigation">
                    <li><a href="#home"><?=lang("L_MAIN")?></a></li>
                    <li><a href="#sigform"><?=lang("L_REQUEST")?></a></li>
     			 </ul>
                
		  </div>
    <div class="scroll">
      <div class="scrollContainer">
        <div class="panel" id="home">
        
         <?php
         
            if (isset($_POST['sigformdata'])) {
                
            
                if (sn_CheckFields()) {
                    sn_CreateRequest();
                } else {
                    print(lang("L_WRONG"));
                }
                
            } else {
                 sn_LoadTemplate("frontpage.html"); 
            }

        
         ?>
  
        </div>
          
                 
        <div class="panel" id="sigform">
            <div class="col_w540">
                <div id="signup_form">
                <h4><?=lang("L_REQFORM")?></h4>
                
                <form method="post" name="contact" action="#home">
                    <input type="hidden" name="sigformdata" value="true">
                    <label for="street"><?=lang("L_STREET")?></label> <input type="text" id="street" name="street" size="34" class="input_field" />
                    <br><br>
                    <label for="build" class="label_line"><?=lang("L_BUILD")?></label> <input type="text" id="build" name="build" size="5" class="input_field" /> &nbsp;
                    <label for="apt" class="label_line"><?=lang("L_APT")?></label>  <input type="text" id="apt" name="apt" size="5" class="input_field" />
                    <div class="cleaner_h10"></div>
                  
                    
                    <label for="realname"><?=lang("L_REALNAME")?></label> <input type="text" id="realname" name="realname" size="34" class="input_field" />
                    <div class="cleaner_h10"></div>
                    
                    <label for="phone"><?=lang("L_PHONE")?></label> <input type="text" name="phone" id="phone" size="34" class="input_field" />
                    <div class="cleaner_h10"></div>
                    
                      <label for="service" class="label_line"><?=lang("L_SERVICE")?></label> 
                       <?php sn_ServicesSelect(); ?>
						<div class="cleaner_h10"></div>
                
                    <label for="notes"><?=lang("L_NOTES")?></label> <textarea id="notes" name="notes" rows="0" cols="0" class="required"></textarea>
                    <div class="cleaner_h10"></div>
                    
                    <input type="submit" class="submit_button float_l" name="submit" id="submit" value="<?=lang("L_SUBMIT")?>" />
                    
                </form>
                
                </div>
            </div>
        
            <div class="col_w240 last_col">
               <?php sn_LoadTemplate("contacts.html"); ?>
            </div>
        </div>
        
      </div>
    </div>
  </div>

    <div id="templatemo_footer">
    	&copy; <?=date("Y"); ?> <a href="<?=lang("ISP_URL")?>"><?=lang("ISP_NAME")?></a> | Powered by <a href="http://ubilling.net.ua">Ubilling</a>
    </div>
    
</div>



</body>
</html>

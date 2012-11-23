<?php
set_time_limit (0);
/*
 * Ubilling remote API
 * -----------------------------
 * 
 * Format: /?module=remoteapi&key=[ubserial]&action=[action][&param=[parameter]]
 * 
 * Possible actions:
 * 
 * reset + param [login] - resets user
 * handlersrebuild - rebuild all network handlers
 *
 */



$alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if ($alterconf['REMOTEAPI_ENABLED'])  {
    if (isset($_GET['key'])) {
        $key=vf($_GET['key']);
          $hostid_q="SELECT * from `ubstats` WHERE `key`='ubid'";
          $hostid=simple_query($hostid_q);
          if (!empty($hostid)) {
              $serial=$hostid['value'];
              if ($key==$serial) {
                  //key ok
                   if (isset($_GET['action'])) {
                       
                       /*
                        * reset user action
                        */
                       if ($_GET['action']=='reset') {
                           if (isset($_GET['param'])) {
                               $billing->resetuser($_GET['param']);
                               log_register("REMOTEAPI RESET User (".$_GET['param'].")");
                               die('OK:RESET');
                           } else {
                               die('ERROR:GET_NO_PARAM');
                           }
                       }
                       
                       
                     /*
                      * handlersrebuild action
                      */  
                       
                       if ($_GET['action']=='handlersrebuild') {
                            multinet_rebuild_all_handlers();
                            log_register("REMOTEAPI HANDLERSREBUILD");
                            die('OK:HANDLERSREBUILD');
                       }
                       
                       
                       /*
                        * CaTV fee processing 
                        */
                       
                        if ($_GET['action']=='catvfeeprocessing') {
                            $currentYear=date("Y");
                            //previous month charge fee
                            if ($alterconf['CATV_BACK_FEE']) {
                                $currentMonth=date("m");
                                if ($currentMonth==1) {
                                    $currentMonth=12;
                                } else {
                                    $currentMonth=$currentMonth-1;
                                }
                            } else {
                                $currentMonth=date("m");
                            }
                            
                            if (catv_FeeChargeCheck($currentMonth, $currentYear)) {
                              catv_FeeChargeAllUsers($currentMonth, $currentYear);
                            } else {
                                die('ERROR:ALREADY_CHARGED');
                            }
                            log_register("REMOTEAPI CATVFEEPROCESSING ".$currentMonth." ".$currentYear);
                            die('OK:CATVFEEPROCESSING');
                       }
                       
                       /*
                        * Virtualservices charge fee
                        */
                       
                       if ($_GET['action']=='vserviceschargefee') {
                                /* debug flags:
                                 * 0 - silent
                                 * 1 - with debug output
                                 * 2 - don`t touch any cash, just testing run
                                 */
                            zb_VservicesProcessAll(1,true);
                            log_register("REMOTEAPI VSERVICE_CHARGE_FEE");
                            die('OK:SERVICE_CHARGE_FEE');
                       }
                       
                       /*
                        * Discount processing
                        */
                       if ($_GET['action']=='discountprocessing') {
                           if ($alterconf['DISCOUNTS_ENABLED']) {
                               //default debug=true
                               zb_DiscountProcessPayments(true);
                               die('OK:DISCOUNTS_PROCESSING');
                           } else {
                               die('ERROR:DISCOUNTS_DISABLED');
                           }
                       }
                       
  ////
  //// End of actions
  ////
                  
                  /*
                   * Exeptions handling
                   */
                       
                   } else {
                       die('ERROR:GET_NO_ACTION');
                   }
              } else {
                  die('ERROR:GET_WRONG_KEY');
              }
  
          } else {
              die('ERROR:NO_UBSERIAL_EXISTS');
          }
        
    } else {
        die('ERROR:GET_NO_KEY');
    }
} else {
    die('ERROR:API_DISABLED');
}

?>

<?php
if(cfr('EXPRESSCARDREG')) {
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        if ($alterconf['CRM_MODE']) {
            
            
          //register subroutine
          if (wf_CheckPost(array('expresscardreg'))) {
                
            //check needed values
            $required=array(
                'citybox',
                'streetbox',
                'buildbox',
                'createapt',
                'newlogin',
                'serviceselect',
                'newmac',
                'editip'
                );
            
            if (wf_CheckPost($required)) {
                $newphone=$_POST['newphone'];
                $newmobile=$_POST['newmobile'];
                $newemail=$_POST['newemail'];
                $newbirthdate=$_POST['newbirthdate'];
                $newcontract=$_POST['newcontract'];
                $newcontractdate=$_POST['newcontractdate'];
                
                $newtariff=$_POST['newtariff'];
                $newip=$_POST['editip'];
                $newip=  loginDB_real_escape_string($newip);
                $newpassword=zb_RegPasswordProposal();
                
                $newsurname=  $_POST['newsurname'];
                $newname=  $_POST['newname'];
                $newpatronymic=$_POST['newpatronymic'];
                $normalRealName=$newsurname.' '.$newname.' '.$newpatronymic;
                $newnotes=$_POST['newnotes'];
                
                //filter login, and check for unique
                $newlogin=$_POST['newlogin'];
                $newlogin=vf($newlogin);
                if (!empty($newlogin)) {
                    $logincheck=  simple_query("SELECT `login` from `users` WHERE `login`='".$newlogin."'");
                    if (!$logincheck) {
                        $newserviceid=$_POST['serviceselect'];
                        $newnetid= multinet_get_service_networkid($newserviceid);
                       
                       //check apt data
                       $newcityid=$_POST['citybox'];
                       $newstreetid=$_POST['streetbox'];
                       $newbuildid=$_POST['buildbox'];
                       $newaptnum=$_POST['createapt'];
                       $newentrance=$_POST['createentrance'];
                       $newfloor=$_POST['createfloor'];
                       
                       //check passport data
                       if (!@$_POST['custompaddress']) {
                       $newpcity=$_POST['newpcity'];
                       $newpstreet=$_POST['newpstreet'];
                       $newpbuild=$_POST['newpbuild'];
                       $newpapt=$_POST['newpapt'];
                       
                        } else {
                          //use the same passport address
                          $cityname=  zb_AddressGetCityData($newcityid);
                          $cityname=$cityname['cityname'];
                          $streetname=  zb_AddressGetStreetData($newstreetid);
                          $streetname=$streetname['streetname'];
                          $buildnum=  zb_AddressGetBuildData($newbuildid);
                          $buildnum=$buildnum['buildnum'];
                          
                          $newpcity=$cityname;
                          $newpstreet=$streetname;
                          $newpbuild=$buildnum;
                          $newpapt=$newaptnum;
                           
                       }
                       
                       $newpassportdate=$_POST['newpassportdate'];
                       $newpassportnum=$_POST['newpassportnum'];
                       $newpassportwho=$_POST['newpassportwho'];
                       
                       //check is ip acceptable for this pool?
                              @$checkfreeip=multinet_get_next_freeip('nethosts', 'ip', $newnetid);
                        
                        if (!empty($checkfreeip)) {
                            //check is ip acceptable for this pool?
                            $allfreeips= multinet_get_all_free_ip('nethosts', 'ip', $newnetid);
                            $allfreeips=  array_flip($allfreeips);
                            if (isset($allfreeips[$newip])) {
                                
                                //MAC address check
                                $newmac=trim($_POST['newmac']);
                               if (multinet_mac_free($newmac)) {
                                 //validate mac format
                                if (check_mac_format($newmac)) {   
                                
                                /*  all is good with critycal data
                                 *  lets begin the collect new userdata and registering user
                                 */
                                    
                                $newuser_data['city'] =$newcityid;
                                $newuser_data['street'] =$newstreetid;
                                $newuser_data['build'] =$newbuildid;
                                $newuser_data['entrance']=$newentrance;
                                $newuser_data['floor'] =$newfloor;
                                $newuser_data['apt'] = $newaptnum;
                                $newuser_data['service']=$newserviceid;
                                $newuser_data['IP']=$newip;
                                $newuser_data['login']=$newlogin;
                                $newuser_data['password']=$newpassword;
                                //register user in stargazer
                                log_register("EXPRESSUSERREG(".$newlogin.") BEGIN"); 
                                zb_UserRegister($newuser_data,false);    
                                //update misc data
                                zb_UserChangeRealName($newlogin, $normalRealName);
                                zb_UserChangeEmail($newlogin, $newemail);
                                zb_UserChangePhone($newlogin, $newphone);
                                zb_UserChangeMobile($newlogin, $newmobile);
                                zb_UserDeleteNotes($newlogin);
                                zb_UserCreateNotes($newlogin,$newnotes);
                                zb_UserChangeContract($newlogin, $newcontract);
                                zb_UserContractDateCreate($newcontract, $newcontractdate);
                                zb_UserPassportDataCreate($newlogin, $newbirthdate, $newpassportnum, $newpassportdate, $newpassportwho, $newpcity, $newpstreet, $newpbuild, $newpapt);
                                
                                $billing->settariff($newlogin,$newtariff);
                                log_register('CHANGE Tariff ('.$newlogin.') ON '.$newtariff);
                                
                                multinet_change_mac($newip, $newmac);
                                log_register("MAC CHANGE (".$newlogin.") ".$newip." ON ".$newmac);
                                multinet_rebuild_all_handlers();
                                //finally reset user
                                $billing->resetuser($newlogin);
                                log_register("RESET User (".$newlogin.")");
                                
                                log_register("EXPRESSUSERREG (".$newlogin.") END"); 
                                rcms_redirect("?module=userprofile&username=".$newlogin);
                    
                                /*
                                 * End of express userreg subroutine
                                 */

                                 } else {
                                    show_window(__('Error'),__('This MAC have wrong format'));
                                    }
                                    } else {
                                        show_window(__('Error'), __('This MAC is currently used'));
                                    }
                 
                                } else {
                                    show_window(__('Error'), __('Wrong IP'));
                            }
                        } else {
                            //no free IPs left in network
                            show_window(('Error'),__('No free IP available in selected pool'));
                            
                        }
                        
                        
                    } else {
                        show_window(__('Error'), __('Busy login'));
                    }
                } else {
                    show_window(__('Error'), __('Empty login'));
                }
                
            } else {
                show_window(__('Error'), __('No all of required fields is filled'));
            }
            
          } else {
            //show registration form
            web_ExpressCardRegForm();
          }
            
            
            
            
            
            
            
        } else {
        show_window(__('Error'),__('Works only with CRM mode enabled'));
    }
    
} else {
      show_error(__('You cant control this module'));
}

?>

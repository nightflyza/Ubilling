<?php

if (cfr('EXPRESSCARD')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['CRM_MODE']) {

        // push ajax data for new address creation
        if (wf_CheckGet(array('ajaxstreet'))) {
            ajax_StreetSelector($_GET['ajaxstreet']);
        }

        if (wf_CheckGet(array('ajaxbuild'))) {
            ajax_BuildSelector($_GET['ajaxbuild']);
        }

        if (wf_CheckGet(array('ajaxapt'))) {
            ajax_AptCreationForm();
        }

        if (wf_CheckGet(array('ajaxip'))) {
            ajax_IpEditForm($_GET['ajaxip']);
        }


        //main code part
        if (wf_CheckGet(array('username'))) {
            $login = $_GET['username'];

            //making this user homeless
            if (wf_CheckGet(array('orphan'))) {
                $deleteaddrdata = zb_AddressGetAptData($login);
                $deleteatpid = $deleteaddrdata['aptid'];
                zb_AddressOrphanUser($login);
                zb_AddressDeleteApartment($deleteatpid);
                rcms_redirect("?module=expresscard&username=" . $login);
            }

            //new address creation
            if (wf_CheckPost(array('citybox', 'streetbox', 'buildbox', 'createapt'))) {
                $apt = $_POST['createapt'];
                @$entrance = $_POST['createentrance'];
                @$floor = $_POST['createfloor'];
                $buildid = $_POST['buildbox'];
                zb_AddressCreateApartment($buildid, $entrance, $floor, $apt);
                $newaptid = zb_AddressGetLastid();
                zb_AddressCreateAddress($login, $newaptid);
            }

            //existing address modify
            if (wf_CheckPost(array('editapt'))) {
                $changeaptdata = zb_AddressGetAptData($login);
                $changeaptid = $changeaptdata['id'];
                $changeaptbuildid = $changeaptdata['buildid'];
                $changeapt = $_POST['editapt'];
                @$changefloor = $_POST['editfloor'];
                @$changeentrance = $_POST['editentrance'];
                zb_AddressChangeApartment($changeaptid, $changeaptbuildid, $changeentrance, $changefloor, $changeapt);
            }

            /*
             * Here user editing if catched all of needed params
             */
            $editrequired = array('expresscardedit');
            if (wf_CheckPost($editrequired)) {
                log_register("EXPRESSCARD (" . $login . ") EDIT BEGIN ");
                //contract edit
                if (wf_CheckPost(array('editcontract'))) {
                    $newcontract = mysql_real_escape_string($_POST['editcontract']);
                    zb_UserChangeContract($login, $newcontract);
                }


                //contract date editing
                if (wf_CheckPost(array('editcontractdate', 'editcontract'))) {
                    $newcontractdate = mysql_real_escape_string($_POST['editcontractdate']);
                    $allcontractdates = zb_UserContractDatesGetAll($_POST['editcontract']);
                    if (isset($allcontractdates[$_POST['editcontract']])) {
                        $currentContractDate = $allcontractdates[$_POST['editcontract']];
                    } else {
                        $currentContractDate = '';
                    }
                    if (empty($currentContractDate)) {
                        zb_UserContractDateCreate($_POST['editcontract'], $newcontractdate);
                    } else {
                        zb_UserContractDateSet($_POST['editcontract'], $newcontractdate);
                    }
                }


                //realname editing
                if (wf_CheckPost(array('editsurname', 'editname', 'editpatronymic'))) {
                    $newsurname = $_POST['editsurname'];
                    $newname = $_POST['editname'];
                    $newpatronymic = $_POST['editpatronymic'];
                    $normalRealName = $newsurname . ' ' . $newname . ' ' . $newpatronymic;
                    zb_UserChangeRealName($login, $normalRealName);
                }

                //passportdata editing
                if (wf_CheckPost(array('editbirthdate'))) {
                    $newbirthdate = $_POST['editbirthdate'];
                    $newpassportnum = $_POST['editpassportnum'];
                    $newpassportdate = $_POST['editpassportdate'];
                    $newpassportwho = $_POST['editpassportwho'];

                    //if address is not like primary
                    if (!isset($_POST['custompaddress'])) {
                        $newpcity = $_POST['editpcity'];
                        $newpstreet = $_POST['editpstreet'];
                        $newpbuild = $_POST['editpbuild'];
                        $newpapt = $_POST['editpapt'];
                    } else {
                        //if paddress must be like primary
                        if (wf_CheckPost(array('samepapt', 'samepbuild', 'samepstreet', 'samepcity'))) {
                            $newpcity = $_POST['samepcity'];
                            $newpstreet = $_POST['samepstreet'];
                            $newpbuild = $_POST['samepbuild'];
                            $newpapt = $_POST['samepapt'];
                        } else {
                            //looks like user have no address, using old data
                            $cpdata = zb_UserPassportDataGet($login);
                            @$newpcity = $cpdata['pcity'];
                            @$newpstreet = $cpdata['pstreet'];
                            @$newpbuild = $cpdata['pbuild'];
                            @$newpapt = $cpdata['papt'];
                        }
                    }

                    zb_UserPassportDataChange($login, $newbirthdate, $newpassportnum, $newpassportdate, $newpassportwho, $newpcity, $newpstreet, $newpbuild, $newpapt);
                }

                //tariff editing
                if (wf_CheckPost(array('edittariff'))) {
                    $newtariff = $_POST['edittariff'];
                    $billing->settariff($login, $newtariff);
                    log_register('CHANGE Tariff (' . $login . ') ON ' . $newtariff);
                }

                //ip editing
                if (wf_CheckPost(array('editip'))) {
                    $service = $_POST['serviceselect'];
                    //if ip is not a same
                    if ($service != 'SAME') {
                        $currentip = zb_UserGetIP($login);
                        $currentmac = zb_MultinetGetMAC($currentip);
                        $newnetid = multinet_get_service_networkid($service);
                        $newip = trim($_POST['editip']);
                        $newip = mysql_real_escape_string($newip);
                        @$checkfreeip = multinet_get_next_freeip('nethosts', 'ip', $newnetid);

                        if (!empty($checkfreeip)) {
                            //check is ip acceptable for this pool?
                            $allfreeips = multinet_get_all_free_ip('nethosts', 'ip', $newnetid);
                            $allfreeips = array_flip($allfreeips);
                            if (isset($allfreeips[$newip])) {
                                $billing->setip($login, $newip);
                                multinet_delete_host($currentip);
                                multinet_add_host($newnetid, $newip, $currentmac);
                                multinet_rebuild_all_handlers();
                                multinet_RestartDhcp();
                                log_register("CHANGE MultiNetIP (" . $login . ") FROM " . $currentip . " ON " . $newip);
                            } else {
                                $alert = '
                            <script type="text/javascript">
                              alert("' . __('Error') . ': ' . __('Wrong IP') . '");
                              document.location.href="?module=expresscard&username=' . $login . '";
                            </script>
                            ';
                                die($alert);
                            }
                        } else {
                            //no free IPs left in network
                            $alert = '
                            <script type="text/javascript">
                            alert("' . __('Error') . ': ' . __('No free IP available in selected pool') . '");
                            </script>
                            ';
                            die($alert);
                        }
                    }
                }

                //editing MAC
                if (wf_CheckPost(array('editmac'))) {
                    $mac = trim($_POST['editmac']);
                    //check mac for free
                    if (multinet_mac_free($mac)) {
                        //validate mac format
                        if (check_mac_format($mac)) {
                            $ip = zb_UserGetIP($login);
                            $old_mac = zb_MultinetGetMAC($ip);
                            multinet_change_mac($ip, $mac);
                            log_register("MAC CHANGE (" . $login . ") " . $ip . " FROM  " . $old_mac . " ON " . $mac);
                            multinet_rebuild_all_handlers();
                        } else {
                            //show error when MAC haz wrong format
                            show_window(__('Error'), __('This MAC have wrong format'));
                            //debuglog
                            log_register("MACINVALID TRY (" . $login . ")");
                        }
                    } else {
                        log_register("MACDUPLICATE TRY (" . $login . ")");
                    }
                }

                //editing notes
                if (wf_CheckPost(array('editnotes'))) {
                    $newnotes = $_POST['editnotes'];
                    zb_UserDeleteNotes($login);
                    zb_UserCreateNotes($login, $newnotes);
                }

                //editing user email
                if (wf_CheckPost(array('editemail'))) {
                    $newemail = $_POST['editemail'];
                    zb_UserChangeEmail($login, $newemail);
                }

                //editing user phone
                if (wf_CheckPost(array('editphone'))) {
                    $newphone = $_POST['editphone'];
                    zb_UserChangePhone($login, $newphone);
                }

                //editing user mobile
                if (wf_CheckPost(array('editmobile'))) {
                    $newmobile = $_POST['editmobile'];
                    zb_UserChangeMobile($login, $newmobile);
                }

                //resetting user after all
                $billing->resetuser($login);
                log_register("RESET User (" . $login . ")");

                log_register("EXPRESSCARD (" . $login . ") EDIT END");
                rcms_redirect("?module=expresscard&username=" . $login);
            }


            //show monstro-form
            web_ExpressCardEditForm($login);
            show_window('', web_UserControls($login));
        } else {
            show_window(__('Error'), __('Strange exeption'));
        }
    } else {
        show_window(__('Error'), __('Works only with CRM mode enabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

<?php

if (cfr('CORPS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $greed = new Avarice();
        $beggar = $greed->runtime('CORPS');
        if (!empty($beggar)) {

            /*
             * controller section
             */

            $corps = new Corps();


            if (wf_CheckGet(array(Corps::ROUTE_PREFIX))) {
                $route = $_GET[Corps::ROUTE_PREFIX];

                //taxtypes controller
                if ($route == Corps::URL_TAXTYPE) {
                    //del 
                    if (wf_CheckGet(array('deltaxtypeid'))) {
                        if (!$corps->taxtypeProtected($_GET['deltaxtypeid'])) {
                            if (method_exists($corps, $beggar['METH']['TTFLUSH']))
                                $corps->$beggar['METH']['TTFLUSH']($_GET['deltaxtypeid']);
                            rcms_redirect(Corps::URL_TAXTYPE_LIST);
                        } else {
                            show_window(__('Error'), __('This item is used by something'));
                        }
                    }
                    //edit
                    if (wf_CheckPost(array('edittaxtypeid', 'edittaxtype'))) {
                        $corps->taxtypeEdit($_POST['edittaxtypeid'], $_POST['edittaxtype']);
                        rcms_redirect(Corps::URL_TAXTYPE_LIST);
                    }
                    //add
                    if (wf_CheckPost(array('newtaxtype'))) {
                        $corps->taxtypeCreate($_POST['newtaxtype']);
                        rcms_redirect(Corps::URL_TAXTYPE_LIST);
                    }

                    show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                    if (method_exists($corps, $beggar['METH']['TTRENDER']))
                        show_window(__('Available tax types'), $corps->$beggar['METH']['TTRENDER']());
                }


                //corps controller
                if ($route == Corps::URL_CORPS) {
                    show_window('', $corps->corpsPanel());

                    //del
                    if (wf_CheckGet(array('deleteid'))) {
                        if (!$corps->corpProtected($_GET['deleteid'])) {
                            if (method_exists($corps, $beggar['METH']['FLUSH']))
                                $corps->$beggar['METH']['FLUSH']($_GET['deleteid']);
                            rcms_redirect(Corps::URL_CORPS_LIST);
                        } else {
                            show_window(__('Error'), __('This item is used by something'));
                        }
                    }

                    //add
                    if (wf_CheckGet(array('add'))) {
                        //creation 
                        if (wf_CheckPost(array('createcorpid'))) {
                            if (wf_CheckPost(array('createcorpname'))) {
                                if (method_exists($corps, $beggar['METH']['ADD'])) {
                                    $corpAddResult = $corps->$beggar['METH']['ADD']();
                                    if (wf_CheckPost(array('alsobindsomelogin'))) {
                                        $corps->userBind($_POST['alsobindsomelogin'], $corpAddResult);
                                        rcms_redirect(Corps::URL_USER_MANAGE . $_POST['alsobindsomelogin']);
                                    } else {
                                        rcms_redirect(Corps::URL_CORPS_LIST);
                                    }
                                }
                            } else {
                                show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
                            }
                        }
                        show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                        if (method_exists($corps, $beggar['VP']['FADF']))
                            show_window(__('Create'), $corps->$beggar['VP']['FADF']());
                    }

                    //editing
                    if (wf_CheckGet(array('editid'))) {
                        //editing push
                        if (wf_CheckPost(array('editcorpid', 'editcorpname'))) {
                            if (method_exists($corps, $beggar['METH']['PUSH']))
                                $corps->$beggar['METH']['PUSH']($_POST['editcorpid']);
                            rcms_redirect(Corps::URL_CORPS_EDIT . $_GET['editid']);
                        }
                        //deleting person
                        if (wf_CheckGet(array('deletepersonid'))) {
                            $corps->personDelete($_GET['deletepersonid']);
                            rcms_redirect(Corps::URL_CORPS_EDIT . $_GET['editid']);
                        }
                        //person creation
                        if (wf_CheckPost(array('addpersoncorpid', 'addpersonrealname'))) {
                            $corps->personCreate();
                            rcms_redirect(Corps::URL_CORPS_EDIT . $_POST['addpersoncorpid']);
                        }

                        //person editing
                        if (wf_CheckPost(array('editpersonid', 'editpersonrealname'))) {
                            $corps->personSave($_POST['editpersonid']);
                            rcms_redirect(Corps::URL_CORPS_EDIT . $_GET['editid']);
                        }

                        show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                        if (method_exists($corps, $beggar['VP']['MODF']))
                            show_window(__('Edit'), $corps->$beggar['VP']['MODF']($_GET['editid']));
                        show_window(__('Contact persons'), $corps->personCreateForm($_GET['editid']));
                        //user binding/unbinding actions
                        if (wf_CheckGet(array('usercallback'))) {
                            if (wf_CheckPost(array('corpsunbindlogin'))) {
                                if (isset($_POST['unbindagree'])) {
                                    $corps->userUnbind($_POST['corpsunbindlogin']);
                                    rcms_redirect("?module=userprofile&username=" . $_POST['corpsunbindlogin']);
                                } else {
                                    show_window(__('Error'), __('You are not mentally prepared for this'));
                                }
                            }
                            show_window(__('Actions'), $corps->userUnbindForm($_GET['usercallback']));
                        }
                    } else {

                        if (!wf_CheckGet(array('add'))) {
                            if (method_exists($corps, $beggar['METH']['RENDER']))
                                show_window(__('Available corps'), $corps->$beggar['METH']['RENDER']());
                        }
                    }
                }

                //user management
                if ($route == Corps::URL_USER) {
                    if (wf_CheckGet(array('username'))) {
                        $login = mysql_real_escape_string($_GET['username']);
                        $userCorpCheck = $corps->userIsCorporate($login);
                        if ($userCorpCheck) {
                            //enterprise user
                            $corpsControls = $corps->corpPreview($userCorpCheck);
                            $corpsControls.= wf_Link(Corps::URL_CORPS_EDIT . $userCorpCheck . '&usercallback=' . $login, web_edit_icon() . ' ' . __('Edit'), true, 'ubButton');
                            $corpsControls.= wf_delimiter();
                            $corpsControls.= web_UserControls($login);
                            show_window(__('Corporate user'), $corpsControls);
                        } else {
                            //user is private
                            if (wf_CheckPost(array('bindsomelogin', 'bindlogintocorpid'))) {
                                $corps->userBind($_POST['bindsomelogin'], $_POST['bindlogintocorpid']);
                                rcms_redirect(Corps::URL_USER_MANAGE . $_POST['bindsomelogin']);
                            }
                            if (method_exists($corps, $beggar['BU']['F'])) {
                                $corpAttachControls = $corps->$beggar['BU']['F']($login);
                                show_window(__('Private user'), $corpAttachControls);
                            }

                            if (method_exists($corps, $beggar['BU']['AB'])) {
                                $corpAddAttachControls = $corps->$beggar['BU']['AB']($login);
                                show_window(__('Create') . ' ' . __('Corporate user'), $corpAddAttachControls);
                            }
                        }
                    }
                }

                if ($route == Corps::URL_SEARCH) {
                    $searchResults = $corps->searchUsersByCorpName($_POST['searchcorpname']);
                    if (!empty($searchResults)) {
                        show_window(__('Search results'), $searchResults);
                    }
                    show_window('', wf_BackLink('?module=usersearch'));
                }
            } else {
                //default list route
                rcms_redirect(Corps::URL_CORPS_LIST);
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>
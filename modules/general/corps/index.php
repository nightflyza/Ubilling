<?php

if (cfr('CORPS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $greed = new Avarice();
        $beggar = $greed->runtime('CORPS');
        if (!empty($beggar)) {
            $corps = new Corps();

            if (ubRouting::checkGet(Corps::ROUTE_PREFIX)) {
                $route = ubRouting::get(Corps::ROUTE_PREFIX);

                //taxtypes controller
                if ($route == Corps::URL_TAXTYPE) {
                    //del 
                    if (ubRouting::checkGet('deltaxtypeid')) {
                        if (!$corps->taxtypeProtected(ubRouting::get('deltaxtypeid'))) {
                            if (isset($beggar['METH']['TTFLUSH']) and method_exists($corps, $beggar['METH']['TTFLUSH'])) {
                                $beggar_m = $beggar['METH']['TTFLUSH'];
                                $corps->$beggar_m(ubRouting::get('deltaxtypeid'));
                                ubRouting::nav(Corps::URL_TAXTYPE_LIST);
                            }
                        } else {
                            show_error(__('This item is used by something'));
                        }
                    }
                    //edit
                    if (ubRouting::checkPost(array('edittaxtypeid', 'edittaxtype'))) {
                        $corps->taxtypeEdit(ubRouting::post('edittaxtypeid'), ubRouting::post('edittaxtype'));
                        ubRouting::nav(Corps::URL_TAXTYPE_LIST);
                    }
                    //add
                    if (ubRouting::checkPost('newtaxtype')) {
                        $corps->taxtypeCreate(ubRouting::post('newtaxtype'));
                        ubRouting::nav(Corps::URL_TAXTYPE_LIST);
                    }

                    show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                    if (isset($beggar['METH']['TTRENDER']) and method_exists($corps, $beggar['METH']['TTRENDER'])) {
                        $beggar_m = $beggar['METH']['TTRENDER'];
                        show_window(__('Available tax types'), $corps->$beggar_m());
                    }
                }

                //corps controller
                if ($route == Corps::URL_CORPS) {
                    show_window('', $corps->corpsPanel());

                    //del
                    if (ubRouting::checkGet('deleteid')) {
                        if (!$corps->corpProtected(ubRouting::get('deleteid'))) {
                            if (isset($beggar['METH']['FLUSH']) and method_exists($corps, $beggar['METH']['FLUSH'])) {
                                $beggar_m = $beggar['METH']['FLUSH'];
                                $corps->$beggar_m(ubRouting::get('deleteid'));
                                ubRouting::nav(Corps::URL_CORPS_LIST);
                            }
                        } else {
                            show_error(__('This item is used by something'));
                        }
                    }

                    //add
                    if (ubRouting::checkGet('add')) {
                        //creation 
                        if (ubRouting::checkPost('createcorpid')) {
                            if (ubRouting::checkPost('createcorpname')) {
                                if (isset($beggar['METH']['ADD']) and method_exists($corps, $beggar['METH']['ADD'])) {
                                    $beggar_m = $beggar['METH']['ADD'];
                                    $corpAddResult = $corps->$beggar_m();
                                    if (ubRouting::checkPost('alsobindsomelogin')) {
                                        $corps->userBind(ubRouting::post('alsobindsomelogin'), $corpAddResult);
                                        ubRouting::nav(Corps::URL_USER_MANAGE . ubRouting::post('alsobindsomelogin'));
                                    } else {
                                        ubRouting::nav(Corps::URL_CORPS_LIST);
                                    }
                                }
                            } else {
                                show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
                            }
                        }
                        show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                        if (isset($beggar['VP']['FADF']) and method_exists($corps, $beggar['VP']['FADF'])) {
                            $beggar_v = $beggar['VP']['FADF'];
                            show_window(__('Create'), $corps->$beggar_v());
                        }
                    }

                    //editing
                    if (ubRouting::checkGet('editid')) {
                        //editing push
                        if (ubRouting::checkPost(array('editcorpid', 'editcorpname'))) {
                            if (isset($beggar['METH']['PUSH']) and method_exists($corps, $beggar['METH']['PUSH'])) {
                                $beggar_m = $beggar['METH']['PUSH'];
                                $corps->$beggar_m(ubRouting::post('editcorpid'));
                                ubRouting::nav(Corps::URL_CORPS_EDIT . ubRouting::get('editid'));
                            }
                        }
                        //deleting person
                        if (ubRouting::checkGet('deletepersonid')) {
                            $corps->personDelete(ubRouting::get('deletepersonid'));
                            ubRouting::nav(Corps::URL_CORPS_EDIT . ubRouting::get('editid'));
                        }
                        //person creation
                        if (ubRouting::checkPost(array('addpersoncorpid', 'addpersonrealname'))) {
                            $corps->personCreate();
                            ubRouting::nav(Corps::URL_CORPS_EDIT . ubRouting::post('addpersoncorpid'));
                        }

                        //person editing
                        if (ubRouting::checkPost(array('editpersonid', 'editpersonrealname'))) {
                            $corps->personSave(ubRouting::post('editpersonid'));
                            ubRouting::nav(Corps::URL_CORPS_EDIT . ubRouting::get('editid'));
                        }

                        show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                        if (isset($beggar['VP']['MODF']) and method_exists($corps, $beggar['VP']['MODF']))
                            $beggar_v = $beggar['VP']['MODF'];
                        show_window(__('Edit'), $corps->$beggar_v(ubRouting::get('editid')));
                        show_window(__('Contact persons'), $corps->personCreateForm(ubRouting::get('editid')));
                        //user binding/unbinding actions
                        if (ubRouting::checkGet('usercallback')) {
                            if (ubRouting::checkPost('corpsunbindlogin')) {
                                if (ubRouting::checkPost('unbindagree')) {
                                    $corps->userUnbind(ubRouting::post('corpsunbindlogin'));
                                    ubRouting::nav("?module=userprofile&username=" . ubRouting::post('corpsunbindlogin'));
                                } else {
                                    show_window(__('Error'), __('You are not mentally prepared for this'));
                                }
                            }
                            show_window(__('Actions'), $corps->userUnbindForm(ubRouting::get('usercallback')));
                        }
                    } else {

                        if (!ubRouting::checkGet('add')) {
                            if (isset($beggar['METH']['RENDER']) and method_exists($corps, $beggar['METH']['RENDER'])) {
                                $beggar_m = $beggar['METH']['RENDER'];
                                if (ubRouting::checkGet($corps::URL_AJDT)) {
                                    $corps->corpsListAjax();
                                }
                                show_window(__('Available corps'), $corps->$beggar_m());
                            }
                        }
                    }
                }

                //user management
                if ($route == Corps::URL_USER) {
                    if (ubRouting::checkGet('username')) {
                        $login = mysql_real_escape_string(ubRouting::get('username'));
                        $userCorpCheck = $corps->userIsCorporate($login);
                        if ($userCorpCheck) {
                            //enterprise user
                            $corpsControls = $corps->corpPreview($userCorpCheck);
                            $corpsControls .= wf_Link(Corps::URL_CORPS_EDIT . $userCorpCheck . '&usercallback=' . $login, web_edit_icon() . ' ' . __('Edit'), true, 'ubButton');
                            $corpsControls .= wf_delimiter();
                            $corpsControls .= web_UserControls($login);
                            show_window(__('Corporate user'), $corpsControls);
                        } else {
                            //user is private
                            if (ubRouting::checkPost(array('bindsomelogin', 'bindlogintocorpid'))) {
                                $corps->userBind(ubRouting::post('bindsomelogin'), ubRouting::post('bindlogintocorpid'));
                                ubRouting::nav(Corps::URL_USER_MANAGE . ubRouting::post('bindsomelogin'));
                            }
                            if (isset($beggar['BU']['F']) and method_exists($corps, $beggar['BU']['F'])) {
                                $beggar_b = $beggar['BU']['F'];
                                $corpAttachControls = $corps->$beggar_b($login);
                                show_window(__('Private user'), $corpAttachControls);
                            }

                            if (isset($beggar['BU']['AB']) and method_exists($corps, $beggar['BU']['AB'])) {
                                $beggar_b = $beggar['BU']['AB'];
                                $corpAddAttachControls = $corps->$beggar_b($login);
                                show_window(__('Create') . ' ' . __('Corporate user'), $corpAddAttachControls);
                            }
                        }
                    }
                }

                if ($route == Corps::URL_SEARCH) {
                    $searchResults = $corps->searchUsersByCorpName(ubRouting::post('searchcorpname'));
                    if (!empty($searchResults)) {
                        show_window(__('Search results'), $searchResults);
                    }
                    show_window('', wf_BackLink('?module=usersearch'));
                }
            } else {
                //default list route
                ubRouting::nav(Corps::URL_CORPS_LIST);
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
    
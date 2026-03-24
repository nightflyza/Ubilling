<?php

if (cfr('CORPS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $corps = new Corps();

            if (ubRouting::checkGet(Corps::ROUTE_PREFIX)) {
                $route = ubRouting::get(Corps::ROUTE_PREFIX);

                //taxtypes controller
                if ($route == Corps::URL_TAXTYPE) {
                    //del 
                    if (ubRouting::checkGet('deltaxtypeid')) {
                        if (!$corps->taxtypeProtected(ubRouting::get('deltaxtypeid'))) {
                            $corps->taxtypeDelete(ubRouting::get('deltaxtypeid'));
                            ubRouting::nav(Corps::URL_TAXTYPE_LIST);
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
                    show_window(__('Available tax types'), $corps->taxtypesList());
                }

                //corps controller
                if ($route == Corps::URL_CORPS) {
                    show_window('', $corps->corpsPanel());

                    //del
                    if (ubRouting::checkGet('deleteid')) {
                        if (!$corps->corpProtected(ubRouting::get('deleteid'))) {
                            $corps->corpDelete(ubRouting::get('deleteid'));
                            ubRouting::nav(Corps::URL_CORPS_LIST);
                        } else {
                            show_error(__('This item is used by something'));
                        }
                    }

                    //add
                    if (ubRouting::checkGet('add')) {
                        //creation 
                        if (ubRouting::checkPost('createcorpid')) {
                            if (ubRouting::checkPost('createcorpname')) {
                                $corpAddResult = $corps->corpCreate();
                                if (ubRouting::checkPost('alsobindsomelogin')) {
                                    $corps->userBind(ubRouting::post('alsobindsomelogin'), $corpAddResult);
                                    ubRouting::nav(Corps::URL_USER_MANAGE . ubRouting::post('alsobindsomelogin'));
                                } else {
                                    ubRouting::nav(Corps::URL_CORPS_LIST);
                                }
                            } else {
                                show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
                            }
                        }
                        show_window('', wf_BackLink(Corps::URL_CORPS_LIST, '', true));
                        show_window(__('Create'), $corps->corpCreateForm());
                    }

                    //editing
                    if (ubRouting::checkGet('editid')) {
                        //editing push
                        if (ubRouting::checkPost(array('editcorpid', 'editcorpname'))) {
                            $corps->corpSave(ubRouting::post('editcorpid'));
                            ubRouting::nav(Corps::URL_CORPS_EDIT . ubRouting::get('editid'));
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
                        show_window(__('Edit'), $corps->corpEditForm(ubRouting::get('editid')));
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
                            if (ubRouting::checkGet($corps::URL_AJDT)) {
                                $corps->corpsListAjax();
                            }
                            show_window(__('Available corps'), $corps->corpsList());
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
                            $corpAttachControls = $corps->corpsBindForm($login);
                            show_window(__('Private user'), $corpAttachControls);

                            $corpAddAttachControls = $corps->corpCreateAndBindForm($login);
                            show_window(__('Create') . ' ' . __('Corporate user'), $corpAddAttachControls);
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
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
    
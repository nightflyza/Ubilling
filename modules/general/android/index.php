<?php
/*
$options = array('options' => array('min_range'=>0, 'max_range'=>9));

$priority = filter_input(INPUT_GET, 'cash', FILTER_VALIDATE_FLOAT, array('options' => array('default'=>1, ), 'flags' => FILTER_FLAG_ALLOW_THOUSAND));
	var_dump($priority );
	if ($priority) {
		print 'PAUTINA: ' . $priority . PHP_EOL;
	}
*/

$android = new AndroidApp();

// Первый уровень защиты
if ($android->access) {

//   $android->DebugMessageAdd('PAUTINA', 'TEST');
//   $android->DebugMessageAdd('PAUTINA', 'TEST2');
//   $android->DebugMessageAdd('PAUTINA3', 'TEST3');
//   $android->DebugMessageAdd('PAUTINA4', 'TEST4');
//   $android->checkRight('TASKMANNWATCHLOG');

    //modify task sub
    if (isset($_GET['action']) and $_GET['action'] == 'modifytask' and $android->checkRight('TASKMAN')) {
        if (wf_CheckPost(array('modifystartdate', 'modifytaskaddress', 'modifytaskphone'))) {
            if (zb_checkDate($_POST['modifystartdate'])) {
                //if (isset($_POST['taskid']) and !empty($_POST['taskid'])) {
                if (filter_input(INPUT_POST, 'taskid', FILTER_VALIDATE_INT)) { // Пробуем новую схему валидации
                    $taskid = $_POST['taskid'];
                    ts_ModifyTask($taskid, $_POST['modifystartdate'], $_POST['modifystarttime'], $_POST['modifytaskaddress'], @$_POST['modifytasklogin'], $_POST['modifytaskphone'], $_POST['modifytaskjobtype'], $_POST['modifytaskemployee'], $_POST['modifytaskjobnote']);
                } else {
                    $android->updateSuccessAndMessage('I dont have TASKID');
                }
            } else {
                $android->updateSuccessAndMessage('Wrong date format');
            }
        } else {
            $android->updateSuccessAndMessage('All fields marked with an asterisk are mandatory');
        }
    }

    //if marking task as done
    if (isset($_GET['action']) and $_GET['action'] == 'changetask' and $android->checkRight('TASKMANDONE')) {
        if (wf_CheckPost(array('editenddate', 'editemployeedone'))) {
            if (zb_checkDate($_POST['editenddate'])) {
                //editing task sub
                ts_TaskIsDone();
                //flushing darkvoid after changing task
                $darkVoid = new DarkVoid();
                $darkVoid->flushCache();
                //generate job for some user
                if (wf_CheckPost(array('generatejob', 'generatelogin', 'generatejobid'))) {
                    stg_add_new_job($_POST['generatelogin'], curdatetime(), $_POST['editemployeedone'], $_POST['generatejobid'], 'TASKID:[' . $_POST['changetask'] . ']');
                    log_register("TASKMAN GENJOB (" . $_POST['generatelogin'] . ') VIA [' . $_POST['changetask'] . ']');
                }
            } else {
                $android->updateSuccessAndMessage('Wrong date format');
            }
        } else {
            $android->updateSuccessAndMessage('All fields marked with an asterisk are mandatory');
        }
    }

    // Add user cash
    if (isset($_GET['action']) and $_GET['action'] == 'addcash' and $android->checkRight('CASH')) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            $login = trim($login);
            // Init
            $cash = @$_POST['newcash'];
            // $operation = vf($_POST['operation']);
            $operation = 'add';
            $cashtype = vf(@$_POST['cashtype']);
            $note = ( isset($_POST['newpaymentnote']) ) ? mysql_real_escape_string($_POST['newpaymentnote']) : '';

            // Empty cash hotfix:
            if ($cash != '') {
                if (zb_checkMoney($cash)) {
                    $whoami = whoami();
                    $employeeId = ts_GetEmployeeByLogin($whoami);
                    $employeeData = stg_get_employee_data($employeeId);
                    $employeeLimit = @$employeeData['amountLimit'];
                    if (!cfr('ROOT') and ! empty($employeeLimit)) {
                        $query = "SELECT sum(`summ`) as `summa` FROM `payments` WHERE MONTH(`date`) = MONTH(NOW()) AND YEAR(`date`) = YEAR(NOW()) AND admin = '" . $whoami . "' AND `summ`>0";
                        $summa = simple_query($query);
                        $summa = $summa['summa'];
                        if ($employeeLimit - $summa >= $cash) {
                            if ($ubillingConfig->getAlterParam('SIGNUP_PAYMENTS')) {
                                zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                            } else {
                                zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                            }
                        } else {
                            $android->updateSuccessAndMessage('Payment amount exceeded per month. You can top up for the amount of: ' . $employeeLimit - $summa);
                            log_register('ANDROID BALANCEADDFAIL (' . $login . ') AMOUNT LIMIT `' . mysql_real_escape_string($employeeLimit - $summa) . '` TRY ADD SUMM `' . $cash . '`');
                        }
                    } else {
                        if ($ubillingConfig->getAlterParam('SIGNUP_PAYMENTS')) {
                            zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                        } else {
                            zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                        }
                    }
                } else {
                    $android->updateSuccessAndMessage('Wrong format of a sum of money to pay');
                    log_register('ANDROID BALANCEADDFAIL (' . $login . ') WRONG SUMM `' . $cash . '`');
                }
            } else {
                $android->updateSuccessAndMessage('You have not completed the required amount of money to deposit into account. We hope next time you will be more attentive.');
                log_register('ANDROID BALANCEADDFAIL (' . $login . ') EMPTY SUMM `' . $cash . '`');
            }

            // Load user data
            $android->getUserData($login);

       } else {
            $android->updateSuccessAndMessage('GET_NO_USERNAME');
        }

    }

    //search users
    if (isset($_GET['action']) and $_GET['action'] == 'usersearch' and $android->checkRight('USERSEARCH')) {
        if (wf_CheckPost(array('searchquery'))) {
            $android->searchUsersQuery($_POST['searchquery']);
       } else {
            $android->updateSuccessAndMessage('Wrong query');
        }
    }

    // Get user data
    if (isset($_GET['action']) and $_GET['action'] == 'userprofile' and $android->checkRight('USERPROFILE')) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            $login = trim($login);
            $android->getUserData($login);
       } else {
            $android->updateSuccessAndMessage('GET_NO_USERNAME');
        }
    }

    /**
     * Change user profile
     *
     * Can change: username, password, REALNAME, PHONE, MOBILE, EMAIL, PASSIVE state, Down state, NOTES
     */
    if (isset($_GET['action']) and $_GET['action'] == 'useredit' and $android->checkRight('USEREDIT')) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            $login = trim($login);

            // change password  if need
            if (wf_CheckPost(array('newpassword')) and $android->checkRight('PASSWORD')) {
                $password = $_POST['newpassword'];
                if (zb_CheckPasswordUnique($password)) {
                    $billing->setpassword($login, $password);
                    log_register('ANDROID CHANGE Password (' . $login . ') ON `' . $password . '`');
                } else {
                     $android->updateSuccessAndMessage('We do not recommend using the same password for different users. Try another.');
                }
            }

            // change realname if need
            if (wf_CheckPost(array('newrealname')) and $android->checkRight('REALNAME')) {
                $realname = $_POST['newrealname'];
                zb_UserChangeRealName($login, $realname);
                log_register('ANDROID CHANGE REALNAME (' . $login . ') ON `' . mysql_real_escape_string($realname) . '`');
            }

            // change  phone if need
            if (wf_CheckPost(array('newphone')) and $android->checkRight('PHONE')) {
                $phone = $_POST['newphone'];
                zb_UserChangePhone($login, $phone);
            }

            // change phone if need
            if (wf_CheckPost(array('newmobile')) and $android->checkRight('MOBILE')) {
                $mobile = $_POST['newmobile'];
                zb_UserChangeMobile($login, $mobile);
            }

            // change mail if need
            if (wf_CheckPost(array('newmail')) and $android->checkRight('EMAIL')) {
                $mail = $_POST['newmail'];
                zb_UserChangeEmail($login, $mail);
            }

            // change down if need
            if (wf_CheckPost(array('newdown')) and $android->checkRight('DOWN')) {
                $down = $_POST['newdown'];
                $billing->setdown($login, $down);
                log_register('ANDROID CHANGE Down (' . $login . ') ON '. $down);
            }

            // change passive if need
            if (wf_CheckPost(array('newpassive')) and $android->checkRight('PASSIVE')) {
                $passive = $_POST['newpassive'];
                $billing->setpassive($login, $passive);
                log_register('ANDROID CHANGE Passive (' . $login . ') ON ' . $passive);
            }
            
            // change notes if need
            if (wf_CheckPost(array('newnotes')) and $android->checkRight('NOTES')) {
                $notes = $_POST['newnotes'];
                zb_UserDeleteNotes($login);
                zb_UserCreateNotes($login, $notes);
            }

            // reset user if need
            if (wf_CheckPost(array('reset')) and $android->checkRight('RESET')) {
                $billing->resetuser($login);
                log_register("ANDROID RESET User (" . $login . ")");
                //resurrect if user is disconnected
                if ($ubillingConfig->getAlterParam('RESETHARD')) {
                    zb_UserResurrect($login);
                }
            }

            // change ConnectionDetails if need
            if (wf_CheckPost(array('editcondet')) and $android->checkRight('CONDET')) {
                if ($ubillingConfig->getAlterParam('CONDET_ENABLED') ) {
                    $conDet = new ConnectionDetails();
                    $conDet->set($login, @$_POST['newseal'], @$_POST['newlength'], @$_POST['newprice']);
                } else {
                    $android->updateSuccessAndMessage('This module is disabled');
                }
            }

            $android->getUserData($login);
       } else {
            $android->updateSuccessAndMessage('GET_NO_USERNAME');
        }
    }

    // Get user DHCP LOG
    if (isset($_GET['action']) and $_GET['action'] == 'pl_dhcp' and $android->checkRight('PLDHCP')) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            $login = trim($login);
            $android->getUserDhcpLog($login);
       } else {
            $android->updateSuccessAndMessage('GET_NO_USERNAME');
        }
    }

    // Get user Ping result
    if (isset($_GET['action']) and $_GET['action'] == 'pl_pinger' and $android->checkRight('PLPINGER')) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            $login = trim($login);
            $android->getUserPingResult($login);
       } else {
            $android->updateSuccessAndMessage('GET_NO_USERNAME');
        }
    }

    // Add new comments for tasks
    if (isset($_GET['action']) and $_GET['action'] == 'newadcommentstext' and $ubillingConfig->getAlterParam('ADCOMMENTS_ENABLED')) {
        if (wf_CheckPost(array('taskid', 'newcommentstext'))) {
            $android->createComment($_POST['taskid'], $_POST['newcommentstext']);
        } else {
            $android->updateSuccessAndMessage('All fields marked with an asterisk are mandatory');
        }
    }

    //search users
    if (isset($_GET['action']) and $_GET['action'] == 'test') {
        print_r('
                <form action="?module=android&debug=true&action=usersearch" method="POST" class="ubLoginForm" id="Form_1zged3ok">
                <input type="text" name="searchquery" value="" size="12" id="wwa1z5db" class="">
                <label for="wwa1z5db">QUERY</label>
                <input type="submit" value="Search" id="Submit_u4etly1m">
                </form>'
        );
        die();
    }

    //search users
    if (isset($_GET['action']) and $_GET['action'] == 'test2') {
        print_r('
                <form action="?module=android&debug=true&action=newadcommentstext" method="POST" class="ubLoginForm" id="Form_1zged3ok">
                <input type="text" name="newcommentstext" value="" size="12" id="wwa1z5db" class="">
                <input type="text" name="taskid" value="" size="12" id="wwa1z5db" class="">
                <label for="wwa1z5db">QUERY</label>
                <input type="submit" value="Search" id="Submit_u4etly1m">
                </form>'
        );
        die();
    }
}

$android->loadData();

die($android->RenderJson());
?>

<?php

//salary daily jobs notification
if ($_GET['action'] == 'salarytelegram') {
    if ($alterconf['SALARY_ENABLED']) {
        $salary = new Salary();
        $salary->telegramDailyNotify();
        die('OK: SALARYTELEGRAM');
    } else {
        die('ERROR: SALARY DISABLED');
    }
}

            


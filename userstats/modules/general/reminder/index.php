<?php
$usRemider = new USReminder();

if (ubRouting::checkPost(array('setremind',
                               'deleteremind',
                               'setremindemail',
                               'deleteremindemail',
                               'pbiopts',
                               'changemobile',
                               'changemail'
                         ), true, true)) {
    $usRemider->router();
}


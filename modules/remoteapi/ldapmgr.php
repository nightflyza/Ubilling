<?php

//LDAP Mgr users export
if ($_GET['action'] == 'ldapmgr') {
    if ($alterconf['LDAPMGR_ENABLED']) {
        $ldapMgr = new UbillingLDAPManager();
        if (isset($_GET['param'])) {
            if ($_GET['param'] == 'queue') {
                $ldapMgr->getQueue();
            }
        }
    } else {
        die('ERROR: LDAPMGR DISABLED');
    }
}

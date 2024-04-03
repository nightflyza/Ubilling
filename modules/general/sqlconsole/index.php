<?php

if (cfr('SQLCONSOLE')) {

    $devCon = new DevConsole();

    /**
     * OnePunch scripts management
     */

    //new script creation
    if (ubRouting::checkPost(array($devCon::PROUTE_OPN_ALIAS, $devCon::PROUTE_OPN_NAME, $devCon::PROUTE_OPN_CONTENT))) {
        $onePunch = new OnePunch();
        $punchCreateResult = $onePunch->createScript(ubRouting::post($devCon::PROUTE_OPN_ALIAS), ubRouting::post($devCon::PROUTE_OPN_NAME), ubRouting::post($devCon::PROUTE_OPN_CONTENT));
        if (!empty($punchCreateResult)) {
            show_error($punchCreateResult);
        } else {
            ubRouting::nav($devCon::URL_DEVCON);
        }
    }

    //existing script deletion
    if (ubRouting::checkGet($devCon::ROUTE_OP_DELETE)) {
        $onePunch = new OnePunch();
        $punchDeleteResult = $onePunch->deleteScript(ubRouting::get($devCon::ROUTE_OP_DELETE));
        if (!empty($punchDeleteResult)) {
            show_error($punchDeleteResult);
        } else {
            ubRouting::nav($devCon::URL_DEVCON);
        }
    }

    //editing existing script
    if (ubRouting::checkPost(array($devCon::PROUTE_OPE_ID, $devCon::PROUTE_OPE_OLDALIAS, $devCon::PROUTE_OPE_NAME, $devCon::PROUTE_OPE_ALIAS, $devCon::PROUTE_OPE_CONTENT))) {
        $onePunch = new OnePunch();
        $onePunch->saveScript();
        ubRouting::nav($devCon::URL_DEVCON . '&' . $devCon::ROUTE_OP_EDIT . '=' . ubRouting::post($devCon::PROUTE_OPE_ALIAS));
    }

    //migrating old code templates from storage
    if (ubRouting::checkGet($devCon::ROUTE_OP_IMPORT)) {
        $onePunch = new OnePunch();
        $onePunch->importOldTemplates();
        ubRouting::nav($devCon::URL_DEVCON);
    }

    /**
     * showing required module interface on top
     */
    if (!ubRouting::checkGet($devCon::ROUTE_PHP_CON)) {
        show_window(__('SQL Console'), $devCon->renderSqlForm());
    } else {
        $devConWindowTitle = __('Developer Console');
        if (ubRouting::checkGet($devCon::ROUTE_OP_EDIT)) {
            $devConWindowTitle .= ': ' . __('Edit') . ' ' . __('One-Punch') . ' ' . __('Script');
        }

        if (ubRouting::checkGet($devCon::ROUTE_OP_CREATE)) {
            $devConWindowTitle .= ': ' . __('Create') . ' ' . __('One-Punch') . ' ' . __('Script');
        }

        $phpgrid = $devCon->renderPhpInterfaces();
        show_window($devConWindowTitle, $phpgrid);
    }

    /**
     * SQL queries / PHP code exection here
     */

    // SQL console requests processing
    if (ubRouting::checkPost($devCon::PROUTE_SQL)) {
        $devCon->executeSqlQuery();
    }


    // PHP console requests processing
    // must be here outside object to emulate normal modules behaviour
    if (ubRouting::checkPost($devCon::PROUTE_PHP)) {
        $phpcode = ubRouting::post($devCon::PROUTE_PHP, 'callback', 'trim');
        if (!empty($phpcode)) {
            //Optional code highlighting
            $devCon->showCodeHighlight($phpcode);
            //executing it
            $stripcode = substr($phpcode, 0, 70) . '..';
            log_register('DEVCONSOLE ' . $stripcode);
            ob_start();
            try {
                eval($phpcode);
            } catch (ParseError $e) {
                show_error(__('Error') . ':' . $e);
            }
            $debugData = ob_get_contents();
            ob_end_clean();
            $devCon->showDebugData($debugData);
            log_register('DEVCONSOLE DONE');
        } else {
            show_window(__('Result'), __('Empty code part received'));
        }
    }


    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}

<?php

if (ubRouting::get('action') == 'herd') {
    if ($alterconf['PON_ENABLED']) {
        if (ubRouting::checkGet('oltid')) {
            $oltId = ubRouting::get('oltid', 'int');

            $startHerd = time();
            $cachedStats = array();
            $statsPath = 'exports/HERD_' . $oltId;
            if (file_exists($statsPath)) {
                $cacheRaw = file_get_contents($statsPath);
                if (!empty($cacheRaw)) {
                    $cachedStats = unserialize($cacheRaw);
                }
            }

            $pony = new PONizer();
            $pony->pollOltSignal($oltId);
            $endHerd = time();
            $cachedStats['start'] = $startHerd;
            $cachedStats['end'] = $endHerd;
            if (!empty($cachedStats)) {
                $cachedStats = serialize($cachedStats);
                file_put_contents($statsPath, $cachedStats);
            }

            die('OK:HERD');
        } else {
            die('ERROR:NO_OLTID');
        }
    } else {
        die('ERROR:PON_DISABLED');
    }
}    
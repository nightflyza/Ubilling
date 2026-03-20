<?php

$result = '';

if ($darkVoidContext['altCfg']['TB_NEWTICKETNOTIFY']) {
    $newticketcount = zb_TicketsGetAllNewCount();
    if ($newticketcount != 0) {
        $result .= wf_Link('?module=ticketing', wf_img('skins/ticketnotify.gif', $newticketcount . ' ' . __('support tickets expected processing')), false);
    }
}

return ($result);

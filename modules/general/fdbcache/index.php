<?php

if (cfr('SWITCHPOLL')) {
    $fdbarchive = new FDBArchive();
    $fdbarchive->renderCacheModule();
} else {
    show_error(__('Access denied'));
}